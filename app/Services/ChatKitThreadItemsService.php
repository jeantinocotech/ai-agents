<?php

namespace App\Services;

use App\Models\Agent;
use App\Support\AtsChatKitSyncNormalizer;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class ChatKitThreadItemsService
{
    /**
     * @return list<array<string, mixed>>
     */
    public function fetchItems(Agent $agent, string $threadId, int $limit = 40): array
    {
        $threadId = trim($threadId);
        if ($threadId === '') {
            return [];
        }

        $limit = max(5, min(100, $limit));

        $response = $this->request($agent, 'get', "chatkit/threads/{$threadId}/items", [
            'limit' => $limit,
            'order' => 'desc',
        ]);

        if (! $response->successful()) {
            Log::warning('ChatKit thread items fetch failed', [
                'agent_id' => $agent->id,
                'thread_id' => mb_substr($threadId, 0, 24),
                'status' => $response->status(),
                'body' => $response->json() ?? $response->body(),
            ]);

            return [];
        }

        $data = $response->json('data');

        return is_array($data) ? $data : [];
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return array<string, mixed>|null
     */
    public function extractAtsSyncPayload(array $items): ?array
    {
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $type = (string) ($item['type'] ?? '');
            if (! str_contains($type, 'client_tool_call')) {
                continue;
            }

            if ((string) ($item['name'] ?? '') !== 'persist_ats_analysis') {
                continue;
            }

            $fromArguments = $this->decodeJsonObject($item['arguments'] ?? null);
            if ($fromArguments !== null) {
                return $fromArguments;
            }

            $fromOutput = $this->decodeJsonObject($item['output'] ?? null);
            if ($fromOutput !== null && isset($fromOutput['items'])) {
                return $fromOutput;
            }
        }

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $type = (string) ($item['type'] ?? '');
            if (! str_contains($type, 'assistant')) {
                continue;
            }

            $text = $this->assistantMessageText($item);
            if ($text === '') {
                continue;
            }

            $normalized = AtsChatKitSyncNormalizer::normalize([
                'raw_table_text' => $text,
                'jd_document_id' => 0,
                'user_cv_id' => 0,
            ]);

            if ($normalized['items'] !== []) {
                return [
                    'ats_score' => $normalized['ats_score'],
                    'items' => $normalized['items'],
                    'raw_table_text' => $text,
                ];
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function assistantMessageText(array $item): string
    {
        $parts = [];
        $content = $item['content'] ?? [];
        if (! is_array($content)) {
            return '';
        }

        foreach ($content as $segment) {
            if (! is_array($segment)) {
                continue;
            }
            $type = (string) ($segment['type'] ?? '');
            if ($type !== 'output_text' && $type !== '') {
                continue;
            }
            $text = trim((string) ($segment['text'] ?? ''));
            if ($text !== '') {
                $parts[] = $text;
            }
        }

        return trim(implode("\n", $parts));
    }

    /**
     * @return array<string, mixed>|null
     */
    private function decodeJsonObject(mixed $value): ?array
    {
        if (is_array($value)) {
            return $value;
        }

        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * @param  array<string, scalar|null>  $query
     */
    private function request(Agent $agent, string $method, string $path, array $query = []): Response
    {
        $apiKey = trim((string) ($agent->api_key ?: config('services.openai.api_key')));
        $headers = [
            'OpenAI-Beta' => (string) config('services.chatkit.beta_header', 'chatkit_beta=v1'),
        ];
        if ($agent->project_id) {
            $headers['OpenAI-Project'] = trim((string) $agent->project_id);
        }

        $timeoutSeconds = max(15, min(180, (int) config('services.chatkit.http_timeout', 60)));
        $url = 'https://api.openai.com/v1/'.ltrim($path, '/');

        $pending = Http::withHeaders($headers)
            ->timeout($timeoutSeconds)
            ->withToken($apiKey)
            ->acceptJson();

        return $method === 'get'
            ? $pending->get($url, $query)
            : $pending->post($url, $query);
    }
}
