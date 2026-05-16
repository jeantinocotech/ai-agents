<?php

namespace App\Services;

use App\Models\AgentDocument;
use App\Models\AtsAnalysis;
use App\Models\AtsAnalysisItem;
use App\Models\UserCv;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class AtsKeywordAnalysisService
{
    /**
     * Gera ou actualiza análise estruturada para o par JD + CV de perfil.
     */
    public function analyzeForPair(UserCv $userCv, AgentDocument $jd, int $userId, ?float $atsScore = null): AtsAnalysis
    {
        $rows = $this->buildItemRows(
            (string) $userCv->body,
            (string) $jd->body
        );

        $analysis = AtsAnalysis::query()->firstOrNew([
            'user_id' => $userId,
            'agent_document_id' => (int) $jd->getKey(),
            'user_cv_id' => (int) $userCv->getKey(),
        ]);

        if ($atsScore !== null && $analysis->exists && $analysis->ats_score !== null) {
            $analysis->previous_ats_score = $analysis->ats_score;
        }
        if ($atsScore !== null) {
            $analysis->ats_score = $atsScore;
        }

        $analysis->status = AtsAnalysis::STATUS_READY;
        $analysis->source = config('services.openai.api_key') ? AtsAnalysis::SOURCE_LLM : AtsAnalysis::SOURCE_MANUAL;
        $analysis->save();

        $analysis->items()->delete();

        foreach ($rows as $index => $row) {
            $analysis->items()->create([
                'keyword' => $row['keyword'],
                'relevance' => $row['relevance'],
                'match_status' => $row['match_status'],
                'cv_snippet' => $row['cv_snippet'],
                'suggestion' => $row['suggestion'],
                'is_addressed' => false,
                'priority_rank' => $row['priority_rank'],
                'sort_order' => $index,
            ]);
        }

        return $analysis->fresh(['items']);
    }

    /**
     * @return list<array{keyword: string, relevance: string, match_status: string, cv_snippet: ?string, suggestion: ?string, priority_rank: int}>
     */
    public function buildItemRows(string $cvBody, string $jdBody): array
    {
        $apiKey = trim((string) config('services.openai.api_key'));
        if ($apiKey !== '') {
            try {
                $fromLlm = $this->fetchFromOpenAi($cvBody, $jdBody, $apiKey);
                if ($fromLlm !== []) {
                    return $this->normalizeAndRank($fromLlm, $cvBody);
                }
            } catch (\Throwable) {
                // fallback heurístico
            }
        }

        return $this->heuristicRows($cvBody, $jdBody);
    }

    /**
     * @return list<array{keyword: string, relevance: string, match_status: string, cv_snippet: ?string, suggestion: ?string}>
     */
    private function fetchFromOpenAi(string $cvBody, string $jdBody, string $apiKey): array
    {
        $cvExcerpt = Str::limit($cvBody, 12000, '…');
        $jdExcerpt = Str::limit($jdBody, 12000, '…');

        $response = Http::withToken($apiKey)
            ->timeout(90)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => config('services.openai.ats_analysis_model', 'gpt-4o-mini'),
                'temperature' => 0.2,
                'response_format' => ['type' => 'json_object'],
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Analise CV vs vaga para ATS. Responda só JSON: {"items":[{"keyword":"...","relevance":"high|medium|low","match_status":"full|partial|missing","cv_snippet":"... ou null","suggestion":"frase curta ou null"}]}. Máximo 25 items. Português de Portugal.',
                    ],
                    [
                        'role' => 'user',
                        'content' => "CV:\n{$cvExcerpt}\n\nVAGA:\n{$jdExcerpt}",
                    ],
                ],
            ]);

        if (! $response->successful()) {
            return [];
        }

        $content = (string) data_get($response->json(), 'choices.0.message.content', '');
        $decoded = json_decode($content, true);
        if (! is_array($decoded) || ! is_array($decoded['items'] ?? null)) {
            return [];
        }

        $out = [];
        foreach ($decoded['items'] as $item) {
            if (! is_array($item) || empty($item['keyword'])) {
                continue;
            }
            $out[] = [
                'keyword' => Str::limit((string) $item['keyword'], 255, ''),
                'relevance' => $this->normalizeRelevance((string) ($item['relevance'] ?? 'medium')),
                'match_status' => $this->normalizeMatchStatus((string) ($item['match_status'] ?? 'missing')),
                'cv_snippet' => isset($item['cv_snippet']) ? Str::limit((string) $item['cv_snippet'], 500, '…') : null,
                'suggestion' => isset($item['suggestion']) ? Str::limit((string) $item['suggestion'], 500, '…') : null,
            ];
        }

        return $out;
    }

    /**
     * @param  list<array{keyword: string, relevance: string, match_status: string, cv_snippet: ?string, suggestion: ?string}>  $items
     * @return list<array{keyword: string, relevance: string, match_status: string, cv_snippet: ?string, suggestion: ?string, priority_rank: int}>
     */
    private function normalizeAndRank(array $items, string $cvBody): array
    {
        $cvLower = mb_strtolower($cvBody);

        foreach ($items as &$item) {
            $kw = mb_strtolower($item['keyword']);
            if ($item['match_status'] === AtsAnalysisItem::MATCH_MISSING && str_contains($cvLower, $kw)) {
                $item['match_status'] = AtsAnalysisItem::MATCH_PARTIAL;
            }
            $item['priority_rank'] = $this->priorityRank(
                $item['match_status'],
                $item['relevance']
            );
        }
        unset($item);

        usort($items, fn ($a, $b) => $a['priority_rank'] <=> $b['priority_rank']);

        return $items;
    }

    /**
     * @return list<array{keyword: string, relevance: string, match_status: string, cv_snippet: ?string, suggestion: ?string, priority_rank: int}>
     */
    private function heuristicRows(string $cvBody, string $jdBody): array
    {
        $keywords = $this->extractKeywords($jdBody);
        $cvLower = mb_strtolower($cvBody);
        $rows = [];

        foreach ($keywords as $keyword) {
            $kwLower = mb_strtolower($keyword);
            $pos = mb_strpos($cvLower, $kwLower);
            if ($pos === false) {
                $match = AtsAnalysisItem::MATCH_MISSING;
                $snippet = null;
                $suggestion = 'Inclua «'.$keyword.'» em experiência, competências ou resumo.';
            } elseif (mb_strlen($keyword) >= 4 && mb_substr_count($cvLower, $kwLower) === 1) {
                $match = AtsAnalysisItem::MATCH_PARTIAL;
                $snippet = Str::limit(mb_substr($cvBody, max(0, $pos - 40), 120), 120, '…');
                $suggestion = 'Reforce a menção a «'.$keyword.'» com o termo exacto da vaga.';
            } else {
                $match = AtsAnalysisItem::MATCH_FULL;
                $snippet = Str::limit(mb_substr($cvBody, max(0, $pos - 40), 120), 120, '…');
                $suggestion = null;
            }

            $relevance = strlen($keyword) >= 8 ? AtsAnalysisItem::RELEVANCE_HIGH : AtsAnalysisItem::RELEVANCE_MEDIUM;

            $rows[] = [
                'keyword' => $keyword,
                'relevance' => $relevance,
                'match_status' => $match,
                'cv_snippet' => $snippet,
                'suggestion' => $suggestion,
                'priority_rank' => $this->priorityRank($match, $relevance),
            ];
        }

        usort($rows, fn ($a, $b) => $a['priority_rank'] <=> $b['priority_rank']);

        return array_slice($rows, 0, 25);
    }

    /**
     * @return list<string>
     */
    private function extractKeywords(string $jdBody): array
    {
        $text = mb_strtolower($jdBody);
        preg_match_all('/\b[a-záàâãéèêíïóôõúç][a-záàâãéèêíïóôõúç0-9\-]{2,}\b/u', $text, $matches);
        $stop = array_flip([
            'para', 'com', 'sem', 'uma', 'uns', 'das', 'dos', 'que', 'por', 'ser', 'são', 'the', 'and', 'you', 'your',
            'will', 'have', 'this', 'from', 'our', 'job', 'vaga', 'anos', 'mais', 'como', 'deve', 'pela', 'pelo',
        ]);

        $freq = [];
        foreach ($matches[0] ?? [] as $word) {
            if (isset($stop[$word]) || mb_strlen($word) < 3) {
                continue;
            }
            $freq[$word] = ($freq[$word] ?? 0) + 1;
        }

        arsort($freq);

        return array_slice(array_map(
            fn ($w) => mb_convert_case($w, MB_CASE_TITLE, 'UTF-8'),
            array_keys($freq)
        ), 0, 20);
    }

    public function priorityRank(string $matchStatus, string $relevance): int
    {
        $matchScore = match ($matchStatus) {
            AtsAnalysisItem::MATCH_MISSING => 0,
            AtsAnalysisItem::MATCH_PARTIAL => 10,
            default => 30,
        };
        $relScore = match ($relevance) {
            AtsAnalysisItem::RELEVANCE_HIGH => 0,
            AtsAnalysisItem::RELEVANCE_LOW => 20,
            default => 10,
        };

        return $matchScore + $relScore;
    }

    private function normalizeRelevance(string $value): string
    {
        return match (strtolower($value)) {
            'high', 'alta' => AtsAnalysisItem::RELEVANCE_HIGH,
            'low', 'baixa' => AtsAnalysisItem::RELEVANCE_LOW,
            default => AtsAnalysisItem::RELEVANCE_MEDIUM,
        };
    }

    private function normalizeMatchStatus(string $value): string
    {
        return match (strtolower($value)) {
            'full', 'completo', 'ok' => AtsAnalysisItem::MATCH_FULL,
            'partial', 'parcial' => AtsAnalysisItem::MATCH_PARTIAL,
            default => AtsAnalysisItem::MATCH_MISSING,
        };
    }
}
