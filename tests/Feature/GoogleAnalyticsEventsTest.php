<?php

use App\Models\TokenPackOrder;
use App\Models\User;
use App\Support\GoogleAnalytics;
use Database\Seeders\CareerTrailStepsSeeder;

beforeEach(function () {
    $this->seed(CareerTrailStepsSeeder::class);
});

test('registration flashes sign_up analytics event for login page', function () {
    config(['services.google_analytics.measurement_id' => 'G-TEST123']);

    $this->post(route('register'), [
        'name' => 'Test User',
        'email' => 'analytics-test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'accept_privacy_policy' => '1',
        'accept_terms' => '1',
    ])->assertRedirect(route('login', absolute: false));

    $events = session(GoogleAnalytics::SESSION_KEY);
    expect($events)->toBeArray()
        ->and($events[0]['name'])->toBe('sign_up')
        ->and($events[0]['params']['method'])->toBe('email');
});

test('login flashes login analytics event on successful auth', function () {
    config(['services.google_analytics.measurement_id' => 'G-TEST123']);

    $user = User::factory()->create([
        'email_verified_at' => now(),
        'privacy_accepted_at' => now(),
        'privacy_policy_accepted_version' => config('legal.privacy_policy_version'),
        'terms_accepted_at' => now(),
        'terms_accepted_version' => config('legal.terms_version'),
    ]);

    $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('career-trail.index', absolute: false));

    $events = session(GoogleAnalytics::SESSION_KEY);
    expect($events)->toBeArray()
        ->and($events[0]['name'])->toBe('login');
});

test('career trail map page includes trail_map_view analytics event', function () {
    config(['services.google_analytics.measurement_id' => 'G-TEST123']);

    $user = User::factory()->create([
        'email_verified_at' => now(),
        'privacy_accepted_at' => now(),
        'privacy_policy_accepted_version' => config('legal.privacy_policy_version'),
        'terms_accepted_at' => now(),
        'terms_accepted_version' => config('legal.terms_version'),
    ]);

    $this->actingAs($user)->get(route('career-trail.index'))
        ->assertOk()
        ->assertSee('trail_map_view', false);
});

test('token purchase page includes begin_checkout analytics event', function () {
    config(['services.google_analytics.measurement_id' => 'G-TEST123']);

    $user = User::factory()->create(['email_verified_at' => now()]);

    $this->actingAs($user)->get(route('tokens.purchase'))
        ->assertOk()
        ->assertSee('begin_checkout', false);
});

test('token thank you page includes purchase analytics event when completed', function () {
    config(['services.google_analytics.measurement_id' => 'G-TEST123']);

    $user = User::factory()->create(['email_verified_at' => now()]);
    $order = TokenPackOrder::query()->create([
        'user_id' => $user->id,
        'tokens_amount' => 1000,
        'amount_brl' => 49.90,
        'status' => TokenPackOrder::STATUS_COMPLETED,
        'payment_method' => TokenPackOrder::PAYMENT_PIX,
        'asaas_payment_id' => 'pay_test_ga',
    ]);

    $this->actingAs($user)->get(route('tokens.thank-you', $order))
        ->assertOk()
        ->assertSee('purchase', false)
        ->assertSee((string) $order->id, false);
});

test('google analytics is disabled for admin users', function () {
    config(['services.google_analytics.measurement_id' => 'G-TEST123']);

    $admin = User::factory()->create([
        'email_verified_at' => now(),
        'is_admin' => true,
        'privacy_accepted_at' => now(),
        'privacy_policy_accepted_version' => config('legal.privacy_policy_version'),
        'terms_accepted_at' => now(),
        'terms_accepted_version' => config('legal.terms_version'),
    ]);

    $this->actingAs($admin)->get(route('career-trail.index'))
        ->assertOk()
        ->assertDontSee('gtag/js', false)
        ->assertDontSee('trail_map_view', false);
});
