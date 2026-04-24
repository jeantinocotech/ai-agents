<?php

use App\Models\User;
use App\Models\UserCareerTrailProgress;
use App\Models\UserCv;
use Database\Seeders\CareerTrailStepsSeeder;

beforeEach(function () {
    $this->seed(CareerTrailStepsSeeder::class);
});

test('guest is redirected from career trail', function () {
    $this->get(route('career-trail.index'))->assertRedirect(route('login'));
});

test('authenticated user sees career trail and progress is created', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('career-trail.index'));

    $response->assertOk();
    $response->assertSee('Sra. Graça', false);
    $response->assertSee('Criar um CV', false);
    $response->assertSee('graca-avatar.png', false);
    $response->assertSee('Recursos sempre disponíveis', false);

    expect(UserCareerTrailProgress::query()->where('user_id', $user->id)->exists())->toBeTrue();
});

test('user can advance and go back along the trail', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('career-trail.index'));

    $progress = UserCareerTrailProgress::query()->where('user_id', $user->id)->firstOrFail();
    expect($progress->currentStep->slug)->toBe('cv');

    $this->actingAs($user)
        ->post(route('career-trail.advance'))
        ->assertRedirect(route('career-trail.index'));

    $progress->refresh();
    expect($progress->currentStep->slug)->toBe('ats');

    $this->actingAs($user)
        ->post(route('career-trail.back'))
        ->assertRedirect(route('career-trail.index'));

    $progress->refresh();
    expect($progress->currentStep->slug)->toBe('cv');
});

test('career trail banner is shown on dashboard when steps exist', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Trilha de carreira', false)
        ->assertSee('Etapa 1', false);

    expect(UserCareerTrailProgress::query()->where('user_id', $user->id)->exists())->toBeTrue();
});

test('career trail banner suggests advance when cv step is satisfied', function () {
    $user = User::factory()->create();
    UserCv::query()->create([
        'user_id' => $user->id,
        'title' => 'CV',
        'body' => str_repeat('x', 50),
        'is_default' => true,
        'source' => UserCv::SOURCE_MANUAL,
    ]);

    $this->actingAs($user)->get(route('career-trail.index'));

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Avançar para', false)
        ->assertSee('ATS', false);
});

test('career trail banner is hidden on admin routes', function () {
    $user = User::factory()->create(['is_admin' => true]);

    $this->actingAs($user)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertDontSee('Trilha de carreira', false);
});
