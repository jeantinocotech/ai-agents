<?php

use App\Models\CareerTrailStep;
use App\Models\User;
use Database\Seeders\CareerTrailStepsSeeder;

beforeEach(function () {
    $this->seed(CareerTrailStepsSeeder::class);
});

test('guest cannot access career trail steps admin', function () {
    $this->get(route('admin.career-trail-steps.index'))->assertRedirect(route('login'));
});

test('non admin cannot access career trail steps admin', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)
        ->get(route('admin.career-trail-steps.index'))
        ->assertForbidden();
});

test('admin can view career trail steps index', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->get(route('admin.career-trail-steps.index'))
        ->assertOk()
        ->assertSee('Trilha — textos da Graça', false)
        ->assertSee('cv', false);
});

test('admin can update step texts', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $step = CareerTrailStep::query()->where('slug', 'cv')->firstOrFail();

    $this->actingAs($admin)
        ->put(route('admin.career-trail-steps.update', $step), [
            'title' => 'Novo título CV',
            'short_description' => 'Descrição curta actualizada.',
            'graca_guidance' => 'Nova orientação da Graça para o passo CV.',
            'is_active' => '1',
        ])
        ->assertRedirect(route('admin.career-trail-steps.index'))
        ->assertSessionHas('success');

    $step->refresh();
    expect($step->title)->toBe('Novo título CV');
    expect($step->short_description)->toBe('Descrição curta actualizada.');
    expect($step->graca_guidance)->toBe('Nova orientação da Graça para o passo CV.');
    expect($step->is_active)->toBeTrue();
});
