<?php

use App\Enums\InterviewApplicationOutcome;
use App\Enums\InterviewPersona;
use App\Enums\InterviewProcessStatus;
use App\Enums\JobApplicationStatus;
use App\Models\Agent;
use App\Models\AgentDocument;
use App\Models\CareerTrailStep;
use App\Models\InterviewPreparation;
use App\Models\InterviewProcess;
use App\Models\User;
use App\Models\UserCareerTrailProgress;
use App\Models\UserCv;
use App\Services\JobApplicationStatusSync;
use App\Support\AgentsDocumentTrailListFilter;
use Database\Seeders\CareerTrailStepsSeeder;

beforeEach(function () {
    $this->seed(CareerTrailStepsSeeder::class);
});

function makeJobStatusChatKitAgent(string $name): Agent
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

test('career trail ats page shows estado column when user has vagas', function () {
    $ats = makeJobStatusChatKitAgent('ATS estado col');
    CareerTrailStep::query()->where('slug', 'ats')->update(['agent_id' => $ats->id]);

    $user = User::factory()->create();
    $atsStep = CareerTrailStep::query()->where('slug', 'ats')->firstOrFail();
    UserCareerTrailProgress::query()->updateOrCreate(
        ['user_id' => $user->id],
        [
            'current_step_id' => $atsStep->id,
            'started_at' => now(),
            'max_sort_order_reached' => (int) $atsStep->sort_order,
        ]
    );

    $cv = UserCv::query()->create([
        'user_id' => $user->id,
        'title' => 'CV',
        'body' => str_repeat('z', 400),
        'is_default' => true,
        'source' => UserCv::SOURCE_MANUAL,
    ]);

    AgentDocument::query()->create([
        'user_id' => $user->id,
        'agent_id' => $ats->id,
        'type' => AgentDocument::TYPE_JD,
        'title' => 'Vaga col',
        'body' => str_repeat('w', 40),
        'user_cv_id' => $cv->id,
    ]);

    $this->actingAs($user)
        ->get(route('career-trail.ats'))
        ->assertOk()
        ->assertSee('Status');
});

test('mark application submitted sets submitted status when json requested', function () {
    $ats = makeJobStatusChatKitAgent('ATS jobstat');
    CareerTrailStep::query()->where('slug', 'ats')->update(['agent_id' => $ats->id]);

    $user = User::factory()->create();
    $atsStep = CareerTrailStep::query()->where('slug', 'ats')->firstOrFail();
    UserCareerTrailProgress::query()->updateOrCreate(
        ['user_id' => $user->id],
        [
            'current_step_id' => $atsStep->id,
            'started_at' => now(),
            'max_sort_order_reached' => (int) $atsStep->sort_order,
        ]
    );
    $cv = UserCv::query()->create([
        'user_id' => $user->id,
        'title' => 'CV',
        'body' => str_repeat('z', 400),
        'is_default' => true,
        'source' => UserCv::SOURCE_MANUAL,
    ]);

    $jd = AgentDocument::query()->create([
        'user_id' => $user->id,
        'agent_id' => $ats->id,
        'type' => AgentDocument::TYPE_JD,
        'title' => 'Vaga X',
        'body' => str_repeat('w', 40),
        'user_cv_id' => $cv->id,
    ]);

    $this->actingAs($user)
        ->postJson(route('agents.documents.mark-application-submitted', [$ats, $jd]))
        ->assertOk()
        ->assertJson(['success' => true, 'application_status' => JobApplicationStatus::Submitted->value]);

    $jd->refresh();
    expect($jd->application_status)->toBe(JobApplicationStatus::Submitted)
        ->and($jd->ats_submitted_at)->not->toBeNull();
});

test('mark application submitted blocked when job status is not draft or submitted', function () {
    $ats = makeJobStatusChatKitAgent('ATS block submit');
    CareerTrailStep::query()->where('slug', 'ats')->update(['agent_id' => $ats->id]);

    $user = User::factory()->create();
    $atsStep = CareerTrailStep::query()->where('slug', 'ats')->firstOrFail();
    UserCareerTrailProgress::query()->updateOrCreate(
        ['user_id' => $user->id],
        [
            'current_step_id' => $atsStep->id,
            'started_at' => now(),
            'max_sort_order_reached' => (int) $atsStep->sort_order,
        ]
    );
    $cv = UserCv::query()->create([
        'user_id' => $user->id,
        'title' => 'CV',
        'body' => str_repeat('z', 400),
        'is_default' => true,
        'source' => UserCv::SOURCE_MANUAL,
    ]);
    $jd = AgentDocument::query()->create([
        'user_id' => $user->id,
        'agent_id' => $ats->id,
        'type' => AgentDocument::TYPE_JD,
        'title' => 'Vaga bloqueada',
        'body' => str_repeat('w', 40),
        'user_cv_id' => $cv->id,
        'ats_submitted_at' => now(),
    ]);
    $jd->forceFill(['application_status' => JobApplicationStatus::CvSent])->saveQuietly();
    $jd->refresh();
    expect($jd->allowsAtsFlow())->toBeFalse();

    $this->actingAs($user)
        ->postJson(route('agents.documents.mark-application-submitted', [$ats, $jd]))
        ->assertStatus(422);
});

