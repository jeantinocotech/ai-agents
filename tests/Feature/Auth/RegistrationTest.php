<?php

use App\Models\User;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Support\Facades\Notification;

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
});

test('new users can register', function () {
    Notification::fake();

    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'accept_privacy_policy' => '1',
        'accept_terms' => '1',
    ]);

    $this->assertGuest();
    $response->assertRedirect(route('login', absolute: false));

    $user = User::query()->where('email', 'test@example.com')->first();
    expect($user)->not->toBeNull();
    Notification::assertSentTo($user, VerifyEmailNotification::class);
});

test('unverified users cannot open the dashboard', function () {
    $user = User::factory()->unverified()->create();

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertRedirect(route('verification.notice', absolute: false));
});
