<?php

use App\Models\Agent;
use App\Models\AgentDocument;
use App\Models\CareerTrailStep;
use App\Models\MotivationLetter;
use App\Models\User;
use App\Models\UserCareerTrailProgress;
use App\Models\UserCv;
use App\Support\CareerTrailStepCompletion;
use Database\Seeders\CareerTrailGracaMessagesSeeder;
use Database\Seeders\CareerTrailStepsSeeder;

beforeEach(function () {
    $this->seed(CareerTrailStepsSeeder::class);
    $this->seed(CareerTrailGracaMessagesSeeder::class);
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
    $response->assertSee('Etapas e assistentes', false);

    expect(UserCareerTrailProgress::query()->where('user_id', $user->id)->exists())->toBeTrue();
});

test('user can advance and go back along the trail', function () {
    $user = User::factory()->create();

    UserCv::query()->create([
        'user_id' => $user->id,
        'title' => 'CV',
        'body' => str_repeat('x', 400),
        'is_default' => true,
        'source' => UserCv::SOURCE_MANUAL,
    ]);

    $this->actingAs($user)->get(route('career-trail.index'));

    $progress = UserCareerTrailProgress::query()->where('user_id', $user->id)->firstOrFail();
    expect($progress->currentStep->slug)->toBe('ats');
    expect((int) $progress->max_sort_order_reached)->toBe(2);

    $this->actingAs($user)
        ->post(route('career-trail.back'))
        ->assertRedirect(route('career-trail.index'));

    $progress->refresh();
    expect($progress->currentStep->slug)->toBe('cv');
    expect((int) $progress->max_sort_order_reached)->toBe(2);
});

test('career trail banner is shown when steps exist', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('career-trail.cv'))
        ->assertOk()
        ->assertSee('Passos da trilha', false)
        ->assertSee('CV Formulário', false);

    expect(UserCareerTrailProgress::query()->where('user_id', $user->id)->exists())->toBeTrue();
});

test('career trail banner suggests advance when cv step is satisfied', function () {
    $user = User::factory()->create();
    UserCv::query()->create([
        'user_id' => $user->id,
        'title' => 'CV',
        'body' => str_repeat('x', 400),
        'is_default' => true,
        'source' => UserCv::SOURCE_MANUAL,
    ]);

    $this->actingAs($user)->get(route('career-trail.index'));

    $this->actingAs($user)
        ->get(route('career-trail.cv'))
        ->assertOk()
        ->assertSee('ATS', false);
});

test('cannot advance from cv step without saved profile cv', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('career-trail.index'));

    $this->actingAs($user)
        ->post(route('career-trail.advance'))
        ->assertRedirect(route('career-trail.index'))
        ->assertSessionHas('error');
});

test('cannot advance from cv step when profile cv text is too short', function () {
    $user = User::factory()->create();
    UserCv::query()->create([
        'user_id' => $user->id,
        'title' => 'CV',
        'body' => str_repeat('x', 10),
        'is_default' => true,
        'source' => UserCv::SOURCE_MANUAL,
    ]);

    $this->actingAs($user)->get(route('career-trail.index'));

    $this->actingAs($user)
        ->post(route('career-trail.advance'))
        ->assertRedirect(route('career-trail.index'))
        ->assertSessionHas('error');
});

test('cannot advance from ats step without paired cv and jd in ats library', function () {
    $agent = Agent::query()->create([
        'name' => 'ATS biblioteca',
        'price' => 0,
        'model_type' => 'gpt-4o-mini',
        'is_active' => true,
    ]);

    CareerTrailStep::query()->where('slug', 'ats')->update(['agent_id' => $agent->id]);

    $user = User::factory()->create();

    UserCv::query()->create([
        'user_id' => $user->id,
        'title' => 'CV',
        'body' => str_repeat('y', 400),
        'is_default' => true,
        'source' => UserCv::SOURCE_MANUAL,
    ]);

    $this->actingAs($user)->get(route('career-trail.index'));

    expect(UserCareerTrailProgress::query()->where('user_id', $user->id)->firstOrFail()->currentStep->slug)->toBe('ats');

    $this->actingAs($user)
        ->post(route('career-trail.advance'))
        ->assertRedirect(route('career-trail.index'))
        ->assertSessionHas('error');
});

test('user on ats step can open chat when ats agent is chatkit with workflow id', function () {
    $agent = Agent::query()->create([
        'name' => 'Agente ATS teste',
        'price' => 0,
        'model_type' => 'gpt-4o-mini',
        'integration' => Agent::INTEGRATION_CHATKIT_WORKFLOW,
        'chatkit_workflow_id' => 'wf_trail_future_unlock',
        'chatkit_workflow_version' => '1',
        'is_active' => true,
    ]);

    CareerTrailStep::query()->where('slug', 'ats')->update(['agent_id' => $agent->id]);

    $user = User::factory()->create();

    UserCv::query()->create([
        'user_id' => $user->id,
        'title' => 'CV',
        'body' => str_repeat('z', 400),
        'is_default' => true,
        'source' => UserCv::SOURCE_MANUAL,
    ]);

    $this->actingAs($user)->get(route('career-trail.index'));

    $this->actingAs($user)
        ->get(route('agents.chat', $agent))
        ->assertOk();
});

