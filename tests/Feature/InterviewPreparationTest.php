<?php

use App\Enums\InterviewApplicationOutcome;
use App\Enums\InterviewPersona;
use App\Enums\InterviewProcessStatus;
use App\Models\Agent;
use App\Models\AgentDocument;
use App\Models\CareerTrailStep;
use App\Models\InterviewPreparation;
use App\Models\InterviewProcess;
use App\Models\User;
use App\Models\UserCareerTrailProgress;
use App\Models\UserCv;
use Database\Seeders\CareerTrailStepsSeeder;

beforeEach(function () {
    $this->seed(CareerTrailStepsSeeder::class);
});

function makeInterviewChatKitAgent(string $name): Agent
{
    return Agent::query()->create([
        'name' => $name,
        'price' => 0,
        'model_type' => 'gpt-4o-mini',
        'integration' => Agent::INTEGRATION_CHATKIT_WORKFLOW,
        'chatkit_workflow_id' => 'wf_iv_'.md5($name),
        'is_active' => true,
    ]);
}

test('interview preparations crud respects ats jd and enum fields', function () {
    $ats = makeInterviewChatKitAgent('ATS iv');
    $interviewAgent = makeInterviewChatKitAgent('Ent iv');

    CareerTrailStep::query()->where('slug', 'ats')->update(['agent_id' => $ats->id]);
    CareerTrailStep::query()->where('slug', 'interviews')->update(['agent_id' => $interviewAgent->id]);

    $user = User::factory()->create();
    $cv = UserCv::query()->create([
        'user_id' => $user->id,
        'title' => 'CV',
        'body' => str_repeat('z', 400),
        'is_default' => true,
        'source' => UserCv::SOURCE_MANUAL,
    ]);

    $interviewStep = CareerTrailStep::query()->where('slug', 'interviews')->firstOrFail();

    UserCareerTrailProgress::query()->updateOrCreate(
        ['user_id' => $user->id],
        [
            'current_step_id' => $interviewStep->id,
            'started_at' => now(),
            'max_sort_order_reached' => (int) $interviewStep->sort_order,
        ]
    );

    $jd = AgentDocument::query()->create([
        'user_id' => $user->id,
        'agent_id' => $ats->id,
        'type' => AgentDocument::TYPE_JD,
        'title' => 'Empresa Beta',
        'body' => str_repeat('w', 40),
        'user_cv_id' => $cv->id,
    ]);
    $jd->forceFill([
        'ats_submitted_at' => now(),
        'cv_sent_to_employer_at' => now(),
    ])->save();

    $this->actingAs($user)->post(route('agents.interview-preparations.store', $interviewAgent), [
        'jd_document_id' => $jd->id,
        'persona' => InterviewPersona::Technical->value,
        'status' => InterviewProcessStatus::InProcess->value,
        'chat_prep_messages' => 'Reforça experiência em microserviços; pedir exemplo de rollback.',
        'learnings' => 'Perguntas sobre arquitectura e testes.',
    ])
        ->assertRedirect(route('agents.interview-preparations.index', $interviewAgent))
        ->assertSessionHas('status');

    $row = InterviewPreparation::query()
        ->where('user_id', $user->id)
        ->where('jd_document_id', $jd->id)
        ->firstOrFail();
    expect($row->sequence)->toBe(1)
        ->and($row->persona)->toBe(InterviewPersona::Technical)
        ->and($row->status)->toBe(InterviewProcessStatus::InProcess);
    expect(str_contains((string) $row->chat_prep_messages, 'microserviços'))->toBeTrue();
    expect(str_contains((string) $row->learnings, 'arquitectura'))->toBeTrue();

    $this->actingAs($user)->put(route('agents.interview-preparations.update', [$interviewAgent, $row]), [
        'persona' => InterviewPersona::HiringManager->value,
        'status' => InterviewProcessStatus::Advanced->value,
        'chat_prep_messages' => 'Pergunta sobre KPIs da equipa.',
        'learnings' => 'Discussão sobre prioridades da equipa.',
    ])
        ->assertRedirect(route('agents.interview-preparations.index', $interviewAgent));

    $row->refresh();
    expect($row->persona)->toBe(InterviewPersona::HiringManager)
        ->and($row->status)->toBe(InterviewProcessStatus::Advanced);
    expect(str_contains((string) $row->chat_prep_messages, 'KPIs'))->toBeTrue();
    expect(str_contains((string) $row->learnings, 'prioridades'))->toBeTrue();

    $this->actingAs($user)
        ->get(route('agents.interview-preparations.index', $interviewAgent))
        ->assertOk()
        ->assertSee('Empresa Beta', false);

    $this->actingAs($user)->post(route('agents.interview-preparations.store', $interviewAgent), [
        'jd_document_id' => $jd->id,
        'persona' => InterviewPersona::Peer->value,
        'status' => InterviewProcessStatus::Advanced->value,
        'learnings' => 'Segunda ronda resumo.',
    ])->assertRedirect(route('agents.interview-preparations.index', $interviewAgent));

    $this->actingAs($user)
        ->get(route('agents.interview-preparations.index', $interviewAgent))
        ->assertOk()
        ->assertSee('2 rondas', false)
        ->assertSee('Ronda 1', false)
        ->assertSee('Ronda 2', false)
        ->assertSee('Empresa Beta', false);
});

