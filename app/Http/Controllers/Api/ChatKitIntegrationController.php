<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\AgentDocument;
use App\Models\AgentDocumentDefault;
use App\Support\AgentDocumentLimits;
use App\Support\ChatKitUserRef;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatKitIntegrationController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $ctx = $this->validatedUserAgentContext($request);
        if ($ctx instanceof JsonResponse) {
            return $ctx;
        }

        [$parsed, $agent] = $ctx;
        $uid = $parsed['user_id'];
        $aid = $parsed['agent_id'];

        $defaults = AgentDocumentDefault::query()
            ->where('user_id', $uid)
            ->where('agent_id', $aid)
            ->with(['defaultCvDocument', 'defaultJdDocument'])
            ->first();

        $cvs = AgentDocument::query()
            ->where('user_id', $uid)
            ->where('agent_id', $aid)
            ->where('type', AgentDocument::TYPE_CV)
            ->orderByDesc('updated_at')
            ->get();

        $jds = AgentDocument::query()
            ->where('user_id', $uid)
            ->where('agent_id', $aid)
            ->where('type', AgentDocument::TYPE_JD)
            ->orderByDesc('updated_at')
            ->get();

        $cvText = $defaults?->defaultCvDocument ? (string) $defaults->defaultCvDocument->body : null;
        $jdText = $defaults?->defaultJdDocument ? (string) $defaults->defaultJdDocument->body : null;

        return response()->json([
            'user' => $request->input('user'),
            'agent_id' => $aid,
            'defaults' => [
                'cv_document_id' => $defaults?->default_cv_document_id,
                'jd_document_id' => $defaults?->default_jd_document_id,
            ],
            'cvs' => $cvs->map(fn (AgentDocument $d) => [
                'id' => $d->id,
                'title' => $d->title,
                'body' => $d->body,
            ])->values(),
            'jds' => $jds->map(fn (AgentDocument $d) => [
                'id' => $d->id,
                'title' => $d->title,
                'body' => $d->body,
                'paired_cv_document_id' => $d->paired_cv_document_id,
            ])->values(),
            'has_cv' => $cvText !== null && trim($cvText) !== '',
            'has_jd' => $jdText !== null && trim($jdText) !== '',
            'cv_text' => $cvText !== null && trim($cvText) !== '' ? $cvText : null,
            'jd_text' => $jdText !== null && trim($jdText) !== '' ? $jdText : null,
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $ctx = $this->validatedUserAgentContext($request);
        if ($ctx instanceof JsonResponse) {
            return $ctx;
        }

        [$parsed, $agent] = $ctx;
        $uid = $parsed['user_id'];
        $aid = $parsed['agent_id'];

        $validated = $request->validate([
            'cv_text' => 'sometimes|nullable|string',
            'jd_text' => 'sometimes|nullable|string',
            'clear_cv' => 'sometimes|boolean',
            'clear_jd' => 'sometimes|boolean',
        ]);

        if ($request->boolean('clear_cv')) {
            AgentDocument::query()
                ->where('user_id', $uid)
                ->where('agent_id', $aid)
                ->where('type', AgentDocument::TYPE_CV)
                ->delete();
            AgentDocumentDefault::query()
                ->where('user_id', $uid)
                ->where('agent_id', $aid)
                ->update(['default_cv_document_id' => null]);
        } elseif ($request->has('cv_text')) {
            $t = trim((string) $request->input('cv_text'));
            if ($t !== '') {
                try {
                    AgentDocumentLimits::assertBodyWithinLimit(AgentDocument::TYPE_CV, $t);
                } catch (\Illuminate\Validation\ValidationException $e) {
                    return response()->json([
                        'message' => collect($e->errors())->flatten()->first() ?? 'Validação falhou.',
                        'errors' => $e->errors(),
                    ], 422);
                }
                $doc = AgentDocument::query()->create([
                    'user_id' => $uid,
                    'agent_id' => $aid,
                    'type' => AgentDocument::TYPE_CV,
                    'title' => 'CV (integração)',
                    'body' => $t,
                ]);
                $this->setDefaultCvId($uid, $aid, $doc->id);
            }
        }

        if ($request->boolean('clear_jd')) {
            AgentDocument::query()
                ->where('user_id', $uid)
                ->where('agent_id', $aid)
                ->where('type', AgentDocument::TYPE_JD)
                ->delete();
            AgentDocumentDefault::query()
                ->where('user_id', $uid)
                ->where('agent_id', $aid)
                ->update(['default_jd_document_id' => null]);
        } elseif ($request->has('jd_text')) {
            $t = trim((string) $request->input('jd_text'));
            if ($t !== '') {
                try {
                    AgentDocumentLimits::assertBodyWithinLimit(AgentDocument::TYPE_JD, $t);
                } catch (\Illuminate\Validation\ValidationException $e) {
                    return response()->json([
                        'message' => collect($e->errors())->flatten()->first() ?? 'Validação falhou.',
                        'errors' => $e->errors(),
                    ], 422);
                }
                $defaultCvId = AgentDocumentDefault::query()
                    ->where('user_id', $uid)
                    ->where('agent_id', $aid)
                    ->value('default_cv_document_id');

                $doc = AgentDocument::query()->create([
                    'user_id' => $uid,
                    'agent_id' => $aid,
                    'type' => AgentDocument::TYPE_JD,
                    'title' => 'Vaga (integração)',
                    'body' => $t,
                    'paired_cv_document_id' => $defaultCvId,
                ]);
                $this->setDefaultJdId($uid, $aid, $doc->id);
            }
        }

        return $this->show($request);
    }

    public function store(Request $request): JsonResponse
    {
        $ctx = $this->validatedUserAgentContext($request);
        if ($ctx instanceof JsonResponse) {
            return $ctx;
        }

        [$parsed, $agent] = $ctx;
        $uid = $parsed['user_id'];
        $aid = $parsed['agent_id'];

        $validated = $request->validate([
            'type' => 'required|string|in:cv,jd',
            'title' => 'nullable|string|max:255',
            'body' => 'required|string',
            'paired_cv_document_id' => 'nullable|integer|exists:agent_documents,id',
            'set_as_default' => 'sometimes|boolean',
        ]);

        if ($validated['type'] === AgentDocument::TYPE_JD) {
            $pairedId = $validated['paired_cv_document_id'] ?? null;
            if ($pairedId !== null) {
                $ok = AgentDocument::query()
                    ->whereKey($pairedId)
                    ->where('user_id', $uid)
                    ->where('agent_id', $aid)
                    ->where('type', AgentDocument::TYPE_CV)
                    ->exists();
                if (! $ok) {
                    return response()->json(['message' => 'paired_cv_document_id inválido.'], 422);
                }
            }
        }

        try {
            AgentDocumentLimits::assertBodyWithinLimit($validated['type'], $validated['body']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => collect($e->errors())->flatten()->first() ?? 'Validação falhou.',
                'errors' => $e->errors(),
            ], 422);
        }

        $doc = AgentDocument::query()->create([
            'user_id' => $uid,
            'agent_id' => $aid,
            'type' => $validated['type'],
            'title' => $validated['title'] ?? null,
            'body' => $validated['body'],
            'paired_cv_document_id' => $validated['type'] === AgentDocument::TYPE_JD
                ? ($validated['paired_cv_document_id'] ?? null)
                : null,
        ]);

        if ($validated['type'] === AgentDocument::TYPE_CV && $request->boolean('set_as_default')) {
            $this->setDefaultCvId($uid, $aid, $doc->id);
        }
        if ($validated['type'] === AgentDocument::TYPE_JD && $request->boolean('set_as_default')) {
            $this->setDefaultJdId($uid, $aid, $doc->id);
        }

        return $this->show($request);
    }

    public function updateDocument(Request $request, int $document): JsonResponse
    {
        $ctx = $this->validatedUserAgentContext($request);
        if ($ctx instanceof JsonResponse) {
            return $ctx;
        }

        [$parsed, $agent] = $ctx;
        $uid = $parsed['user_id'];
        $aid = $parsed['agent_id'];

        $doc = AgentDocument::query()
            ->whereKey($document)
            ->where('user_id', $uid)
            ->where('agent_id', $aid)
            ->firstOrFail();

        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'body' => 'required|string',
            'paired_cv_document_id' => 'nullable|integer|exists:agent_documents,id',
            'set_as_default' => 'sometimes|boolean',
        ]);

        if ($doc->type === AgentDocument::TYPE_JD) {
            $pairedId = $validated['paired_cv_document_id'] ?? null;
            if ($pairedId !== null) {
                $ok = AgentDocument::query()
                    ->whereKey($pairedId)
                    ->where('user_id', $uid)
                    ->where('agent_id', $aid)
                    ->where('type', AgentDocument::TYPE_CV)
                    ->exists();
                if (! $ok) {
                    return response()->json(['message' => 'paired_cv_document_id inválido.'], 422);
                }
            }
            $doc->paired_cv_document_id = $pairedId;
        }

        try {
            AgentDocumentLimits::assertBodyWithinLimit($doc->type, $validated['body']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => collect($e->errors())->flatten()->first() ?? 'Validação falhou.',
                'errors' => $e->errors(),
            ], 422);
        }

        $doc->title = $validated['title'] ?? null;
        $doc->body = $validated['body'];
        $doc->save();

        if ($doc->type === AgentDocument::TYPE_CV && $request->boolean('set_as_default')) {
            $this->setDefaultCvId($uid, $aid, $doc->id);
        }
        if ($doc->type === AgentDocument::TYPE_JD && $request->boolean('set_as_default')) {
            $this->setDefaultJdId($uid, $aid, $doc->id);
        }

        return $this->show($request);
    }

    public function destroyDocument(Request $request, int $document): JsonResponse
    {
        $ctx = $this->validatedUserAgentContext($request);
        if ($ctx instanceof JsonResponse) {
            return $ctx;
        }

        [$parsed, $agent] = $ctx;
        $uid = $parsed['user_id'];
        $aid = $parsed['agent_id'];

        $doc = AgentDocument::query()
            ->whereKey($document)
            ->where('user_id', $uid)
            ->where('agent_id', $aid)
            ->firstOrFail();

        $defaults = AgentDocumentDefault::query()
            ->where('user_id', $uid)
            ->where('agent_id', $aid)
            ->first();

        if ($defaults) {
            if ((int) $defaults->default_cv_document_id === (int) $doc->id) {
                $defaults->default_cv_document_id = null;
            }
            if ((int) $defaults->default_jd_document_id === (int) $doc->id) {
                $defaults->default_jd_document_id = null;
            }
            $defaults->save();
        }

        $doc->delete();

        return $this->show($request);
    }

    public function updateDefaults(Request $request): JsonResponse
    {
        $ctx = $this->validatedUserAgentContext($request);
        if ($ctx instanceof JsonResponse) {
            return $ctx;
        }

        [$parsed, $agent] = $ctx;
        $uid = $parsed['user_id'];
        $aid = $parsed['agent_id'];

        $validated = $request->validate([
            'default_cv_document_id' => 'nullable|integer|exists:agent_documents,id',
            'default_jd_document_id' => 'nullable|integer|exists:agent_documents,id',
        ]);

        if (array_key_exists('default_cv_document_id', $validated) && $validated['default_cv_document_id'] !== null) {
            $ok = AgentDocument::query()
                ->whereKey($validated['default_cv_document_id'])
                ->where('user_id', $uid)
                ->where('agent_id', $aid)
                ->where('type', AgentDocument::TYPE_CV)
                ->exists();
            if (! $ok) {
                return response()->json(['message' => 'default_cv_document_id inválido.'], 422);
            }
        }

        if (array_key_exists('default_jd_document_id', $validated) && $validated['default_jd_document_id'] !== null) {
            $ok = AgentDocument::query()
                ->whereKey($validated['default_jd_document_id'])
                ->where('user_id', $uid)
                ->where('agent_id', $aid)
                ->where('type', AgentDocument::TYPE_JD)
                ->exists();
            if (! $ok) {
                return response()->json(['message' => 'default_jd_document_id inválido.'], 422);
            }
        }

        $defaults = AgentDocumentDefault::query()->firstOrCreate(
            ['user_id' => $uid, 'agent_id' => $aid],
            []
        );

        if (array_key_exists('default_cv_document_id', $validated)) {
            $defaults->default_cv_document_id = $validated['default_cv_document_id'];
        }
        if (array_key_exists('default_jd_document_id', $validated)) {
            $defaults->default_jd_document_id = $validated['default_jd_document_id'];
        }

        $defaults->save();

        return $this->show($request);
    }

    /**
     * @return array{0: array{user_id: int, agent_id: int}, 1: Agent}|JsonResponse
     */
    private function validatedUserAgentContext(Request $request): array|JsonResponse
    {
        $validated = $request->validate([
            'user' => 'required|string|max:128',
            'agent_id' => 'required|integer|exists:agents,id',
        ]);

        $parsed = ChatKitUserRef::parse($validated['user']);
        if ($parsed === null
            || $parsed['user_id'] < 1
            || $parsed['agent_id'] < 1
            || $parsed['agent_id'] !== (int) $validated['agent_id']) {
            return response()->json(['message' => 'Parâmetro user inválido ou não coincide com agent_id.'], 422);
        }

        $agent = Agent::query()->findOrFail($validated['agent_id']);
        if (! $agent->isChatKitWorkflow()) {
            return response()->json(['message' => 'Este agente não usa ChatKit.'], 422);
        }

        return [$parsed, $agent];
    }

    private function setDefaultCvId(int $userId, int $agentId, int $cvId): void
    {
        $defaults = AgentDocumentDefault::query()->firstOrCreate(
            ['user_id' => $userId, 'agent_id' => $agentId],
            []
        );
        $defaults->default_cv_document_id = $cvId;
        $defaults->save();
    }

    private function setDefaultJdId(int $userId, int $agentId, int $jdId): void
    {
        $defaults = AgentDocumentDefault::query()->firstOrCreate(
            ['user_id' => $userId, 'agent_id' => $agentId],
            []
        );
        $defaults->default_jd_document_id = $jdId;
        $defaults->save();
    }
}
