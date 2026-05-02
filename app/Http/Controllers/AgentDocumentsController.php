<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\AgentDocument;
use App\Models\AgentDocumentDefault;
use App\Models\UserCv;
use App\Services\CareerTrailAgentAccess;
use App\Support\AgentDocumentLimits;
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

        $profileCvs = UserCv::query()
            ->where('user_id', $user->id)
            ->orderByDesc('is_default')
            ->orderByDesc('updated_at')
            ->get();

        $jds = AgentDocument::query()
            ->where('user_id', $user->id)
            ->where('agent_id', $agent->id)
            ->where('type', AgentDocument::TYPE_JD)
            ->with('userCv')
            ->orderByDesc('updated_at')
            ->get();

        $defaults = AgentDocumentDefault::query()
            ->where('user_id', $user->id)
            ->where('agent_id', $agent->id)
            ->with(['defaultJdDocument'])
            ->first();

        return view('agents.documents.index', [
            'agent' => $agent,
            'profileCvs' => $profileCvs,
            'jds' => $jds,
            'defaults' => $defaults,
            'maxCvBodyChars' => AgentDocumentLimits::maxCharsForType(AgentDocument::TYPE_CV),
            'maxJdBodyChars' => AgentDocumentLimits::maxCharsForType(AgentDocument::TYPE_JD),
        ]);
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

        return redirect()
            ->route('agents.documents.index', $agent)
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

        return redirect()
            ->route('agents.documents.index', $agent)
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
            'set_as_default' => 'sometimes|boolean',
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

        if ($validated['type'] === AgentDocument::TYPE_JD && $request->boolean('set_as_default')) {
            $this->setDefaultJd($user->id, $agent->id, $doc->id);
        }

        $label = 'Vaga (JD)';

        return redirect()
            ->route('agents.documents.index', $agent)
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
            'set_as_default' => 'sometimes|boolean',
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

        if ($document->type === AgentDocument::TYPE_JD && $request->boolean('set_as_default')) {
            $this->setDefaultJd($user->id, $agent->id, $document->id);
        }

        $label = 'Vaga';

        return redirect()
            ->route('agents.documents.index', $agent)
            ->with('status', "{$label} atualizado.");
    }

    public function destroy(Request $request, Agent $agent, AgentDocument $document): RedirectResponse
    {
        CareerTrailAgentAccess::abortUnlessCanAccess($request->user(), $agent);

        $this->authorizeOwnedDocument($document, $request->user(), $agent);

        $defaults = AgentDocumentDefault::query()
            ->where('user_id', $request->user()->id)
            ->where('agent_id', $agent->id)
            ->first();

        if ($defaults) {
            if ((int) $defaults->default_cv_document_id === (int) $document->id) {
                $defaults->default_cv_document_id = null;
            }
            if ((int) $defaults->default_jd_document_id === (int) $document->id) {
                $defaults->default_jd_document_id = null;
            }
            $defaults->save();
        }

        $document->delete();

        return redirect()
            ->route('agents.documents.index', $agent)
            ->with('status', 'Documento removido.');
    }

    public function updateDefaults(Request $request, Agent $agent): RedirectResponse|JsonResponse
    {
        CareerTrailAgentAccess::abortUnlessCanAccess($request->user(), $agent);

        if ($request->has('default_cv_document_id') && ! $request->has('default_jd_document_id')) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'CV de perfil não usa predefinições desta biblioteca.',
                ]);
            }

            return redirect()->back();
        }

        $validated = $request->validate([
            'default_jd_document_id' => 'sometimes|nullable|integer|exists:agent_documents,id',
        ]);

        if (! array_key_exists('default_jd_document_id', $validated)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Indique a vaga (JD) predefinida (ou desactive «Guardar seleção» no CV).',
                ], 422);
            }

            return redirect()
                ->route('agents.documents.index', $agent)
                ->withErrors(['defaults' => 'Indique a vaga (JD) que deseja gravar como predefinida.']);
        }

        $user = $request->user();

        if (array_key_exists('default_jd_document_id', $validated) && $validated['default_jd_document_id'] !== null) {
            $this->assertJdOwnedByUserAgent((int) $validated['default_jd_document_id'], $user->id, $agent->id);
        }

        $defaults = AgentDocumentDefault::query()->firstOrCreate(
            [
                'user_id' => $user->id,
                'agent_id' => $agent->id,
            ],
            []
        );

        if (array_key_exists('default_jd_document_id', $validated)) {
            $defaults->default_jd_document_id = $validated['default_jd_document_id'];
        }

        $defaults->save();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Predefinições gravadas.',
                'defaults' => [
                    'jd_document_id' => $defaults->default_jd_document_id,
                ],
            ]);
        }

        return redirect()
            ->route('agents.documents.index', $agent)
            ->with('status', 'Predefinições gravadas.');
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

    private function setDefaultJd(int $userId, int $agentId, int $jdId): void
    {
        $defaults = AgentDocumentDefault::query()->firstOrCreate(
            ['user_id' => $userId, 'agent_id' => $agentId],
            []
        );
        $defaults->default_jd_document_id = $jdId;
        $defaults->save();
    }
}
