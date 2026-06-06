<?php

use App\Models\TokenTransaction;
use App\Models\User;

test('token purchase page shows balance and renewal date without promo box for users without purchase', function () {
    $renewalAt = now()->addDays(15);
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'token_balance' => 750,
        'tokens_next_renewal_at' => $renewalAt,
    ]);

    $this->actingAs($user)->get(route('tokens.purchase'))
        ->assertOk()
        ->assertDontSee('Condição Especial de Lançamento', false)
        ->assertSee('Saldo atual', false)
        ->assertSee('750', false)
        ->assertSee('Próxima renovação gratuita', false)
        ->assertSee($renewalAt->timezone(config('app.timezone'))->format('d/m/Y'), false);
});

test('token purchase page hides renewal date after first purchase', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'tokens_next_renewal_at' => now()->addDays(15),
    ]);

    TokenTransaction::query()->create([
        'user_id' => $user->id,
        'delta' => 1000,
        'balance_after' => 1000,
        'type' => TokenTransaction::TYPE_PURCHASE,
        'reference_type' => null,
        'reference_id' => null,
        'meta' => ['test' => true],
    ]);

    $this->actingAs($user)->get(route('tokens.purchase'))
        ->assertOk()
        ->assertDontSee('Condição Especial de Lançamento', false)
        ->assertDontSee('Próxima renovação gratuita', false);
});
