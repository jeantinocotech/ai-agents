<?php

use App\Services\UserCvDuplicateService;

test('title for job copy uses cv title and job title', function () {
    $service = new UserCvDuplicateService;

    expect($service->titleForJobCopy('CV Comercial', 'Engenheiro Sénior'))
        ->toBe('CV Comercial — Engenheiro Sénior');
});

test('title for job copy falls back when job title is empty', function () {
    $service = new UserCvDuplicateService;

    expect($service->titleForJobCopy('CV Base', ''))
        ->toBe('Cópia de CV Base');
});
