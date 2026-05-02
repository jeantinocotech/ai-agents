<?php

use App\Models\User;

test('guest can view privacy policy and terms pages', function () {
    $this->get(route('privacidade'))
        ->assertOk()
        ->assertSee('Política de Privacidade', false);

    $this->get(route('termos-uso'))
        ->assertOk()
        ->assertSee('Termos de Uso', false);
});

test('legal pages expose return link when opened from registration', function () {
    $this->get(route('privacidade').'?from=register')
        ->assertOk()
        ->assertSee('Continuar o registo', false)
        ->assertSee('voltar ao registo', false);

    $this->get(route('termos-uso').'?from=register')
        ->assertOk()
        ->assertSee('Continuar o registo', false);
});

test('authenticated user pending legal consent can view policy and terms pages', function () {
    $user = User::factory()->create([
        'privacy_accepted_at' => null,
        'privacy_policy_accepted_version' => null,
        'terms_accepted_at' => null,
        'terms_accepted_version' => null,
    ]);

    $this->actingAs($user)->get(route('privacidade'))
        ->assertOk()
        ->assertSee('Política de Privacidade', false);

    $this->actingAs($user)->get(route('termos-uso'))
        ->assertOk()
        ->assertSee('Termos de Uso', false);
});
