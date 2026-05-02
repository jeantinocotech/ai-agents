<?php

use App\Models\User;
use App\Support\EmailVerificationCode;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;

test('email verification screen can be rendered', function () {
    $user = User::factory()->unverified()->create();

    $response = $this->actingAs($user)->get('/verify-email');

    $response->assertStatus(200);
});

test('guest can open verify email code form', function () {
    $this->get(route('verification.code.form'))->assertOk();
});

test('email can be verified', function () {
    $user = User::factory()->unverified()->create();

    Event::fake();

    $signedPath = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)],
        absolute: false,
    );

    $response = $this->get(URL::to($signedPath));

    Event::assertDispatched(Verified::class);
    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
    $response->assertRedirect(route('login', absolute: false));
    $response->assertSessionHas('status');
});

test('email is not verified with invalid hash', function () {
    $user = User::factory()->unverified()->create();

    $signedPath = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1('wrong-email')],
        absolute: false,
    );

    $response = $this->get(URL::to($signedPath));

    $response->assertForbidden();
    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

test('email can be verified with six digit code', function () {
    $user = User::factory()->unverified()->create();
    EmailVerificationCode::remember((int) $user->getKey(), '123456');

    $response = $this->post(route('verification.code.store'), [
        'email' => $user->email,
        'code' => '123456',
    ]);

    $response->assertRedirect(route('login', absolute: false));
    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});

test('unverified users cannot authenticate', function () {
    $user = User::factory()->unverified()->create();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});

test('guest verification resend returns generic flash', function () {
    $response = $this->from(route('login'))
        ->post(route('verification.resend.public'), ['email' => 'nobody@example.com']);

    $response->assertRedirect(route('login', absolute: false));
    $response->assertSessionHas('status');
});
