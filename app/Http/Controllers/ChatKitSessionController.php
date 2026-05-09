<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\Setting;
use App\Services\CareerTrailAgentAccess;
use App\Services\GamificationService;
use App\Services\TokenWalletService;
use App\Support\ChatKitUserRef;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatKitSessionController extends Controller
{
    public function __construct(
        private TokenWalletService $tokenWallet,
        private GamificationService $gamification
    ) {}

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'agent_id' => 'required|integer|exists:agents,id',
        ]);

        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Não autenticado.'], 401);
        }

        $user->refresh();

        if (! $this->tokenWallet->userHasMinimumBalance($user)) {
            return response()->json([
                'message' => 'Seus tokens acabaram ou estão abaixo do mínimo.',
                'error' => 'insufficient_tokens',
                'token_balance' => (int) $user->token_balance,
            ], 402);
        }

        $agent = Agent::query()->findOrFail($validated['agent_id']);

        if ($denied = CareerTrailAgentAccess::denyJsonUnlessCanAccess($user, $agent)) {
            return $denied;
        }

        if (! $agent->isChatKitWorkflow()) {
            return response()->json(['message' => 'Este agente não está configurado para ChatKit.'], 422);
        }

        if (! $agent->chatkit_workflow_id) {
            return response()->json(['message' => 'Workflow ChatKit não configurado para este agente.'], 422);
        }

        $apiKey = trim((string) ($agent->api_key ?: config('services.openai.api_key')));
        if ($apiKey === '') {
            return response()->json([
                'message' => 'Defina a chave API do agente ou OPENAI_API_KEY no .env para criar sessões ChatKit.',
            ], 503);
        }

        $workflow = ['id' => $agent->chatkit_workflow_id];
        $version = trim((string) ($agent->chatkit_workflow_version ?? ''));
        if ($version !== '') {
            $workflow['version'] = $version;
        }

        $headers = [
            'OpenAI-Beta' => (string) config('services.chatkit.beta_header', 'chatkit_beta=v1'),
        ];
        if ($agent->project_id) {
            $headers['OpenAI-Project'] = trim((string) $agent->project_id);
        }

        $timeoutSeconds = max(15, min(180, (int) config('services.chatkit.http_timeout', 60)));

        try {
            $response = Http::withHeaders($headers)
                ->timeout($timeoutSeconds)
                ->withToken($apiKey)
                ->acceptJson()
                ->post('https://api.openai.com/v1/chatkit/sessions', [
                    'workflow' => $workflow,
                    'user' => ChatKitUserRef::build($user->id, $agent->id),
                ]);
        } catch (\Throwable $e) {
            Log::error('ChatKit session request failed', [
                'error' => $e->getMessage(),
                'agent_id' => $agent->id,
                'timeout_seconds' => $timeoutSeconds,
            ]);

            return response()->json(['message' => 'Erro ao contatar a API OpenAI.'], 502);
        }

        if (! $response->successful()) {
            $json = $response->json();
            Log::warning('ChatKit session OpenAI error', [
                'status' => $response->status(),
                'agent_id' => $agent->id,
                'openai_beta' => $headers['OpenAI-Beta'] ?? '',
                'openai_project_sent' => ! empty($agent->project_id),
                'workflow_id_prefix' => is_string($agent->chatkit_workflow_id ?? null)
                    ? mb_substr((string) $agent->chatkit_workflow_id, 0, 12)
                    : '',
                'body' => $json ?? $response->body(),
            ]);

            $openaiHint = self::openAiErrorHint($json);
            $message = $openaiHint !== ''
                ? 'OpenAI: '.$openaiHint
                : 'OpenAI recusou criar a sessão ChatKit. Verifique o workflow, a chave e o header beta (CHATKIT_OPENAI_BETA).';

            return response()->json([
                'message' => $message,
                'detail' => $json,
            ], $response->status() >= 400 && $response->status() < 600 ? $response->status() : 502);
        }

        $clientSecret = data_get($response->json(), 'client_secret');
        if (! is_string($clientSecret) || $clientSecret === '') {
            Log::error('ChatKit session missing client_secret', ['json' => $response->json()]);

            return response()->json(['message' => 'Resposta inválida da OpenAI (sem client_secret).'], 502);
        }

        Log::info('ChatKit session created', [
            'agent_id' => $agent->id,
            'workflow_id_suffix' => is_string($agent->chatkit_workflow_id ?? null)
                ? mb_substr((string) $agent->chatkit_workflow_id, -8)
                : '',
        ]);

        $user->refresh();

        return response()->json([
            'client_secret' => $clientSecret,
            'token_balance' => (int) $user->token_balance,
            'tokens_debited' => 0,
        ]);
    }

    /**
     * Débito fixo após conclusão da resposta do assistente à consulta (JD enviado — análise ATS).
     * A criação/renovação da sessão ChatKit não debita tokens.
     */
    public function debitConsultation(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'agent_id' => 'required|integer|exists:agents,id',
            'context' => 'nullable|string|in:cv_turn',
        ]);

        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Não autenticado.'], 401);
        }

        $user->refresh();

        $agent = Agent::query()->findOrFail($validated['agent_id']);

        if ($denied = CareerTrailAgentAccess::denyJsonUnlessCanAccess($user, $agent)) {
            return $denied;
        }

        if (! $agent->isChatKitWorkflow()) {
            return response()->json(['message' => 'Este agente não está configurado para ChatKit.'], 422);
        }

        $amount = max(0, (int) Setting::get('chatkit_tokens_per_session', '50'));

        if ($amount <= 0) {
            return response()->json([
                'token_balance' => (int) $user->token_balance,
                'tokens_debited' => 0,
            ]);
        }

        if ((int) $user->token_balance < $amount) {
            return response()->json([
                'message' => 'Saldo insuficiente para registrar esta consulta (tokens: '.$amount.').',
                'error' => 'insufficient_tokens',
                'required' => $amount,
                'token_balance' => (int) $user->token_balance,
            ], 402);
        }

        $billing = ($validated['context'] ?? null) === 'cv_turn'
            ? 'chatkit_cv_assistant_turn'
            : 'chatkit_ats_consultation';

        $debited = $this->tokenWallet->debitUsage(
            $user->fresh(),
            $amount,
            [
                'provider' => 'chatkit',
                'billing' => $billing,
                'agent_id' => $agent->id,
                'workflow_id' => $agent->chatkit_workflow_id,
            ],
            Agent::class,
            $agent->id
        );

        $user->refresh();

        if ($debited > 0) {
            $eventKey = $billing === 'chatkit_cv_assistant_turn'
                ? 'chatkit_cv_turn'
                : 'chatkit_ats_consultation';
            $this->gamification->recordEvent(
                $user,
                $eventKey,
                Agent::class,
                (int) $agent->id,
                [
                    'billing' => $billing,
                    'agent_id' => (int) $agent->id,
                    'tokens_debited' => (int) $debited,
                ]
            );
            $this->gamification->ensureFreshSnapshot($user);
        }

        return response()->json([
            'token_balance' => (int) $user->token_balance,
            'tokens_debited' => $debited,
        ]);
    }

    /**
     * @param  array<string, mixed>|null  $json
     */
    private static function openAiErrorHint(?array $json): string
    {
        if ($json === null) {
            return '';
        }

        $parts = [];
        $err = data_get($json, 'error');
        if (is_array($err)) {
            $parts[] = (string) (data_get($err, 'message') ?: '');
            $parts[] = (string) (data_get($err, 'code') ?: '');
        }
        $parts[] = (string) (data_get($json, 'message') ?: '');

        $hint = trim(implode(' — ', array_filter(array_map('trim', $parts))));

        if (mb_strlen($hint) > 420) {
            return mb_substr($hint, 0, 417).'…';
        }

        return $hint;
    }
}
