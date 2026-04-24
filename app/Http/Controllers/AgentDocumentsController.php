<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\AgentDocument;
use App\Models\AgentDocumentDefault;
use App\Models\UserCv;
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
        $cvs = AgentDocument::query()
            ->where('user_id', $user->id)
            ->where('agent_id', $agent->id)
            ->where('type', AgentDocument::TYPE_CV)
            ->orderByDesc('updated_at')
            ->get();

        $jds = AgentDocument::query()
            ->where('user_id', $user->id)
            ->where('agent_id', $agent->id)
            ->where('type', AgentDocument::TYPE_JD)
            ->with('pairedCv')
            ->orderByDesc('updated_at')
            ->get();

        $defaults = AgentDocumentDefault::query()
            ->where('user_id', $user->id)
            ->where('agent_id', $agent->id)
            ->with(['defaultCvDocument', 'defaultJdDocument'])
            ->first();

        return view('agents.documents.index', [
            'agent' => $agent,
            'cvs' => $cvs,
            'jds' => $jds,
            'defaults' => $defaults,
            'maxCvBodyChars' => AgentDocumentLimits::maxCharsForType(AgentDocument::TYPE_CV),
            'maxJdBodyChars' => AgentDocumentLimits::maxCharsForType(AgentDocument::TYPE_JD),
        ]);
    }

    public function content(Request $request, Agent $agent, AgentDocument $document): JsonResponse
    {
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

    public function store(Request $request, Agent $agent): RedirectResponse
    {
        $validated = $request->validate([
            'type' => 'required|string|in:cv,jd',
            'title' => 'nullable|string|max:255',
            'body' => 'required|string',
            'paired_cv_document_id' => 'nullable|integer|exists:agent_documents,id',
            'set_as_default' => 'sometimes|boolean',
        ]);

        AgentDocumentLimits::assertBodyWithinLimit($validated['type'], $validated['body']);

        $user = $request->user();

        if ($validated['type'] === AgentDocument::TYPE_JD) {
            $pairedId = $validated['paired_cv_document_id'] ?? null;
            if ($pairedId !== null) {
                $this->assertCvOwnedByUserAgent((int) $pairedId, $user->id, $agent->id);
            }
        }

        $doc = AgentDocument::query()->create([
            'user_id' => $user->id,
            'agent_id' => $agent->id,
            'type' => $validated['type'],
            'title' => $validated['title'] ?? null,
            'body' => $validated['body'],
            'paired_cv_document_id' => $validated['type'] === AgentDocument::TYPE_JD
                ? ($validated['paired_cv_document_id'] ?? null)
                : null,
        ]);

        if ($validated['type'] === AgentDocument::TYPE_CV && $request->boolean('set_as_default')) {
            $this->setDefaultCv($user->id, $agent->id, $doc->id);
        }
        if ($validated['type'] === AgentDocument::TYPE_JD && $request->boolean('set_as_default')) {
            $this->setDefaultJd($user->id, $agent->id, $doc->id);
        }

        $label = $validated['type'] === AgentDocument::TYPE_CV ? 'CV' : 'Vaga (JD)';

        return redirect()
            ->route('agents.documents.index', $agent)
            ->with('status', "{$label} adicionado.");
    }

    public function update(Request $request, Agent $agent, AgentDocument $document): RedirectResponse
    {
        $this->authorizeOwnedDocument($document, $request->user(), $agent);

        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'body' => 'required|string',
            'paired_cv_document_id' => 'nullable|integer|exists:agent_documents,id',
            'set_as_default' => 'sometimes|boolean',
        ]);

        AgentDocumentLimits::assertBodyWithinLimit($document->type, $validated['body']);

        $user = $request->user();

        if ($document->type === AgentDocument::TYPE_JD) {
            $pairedId = $validated['paired_cv_document_id'] ?? null;
            if ($pairedId !== null) {
                $this->assertCvOwnedByUserAgent((int) $pairedId, $user->id, $agent->id);
            }
            $document->paired_cv_document_id = $pairedId;
        }

        $document->title = $validated['title'] ?? null;
        $document->body = $validated['body'];
        $document->save();

        if ($document->type === AgentDocument::TYPE_CV && $request->boolean('set_as_default')) {
            $this->setDefaultCv($user->id, $agent->id, $document->id);
        }
        if ($document->type === AgentDocument::TYPE_JD && $request->boolean('set_as_default')) {
            $this->setDefaultJd($user->id, $agent->id, $document->id);
        }

        $label = $document->type === AgentDocument::TYPE_CV ? 'CV' : 'Vaga';

        return redirect()
            ->route('agents.documents.index', $agent)
            ->with('status', "{$label} atualizado.");
    }

    public function destroy(Request $request, Agent $agent, AgentDocument $document): RedirectResponse
    {
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
        $validated = $request->validate([
            'default_cv_document_id' => 'sometimes|nullable|integer|exists:agent_documents,id',
            'default_jd_document_id' => 'sometimes|nullable|integer|exists:agent_documents,id',
        ]);

        if (! array_key_exists('default_cv_document_id', $validated) && ! array_key_exists('default_jd_document_id', $validated)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Marque guardar como predefinido para o CV e/ou para a vaga, ou altere as opções antes de gravar.',
                ], 422);
            }

            return redirect()
                ->route('agents.documents.index', $agent)
                ->withErrors(['defaults' => 'Indique o que deseja gravar (CV e/ou vaga como predefinido).']);
        }

        $user = $request->user();

        if (array_key_exists('default_cv_document_id', $validated) && $validated['default_cv_document_id'] !== null) {
            $this->assertCvOwnedByUserAgent((int) $validated['default_cv_document_id'], $user->id, $agent->id);
        }

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

        if (array_key_exists('default_cv_document_id', $validated)) {
            $defaults->default_cv_document_id = $validated['default_cv_document_id'];
        }
        if (array_key_exists('default_jd_document_id', $validated)) {
            $defaults->default_jd_document_id = $validated['default_jd_document_id'];
        }

        $defaults->save();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Predefinições gravadas.',
                'defaults' => [
                    'cv_document_id' => $defaults->default_cv_document_id,
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

    private function assertCvOwnedByUserAgent(int $cvId, int $userId, int $agentId): void
    {
        $exists = AgentDocument::query()
            ->whereKey($cvId)
            ->where('user_id', $userId)
            ->where('agent_id', $agentId)
            ->where('type', AgentDocument::TYPE_CV)
            ->exists();
        abort_unless($exists, 422);
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

    private function setDefaultCv(int $userId, int $agentId, int $cvId): void
    {
        $defaults = AgentDocumentDefault::query()->firstOrCreate(
            ['user_id' => $userId, 'agent_id' => $agentId],
            []
        );
        $defaults->default_cv_document_id = $cvId;
        $defaults->save();
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