test('mark cv sent to employer requires alignment ats first', function () {
    $ats = makeJobStatusChatKitAgent('ATS cvsent');
    CareerTrailStep::query()->where('slug', 'ats')->update(['agent_id' => $ats->id]);

    $user = User::factory()->create();
    $atsStep = CareerTrailStep::query()->where('slug', 'ats')->firstOrFail();
    UserCareerTrailProgress::query()->updateOrCreate(
        ['user_id' => $user->id],
        [
            'current_step_id' => $atsStep->id,
            'started_at' => now(),
            'max_sort_order_reached' => (int) $atsStep->sort_order,
        ]
    );

    $cv = UserCv::query()->create([
        'user_id' => $user->id,
        'title' => 'CV',
        'body' => str_repeat('z', 400),
        'is_default' => true,
        'source' => UserCv::SOURCE_MANUAL,
    ]);

    $jd = AgentDocument::query()->create([
        'user_id' => $user->id,
        'agent_id' => $ats->id,
        'type' => AgentDocument::TYPE_JD,
        'title' => 'Vaga CVSent',
        'body' => str_repeat('w', 40),
        'user_cv_id' => $cv->id,
    ]);

    $this->actingAs($user)
        ->post(route('agents.documents.mark-cv-sent-to-employer', [$ats, $jd]))
        ->assertStatus(422);

    $this->actingAs($user)
        ->postJson(route('agents.documents.mark-application-submitted', [$ats, $jd]))
        ->assertOk();

    $this->actingAs($user)
        ->postJson(route('agents.documents.mark-cv-sent-to-employer', [$ats, $jd]))
        ->assertOk()
        ->assertJson(['success' => true, 'application_status' => JobApplicationStatus::CvSent->value]);

    $jd->refresh();
    expect($jd->application_status)->toBe(JobApplicationStatus::CvSent)
        ->and($jd->cv_sent_to_employer_at)->not->toBeNull();

    $this->actingAs($user)
        ->post(route('agents.documents.trail-desired-status', [$ats, $jd]), [
            'desired_status' => JobApplicationStatus::Submitted->value,
            'trail_return' => 'career_trail_ats',
        ])
        ->assertRedirect();

    $jd->refresh();
    expect($jd->application_status)->toBe(JobApplicationStatus::Submitted)
        ->and($jd->ats_submitted_at)->not->toBeNull()
        ->and($jd->cv_sent_to_employer_at)->toBeNull();
});

test('first interview round requires cv sent to employer', function () {
    $ats = makeJobStatusChatKitAgent('ATS first iv');
    $interviewAgent = makeJobStatusChatKitAgent('Ent first iv');

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
        'title' => 'Vaga sem CV empresa',
        'body' => str_repeat('w', 40),
        'user_cv_id' => $cv->id,
    ]);
    $jd->forceFill(['ats_submitted_at' => now()])->save();

    $this->actingAs($user)
        ->post(route('agents.interview-preparations.store', $interviewAgent), [
            'jd_document_id' => $jd->id,
            'persona' => InterviewPersona::Technical->value,
            'status' => InterviewProcessStatus::InProcess->value,
        ])
        ->assertStatus(422);
});

test('creating interview preparation moves jd application status to interviewing', function () {
    $ats = makeJobStatusChatKitAgent('ATS intv');
    $interviewAgent = makeJobStatusChatKitAgent('Ent intv');

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
        'title' => 'Vaga Y',
        'body' => str_repeat('w', 40),
        'user_cv_id' => $cv->id,
    ]);
    $jd->forceFill([
        'ats_submitted_at' => now(),
        'cv_sent_to_employer_at' => now(),
    ])->save();

    $this->actingAs($user)
        ->post(route('agents.interview-preparations.store', $interviewAgent), [
            'jd_document_id' => $jd->id,
            'persona' => InterviewPersona::Technical->value,
            'status' => InterviewProcessStatus::InProcess->value,
        ])
        ->assertRedirect(route('agents.interview-preparations.index', $interviewAgent));

    $jd->refresh();
    expect($jd->application_status)->toBe(JobApplicationStatus::Interviewing);
});

