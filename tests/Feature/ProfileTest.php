<?php

use App\Models\User;

test('profile page is displayed', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get('/profile');

    $response->assertOk();
});

test('profile photo can be uploaded as jpg', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->patch('/profile', [
        'name' => $user->name,
        'email' => $user->email,
        'state' => '',
        'profile_photo' => \Illuminate\Http\UploadedFile::fake()->image('avatar.jpg', 200, 200),
    ]);

    $response->assertSessionHasNoErrors()->assertRedirect('/profile');

    $user->refresh();
    expect($user->profile_photo)->not->toBeNull();
    \Illuminate\Support\Facades\Storage::disk('public')->assertExists($user->profile_photo);
});

test('profile photo can be uploaded as png', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->patch('/profile', [
        'name' => $user->name,
        'email' => $user->email,
        'state' => '',
        'profile_photo' => \Illuminate\Http\UploadedFile::fake()->image('avatar.png', 200, 200),
    ]);

    $response->assertSessionHasNoErrors()->assertRedirect('/profile');

    $user->refresh();
    expect($user->profile_photo)->not->toBeNull();
    \Illuminate\Support\Facades\Storage::disk('public')->assertExists($user->profile_photo);
});

test('profile information can be updated', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch('/profile', [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $user->refresh();

    $this->assertSame('Test User', $user->name);
    $this->assertSame('test@example.com', $user->email);
    $this->assertNull($user->email_verified_at);
});

test('email verification status is unchanged when the email address is unchanged', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch('/profile', [
            'name' => 'Test User',
            'email' => $user->email,
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $this->assertNotNull($user->refresh()->email_verified_at);
});

test('user can delete their account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->delete('/profile', [
            'password' => 'password',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/');

    $this->assertGuest();
    $this->assertNull($user->fresh());
});

test('correct password must be provided to delete account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from('/profile')
        ->delete('/profile', [
            'password' => 'wrong-password',
        ]);

    $response
        ->assertSessionHasErrorsIn('userDeletion', 'password')
        ->assertRedirect('/profile');

    $this->assertNotNull($user->fresh());
});
