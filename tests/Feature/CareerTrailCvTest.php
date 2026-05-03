<?php

use App\Models\Agent;
use App\Models\AgentDocument;
use App\Models\CareerTrailStep;
use App\Models\User;
use App\Models\UserCv;
use Database\Seeders\CareerTrailStepsSeeder;

beforeEach(function () {
    $this->seed(CareerTrailStepsSeeder::class);
});

function bindCareerCvStepAgent(int $agentId): void
{
    CareerTrailStep::query()->where('slug', 'cv')->update(['agent_id' => $agentId]);
}

function makeCareerTrailChatKitAgent(array $overrides = []): Agent
{
    return Agent::query()->create(array_merge([
        'name' => 'Assistente CV trilha',
        'price' => 0,
        'model_type' => 'gpt-4o-mini',
        'integration' => Agent::INTEGRATION_CHATKIT_WORKFLOW,
        'chatkit_workflow_id' => 'wf_test',
        'chatkit_workflow_version' => '1',
        'is_active' => true,
    ], $overrides));
}

test('guest cannot access career trail cv page', function () {
    $this->get(route('career-trail.cv'))->assertRedirect(route('login'));
});

test('guest cannot destroy profile cv', function () {
    $this->delete(route('career-trail.cv.destroy', ['userCv' => 1]))->assertRedirect(route('login'));
});

test('user can destroy a profile cv', function () {
    $user = User::factory()->create();
    $cv = UserCv::query()->create([
        'user_id' => $user->id,
        'title' => 'Para apagar',
        'body' => str_repeat('b', 50),
        'is_default' => true,
        'source' => UserCv::SOURCE_MANUAL,
    ]);

    $this->actingAs($user)
        ->delete(route('career-trail.cv.destroy', $cv))
        ->assertRedirect(route('career-trail.cv'))
        ->assertSessionHas('status');

    expect(UserCv::defaultForUserId((int) $user->id))->toBeNull();
    expect(UserCv::query()->where('user_id', $user->id)->count())->toBe(0);
});

test('destroy unknown profile cv returns not found', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->delete(route('career-trail.cv.destroy', ['userCv' => 999999]))
        ->assertNotFound();
});

test('user can save profile cv from text', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('career-trail.cv.store'), [
            'has_existing_cv' => '0',
            'title' => 'O meu CV',
            'body' => str_repeat('a', 50),
            'linkedin_url' => 'https://www.linkedin.com/in/example',
        ])
        ->assertRedirect(route('career-trail.cv'));

    $cv = UserCv::defaultForUserId((int) $user->id);
    expect($cv)->not->toBeNull();
    expect($cv->title)->toBe('O meu CV');
    expect($cv->is_default)->toBeTrue();

    $user->refresh();
    expect($user->linkedin_url)->toBe('https://www.linkedin.com/in/example');
});

test('second profile cv stays non default unless requested', function () {
    $user = User::factory()->create();
    $first = UserCv::query()->create([
        'user_id' => $user->id,
        'title' => 'A',
        'body' => str_repeat('x', 50),
        'is_default' => true,
        'source' => UserCv::SOURCE_MANUAL,
    ]);

    $this->actingAs($user)
        ->post(route('career-trail.cv.store'), [
            'has_existing_cv' => '0',
            'title' => 'B',
            'body' => str_repeat('y', 50),
        ])
        ->assertRedirect(route('career-trail.cv'));

    $first->refresh();
    expect($first->is_default)->toBeTrue();
    expect(UserCv::query()->where('user_id', $user->id)->count())->toBe(2);
    expect(UserCv::query()->where('user_id', $user->id)->where('is_default', true)->count())->toBe(1);
});

test('deleting default cv promotes another', function () {
    $user = User::factory()->create();
    $a = UserCv::query()->create([
        'user_id' => $user->id,
        'title' => 'Old default',
        'body' => str_repeat('a', 50),
        'is_default' => true,
        'source' => UserCv::SOURCE_MANUAL,
    ]);
    $b = UserCv::query()->create([
        'user_id' => $user->id,
        'title' => 'Second',
        'body' => str_repeat('b', 50),
        'is_default' => false,
        'source' => UserCv::SOURCE_MANUAL,
    ]);
    $b->touch();

    $this->actingAs($user)
        ->delete(route('career-trail.cv.destroy', $a))
        ->assertRedirect(route('career-trail.cv'));

    $b->refresh();
    expect($b->is_default)->toBeTrue();
    expect(UserCv::defaultForUserId((int) $user->id)?->id)->toBe($b->id);
});

test('user can import cv from agent library to profile', function () {
    $user = User::factory()->create();
    $agent = Agent::query()->create([
        'name' => 'ATS library',
        'price' => 0,
        'model_type' => 'gpt-4o-mini',
        'integration' => Agent::INTEGRATION_CHATKIT_WORKFLOW,
        'chatkit_workflow_id' => 'wf_ats',
        'chatkit_workflow_version' => '1',
        'is_active' => true,
    ]);

    $doc = AgentDocument::query()->create([
        'user_id' => $user->id,
        'agent_id' => $agent->id,
        'type' => AgentDocument::TYPE_CV,
        'title' => 'CV ATS',
        'body' => str_repeat('z', 60),
        'paired_cv_document_id' => null,
    ]);

    $this->actingAs($user)
        ->post(route('career-trail.cv.import-agent'), [
            'agent_document_id' => $doc->id,
        ])
        ->assertRedirect(route('career-trail.cv'));

    $profile = UserCv::defaultForUserId((int) $user->id);
    expect($profile)->not->toBeNull();
    expect($profile->body)->toBe(str_repeat('z', 60));
    expect($profile->source)->toBe(UserCv::SOURCE_AGENT_IMPORT);
});

