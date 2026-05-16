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

        if ($items === [] && is_string($payload['raw_table_text'] ?? null)) {
            $items = self::parseItemsFromTableText((string) $payload['raw_table_text']);
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

        if ($atsScore === null && is_string($payload['raw_table_text'] ?? null)) {
            $atsScore = self::parseAtsScoreFromText((string) $payload['raw_table_text']);
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

    /**
     * Extrai linhas de tabela ATS a partir de texto copiado do ChatKit (markdown, TSV ou linhas com %).
     *
     * @return list<array{keyword: string, relevance: ?string, match_status: ?string, cv_snippet: ?string, suggestion: ?string}>
     */
    public static function parseItemsFromTableText(string $text): array
    {
        $text = trim($text);
        if ($text === '') {
            return [];
        }

        $fromMarkdown = self::parseMarkdownTableRows($text);
        if ($fromMarkdown !== []) {
            return $fromMarkdown;
        }

        $fromTsv = self::parseDelimitedTableRows($text, "\t");
        if ($fromTsv !== []) {
            return $fromTsv;
        }

        return self::parsePlainScoreLines($text);
    }

    public static function parseAtsScoreFromText(string $text): ?float
    {
        if (preg_match('/ATS\s*[:=]?\s*(\d+(?:[.,]\d+)?)\s*%/iu', $text, $matches)) {
            return self::parseAtsScore($matches[1]);
        }

        return null;
    }

    /**
     * @return list<array{keyword: string, relevance: ?string, match_status: ?string, cv_snippet: ?string, suggestion: ?string}>
     */
    private static function parseMarkdownTableRows(string $text): array
    {
        $lines = preg_split('/\r\n|\r|\n/', $text) ?: [];
        $keys = [];
        $items = [];

        foreach ($lines as $line) {
            $line = trim((string) $line);
            if ($line === '' || ! str_contains($line, '|')) {
                continue;
            }

            $cells = self::splitMarkdownCells($line);
            if ($cells === [] || count($cells) < 2) {
                continue;
            }

            if (self::cellsAreSeparator($cells)) {
                continue;
            }

            if ($keys === []) {
                $keys = array_map([self::class, 'mapHeaderKey'], $cells);
                if ($keys[0] !== 'keyword') {
                    $keys[0] = 'keyword';
                }

                continue;
            }

            $item = self::itemFromCells($cells, $keys);
            if ($item !== null) {
                $items[] = $item;
            }
        }

        return $items;
    }

    /**
     * @return list<array{keyword: string, relevance: ?string, match_status: ?string, cv_snippet: ?string, suggestion: ?string}>
     */
    private static function parseDelimitedTableRows(string $text, string $delimiter): array
    {
        $lines = preg_split('/\r\n|\r|\n/', $text) ?: [];
        $keys = [];
        $items = [];

        foreach ($lines as $line) {
            $line = trim((string) $line);
            if ($line === '' || ! str_contains($line, $delimiter)) {
                continue;
            }

            $cells = array_map('trim', explode($delimiter, $line));
            if (count($cells) < 2) {
                continue;
            }

            if ($keys === []) {
                $keys = array_map([self::class, 'mapHeaderKey'], $cells);

                continue;
            }

            $item = self::itemFromCells($cells, $keys);
            if ($item !== null) {
                $items[] = $item;
            }
        }

        return $items;
    }

    /**
     * Linhas copiadas como lista (keyword … 60%).
     *
     * @return list<array{keyword: string, relevance: ?string, match_status: ?string, cv_snippet: ?string, suggestion: ?string}>
     */
    private static function parsePlainScoreLines(string $text): array
    {
        $lines = preg_split('/\r\n|\r|\n/', $text) ?: [];
        $items = [];

        foreach ($lines as $line) {
            $line = trim((string) $line);
            if ($line === '' || mb_strlen($line) < 4) {
                continue;
            }
            if (preg_match('/^ATS\s*[:=]?\s*\d+/iu', $line)) {
                continue;
            }
            if (! preg_match('/(\d+(?:[.,]\d+)?)\s*%/', $line, $scoreMatch)) {
                continue;
            }

            $keyword = trim(preg_replace('/\s*(\d+(?:[.,]\d+)?)\s*%.*$/u', '', $line) ?? $line);
            if ($keyword === '' || mb_strlen($keyword) < 3) {
                continue;
            }

            $items[] = [
                'keyword' => mb_substr($keyword, 0, 255),
                'relevance' => 'medium',
                'match_status' => self::matchFromScore((float) str_replace(',', '.', $scoreMatch[1])),
                'cv_snippet' => null,
                'suggestion' => null,
            ];
        }

        return $items;
    }

    /**
     * @param  list<string>  $cells
     * @param  list<string>  $keys
     * @return array{keyword: string, relevance: ?string, match_status: ?string, cv_snippet: ?string, suggestion: ?string}|null
     */
    private static function itemFromCells(array $cells, array $keys): ?array
    {
        $row = [];
        foreach ($cells as $index => $cell) {
            $key = $keys[$index] ?? ('col'.$index);
            $row[$key] = $cell;
        }

        $keyword = trim((string) ($row['keyword'] ?? $row['col0'] ?? $cells[0] ?? ''));
        if ($keyword === '' || mb_strlen($keyword) < 2) {
            return null;
        }

        $rowForNormalize = [
            'keyword' => $keyword,
            'relevance' => $row['relevance'] ?? $row['prioridade'] ?? ($cells[1] ?? null),
            'match_status' => $row['match_status'] ?? $row['status'] ?? ($cells[2] ?? null),
            'cv_snippet' => $row['cv_snippet'] ?? null,
            'suggestion' => $row['suggestion'] ?? $row['comments'] ?? $row['explicacao'] ?? null,
        ];

        $scoreCell = $row['score'] ?? ($cells[count($cells) - 1] ?? null);
        $item = self::normalizeItemRow($rowForNormalize);
        if ($item === null) {
            return null;
        }

        $score = self::parseAtsScore($scoreCell);
        if ($score !== null && ($item['match_status'] === null || $item['match_status'] === 'missing')) {
            $item['match_status'] = self::matchFromScore($score);
        }

        return $item;
    }

    /**
     * @param  list<string>  $cells
     * @return list<string>
     */
    private static function splitMarkdownCells(string $line): array
    {
        $trimmed = trim($line);
        if (str_starts_with($trimmed, '|')) {
            $trimmed = substr($trimmed, 1);
        }
        if (str_ends_with($trimmed, '|')) {
            $trimmed = substr($trimmed, 0, -1);
        }

        return array_map('trim', explode('|', $trimmed));
    }

    /**
     * @param  list<string>  $cells
     */
    private static function cellsAreSeparator(array $cells): bool
    {
        foreach ($cells as $cell) {
            if (! preg_match('/^[\-\:\s]+$/u', $cell)) {
                return false;
            }
        }

        return true;
    }

    private static function mapHeaderKey(string $label): string
    {
        $s = mb_strtolower(trim($label));
        $s = str_replace(['á', 'à', 'â', 'ã', 'é', 'ê', 'í', 'ó', 'ô', 'õ', 'ú', 'ç'], ['a', 'a', 'a', 'a', 'e', 'e', 'i', 'o', 'o', 'o', 'u', 'c'], $s);

        return match (true) {
            str_contains($s, 'keyword') || str_contains($s, 'palavra') || str_contains($s, 'termo') || str_contains($s, 'skill') || str_contains($s, 'competenc') || str_contains($s, 'requisito') => 'keyword',
            str_contains($s, 'prior') || str_contains($s, 'relev') => 'relevance',
            str_contains($s, 'status') || str_contains($s, 'match') || str_contains($s, 'inclu') || str_contains($s, 'presen') || str_contains($s, 'ausen') => 'match_status',
            str_contains($s, 'comment') || str_contains($s, 'explic') || str_contains($s, 'sugest') || str_contains($s, 'nota') => 'suggestion',
            str_contains($s, 'snippet') || str_contains($s, 'trecho') || str_contains($s, 'cv') => 'cv_snippet',
            str_contains($s, 'score') || str_contains($s, 'pontu') || str_contains($s, 'percent') || str_contains($s, '%') => 'score',
            default => $s,
        };
    }

    private static function matchFromScore(float $score): string
    {
        return match (true) {
            $score >= 75 => 'full',
            $score >= 35 => 'partial',
            default => 'missing',
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
