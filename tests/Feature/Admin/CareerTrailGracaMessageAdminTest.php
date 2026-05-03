<?php

use App\Models\CareerTrailGracaMessage;
use App\Models\CareerTrailStep;
use App\Models\User;
use App\Support\CareerTrailGracaSlots;
use Database\Seeders\CareerTrailGracaMessagesSeeder;
use Database\Seeders\CareerTrailStepsSeeder;

beforeEach(function () {
    $this->seed(CareerTrailStepsSeeder::class);
    $this->seed(CareerTrailGracaMessagesSeeder::class);
});

test('guest cannot access career trail graca messages admin', function () {
    $this->get(route('admin.career-trail-graca-messages.index'))->assertRedirect(route('login'));
});

test('non admin cannot access career trail graca messages admin', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)
        ->get(route('admin.career-trail-graca-messages.index'))
        ->assertForbidden();
});

test('admin can view graca messages index', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->get(route('admin.career-trail-graca-messages.index'))
        ->assertOk()
        ->assertSee('Mensagens da Graça', false);
});

test('admin can filter graca messages by career trail step id', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $cvStep = CareerTrailStep::query()->where('slug', 'cv')->firstOrFail();

    CareerTrailGracaMessage::query()->create([
        'process_key' => 'career_trail',
        'career_trail_step_id' => $cvStep->id,
        'slot' => CareerTrailGracaSlots::TRAIL_STEP_HEADER,
        'sort_order' => 5,
        'body' => 'TEXTO_FILTRO_CV_APENAS',
        'is_active' => true,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.career-trail-graca-messages.index', ['career_trail_step_id' => $cvStep->id]))
        ->assertOk()
        ->assertSee('TEXTO_FILTRO_CV_APENAS', false)
        ->assertSee('A filtrar mensagens do passo', false);

    $atsStep = CareerTrailStep::query()->where('slug', 'ats')->firstOrFail();

    $this->actingAs($admin)
        ->get(route('admin.career-trail-graca-messages.index', ['career_trail_step_id' => $atsStep->id]))
        ->assertOk()
        ->assertDontSee('TEXTO_FILTRO_CV_APENAS', false);
});

test('admin can create a graca message', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $cvStep = CareerTrailStep::query()->where('slug', 'cv')->firstOrFail();

    $this->actingAs($admin)
        ->post(route('admin.career-trail-graca-messages.store'), [
            'process_key' => 'career_trail',
            'career_trail_step_id' => (string) $cvStep->id,
            'slot' => CareerTrailGracaSlots::CV_PAGE_INTRO,
            'body' => 'Corpo criado pelo teste.',
            'sort_order' => 7,
            'is_active' => true,
        ])
        ->assertRedirect(route('admin.career-trail-graca-messages.index'))
        ->assertSessionHas('success');

    expect(
        CareerTrailGracaMessage::query()
            ->where('career_trail_step_id', $cvStep->id)
            ->where('slot', CareerTrailGracaSlots::CV_PAGE_INTRO)
            ->where('sort_order', 7)
            ->where('body', 'Corpo criado pelo teste.')
            ->exists()
    )->toBeTrue();
});

test('admin can update and delete a graca message', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $cvStep = CareerTrailStep::query()->where('slug', 'cv')->firstOrFail();

    $message = CareerTrailGracaMessage::query()->create([
        'process_key' => 'career_trail',
        'career_trail_step_id' => $cvStep->id,
        'slot' => CareerTrailGracaSlots::TRAIL_STEP_HEADER,
        'sort_order' => 9,
        'body' => 'Antes da edição.',
        'is_active' => true,
    ]);

    $this->actingAs($admin)
        ->put(route('admin.career-trail-graca-messages.update', $message), [
            'process_key' => 'career_trail',
            'career_trail_step_id' => (string) $cvStep->id,
            'slot' => CareerTrailGracaSlots::TRAIL_STEP_HEADER,
            'body' => 'Depois da edição.',
            'sort_order' => 9,
            'is_active' => false,
        ])
        ->assertRedirect(route('admin.career-trail-graca-messages.index'))
        ->assertSessionHas('success');

    $message->refresh();
    expect($message->body)->toBe('Depois da edição.');
    expect($message->is_active)->toBeFalse();

    $this->actingAs($admin)
        ->delete(route('admin.career-trail-graca-messages.destroy', $message))
        ->assertRedirect(route('admin.career-trail-graca-messages.index'))
        ->assertSessionHas('success');

    expect(CareerTrailGracaMessage::query()->whereKey($message->id)->exists())->toBeFalse();
});
