<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\AtsAnalysis;
use App\Models\AtsAnalysisItem;
use App\Models\CareerTrailStep;
use App\Models\UserCv;
use App\Services\AtsKeywordAnalysisService;
use App\Services\ChatKitThreadItemsService;
use App\Support\AtsChatKitSyncNormalizer;
use App\Support\CareerTrailAtsJdValidator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CareerTrailAtsWorkspaceController extends Controller
{
    public function store(Request $request, AtsKeywordAnalysisService $analysisService): RedirectResponse
    {
        $validated = $request->validate([
            'jd_document_id' => ['required', 'integer'],
            'ats_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $user = $request->user();
        $jd = CareerTrailAtsJdValidator::validatedJdForUser((int) $validated['jd_document_id'], $user);
        abort_if($reason = $jd->atsFlowBlockReason(), 403, $reason);
        $userCv = UserCv::query()
            ->whereKey($jd->user_cv_id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $existing = AtsAnalysis::findForPair((int) $user->id, (int) $jd->getKey(), (int) $userCv->getKey());
        if ($existing !== null && $existing->source === AtsAnalysis::SOURCE_CHATKIT_TOOL) {
            return redirect()
                ->route('career-trail.ats.workspace', $existing)
                ->with('status', 'A lista do ChatKit já está disponível — ajuste o CV keyword a keyword.');
        }

        $score = isset($validated['ats_score']) ? (float) $validated['ats_score'] : null;
        $analysis = $analysisService->analyzeForPair($userCv, $jd, (int) $user->id, $score);

        return redirect()
            ->route('career-trail.ats.workspace', $analysis)
            ->with(
                'status',
                'Lista automática gerada pela app (pode demorar e não substitui o ChatKit). Para a tabela oficial e ATS %, use «Passar no filtro» no chat e aguarde «Tabela ATS guardada».'
            );
    }

    public function show(Request $request, AtsAnalysis $analysis): View
    {
        $this->authorizeAnalysis($request, $analysis);
        $this->authorizeWorkspaceEditable($analysis);

        $analysis->load(['items', 'jdDocument', 'userCv']);

        $atsStep = CareerTrailStep::query()->where('slug', 'ats')->where('is_active', true)->first();
        $atsAgent = $atsStep?->resolvedAgent();
        $reanalyzeUrl = ($atsAgent && $analysis->jdDocument)
            ? CareerTrailStep::atsAnalyzeChatUrlForJd($request->user(), $atsAgent, (int) $analysis->agent_document_id)
            : null;

        $items = $analysis->items;
        $pendingCount = $items->where('is_addressed', false)
            ->whereIn('match_status', [AtsAnalysisItem::MATCH_MISSING, AtsAnalysisItem::MATCH_PARTIAL])
            ->count();
        $addressedCount = $items->where('is_addressed', true)->count();

        return view('career-trail.ats-workspace', [
            'analysis' => $analysis,
            'items' => $items,
            'pendingCount' => $pendingCount,
            'addressedCount' => $addressedCount,
            'reanalyzeUrl' => $reanalyzeUrl,
            'maxCvBodyChars' => (int) config('agent_documents.max_cv_body_chars', 60000),
        ]);
    }

    public function updateCv(Request $request, AtsAnalysis $analysis): RedirectResponse
    {
        $this->authorizeAnalysis($request, $analysis);
        $this->authorizeWorkspaceEditable($analysis);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:'.(int) config('agent_documents.max_cv_body_chars', 60000)],
            'redirect' => ['nullable', 'string', 'in:workspace,reanalyze'],
        ]);

        $userCv = $analysis->userCv;
        abort_if($userCv === null, 404);

        $userCv->update(['body' => $validated['body']]);

        if (($validated['redirect'] ?? 'workspace') === 'reanalyze') {
            $atsStep = CareerTrailStep::query()->where('slug', 'ats')->where('is_active', true)->first();
            $atsAgent = $atsStep?->resolvedAgent();
            if ($atsAgent) {
                $url = CareerTrailStep::atsAnalyzeChatUrlForJd(
                    $request->user(),
                    $atsAgent,
                    (int) $analysis->agent_document_id,
                    (int) $analysis->id,
                );
                if ($url) {
                    return redirect()->away($url)->with(
                        'status',
                        'CV guardado. Aguarde a nova tabela no chat e «Tabela ATS guardada» — a lista no workspace será actualizada automaticamente.'
                    );
                }
            }
        }

        return redirect()
            ->route('career-trail.ats.workspace', $analysis)
            ->with('status', 'CV guardado.');
    }

    public function patchItem(Request $request, AtsAnalysisItem $item): JsonResponse|RedirectResponse
    {
        $analysis = $item->analysis;
        abort_if($analysis === null, 404);
        $this->authorizeAnalysis($request, $analysis);

        $validated = $request->validate([
            'is_addressed' => ['sometimes', 'boolean'],
        ]);

        if (array_key_exists('is_addressed', $validated)) {
            $item->update(['is_addressed' => (bool) $validated['is_addressed']]);
        }

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'item' => $item->fresh()]);
        }

        return redirect()
            ->route('career-trail.ats.workspace', $analysis)
            ->with('status', 'Item actualizado.');
    }

    public function pairStatus(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'jd_document_id' => ['required', 'integer'],
            'user_cv_id' => ['required', 'integer'],
        ]);

        $user = $request->user();
        $jd = CareerTrailAtsJdValidator::validatedJdForUser((int) $validated['jd_document_id'], $user);

        if ($blockReason = $jd->atsFlowBlockReason()) {
            return response()->json([
                'ok' => true,
                'pair_valid' => false,
                'allows_ats_flow' => false,
                'message' => $blockReason,
            ]);
        }

        if ((int) $jd->user_cv_id !== (int) $validated['user_cv_id']) {
            return response()->json([
                'ok' => true,
                'pair_valid' => false,
                'allows_ats_flow' => true,
                'message' => 'O CV seleccionado não corresponde a esta vaga.',
            ]);
        }

        $analysis = AtsAnalysis::findForPair(
            (int) $user->id,
            (int) $jd->getKey(),
            (int) $validated['user_cv_id'],
        );

        return response()->json([
            'ok' => true,
            'pair_valid' => true,
            'allows_ats_flow' => true,
            'analysis_id' => $analysis?->id,
            'source' => $analysis?->source,
            'updated_at' => $analysis?->updated_at?->toIso8601String(),
            'items_count' => $analysis?->items()->count(),
            'workspace_url' => $analysis !== null
                ? route('career-trail.ats.workspace', $analysis)
                : null,
        ]);
    }

    public function syncFromChatKit(Request $request): JsonResponse
    {
        $request->validate([
            'jd_document_id' => ['required', 'integer', 'min:1'],
            'user_cv_id' => ['required', 'integer', 'min:1'],
            'raw_table_text' => ['nullable', 'string', 'max:50000'],
        ]);

        return $this->persistChatKitSync($request, $request->all());
    }

    public function syncFromChatKitThread(Request $request, ChatKitThreadItemsService $threadItems): JsonResponse
    {
        $validated = $request->validate([
            'agent_id' => ['required', 'integer', 'exists:agents,id'],
            'thread_id' => ['required', 'string', 'max:128'],
            'jd_document_id' => ['required', 'integer', 'min:1'],
            'user_cv_id' => ['required', 'integer', 'min:1'],
        ]);

        $user = $request->user();
        abort_if($user === null, 401);

        $agent = Agent::query()->findOrFail($validated['agent_id']);
        abort_unless($agent->isChatKitWorkflow(), 422, 'Este agente não usa ChatKit.');

        $jd = CareerTrailAtsJdValidator::validatedJdForUser((int) $validated['jd_document_id'], $user);
        abort_unless((int) $jd->agent_id === (int) $agent->id, 422, 'Agente não corresponde à vaga.');
        abort_unless((int) $jd->user_cv_id === (int) $validated['user_cv_id'], 422, 'CV não corresponde à vaga.');
        abort_if($reason = $jd->atsFlowBlockReason(), 403, $reason);

        $items = $threadItems->fetchItems($agent, $validated['thread_id']);
        $extracted = $threadItems->extractAtsSyncPayload($items);

        if ($extracted === null) {
            Log::info('ats-thread-sync: table not ready', [
                'user_id' => $user->id,
                'agent_id' => $agent->id,
                'thread_id' => mb_substr($validated['thread_id'], 0, 24),
                'items_fetched' => count($items),
            ]);

            return response()->json([
                'ok' => false,
                'ready' => false,
                'message' => 'A tabela ATS ainda não está disponível no thread. Aguarde alguns segundos.',
            ], 404);
        }

        $payload = array_merge($extracted, [
            'jd_document_id' => (int) $validated['jd_document_id'],
            'user_cv_id' => (int) $validated['user_cv_id'],
        ]);

        return $this->persistChatKitSync($request, $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function persistChatKitSync(Request $request, array $payload): JsonResponse
    {
        $normalized = AtsChatKitSyncNormalizer::normalize($payload);
        $scoreFromPayload = AtsChatKitSyncNormalizer::parseAtsScore(
            $payload['ats_score'] ?? $payload['ats_percent'] ?? $payload['score'] ?? null
        );

        try {
            $validated = validator($normalized, [
                'jd_document_id' => ['required', 'integer', 'min:1'],
                'user_cv_id' => ['required', 'integer', 'min:1'],
                'ats_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
                'items' => ['required', 'array', 'min:1', 'max:50'],
                'items.*.keyword' => ['required', 'string', 'max:255'],
                'items.*.relevance' => ['nullable', 'string', 'max:16'],
                'items.*.match_status' => ['nullable', 'string', 'max:32'],
                'items.*.cv_snippet' => ['nullable', 'string', 'max:500'],
                'items.*.suggestion' => ['nullable', 'string', 'max:500'],
            ])->validate();
        } catch (ValidationException $e) {
            Log::warning('ats-analysis-sync validation failed', [
                'user_id' => $request->user()?->id,
                'jd_document_id' => $normalized['jd_document_id'] ?? null,
                'user_cv_id' => $normalized['user_cv_id'] ?? null,
                'items_count' => is_array($normalized['items'] ?? null) ? count($normalized['items']) : 0,
                'errors' => $e->errors(),
            ]);

            throw $e;
        }

        $user = $request->user();
        $jd = CareerTrailAtsJdValidator::validatedJdForUser((int) $validated['jd_document_id'], $user);
        abort_unless((int) $jd->user_cv_id === (int) $validated['user_cv_id'], 422, 'CV não corresponde à vaga.');
        abort_if($reason = $jd->atsFlowBlockReason(), 403, $reason);

        $userCv = UserCv::query()
            ->whereKey($validated['user_cv_id'])
            ->where('user_id', $user->id)
            ->firstOrFail();

        $analysis = AtsAnalysis::query()->firstOrNew([
            'user_id' => $user->id,
            'agent_document_id' => (int) $jd->getKey(),
            'user_cv_id' => (int) $userCv->getKey(),
        ]);

        if (isset($validated['ats_score'])) {
            $newScore = (float) $validated['ats_score'];
            if ($analysis->exists && $analysis->ats_score !== null) {
                $analysis->previous_ats_score = $analysis->ats_score;
            }
            $analysis->ats_score = $newScore;
        }

        $analysis->status = AtsAnalysis::STATUS_READY;
        $analysis->source = AtsAnalysis::SOURCE_CHATKIT_TOOL;
        $analysis->save();
        $analysis->items()->delete();

        $service = app(AtsKeywordAnalysisService::class);
        foreach ($validated['items'] as $index => $row) {
            $relevance = match (strtolower((string) ($row['relevance'] ?? 'medium'))) {
                'high', 'alta' => AtsAnalysisItem::RELEVANCE_HIGH,
                'low', 'baixa' => AtsAnalysisItem::RELEVANCE_LOW,
                default => AtsAnalysisItem::RELEVANCE_MEDIUM,
            };
            $matchStatus = match (strtolower((string) ($row['match_status'] ?? 'missing'))) {
                'full', 'completo', 'ok', 'presente', 'present', 'included', 'include' => AtsAnalysisItem::MATCH_FULL,
                'partial', 'parcial' => AtsAnalysisItem::MATCH_PARTIAL,
                default => AtsAnalysisItem::MATCH_MISSING,
            };
            $priorityRank = $service->priorityRank($matchStatus, $relevance);

            $analysis->items()->create([
                'keyword' => (string) $row['keyword'],
                'relevance' => $relevance,
                'match_status' => $matchStatus,
                'cv_snippet' => $row['cv_snippet'] ?? null,
                'suggestion' => $row['suggestion'] ?? null,
                'is_addressed' => false,
                'priority_rank' => $priorityRank,
                'sort_order' => $index,
            ]);
        }

        return response()->json([
            'ok' => true,
            'analysis_id' => $analysis->id,
            'source' => $analysis->source,
            'ats_score' => $analysis->ats_score !== null ? (float) $analysis->ats_score : null,
            'score_estimated' => $scoreFromPayload === null && $analysis->ats_score !== null,
            'items_count' => $analysis->items()->count(),
            'workspace_url' => route('career-trail.ats.workspace', $analysis),
        ]);
    }

    private function authorizeAnalysis(Request $request, AtsAnalysis $analysis): void
    {
        abort_unless((int) $analysis->user_id === (int) $request->user()->id, 403);
    }

    private function authorizeWorkspaceEditable(AtsAnalysis $analysis): void
    {
        $analysis->loadMissing('jdDocument');
        $jd = $analysis->jdDocument;
        abort_if($jd === null, 404);
        abort_if($reason = $jd->atsFlowBlockReason(), 403, $reason);
    }
}
