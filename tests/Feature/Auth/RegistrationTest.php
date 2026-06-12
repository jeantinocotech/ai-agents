<?php

use App\Models\User;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Support\Facades\Notification;

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200)
        ->assertDontSee('Abra ambos os documentos', false)
        ->assertDontSee('disabled aria-describedby="register-legal-note"', false);
});

test('registration legal checkboxes are not gated behind document read', function () {
    $this->get('/register')
        ->assertOk()
        ->assertSee('accept_privacy_policy', false)
        ->assertSee('accept_terms', false)
        ->assertSee('Ao marcar as caixas abaixo', false);
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