test('mark application did not proceed blocks new interview rounds', function () {
    $ats = makeJobStatusChatKitAgent('ATS block');
    $interviewAgent = makeJobStatusChatKitAgent('Ent block');

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
        'title' => 'Vaga Z',
        'body' => str_repeat('w', 40),
        'user_cv_id' => $cv->id,
        'application_status' => JobApplicationStatus::Submitted,
        'ats_submitted_at' => now(),
    ]);

    $this->actingAs($user)
        ->post(route('agents.documents.mark-application-not-proceeded', [$ats, $jd]), [
            'trail_return' => 'career_trail_ats',
        ])
        ->assertRedirect(route('career-trail.ats').'#ats-biblioteca');

    $jd->refresh();
    expect($jd->application_status)->toBe(JobApplicationStatus::DidNotProceed);

    $this->actingAs($user)
        ->post(route('agents.interview-preparations.store', $interviewAgent), [
            'jd_document_id' => $jd->id,
            'persona' => InterviewPersona::Technical->value,
            'status' => InterviewProcessStatus::InProcess->value,
        ])
        ->assertStatus(422);
});

test('terminal interview process is kept when all preparations are deleted', function () {
    $ats = makeJobStatusChatKitAgent('ATS term');
    $interviewAgent = makeJobStatusChatKitAgent('Ent term');

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
        'title' => 'Vaga T',
        'body' => str_repeat('w', 40),
        'user_cv_id' => $cv->id,
    ]);
    $jd->forceFill([
        'ats_submitted_at' => now(),
        'cv_sent_to_employer_at' => now(),
    ])->save();

    $prep = InterviewPreparation::query()->create([
        'user_id' => $user->id,
        'jd_document_id' => $jd->id,
        'sequence' => 1,
        'persona' => InterviewPersona::Technical,
        'status' => InterviewProcessStatus::Rejected,
    ]);

    expect(InterviewProcess::query()->where('user_id', $user->id)->where('jd_document_id', $jd->id)->exists())->toBeTrue();

    $prep->delete();

    $proc = InterviewProcess::query()->where('user_id', $user->id)->where('jd_document_id', $jd->id)->first();
    expect($proc)->not->toBeNull()
        ->and($proc->outcome)->toBe(InterviewApplicationOutcome::DidNotProceed);

    $jd->refresh();
    expect($jd->application_status)->toBe(JobApplicationStatus::DidNotProceed);
});

test('career trail ats vagas list hides finalized by default', function () {
    $ats = makeJobStatusChatKitAgent('ATS filt');
    CareerTrailStep::query()->where('slug', 'ats')->update(['agent_id' => $ats->id]);

    $user = User::factory()->create();
    $atsStep = CareerTrailStep::query()->where('slug', 'ats')->firstOrFail();
    UserCareerTrailProgress::query()->updateOrCreate(
        ['user_id' => $user->id],
        [
            'current_step_id' => $atsStep->id,
            'started_at' => now(),
            'max_sort_order_reached' => (int) $atsStep->sort_order,
        ]
    );

    $cv = UserCv::query()->create([
        'user_id' => $user->id,
        'title' => 'CV',
        'body' => str_repeat('z', 400),
        'is_default' => true,
        'source' => UserCv::SOURCE_MANUAL,
    ]);

    $jdOpen = AgentDocument::query()->create([
        'user_id' => $user->id,
        'agent_id' => $ats->id,
        'type' => AgentDocument::TYPE_JD,
        'title' => 'VagaAbertaFiltro',
        'body' => str_repeat('w', 40),
        'user_cv_id' => $cv->id,
    ]);

    $jdClosed = AgentDocument::query()->create([
        'user_id' => $user->id,
        'agent_id' => $ats->id,
        'type' => AgentDocument::TYPE_JD,
        'title' => 'VagaFechadaFiltro',
        'body' => str_repeat('x', 40),
        'user_cv_id' => $cv->id,
    ]);

    InterviewProcess::query()->create([
        'user_id' => $user->id,
        'jd_document_id' => $jdClosed->id,
        'outcome' => InterviewApplicationOutcome::DidNotProceed,
    ]);
    JobApplicationStatusSync::reconcileForJdId((int) $user->id, (int) $jdClosed->id);

    $this->actingAs($user)
        ->get(route('career-trail.ats'))
        ->assertOk()
        ->assertSee('VagaAbertaFiltro', false)
        ->assertDontSee('VagaFechadaFiltro', false);

    $this->actingAs($user)
        ->get(route('career-trail.ats', ['jd_list_filter' => AgentsDocumentTrailListFilter::ACTIVE_ALL]))
        ->assertOk()
        ->assertSee('VagaAbertaFiltro', false)
        ->assertSee('VagaFechadaFiltro', false);

    $this->actingAs($user)
        ->get(route('career-trail.ats', ['jd_list_filter' => AgentsDocumentTrailListFilter::CLOSED]))
        ->assertOk()
        ->assertDontSee('VagaAbertaFiltro', false)
        ->assertSee('VagaFechadaFiltro', false);
});

