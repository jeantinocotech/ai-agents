<?php

namespace App\Support;

final class AtsChatKitSyncNormalizer
{
    /**
     * @param  array<string, mixed>  $payload
     * @return array{jd_document_id: int, user_cv_id: int, ats_score: ?float, items: list<array{keyword: string, relevance: ?string, match_status: ?string, cv_snippet: ?string, suggestion: ?string}>}
     */
    public static function normalize(array $payload): array
    {
        $itemsRaw = $payload['items'] ?? $payload['keywords'] ?? $payload['rows'] ?? [];
        if (! is_array($itemsRaw)) {
            $itemsRaw = [];
        }

        $items = [];
        foreach ($itemsRaw as $row) {
            if (! is_array($row)) {
                continue;
            }
            $normalized = self::normalizeItemRow($row);
            if ($normalized !== null) {
                $items[] = $normalized;
            }
        }

        $atsScore = self::parseAtsScore(
            $payload['ats_score']
            ?? $payload['ats_percent']
            ?? $payload['score']
            ?? $payload['ats_result']
            ?? $payload['ats_percentage']
            ?? null
        );

        if ($atsScore === null && $items !== []) {
            $atsScore = self::estimateScoreFromItems($items);
        }

        return [
            'jd_document_id' => (int) ($payload['jd_document_id'] ?? 0),
            'user_cv_id' => (int) ($payload['user_cv_id'] ?? 0),
            'ats_score' => $atsScore,
            'items' => $items,
        ];
    }

    /**
     * Estimativa ponderada quando o agente não envia ats_score na tool.
     *
     * @param  list<array{keyword: string, relevance: ?string, match_status: ?string, cv_snippet: ?string, suggestion: ?string}>  $items
     */
    public static function estimateScoreFromItems(array $items): ?float
    {
        if ($items === []) {
            return null;
        }

        $totalWeight = 0.0;
        $earned = 0.0;

        foreach ($items as $row) {
            $relevance = strtolower((string) ($row['relevance'] ?? 'medium'));
            $weight = match (true) {
                in_array($relevance, ['high', 'alta', 'elevada'], true) => 3.0,
                in_array($relevance, ['low', 'baixa'], true) => 1.0,
                default => 2.0,
            };

            $match = strtolower((string) ($row['match_status'] ?? 'missing'));
            $points = match (true) {
                in_array($match, ['full', 'presente', 'present', 'completo', 'ok'], true) => 1.0,
                in_array($match, ['partial', 'parcial', 'partly'], true) => 0.5,
                default => 0.0,
            };

            $totalWeight += $weight;
            $earned += $weight * $points;
        }

        if ($totalWeight <= 0) {
            return null;
        }

        return round(($earned / $totalWeight) * 100, 1);
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array{keyword: string, relevance: ?string, match_status: ?string, cv_snippet: ?string, suggestion: ?string}|null
     */
    private static function normalizeItemRow(array $row): ?array
    {
        $keyword = self::firstString($row, ['keyword', 'Keyword', 'term', 'palavra_chave']);
        if ($keyword === null || $keyword === '') {
            return null;
        }

        return [
            'keyword' => $keyword,
            'relevance' => self::normalizeRelevance($row),
            'match_status' => self::normalizeMatchStatus($row),
            'cv_snippet' => self::firstString($row, ['cv_snippet', 'cvSnippet', 'snippet']),
            'suggestion' => self::firstString($row, ['suggestion', 'comments', 'Comments', 'comment', 'nota']),
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  list<string>  $keys
     */
    private static function firstString(array $row, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (! array_key_exists($key, $row)) {
                continue;
            }
            $value = $row[$key];
            if ($value === null) {
                continue;
            }
            $text = trim((string) $value);

            return $text === '' ? null : $text;
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private static function normalizeRelevance(array $row): ?string
    {
        $raw = self::firstString($row, [
            'relevance', 'Relevance', 'relevancia', 'Relevância',
        ]);
        $value = strtolower((string) ($raw ?? ''));

        return match (true) {
            in_array($value, ['high', 'alta', 'elevada'], true) => 'high',
            in_array($value, ['low', 'baixa'], true) => 'low',
            in_array($value, ['medium', 'media', 'média', 'medio', 'médio'], true) => 'medium',
            default => $value !== '' ? $value : null,
        };
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private static function normalizeMatchStatus(array $row): ?string
    {
        $raw = self::firstString($row, [
            'match_status', 'matchStatus', 'include', 'include_missing',
            'Include/Missing', 'Include/missing', 'status', 'estado',
            'presence', 'present',
        ]);
        $value = strtolower((string) ($raw ?? ''));

        return match (true) {
            in_array($value, ['full', 'presente', 'present', 'completo', 'ok', 'included', 'include', 'yes', 'sim'], true) => 'full',
            in_array($value, ['partial', 'parcial', 'partly'], true) => 'partial',
            in_array($value, ['missing', 'ausente', 'absent', 'no', 'nao', 'não'], true) => 'missing',
            default => $value !== '' ? $value : null,
        };
    }

    public static function parseAtsScore(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            $score = (float) $value;

            return $score >= 0 && $score <= 100 ? $score : null;
        }

        if (is_string($value) && preg_match('/(\d+(?:[.,]\d+)?)/', $value, $matches)) {
            $score = (float) str_replace(',', '.', $matches[1]);

            return $score >= 0 && $score <= 100 ? $score : null;
        }

        return null;
    }
}