test('ats agent with openai assistant id opens chat without agent steps', function () {
    $agent = Agent::query()->create([
        'name' => 'ATS Assistants API',
        'price' => 0,
        'model_type' => 'gpt-4o-mini',
        'assistant_id' => 'asst_trail_no_steps',
        'is_active' => true,
    ]);
    CareerTrailStep::query()->where('slug', 'ats')->update(['agent_id' => $agent->id]);

    $user = User::factory()->create();
    UserCv::query()->create([
        'user_id' => $user->id,
        'title' => 'CV perfil',
        'body' => str_repeat('a', 400),
        'is_default' => true,
        'source' => UserCv::SOURCE_MANUAL,
    ]);

    $this->actingAs($user)->get(route('career-trail.index'));

    $this->actingAs($user)
        ->get(route('agents.chat', $agent))
        ->assertOk();
});

test('ats chat redirects to ats hub when agent is openai without steps', function () {
    $agent = Agent::query()->create([
        'name' => 'ATS openai sem passos',
        'price' => 0,
        'model_type' => 'gpt-4o-mini',
        'is_active' => true,
    ]);
    CareerTrailStep::query()->where('slug', 'ats')->update(['agent_id' => $agent->id]);

    $user = User::factory()->create();
    $cv = UserCv::query()->create([
        'user_id' => $user->id,
        'title' => 'CV perfil',
        'body' => str_repeat('a', 400),
        'is_default' => true,
        'source' => UserCv::SOURCE_MANUAL,
    ]);

    $this->actingAs($user)->get(route('career-trail.index'));

    AgentDocument::query()->create([
        'user_id' => $user->id,
        'agent_id' => $agent->id,
        'type' => AgentDocument::TYPE_JD,
        'title' => 'Vaga Y',
        'body' => str_repeat('b', 200),
        'user_cv_id' => $cv->id,
    ]);

    $this->actingAs($user)
        ->get(route('agents.chat', $agent))
        ->assertRedirect(route('career-trail.ats'))
        ->assertSessionHas('error');
});

test('ats hub shows misconfiguration alert when jd cv pair exists but agent has no usable chat flow', function () {
    $agent = Agent::query()->create([
        'name' => 'ATS sem backend',
        'price' => 0,
        'model_type' => 'gpt-4o-mini',
        'is_active' => true,
    ]);
    CareerTrailStep::query()->where('slug', 'ats')->update(['agent_id' => $agent->id]);

    $user = User::factory()->create();
    $cv = UserCv::query()->create([
        'user_id' => $user->id,
        'title' => 'CV perfil',
        'body' => str_repeat('c', 400),
        'is_default' => true,
        'source' => UserCv::SOURCE_MANUAL,
    ]);

    $this->actingAs($user)->get(route('career-trail.index'));

    AgentDocument::query()->create([
        'user_id' => $user->id,
        'agent_id' => $agent->id,
        'type' => AgentDocument::TYPE_JD,
        'title' => 'Vaga Z',
        'body' => str_repeat('d', 200),
        'user_cv_id' => $cv->id,
    ]);

    $this->actingAs($user)
        ->get(route('career-trail.ats'))
        ->assertOk()
        ->assertSee('ATS check indisponível', false);
});

test('jd created via chat pairing paired_cv_document_id gets user_cv_id and unlocks ats continuation', function () {
    $agent = Agent::query()->create([
        'name' => 'ATS pairing migrate',
        'price' => 0,
        'model_type' => 'gpt-4o-mini',
        'is_active' => true,
    ]);

    CareerTrailStep::query()->where('slug', 'ats')->update(['agent_id' => $agent->id]);

    $user = User::factory()->create();

    $userCv = UserCv::query()->create([
        'user_id' => $user->id,
        'title' => 'CV perfil',
        'body' => str_repeat('b', 400),
        'is_default' => true,
        'source' => UserCv::SOURCE_MANUAL,
    ]);

    $this->actingAs($user)->get(route('career-trail.index'));

    $cvDoc = AgentDocument::query()->create([
        'user_id' => $user->id,
        'agent_id' => $agent->id,
        'type' => AgentDocument::TYPE_CV,
        'title' => 'CV bib',
        'body' => 'Texto biblioteca',
    ]);

    $jd = AgentDocument::query()->create([
        'user_id' => $user->id,
        'agent_id' => $agent->id,
        'type' => AgentDocument::TYPE_JD,
        'title' => 'Vaga',
        'body' => 'Descrição da vaga com palavras.',
        'paired_cv_document_id' => $cvDoc->id,
        'user_cv_id' => null,
    ]);

    $jd->refresh();
    expect((int) $jd->user_cv_id)->toBe((int) $userCv->id);
    expect($jd->paired_cv_document_id)->toBeNull();

    $gate = CareerTrailStepCompletion::readiness($user, CareerTrailStep::query()->where('slug', 'ats')->firstOrFail());
    expect($gate['ready'] ?? false)->toBeTrue();
});

