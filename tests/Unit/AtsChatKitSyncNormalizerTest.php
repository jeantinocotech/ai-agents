<?php

use App\Support\AtsChatKitSyncNormalizer;

test('normalizes portuguese item fields and ats percent string', function () {
    $normalized = AtsChatKitSyncNormalizer::normalize([
        'jd_document_id' => 10,
        'user_cv_id' => 5,
        'ats_score' => '72%',
        'items' => [
            [
                'Keyword' => 'Scrum',
                'Relevance' => 'Alta',
                'Include/Missing' => 'Presente',
                'Comments' => 'Já mencionado.',
            ],
            [
                'keyword' => 'Docker',
                'relevance' => 'medium',
                'match_status' => 'missing',
            ],
        ],
    ]);

    expect($normalized['ats_score'])->toBe(72.0);
    expect($normalized['items'])->toHaveCount(2);
    expect($normalized['items'][0]['keyword'])->toBe('Scrum');
    expect($normalized['items'][0]['relevance'])->toBe('high');
    expect($normalized['items'][0]['match_status'])->toBe('full');
    expect($normalized['items'][0]['suggestion'])->toBe('Já mencionado.');
});

test('estimates ats score from items when agent omits score', function () {
    $normalized = AtsChatKitSyncNormalizer::normalize([
        'jd_document_id' => 1,
        'user_cv_id' => 2,
        'items' => [
            ['keyword' => 'A', 'relevance' => 'high', 'match_status' => 'full'],
            ['keyword' => 'B', 'relevance' => 'high', 'match_status' => 'missing'],
            ['keyword' => 'C', 'relevance' => 'low', 'match_status' => 'partial'],
        ],
    ]);

    expect($normalized['ats_score'])->toBe(50.0);
});

test('estimate score from items helper', function () {
    $score = AtsChatKitSyncNormalizer::estimateScoreFromItems([
        ['keyword' => 'X', 'relevance' => 'medium', 'match_status' => 'full'],
        ['keyword' => 'Y', 'relevance' => 'medium', 'match_status' => 'full'],
    ]);

    expect($score)->toBe(100.0);
});
