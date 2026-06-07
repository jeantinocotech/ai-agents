<?php

use App\Support\ChatKitCvAutoAnalyze;
use Illuminate\Http\Request;

test('chatkit cv auto analyze preselect resolves profile cv from query', function () {
    $library = [
        'cvs' => [
            ['id' => 'p1', 'title' => 'CV A'],
            ['id' => 'p2', 'title' => 'CV B'],
        ],
        'defaults' => ['cv_document_id' => 'p1'],
    ];

    $request = Request::create('/agents/1/chat', 'GET', [
        'user_cv_id' => '2',
        'auto_send' => '1',
    ]);

    expect(ChatKitCvAutoAnalyze::preselectCvIdFromRequest($request, $library))->toBe('p2');
});

test('chatkit cv auto analyze preselect ignores missing cv or auto send flag', function () {
    $library = [
        'cvs' => [['id' => 'p1', 'title' => 'CV A']],
        'defaults' => ['cv_document_id' => 'p1'],
    ];

    $withoutFlag = Request::create('/agents/1/chat', 'GET', ['user_cv_id' => '1']);
    expect(ChatKitCvAutoAnalyze::preselectCvIdFromRequest($withoutFlag, $library))->toBeNull();

    $missingCv = Request::create('/agents/1/chat', 'GET', [
        'user_cv_id' => '99',
        'auto_send' => '1',
    ]);
    expect(ChatKitCvAutoAnalyze::preselectCvIdFromRequest($missingCv, $library))->toBeNull();
});