test('ats completion unlocks motivation and interviews in parallel; advance lands on interviews', function () {
    $agent = Agent::query()->create([
        'name' => 'ATS paralelo',
        'price' => 0,
        'model_type' => 'gpt-4o-mini',
        'is_active' => true,
    ]);

    CareerTrailStep::query()->where('slug', 'ats')->update(['agent_id' => $agent->id]);

    $user = User::factory()->create();

    $userCv = UserCv::query()->create([
        'user_id' => $user->id,
        'title' => 'CV',
        'body' => str_repeat('a', 400),
        'is_default' => true,
        'source' => UserCv::SOURCE_MANUAL,
    ]);

    $this->actingAs($user)->get(route('career-trail.index'));

    $progress = UserCareerTrailProgress::query()->where('user_id', $user->id)->firstOrFail();
    expect($progress->currentStep->slug)->toBe('ats');
    expect((int) $progress->max_sort_order_reached)->toBe(2);

    AgentDocument::query()->create([
        'user_id' => $user->id,
        'agent_id' => $agent->id,
        'type' => AgentDocument::TYPE_JD,
        'title' => 'Vaga teste',
        'body' => 'Descrição',
        'user_cv_id' => $userCv->id,
    ]);

    $this->actingAs($user)->get(route('career-trail.index'));

    $progress->refresh();
    $interviewOrder = (int) CareerTrailStep::query()->where('slug', 'interviews')->value('sort_order');
    expect((int) $progress->max_sort_order_reached)->toBeGreaterThanOrEqual($interviewOrder);
    expect($progress->currentStep->slug)->toBe('interviews');

    $offerStep = CareerTrailStep::query()->where('slug', 'offer')->firstOrFail();
    $this->actingAs($user)
        ->post(route('career-trail.advance'))
        ->assertRedirect(route('career-trail.index'))
        ->assertSessionHas('status');

    $progress->refresh();
    expect($progress->currentStep->slug)->toBe($offerStep->slug);
});

test('career trail banner is hidden on admin routes', function () {
    $user = User::factory()->create(['is_admin' => true]);

    $this->actingAs($user)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertDontSee('Trilha de carreira', false);
});

test('banner badge marks ats completed when jd cv pair exists even if pointer is still on ats', function () {
    $agent = Agent::query()->create([
        'name' => 'ATS badge',
        'price' => 0,
        'model_type' => 'gpt-4o-mini',
        'is_active' => true,
    ]);
    CareerTrailStep::query()->where('slug', 'ats')->update(['agent_id' => $agent->id]);

    $user = User::factory()->create();
    $cvProfile = UserCv::query()->create([
        'user_id' => $user->id,
        'title' => 'CV',
        'body' => str_repeat('p', 400),
        'is_default' => true,
        'source' => UserCv::SOURCE_MANUAL,
    ]);

    $this->actingAs($user)->get(route('career-trail.index'));

    $progress = UserCareerTrailProgress::query()->where('user_id', $user->id)->firstOrFail();
    $current = $progress->currentStep;
    expect($current->slug)->toBe('ats');

    $cvStep = CareerTrailStep::query()->where('slug', 'cv')->firstOrFail();
    $atsStep = CareerTrailStep::query()->where('slug', 'ats')->firstOrFail();

    expect(CareerTrailStepCompletion::bannerShowsCompletedBadge($user, $cvStep, $current))->toBeTrue();
    expect(CareerTrailStepCompletion::bannerShowsCompletedBadge($user, $atsStep, $current))->toBeFalse();

    AgentDocument::query()->create([
        'user_id' => $user->id,
        'agent_id' => $agent->id,
        'type' => AgentDocument::TYPE_JD,
        'title' => 'Vaga',
        'body' => str_repeat('j', 40),
        'user_cv_id' => $cvProfile->id,
    ]);

    $this->actingAs($user)->get(route('career-trail.index'));

    $progress->refresh();
    $currentAfterUnlock = $progress->currentStep;
    expect(CareerTrailStepCompletion::bannerShowsCompletedBadge($user, $atsStep, $currentAfterUnlock))->toBeTrue();
    expect(CareerTrailStepCompletion::bannerShowsCompletedBadge($user, $cvStep, $currentAfterUnlock))->toBeTrue();
    expect($currentAfterUnlock->slug)->toBe('interviews');
});

