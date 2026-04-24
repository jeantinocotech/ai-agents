<?php

namespace App\Services;

use App\Models\Setting;

/**
 * Correlates provider-reported usage with an estimated USD cost, then maps to platform wallet tokens.
 */
class ApiUsageBillingService
{
    public const MODE_ESTIMATED_USD = 'estimated_usd';

    public const MODE_API_TOKENS = 'api_tokens';

    /**
     * @param  array<string, mixed>  $usage
     * @return array{debit: int, estimated_cost_usd: float, api_prompt_tokens: int, api_completion_tokens: int, billing_mode: string, model: string}
     */
    public function computeDebit(array $usage, string $provider, string $model): array
    {
        $mode = Setting::get('usage_billing_mode', self::MODE_ESTIMATED_USD);
        [$prompt, $completion] = $this->extractTokenCounts($usage, $provider);

        if ($mode === self::MODE_API_TOKENS) {
            $raw = $this->rawApiTokenTotal($prompt, $completion, $usage, $provider);

            return [
                'debit' => max(1, $raw),
                'estimated_cost_usd' => 0.0,
                'api_prompt_tokens' => $prompt,
                'api_completion_tokens' => $completion,
                'billing_mode' => self::MODE_API_TOKENS,
                'model' => $model,
            ];
        }

        $costUsd = $this->estimateCostUsd($provider, $prompt, $completion);
        $perUsd = (float) Setting::get('platform_tokens_per_usd', '1000');
        $perUsd = max(0.000001, $perUsd);

        if ($costUsd > 0) {
            $debit = max(1, (int) ceil($costUsd * $perUsd));
        } else {
            $debit = max(1, $this->rawApiTokenTotal($prompt, $completion, $usage, $provider));
        }

        return [
            'debit' => $debit,
            'estimated_cost_usd' => round($costUsd, 8),
            'api_prompt_tokens' => $prompt,
            'api_completion_tokens' => $completion,
            'billing_mode' => self::MODE_ESTIMATED_USD,
            'model' => $model,
        ];
    }

    /**
     * @param  array<string, mixed>  $usage
     * @return array{0: int, 1: int}
     */
    private function extractTokenCounts(array $usage, string $provider): array
    {
        if ($provider === 'anthropic') {
            return [
                (int) ($usage['input_tokens'] ?? 0),
                (int) ($usage['output_tokens'] ?? 0),
            ];
        }

        $prompt = (int) ($usage['prompt_tokens'] ?? 0);
        $completion = (int) ($usage['completion_tokens'] ?? 0);
        if ($prompt + $completion === 0 && isset($usage['total_tokens'])) {
            $total = (int) $usage['total_tokens'];
            if ($total > 0) {
                $prompt = $total;
            }
        }

        return [$prompt, $completion];
    }

    /**
     * @param  array<string, mixed>  $usage
     */
    private function rawApiTokenTotal(int $prompt, int $completion, array $usage, string $provider): int
    {
        if ($provider === 'anthropic') {
            return max(0, $prompt + $completion);
        }

        $total = (int) ($usage['total_tokens'] ?? 0);
        if ($total > 0) {
            return $total;
        }

        return max(0, $prompt + $completion);
    }

    private function estimateCostUsd(string $provider, int $promptTokens, int $completionTokens): float
    {
        if ($provider === 'anthropic') {
            $inPerM = (float) Setting::get('anthropic_input_price_per_million_usd', '3');
            $outPerM = (float) Setting::get('anthropic_output_price_per_million_usd', '15');
        } else {
            $inPerM = (float) Setting::get('openai_input_price_per_million_usd', '5');
            $outPerM = (float) Setting::get('openai_output_price_per_million_usd', '15');
        }

        return ($promptTokens / 1_000_000) * $inPerM
            + ($completionTokens / 1_000_000) * $outPerM;
    }
}
