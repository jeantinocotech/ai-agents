<?php

namespace App\Http\Controllers;

use App\Enums\InterviewApplicationOutcome;
use App\Enums\JobApplicationStatus;
use App\Models\Agent;
use App\Models\AgentDocument;
use App\Models\AgentDocumentDefault;
use App\Models\InterviewPreparation;
use App\Models\InterviewProcess;
use App\Models\UserCv;
use App\Services\AgentDocumentDefaultJdSync;
use App\Services\CareerTrailAgentAccess;
use App\Services\InterviewProcessOutcomeService;
use App\Support\AgentDocumentLimits;
use App\Support\AgentsDocumentLibraryViewData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AgentDocumentsController extends Controller
{
    public function index(Request $request, Agent $agent): View
    {
        $user = $request->user();
        CareerTrailAgentAccess::abortUnlessCanAccess($user, $agent);

        return view('agents.documents.index', array_merge(
            ['agent' => $agent],
            AgentsDocumentLibraryViewData::payload($user, $agent),
        ));
    }

    public function content(Request $request, Agent $agent, AgentDocument $document): JsonResponse
    {
        CareerTrailAgentAccess::abortUnlessCanAccess($request->user(), $agent);

        $this->authorizeOwnedDocument($document, $request->user(), $agent);

        return response()->json([
            'id' => $document->id,
            'type' => $document->type,
            'title' => $document->title,
            'body' => $document->body,
            'char_length' => mb_strlen((string) $document->body),
            'max_body_chars' => AgentDocumentLimits::maxCharsForType($document->type),
        ]);
    }

    /**
     * Conteúdo do CV guardado no perfil (trilha), para o widget ChatKit carregar como mensagem.
     */
    public function profileCvContent(Request $request, Agent $agent, UserCv $userCv): JsonResponse
    {
        CareerTrailAgentAccess::abortUnlessCanAccess($request->user(), $agent);

        abort_unless((int) $userCv->user_id === (int) $request->user()->id, 403);

        return response()->json([
            'id' => 'p'.$userCv->id,
            'type' => AgentDocument::TYPE_CV,
            'title' => $userCv->title,
            'body' => $userCv->body,
            'char_length' => mb_strlen((string) $userCv->body),
            'max_body_chars' => AgentDocumentLimits::maxCharsForType(AgentDocument::TYPE_CV),
        ]);
    }

    public function storeProfileCv(Request $request, Agent $agent): RedirectResponse
    {
        CareerTrailAgentAccess::abortUnlessCanAccess($request->user(), $agent);

        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'body' => 'required|string',
            'make_default' => 'sometimes|boolean',
        ]);

        $user = $request->user();

        AgentDocumentLimits::assertBodyWithinLimit(AgentDocument::TYPE_CV, $validated['body']);

        $cv = UserCv::query()->create([
            'user_id' => $user->id,
            'title' => $validated['title'] ?? null,
            'body' => $validated['body'],
            'is_default' => false,
            'source' => UserCv::SOURCE_MANUAL,
        ]);

        if ($request->boolean('make_default') || UserCv::query()->where('user_id', $user->id)->count() === 1) {
            UserCv::query()->where('user_id', $user->id)->update(['is_default' => false]);
            $cv->forceFill(['is_default' => true])->save();
        }

        return $this->documentsHubRedirect($request, $agent)
            ->with('status', 'CV guardado no perfil.');
    }

    public function updateProfileCv(Request $request, Agent $agent, UserCv $userCv): RedirectResponse
    {
        CareerTrailAgentAccess::abortUnlessCanAccess($request->user(), $agent);
        abort_unless((int) $userCv->user_id === (int) $request->user()->id, 403);

        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'body' => 'required|string',
            'make_default' => 'sometimes|boolean',
        ]);

        AgentDocumentLimits::assertBodyWithinLimit(AgentDocument::TYPE_CV, $validated['body']);

        $userCv->title = $validated['title'] ?? null;
        $userCv->body = $validated['body'];
        $userCv->save();

        if ($request->boolean('make_default')) {
            UserCv::query()->where('user_id', $request->user()->id)->update(['is_default' => false]);
            $userCv->forceFill(['is_default' => true])->save();
        }

        return $this->documentsHubRedirect($request, $agent)
            ->with('status', 'CV do perfil atualizado.');
    }

    public function store(Request $request, Agent $agent): RedirectResponse
    {
        CareerTrailAgentAccess::abortUnlessCanAccess($request->user(), $agent);

        $validated = $request->validate([
            'type' => 'required|string|in:jd',
            'title' => 'nullable|string|max:255',
            'body' => 'required|string',
            'user_cv_id' => 'nullable|integer|exists:user_cvs,id',
        ]);

        AgentDocumentLimits::assertBodyWithinLimit($validated['type'], $validated['body']);

        $user = $request->user();

        $userCvId = $validated['user_cv_id'] ?? null;
        if ($userCvId !== null) {
            abort_unless(UserCv::query()->whereKey($userCvId)->where('user_id', $user->id)->exists(), 422);
        }

        $doc = AgentDocument::query()->create([
            'user_id' => $user->id,
            'agent_id' => $agent->id,
            'type' => $validated['type'],
            'title' => $validated['title'] ?? null,
            'body' => $validated['body'],
            'paired_cv_document_id' => null,
            'user_cv_id' => $validated['type'] === AgentDocument::TYPE_JD ? $userCvId : null,
        ]);

        AgentDocumentDefaultJdSync::sync($user->id, $agent->id, (int) $doc->id);

        $label = 'Vaga (JD)';

        $trailEditJd = ($request->input('trail_return') === 'career_trail_ats' && $validated['type'] === AgentDocument::TYPE_JD)
            ? (int) $doc->id
            : null;

        return $this->documentsHubRedirect($request, $agent, $trailEditJd)
            ->with('status', "{$label} adicionado.");
    }

    public function update(Request $request, Agent $agent, AgentDocument $document): RedirectResponse
    {
        CareerTrailAgentAccess::abortUnlessCanAccess($request->user(), $agent);

        $this->authorizeOwnedDocument($document, $request->user(), $agent);

        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'body' => 'required|string',
            'user_cv_id' => 'nullable|integer|exists:user_cvs,id',
        ]);

        AgentDocumentLimits::assertBodyWithinLimit($document->type, $validated['body']);

        $user = $request->user();

        if ($document->type === AgentDocument::TYPE_JD) {
            $userCvId = $validated['user_cv_id'] ?? null;
            if ($userCvId !== null) {
                abort_unless(UserCv::query()->whereKey($userCvId)->where('user_id', $user->id)->exists(), 422);
            }
            $document->paired_cv_document_id = null;
            $document->user_cv_id = $userCvId;
        }

        $document->title = $validated['title'] ?? null;
        $document->body = $validated['body'];
        $document->save();

        if ($document->type === AgentDocument::TYPE_JD) {
            AgentDocumentDefaultJdSync::sync($user->id, $agent->id, (int) $document->id);
        }

        $label = 'Vaga';

        $trailEditJd = ($request->input('trail_return') === 'career_trail_ats' && $document->type === AgentDocument::TYPE_JD)
            ? (int) $document->id
            : null;

        return $this->documentsHubRedirect($request, $agent, $trailEditJd)
            ->with('status', "{$label} atualizado.");
    }

    public function destroy(Request $request, Agent $agent, AgentDocument $document): RedirectResponse
    {
        CareerTrailAgentAccess::abortUnlessCanAccess($request->user(), $agent);

        $this->authorizeOwnedDocument($document, $request->user(), $agent);

        $wasJd = $document->type === AgentDocument::TYPE_JD;

        $defaults = AgentDocumentDefault::query()
            ->where('user_id', $request->user()->id)
            ->where('agent_id', $agent->id)
            ->first();

        if ($defaults) {
            if ((int) $defaults->default_cv_document_id === (int) $document->id) {
                $defaults->default_cv_document_id = null;
            }
            $defaults->save();
        }

        $document->delete();

        if ($wasJd) {
            AgentDocumentDefaultJdSync::sync((int) $request->user()->id, (int) $agent->id, null);
        }

        return $this->documentsHubRedirect($request, $agent)
            ->with('status', 'Documento removido.');
    }

    public function markApplicationSubmitted(Request $request, Agent $agent, AgentDocument $document): JsonResponse|RedirectResponse
    {
        CareerTrailAgentAccess::abortUnlessCanAccess($request->user(), $agent);
        $this->authorizeOwnedDocument($document, $request->user(), $agent);
        abort_unless($document->type === AgentDocument::TYPE_JD, 404);
        abort_if($document->user_cv_id === null, 422, 'Associe um CV do perfil à vaga antes de registar o envio ao ATS.');

        $process = InterviewProcess::query()
            ->where('user_id', $request->user()->id)
            ->where('jd_document_id', $document->id)
            ->first();
        abort_if(
            $process !== null && in_array($process->outcome, [
                InterviewApplicationOutcome::DidNotProceed,
                InterviewApplicationOutcome::Approved,
            ], true),
            422,
            'Este processo já está encerrado ou aprovado; não é possível registar novo envio ATS.'
        );

        $document->ats_submitted_at = now();
        $document->save();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'application_status' => $document->application_status?->value,
            ]);
        }

        return $this->documentsHubRedirect($request, $agent)
            ->with('status', 'Envio ATS registado para esta vaga.');
    }

    public function markCvSentToEmployer(Request $request, Agent $agent, AgentDocument $document): JsonResponse|RedirectResponse
    {
        CareerTrailAgentAccess::abortUnlessCanAccess($request->user(), $agent);
        $this->authorizeOwnedDocument($document, $request->user(), $agent);
        abort_unless($document->type === AgentDocument::TYPE_JD, 404);

        abort_if($document->ats_submitted_at === null, 422, 'Registe primeiro o alinhamento ATS (envio ao assistente) antes de indicar o envio à empresa.');
        abort_if($document->cv_sent_to_employer_at !== null, 422, 'O envio do CV à empresa já está registado para esta vaga.');

        abort_if(
            InterviewPreparation::query()
                ->where('user_id', $request->user()->id)
                ->where('jd_document_id', $document->id)
                ->exists(),
            422,
            'Já existem rondas de entrevista; utilize o ecrã de entrevistas para actualizar o processo.'
        );

        $process = InterviewProcess::query()
            ->where('user_id', $request->user()->id)
            ->where('jd_document_id', $document->id)
            ->first();
        abort_if(
            $process !== null && in_array($process->outcome, [
                InterviewApplicationOutcome::DidNotProceed,
                InterviewApplicationOutcome::Approved,
            ], true),
            422,
            'Este processo já está encerrado na fase de entrevistas.'
        );

        abort_unless(
            $document->application_status === null || $document->application_status === JobApplicationStatus::Submitted,
            422,
            'Só é possível registar o envio à empresa após o estado «Alinhamento ATS».'
        );

        $document->cv_sent_to_employer_at = now();
        $document->save();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'application_status' => $document->application_status?->value,
            ]);
        }

        return $this->documentsHubRedirect($request, $agent)
            ->with('status', 'Envio do CV à empresa registado. Pode aguardar retorno ou registar entrevistas.');
    }

    public function markApplicationDidNotProceed(Request $request, Agent $agent, AgentDocument $document): RedirectResponse
    {
        CareerTrailAgentAccess::abortUnlessCanAccess($request->user(), $agent);
        $this->authorizeOwnedDocument($document, $request->user(), $agent);
        abort_unless($document->type === AgentDocument::TYPE_JD, 404);

        abort_if(
            InterviewPreparation::query()
                ->where('user_id', $request->user()->id)
                ->where('jd_document_id', $document->id)
                ->exists(),
            422,
            'Existem rondas de entrevista para esta vaga; actualize o estado nas entrevistas em vez de fechar aqui.'
        );

        $process = InterviewProcess::query()
            ->where('user_id', $request->user()->id)
            ->where('jd_document_id', $document->id)
            ->first();
        abort_if(
            $process !== null && $process->outcome === InterviewApplicationOutcome::Approved,
            422,
            'Candidatura aprovada: utilize o ecrã de entrevistas para reabrir antes de mudar este estado.'
        );

        InterviewProcess::query()->updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'jd_document_id' => $document->id,
            ],
            [
                'outcome' => InterviewApplicationOutcome::DidNotProceed,
            ]
        );

        InterviewProcessOutcomeService::syncAfterPreparationMutation((int) $request->user()->id, (int) $document->id);

        return $this->documentsHubRedirect($request, $agent)
            ->with('status', 'Candidatura marcada como «não prosseguiu».');
    }

    public function updateDefaults(Request $request, Agent $agent): RedirectResponse|JsonResponse
    {
        CareerTrailAgentAccess::abortUnlessCanAccess($request->user(), $agent);

        $validated = $request->validate([
            'default_cv_document_id' => 'sometimes|nullable|integer|exists:agent_documents,id',
            'default_jd_document_id' => 'sometimes|nullable|integer|exists:agent_documents,id',
        ]);

        $cvTouched = array_key_exists('default_cv_document_id', $validated);
        $jdTouched = array_key_exists('default_jd_document_id', $validated);

        if ($cvTouched && ! $jdTouched) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'CV de perfil não usa as preferências padrão desta biblioteca.',
                ]);
            }

            return redirect()->back();
        }

        $user = $request->user();

        $defaults = AgentDocumentDefault::query()->firstOrCreate(
            [
                'user_id' => $user->id,
                'agent_id' => $agent->id,
            ],
            []
        );

        if ($cvTouched) {
            if ($validated['default_cv_document_id'] !== null) {
                $ok = AgentDocument::query()
                    ->whereKey($validated['default_cv_document_id'])
                    ->where('user_id', $user->id)
                    ->where('agent_id', $agent->id)
                    ->where('type', AgentDocument::TYPE_CV)
                    ->exists();
                abort_unless($ok, 422);
            }
            $defaults->default_cv_document_id = $validated['default_cv_document_id'];
        }

        $defaults->save();

        if ($jdTouched) {
            if ($validated['default_jd_document_id'] !== null) {
                $this->assertJdOwnedByUserAgent((int) $validated['default_jd_document_id'], $user->id, $agent->id);
            }
            AgentDocumentDefaultJdSync::sync($user->id, $agent->id, $validated['default_jd_document_id']);
            $defaults->refresh();
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Preferências salvas.',
                'defaults' => [
                    'jd_document_id' => $defaults->default_jd_document_id,
                ],
            ]);
        }

        return $this->documentsHubRedirect($request, $agent)
            ->with('status', 'Preferências salvas.');
    }

    private function documentsHubRedirect(Request $request, Agent $agent, ?int $trailEditJdId = null): RedirectResponse
    {
        if ($request->input('trail_return') === 'career_trail_ats') {
            $to = route('career-trail.ats');
            if ($trailEditJdId !== null && $trailEditJdId > 0) {
                $to .= '?edit_jd='.$trailEditJdId;
            }

            return redirect()->to($to);
        }

        return redirect()->route('agents.documents.index', $agent);
    }

    private function authorizeOwnedDocument(AgentDocument $document, $user, Agent $agent): void
    {
        abort_unless(
            (int) $document->user_id === (int) $user->id
            && (int) $document->agent_id === (int) $agent->id,
            403
        );
    }

    private function assertJdOwnedByUserAgent(int $jdId, int $userId, int $agentId): void
    {
        $exists = AgentDocument::query()
            ->whereKey($jdId)
            ->where('user_id', $userId)
            ->where('agent_id', $agentId)
            ->where('type', AgentDocument::TYPE_JD)
            ->exists();
        abort_unless($exists, 422);
    }
}
