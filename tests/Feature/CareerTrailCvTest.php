<?php

use App\Models\Agent;
use App\Models\AgentDocument;
use App\Models\AgentDocumentDefault;
use App\Models\CareerTrailStep;
use App\Models\User;
use App\Models\UserCv;
use Database\Seeders\CareerTrailGracaMessagesSeeder;
use Database\Seeders\CareerTrailStepsSeeder;
use Illuminate\Http\UploadedFile;

beforeEach(function () {
    $this->seed(CareerTrailStepsSeeder::class);
    $this->seed(CareerTrailGracaMessagesSeeder::class);
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

test('guest cannot extract career trail cv file', function () {
    $file = UploadedFile::fake()->createWithContent('a.txt', 'hello');

    $this->post(route('career-trail.cv.extract-file'), ['cv_file' => $file])
        ->assertRedirect(route('login'));
});

test('authenticated user can extract text from uploaded txt via extract endpoint', function () {
    $user = User::factory()->create();
    $content = str_repeat('x', 420);
    $file = UploadedFile::fake()->createWithContent('cv.txt', $content);

    $this->actingAs($user)->post(route('career-trail.cv.extract-file'), ['cv_file' => $file])
        ->assertOk()
        ->assertJson(['body' => $content, 'suggested_title' => 'cv']);
});

test('profile cv store requires title', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('career-trail.cv.store'), [
            'body' => str_repeat('a', 400),
        ])
        ->assertSessionHasErrors('title');

    expect(UserCv::query()->where('user_id', $user->id)->count())->toBe(0);
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

    $response = $this->actingAs($user)
        ->post(route('career-trail.cv.store'), [
            'title' => 'O meu CV',
            'body' => str_repeat('a', 400),
            'linkedin_url' => 'https://www.linkedin.com/in/example',
        ]);
    $cv = UserCv::query()->where('user_id', $user->id)->first();
    expect($cv)->not->toBeNull();
    $response->assertRedirect(route('career-trail.cv', ['edit' => $cv->id]).'#sec-cv-form');

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
        'body' => str_repeat('x', 400),
        'is_default' => true,
        'source' => UserCv::SOURCE_MANUAL,
    ]);

    $resp = $this->actingAs($user)
        ->post(route('career-trail.cv.store'), [
            'title' => 'B',
            'body' => str_repeat('y', 400),
        ]);
    $newB = UserCv::query()->where('user_id', $user->id)->where('title', 'B')->first();
    expect($newB)->not->toBeNull();
    $resp->assertRedirect(route('career-trail.cv', ['edit' => $newB->id]).'#sec-cv-form');

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
        'body' => str_repeat('z', 400),
        'paired_cv_document_id' => null,
    ]);

    $this->actingAs($user)
        ->post(route('career-trail.cv.import-agent'), [
            'agent_document_id' => $doc->id,
        ])
        ->assertRedirect(route('career-trail.cv'));

    $profile = UserCv::defaultForUserId((int) $user->id);
    expect($profile)->not->toBeNull();
    expect($profile->body)->toBe(str_repeat('z', 400));
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

test('user can duplicate profile cv', function () {
    $user = User::factory()->create();
    $cv = UserCv::query()->create([
        'user_id' => $user->id,
        'title' => 'Original',
        'body' => str_repeat('o', 400),
        'is_default' => true,
        'source' => UserCv::SOURCE_MANUAL,
    ]);

    $this->actingAs($user)
        ->post(route('career-trail.cv.duplicate', $cv));

    $copy = UserCv::query()->where('user_id', $user->id)->where('title', 'like', 'Cópia de%')->first();
    expect($copy)->not->toBeNull();
    expect($copy->is_default)->toBeFalse();
    expect((string) $copy->body)->toBe(str_repeat('o', 400));
});

test('career trail cv page shows embedded assistant when user has no cv and agent is configured', function () {
    $user = User::factory()->create();
    $agent = makeCareerTrailChatKitAgent();
    bindCareerCvStepAgent((int) $agent->id);

    $response = $this->actingAs($user)->get(route('career-trail.cv'));

    $response->assertOk();
    $html = str_replace('\\/', '/', $response->getContent());
    expect($html)->toContain('embedded=1');
    expect($html)->not->toContain('no_documents=1');
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
        'body' => str_repeat('x', 400),
        'is_default' => true,
        'source' => UserCv::SOURCE_MANUAL,
    ]);

    $this->actingAs($user)
        ->get(route('career-trail.cv'))
        ->assertOk()
        ->assertSee('btn-open-cv-assistant', false)
        ->assertSee('Ajustar / Criar CV', false);
});

test('default profile cv is upserted to ats agent library when saved as predefinido', function () {
    $ats = makeCareerTrailChatKitAgent(['name' => 'ATS auto-sync']);
    CareerTrailStep::query()->where('slug', 'ats')->update(['agent_id' => $ats->id]);

    $user = User::factory()->create();

    $r = $this->actingAs($user)->post(route('career-trail.cv.store'), [
        'title' => 'CV perfil',
        'body' => str_repeat('t', 400),
    ]);
    $cvSaved = UserCv::query()->where('user_id', $user->id)->where('title', 'CV perfil')->first();
    $r->assertRedirect(route('career-trail.cv', ['edit' => $cvSaved->id]).'#sec-cv-form');

    $defaults = AgentDocumentDefault::query()
        ->where('user_id', $user->id)
        ->where('agent_id', $ats->id)
        ->first();
    expect($defaults)->not->toBeNull();
    expect($defaults->default_cv_document_id)->not->toBeNull();

    $doc = AgentDocument::query()->findOrFail((int) $defaults->default_cv_document_id);
    expect($doc->type)->toBe(AgentDocument::TYPE_CV)
        ->and($doc->body)->toBe(str_repeat('t', 400));

    $r2 = $this->actingAs($user)->post(route('career-trail.cv.store'), [
        'title' => 'Outro',
        'body' => str_repeat('u', 400),
    ]);
    $other = UserCv::query()->where('user_id', $user->id)->where('title', 'Outro')->first();
    $r2->assertRedirect(route('career-trail.cv', ['edit' => $other->id]).'#sec-cv-form');

    $doc->refresh();
    expect($doc->body)->toBe(str_repeat('t', 400));

    $cvB = UserCv::query()->where('user_id', $user->id)->where('title', 'Outro')->firstOrFail();
    $this->actingAs($user)->post(route('career-trail.cv.default', $cvB))->assertRedirect(route('career-trail.cv'));

    $doc->refresh();
    expect($doc->body)->toBe(str_repeat('u', 400));
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

test('embedded career cv assistant without no_documents uses compact cv-only workspace', function () {
    $user = User::factory()->create();
    $agent = makeCareerTrailChatKitAgent();
    bindCareerCvStepAgent((int) $agent->id);

    $this->actingAs($user)
        ->get(route('agents.chat', $agent).'?embedded=1')
        ->assertOk()
        ->assertViewHas('chatkitSimpleChat', false)
        ->assertViewHas('compactTrailChatUi', true)
        ->assertSee('Gerir CVs (Meu CV)', false)
        ->assertSee('id="chatkit-create-cv-from-scratch"', false)
        ->assertSee('Criar novo CV', false)
        ->assertDontSee('Vaga (JD)', false);
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