test('jd destroy archives vacancy removes preparations and sets did not proceed', function () {
    $ats = makeJobStatusChatKitAgent('ATS archive jd');
    CareerTrailStep::query()->where('slug', 'ats')->update(['agent_id' => $ats->id]);

    $user = User::factory()->create();
    $atsStep = CareerTrailStep::query()->where('slug', 'ats')->firstOrFail();
    UserCareerTrailProgress::query()->updateOrCreate(
        ['user_id' => $user->id],
        [
            'current_step_id' => $atsStep->id,
            'started_at' => now(),
            'max_sort_order_reached' => (int) $atsStep->sort_order,
        ]
    );

    $cv = UserCv::query()->create([
        'user_id' => $user->id,
        'title' => 'CV',
        'body' => str_repeat('z', 400),
        'is_default' => true,
        'source' => UserCv::SOURCE_MANUAL,
    ]);

    $jd = AgentDocument::query()->create([
        'user_id' => $user->id,
        'agent_id' => $ats->id,
        'type' => AgentDocument::TYPE_JD,
        'title' => 'VagaArchivePrep',
        'body' => str_repeat('w', 40),
        'user_cv_id' => $cv->id,
    ]);
    $jd->forceFill([
        'ats_submitted_at' => now(),
        'cv_sent_to_employer_at' => now(),
    ])->save();

    InterviewPreparation::query()->create([
        'user_id' => $user->id,
        'jd_document_id' => $jd->id,
        'sequence' => 1,
        'persona' => InterviewPersona::Technical,
        'status' => InterviewProcessStatus::InProcess,
    ]);

    $this->actingAs($user)
        ->delete(route('agents.documents.destroy', [$ats, $jd]), [
            'trail_return' => 'career_trail_ats',
        ])
        ->assertRedirect(route('career-trail.ats').'#ats-biblioteca');

    expect(InterviewPreparation::query()->where('jd_document_id', $jd->id)->exists())->toBeFalse();

    $jd->refresh();
    expect($jd->is_active)->toBeFalse()
        ->and($jd->application_status)->toBe(JobApplicationStatus::DidNotProceed);
});

test('career trail ats lists inactive jds only when jd_list_filter inactive', function () {
    $ats = makeJobStatusChatKitAgent('ATS active filter');
    CareerTrailStep::query()->where('slug', 'ats')->update(['agent_id' => $ats->id]);

    $user = User::factory()->create();
    $atsStep = CareerTrailStep::query()->where('slug', 'ats')->firstOrFail();
    UserCareerTrailProgress::query()->updateOrCreate(
        ['user_id' => $user->id],
        [
            'current_step_id' => $atsStep->id,
            'started_at' => now(),
            'max_sort_order_reached' => (int) $atsStep->sort_order,
        ]
    );

    $cv = UserCv::query()->create([
        'user_id' => $user->id,
        'title' => 'CV',
        'body' => str_repeat('z', 400),
        'is_default' => true,
        'source' => UserCv::SOURCE_MANUAL,
    ]);

    AgentDocument::query()->create([
        'user_id' => $user->id,
        'agent_id' => $ats->id,
        'type' => AgentDocument::TYPE_JD,
        'title' => 'VagaActivaLista',
        'body' => str_repeat('w', 40),
        'user_cv_id' => $cv->id,
        'is_active' => true,
    ]);

    AgentDocument::query()->create([
        'user_id' => $user->id,
        'agent_id' => $ats->id,
        'type' => AgentDocument::TYPE_JD,
        'title' => 'VagaInactivaLista',
        'body' => str_repeat('x', 40),
        'user_cv_id' => $cv->id,
        'is_active' => false,
    ]);

    $this->actingAs($user)
        ->get(route('career-trail.ats'))
        ->assertOk()
        ->assertSee('VagaActivaLista', false)
        ->assertDontSee('VagaInactivaLista', false);

    $this->actingAs($user)
        ->get(route('career-trail.ats', ['jd_list_filter' => AgentsDocumentTrailListFilter::INACTIVE]))
        ->assertOk()
        ->assertSee('VagaInactivaLista', false)
        ->assertDontSee('VagaActivaLista', false);
});
