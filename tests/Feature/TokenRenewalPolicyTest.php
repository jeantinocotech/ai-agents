<?php

use App\Console\Commands\GrantTokenRenewals;
use App\Models\Setting;
use App\Models\TokenTransaction;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;

test('free renewal resets balance to welcome amount when balance is below target', function () {
    Setting::set('tokens_welcome_amount', '500');
    Setting::set('tokens_renewal_interval_days', '30');

    $user = User::factory()->create([
        'token_balance' => 400,
        'tokens_next_renewal_at' => now()->subMinute(),
    ]);

    Artisan::call('tokens:grant-renewal');

    $user->refresh();
    expect((int) $user->token_balance)->toBe(500);

    $txn = TokenTransaction::query()
        ->where('user_id', $user->id)
        ->where('type', TokenTransaction::TYPE_RENEWAL)
        ->latest('id')
        ->first();
    expect($txn)->not->toBeNull();
    expect((int) $txn->delta)->toBe(100)
        ->and((int) $txn->balance_after)->toBe(500);
});

test('free renewal resets balance to welcome amount even when balance is above target', function () {
    Setting::set('tokens_welcome_amount', '500');
    Setting::set('tokens_renewal_interval_days', '30');

    $user = User::factory()->create([
        'token_balance' => 900,
        'tokens_next_renewal_at' => now()->subMinute(),
    ]);

    Artisan::call('tokens:grant-renewal');

    $user->refresh();
    expect((int) $user->token_balance)->toBe(500);

    $txn = TokenTransaction::query()
        ->where('user_id', $user->id)
        ->where('type', TokenTransaction::TYPE_RENEWAL)
        ->latest('id')
        ->first();
    expect($txn)->not->toBeNull();
    expect((int) $txn->delta)->toBe(-400)
        ->and((int) $txn->balance_after)->toBe(500);
});

test('users with purchases never receive free renewal resets', function () {
    Setting::set('tokens_welcome_amount', '500');
    Setting::set('tokens_renewal_interval_days', '30');

    $user = User::factory()->create([
        'token_balance' => 123,
        'tokens_next_renewal_at' => now()->subMinute(),
    ]);

    TokenTransaction::query()->create([
        'user_id' => $user->id,
        'delta' => 1000,
        'balance_after' => 1123,
        'type' => TokenTransaction::TYPE_PURCHASE,
        'reference_type' => null,
        'reference_id' => null,
        'meta' => ['test' => true],
    ]);

    Artisan::call('tokens:grant-renewal');

    $user->refresh();
    expect((int) $user->token_balance)->toBe(123);

    $renewalCount = TokenTransaction::query()
        ->where('user_id', $user->id)
        ->where('type', TokenTransaction::TYPE_RENEWAL)
        ->count();
    expect($renewalCount)->toBe(0);
});

