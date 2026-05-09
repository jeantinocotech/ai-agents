<?php

use App\Models\Agent;
use App\Models\ChatSession;
use App\Models\GamificationScoreEvent;
use App\Models\TokenPackOrder;
use App\Models\TokenTransaction;
use App\Models\User;

test('non-admin cannot access admin dashboard', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)
        ->get(route('admin.dashboard'))
        ->assertForbidden();
});

test('admin dashboard loads overview with period default', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $response = $this->actingAs($admin)->get(route('admin.dashboard'));

    $response->assertOk();
    $response->assertViewHas('kpis');
    $response->assertViewHas('period');
    expect($response->viewData('period')['key'])->toBe('7d');
    $response->assertSee('Visão geral', false);
});

test('admin dashboard accepts period query strings', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    foreach (['30d', '12m'] as $p) {

        $this->actingAs($admin)
            ->get(route('admin.dashboard', ['period' => $p]))
            ->assertOk()
            ->assertViewHas('period', fn ($period) => $period['key'] === $p);

    }

});

test('admin dashboard kpis reflect data in selected period', function () {
    /** @phpstan-ignore-next-line */
    \Carbon\Carbon::setTestNow('2026-06-15 14:00:00');

    $agent = Agent::query()->create([
        'name' => 'Tester agent',
        'price' => 0,
        'model_type' => 'gpt-4o-mini',
        'integration' => Agent::INTEGRATION_OPENAI,
        'is_active' => true,
    ]);

    $u1 = User::factory()->create(['created_at' => now()->subDay()]);
    $u2 = User::factory()->create(['created_at' => now()->subDay()]);

    ChatSession::query()->create([
        'user_id' => $u1->id,
        'agent_id' => $agent->id,
        'should_persist' => false,
        'is_active' => true,
    ]);

    TokenTransaction::query()->create([
        'user_id' => $u1->id,
        'delta' => -50,
        'balance_after' => 950,
        'type' => TokenTransaction::TYPE_USAGE,
        'reference_type' => null,
        'reference_id' => null,
        'meta' => null,
        'created_at' => now()->subHours(2),
        'updated_at' => now()->subHours(2),
    ]);

    TokenPackOrder::query()->create([
        'user_id' => $u1->id,
        'tokens_amount' => 100,
        'amount_brl' => 19.90,
        'asaas_payment_id' => null,
        'status' => TokenPackOrder::STATUS_COMPLETED,
        'updated_at' => now()->subHour(),
        'created_at' => now()->subDay(),
    ]);

    GamificationScoreEvent::query()->create([
        'user_id' => $u2->id,
        'event_key' => 'cv_created',
        'reference_type' => null,
        'reference_id' => null,
        'meta' => null,
        'occurred_at' => now()->subMinutes(30),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $admin = User::factory()->create(['is_admin' => true]);
    $response = $this->actingAs($admin)->get(route('admin.dashboard'));

    /** @phpstan-ignore-next-line */
    \Carbon\Carbon::setTestNow();

    $response->assertOk();
    /** @var array $kpis */
    $kpis = $response->viewData('kpis');
    expect($kpis['new_users'])->toBeGreaterThanOrEqual(2);
    expect($kpis['chat_sessions'])->toBeGreaterThanOrEqual(1);
    expect($kpis['tokens_consumed_total'])->toBeGreaterThanOrEqual(50);
    expect($kpis['purchases_count'])->toBeGreaterThanOrEqual(1);
    expect($kpis['gamification_event_count'])->toBeGreaterThanOrEqual(1);
});