test('banner badge motivation is false until a letter is saved', function () {
    $motivation = Agent::query()->create([
        'name' => 'Mot badge',
        'price' => 0,
        'model_type' => 'gpt-4o-mini',
        'is_active' => true,
    ]);
    CareerTrailStep::query()->where('slug', 'cover-letter')->update(['agent_id' => $motivation->id]);

    $ats = Agent::query()->create([
        'name' => 'ATS badge mot',
        'price' => 0,
        'model_type' => 'gpt-4o-mini',
        'is_active' => true,
    ]);
    CareerTrailStep::query()->where('slug', 'ats')->update(['agent_id' => $ats->id]);

    $user = User::factory()->create();
    $profileCv = UserCv::query()->create([
        'user_id' => $user->id,
        'title' => 'CV',
        'body' => str_repeat('m', 400),
        'is_default' => true,
        'source' => UserCv::SOURCE_MANUAL,
    ]);

    $this->actingAs($user)->get(route('career-trail.index'));

    $jd = AgentDocument::query()->create([
        'user_id' => $user->id,
        'agent_id' => $ats->id,
        'type' => AgentDocument::TYPE_JD,
        'title' => 'Vaga',
        'body' => str_repeat('d', 400),
        'user_cv_id' => $profileCv->id,
    ]);

    $this->actingAs($user)->get(route('career-trail.index'));

    UserCareerTrailProgress::query()->where('user_id', $user->id)->update([
        'current_step_id' => CareerTrailStep::query()->where('slug', 'interviews')->value('id'),
    ]);

    $current = CareerTrailStep::query()->where('slug', 'interviews')->firstOrFail();
    $motivationStep = CareerTrailStep::query()->where('slug', 'cover-letter')->firstOrFail();

    expect(CareerTrailStepCompletion::bannerShowsCompletedBadge($user, $motivationStep, $current))->toBeFalse();

    MotivationLetter::query()->create([
        'user_id' => $user->id,
        'jd_document_id' => $jd->id,
        'title' => 'Carta',
        'body' => 'Texto',
        'source' => MotivationLetter::SOURCE_MANUAL,
    ]);

    expect(CareerTrailStepCompletion::bannerShowsCompletedBadge($user, $motivationStep, $current))->toBeTrue();
});

test('career trail ats page includes library forms when agent is active', function () {
    $agent = Agent::query()->create([
        'name' => 'ATS hub',
        'price' => 0,
        'model_type' => 'gpt-4o-mini',
        'is_active' => true,
    ]);
    CareerTrailStep::query()->where('slug', 'ats')->update(['agent_id' => $agent->id]);

    $user = User::factory()->create();
    UserCv::query()->create([
        'user_id' => $user->id,
        'title' => 'CV',
        'body' => str_repeat('p', 400),
        'is_default' => true,
        'source' => UserCv::SOURCE_MANUAL,
    ]);

    $this->actingAs($user)->get(route('career-trail.index'));

    $this->actingAs($user)
        ->get(route('career-trail.ats'))
        ->assertOk()
        ->assertSee('Biblioteca ATS — CV do perfil')
        ->assertSee('Nova vaga (JD)', false);
});

test('career trail ats shows ats check link only when jd has profile cv linked', function () {
    $agent = Agent::query()->create([
        'name' => 'ATS gate',
        'price' => 0,
        'model_type' => 'gpt-4o-mini',
        'integration' => Agent::INTEGRATION_CHATKIT_WORKFLOW,
        'chatkit_workflow_id' => 'wf_ats_hub',
        'chatkit_workflow_version' => '1',
        'is_active' => true,
    ]);
    CareerTrailStep::query()->where('slug', 'ats')->update(['agent_id' => $agent->id]);

    $user = User::factory()->create();
    $cv = UserCv::query()->create([
        'user_id' => $user->id,
        'title' => 'CV perfil',
        'body' => str_repeat('q', 400),
        'is_default' => true,
        'source' => UserCv::SOURCE_MANUAL,
    ]);

    $this->actingAs($user)->get(route('career-trail.index'));

    $noPair = $this->actingAs($user)->get(route('career-trail.ats'));
    $noPair->assertOk();
    $noPair->assertDontSee('/agents/'.$agent->id.'/chat', false);

    AgentDocument::query()->create([
        'user_id' => $user->id,
        'agent_id' => $agent->id,
        'type' => AgentDocument::TYPE_JD,
        'title' => 'Vaga X',
        'body' => str_repeat('r', 200),
        'user_cv_id' => $cv->id,
    ]);

    $withPair = $this->actingAs($user)->get(route('career-trail.ats'));
    $withPair->assertOk();
    $withPair->assertSee('/agents/'.$agent->id.'/chat', false);
});
