<?php

use App\Models\User;
use App\Models\UserCv;

test('guest sees public landing with sign up affordance', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Criar conta');
});

test('authenticated user without a saved cv sees home onboarding ctas', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('home'))
        ->assertOk()
        ->assertSee('deixe-me apresentar-me', false)
        ->assertSee('CV completo', false)
        ->assertSee('Ver o mapa da trilha', false);
});

test('authenticated user with a saved cv is redirected from home to the career trail map', function () {
    $user = User::factory()->create();

    UserCv::query()->create([
        'user_id' => $user->id,
        'title' => 'CV',
        'body' => str_repeat('x', 40),
        'is_default' => true,
        'source' => UserCv::SOURCE_MANUAL,
    ]);

    $this->actingAs($user)->get(route('home'))
        ->assertRedirect(route('career-trail.index', absolute: false));
});