test('profile cv content endpoint returns json for owner', function () {
    $user = User::factory()->create();
    $agent = Agent::query()->create([
        'name' => 'Agente teste CV',
        'price' => 0,
        'model_type' => 'gpt-4o-mini',
    ]);
    $cv = UserCv::query()->create([
        'user_id' => $user->id,
        'title' => 'T',
        'body' => 'Corpo do CV para teste.',
        'is_default' => true,
        'source' => UserCv::SOURCE_MANUAL,
    ]);

    $this->actingAs($user)
        ->getJson(route('agents.documents.profile-cv-content', [$agent, $cv]))
        ->assertOk()
        ->assertJsonPath('type', 'cv')
        ->assertJsonPath('body', 'Corpo do CV para teste.');
});

test('career trail cv page shows embedded assistant when user has no cv and agent is configured', function () {
    $user = User::factory()->create();
    $agent = makeCareerTrailChatKitAgent();
    bindCareerCvStepAgent((int) $agent->id);

    $response = $this->actingAs($user)->get(route('career-trail.cv'));

    $response->assertOk();
    $html = str_replace('\\/', '/', $response->getContent());
    expect($html)->toContain('embedded=1')->toContain('no_documents=1');
    expect($html)->toContain('/agents/'.$agent->id.'/chat');
    $response->assertSee('btn-open-cv-assistant', false);
});

test('career trail cv page still shows assistant when user already has default cv', function () {
    $user = User::factory()->create();
    $agent = makeCareerTrailChatKitAgent();
    bindCareerCvStepAgent((int) $agent->id);
    UserCv::query()->create([
        'user_id' => $user->id,
        'title' => 'Existente',
        'body' => str_repeat('x', 40),
        'is_default' => true,
        'source' => UserCv::SOURCE_MANUAL,
    ]);

    $this->actingAs($user)
        ->get(route('career-trail.cv'))
        ->assertOk()
        ->assertSee('btn-open-cv-assistant', false)
        ->assertSee('Abrir assistente de CV', false);
});

test('career trail cv page does not expose assistant when configured agent is inactive', function () {
    $user = User::factory()->create();
    $agent = makeCareerTrailChatKitAgent(['is_active' => false]);
    bindCareerCvStepAgent((int) $agent->id);

    $this->actingAs($user)
        ->get(route('career-trail.cv'))
        ->assertOk()
        ->assertDontSee('btn-open-cv-assistant', false);
});

test('agents chat embedded view is used for chatkit agent with embedded query', function () {
    $user = User::factory()->create();
    $agent = makeCareerTrailChatKitAgent();

    $this->actingAs($user)
        ->get(route('agents.chat', $agent).'?embedded=1')
        ->assertOk()
        ->assertViewIs('agents.chat-embedded');
});

test('embedded career cv assistant hides document library when no_documents is set', function () {
    $user = User::factory()->create();
    $agent = makeCareerTrailChatKitAgent();
    bindCareerCvStepAgent((int) $agent->id);

    $this->actingAs($user)
        ->get(route('agents.chat', $agent).'?embedded=1&no_documents=1')
        ->assertOk()
        ->assertViewHas('chatkitSimpleChat', true)
        ->assertDontSee('Documentos para o assistente', false)
        ->assertSee('Converse diretamente com o assistente', false);
});

test('career cv assistant full page uses app chat layout like ats including trail chrome', function () {
    $user = User::factory()->create();
    $agent = makeCareerTrailChatKitAgent();
    bindCareerCvStepAgent((int) $agent->id);

    $this->actingAs($user)
        ->get(route('agents.chat', $agent).'?no_documents=1')
        ->assertOk()
        ->assertViewIs('agents.chat')
        ->assertViewHas('chatkitSimpleChat', true)
        ->assertSee('Comprar tokens', false)
        ->assertSee('Voltar à trilha', false)
        ->assertSee('Nesta visita', false)
        ->assertSee('Converse diretamente com o assistente', false)
        ->assertDontSee('Documentos para o assistente', false);
});

test('embedded no_documents is ignored for agents that are not the career cv assistant', function () {
    $user = User::factory()->create();
    $careerAgent = makeCareerTrailChatKitAgent(['name' => 'Career']);
    $otherAgent = makeCareerTrailChatKitAgent(['name' => 'Outro']);
    bindCareerCvStepAgent((int) $careerAgent->id);

    $this->actingAs($user)
        ->get(route('agents.chat', $otherAgent).'?embedded=1&no_documents=1')
        ->assertOk()
        ->assertViewHas('chatkitSimpleChat', false)
        ->assertSee('Documentos para o assistente', false);
});