test('interview create form hides processes with global outcome did not proceed', function () {
    $ats = makeInterviewChatKitAgent('ATS iv closed');
    $interviewAgent = makeInterviewChatKitAgent('Ent iv closed');

    CareerTrailStep::query()->where('slug', 'ats')->update(['agent_id' => $ats->id]);
    CareerTrailStep::query()->where('slug', 'interviews')->update(['agent_id' => $interviewAgent->id]);

    $user = User::factory()->create();
    $cv = UserCv::query()->create([
        'user_id' => $user->id,
        'title' => 'CV',
        'body' => str_repeat('z', 400),
        'is_default' => true,
        'source' => UserCv::SOURCE_MANUAL,
    ]);

    $interviewStep = CareerTrailStep::query()->where('slug', 'interviews')->firstOrFail();

    UserCareerTrailProgress::query()->updateOrCreate(
        ['user_id' => $user->id],
        [
            'current_step_id' => $interviewStep->id,
            'started_at' => now(),
            'max_sort_order_reached' => (int) $interviewStep->sort_order,
        ]
    );

    $jdOpen = AgentDocument::query()->create([
        'user_id' => $user->id,
        'agent_id' => $ats->id,
        'type' => AgentDocument::TYPE_JD,
        'title' => 'VagaActivosUnica',
        'body' => str_repeat('w', 40),
        'user_cv_id' => $cv->id,
    ]);

    $jdClosed = AgentDocument::query()->create([
        'user_id' => $user->id,
        'agent_id' => $ats->id,
        'type' => AgentDocument::TYPE_JD,
        'title' => 'VagaEncerradaUnica',
        'body' => str_repeat('w', 41),
        'user_cv_id' => $cv->id,
    ]);

    InterviewProcess::query()->create([
        'user_id' => $user->id,
        'jd_document_id' => $jdClosed->id,
        'outcome' => InterviewApplicationOutcome::DidNotProceed,
    ]);

    $this->actingAs($user)
        ->get(route('agents.interview-preparations.create', $interviewAgent))
        ->assertOk()
        ->assertSee('VagaActivosUnica', false)
        ->assertDontSee('VagaEncerradaUnica', false);

    $this->actingAs($user)->post(route('agents.interview-preparations.store', $interviewAgent), [
        'jd_document_id' => $jdClosed->id,
        'persona' => InterviewPersona::Technical->value,
        'status' => InterviewProcessStatus::InProcess->value,
    ])->assertStatus(422);
});

test('trail step interviews trailChatUrl points to interview preparations index', function () {
    $agent = makeInterviewChatKitAgent('Iv url');

    CareerTrailStep::query()->where('slug', 'interviews')->update(['agent_id' => $agent->id]);

    $step = CareerTrailStep::query()->where('slug', 'interviews')->firstOrFail();
    expect($step->trailChatUrl())->toBe(route('agents.interview-preparations.index', $agent));
});
