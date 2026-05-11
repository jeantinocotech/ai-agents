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

function makeOutcomeTestChatKitAgent(string $name): Agent
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

test('rejectround sets process outcome did not proceed', function () {
    $ats = makeOutcomeTestChatKitAgent('ATS out');
    $interviewAgent = makeOutcomeTestChatKitAgent('Ent out');

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
        'title' => 'Out Corp',
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

    $proc = InterviewProcess::query()->where('user_id', $user->id)->where('jd_document_id', $jd->id)->first();
    expect($proc)->not->toBeNull()
        ->and($proc->outcome)->toBe(InterviewApplicationOutcome::DidNotProceed);
});

test('marking candidate approved unlocks offer step on career trail progress', function () {
    $ats = makeOutcomeTestChatKitAgent('ATS apr');
    $interviewAgent = makeOutcomeTestChatKitAgent('Ent apr');

    CareerTrailStep::query()->where('slug', 'ats')->update(['agent_id' => $ats->id]);
    CareerTrailStep::query()->where('slug', 'interviews')->update(['agent_id' => $interviewAgent->id]);

    $offerSort = CareerTrailStep::query()->where('slug', 'offer')->value('sort_order');
    expect($offerSort)->not->toBeNull();

    $user = User::factory()->create();
    $cv = UserCv::query()->create([
        'user_id' => $user->id,
        'title' => 'CV',
        'body' => str_repeat('z', 400),
        'is_default' => true,
        'source' => UserCv::SOURCE_MANUAL,
    ]);

    $interviewStep = CareerTrailStep::query()->where('slug', 'interviews')->firstOrFail();

    UserCareerTrailProgress::query()->create([
        'user_id' => $user->id,
        'current_step_id' => $interviewStep->id,
        'started_at' => now(),
        'max_sort_order_reached' => (int) $interviewStep->sort_order,
    ]);

    $jd = AgentDocument::query()->create([
        'user_id' => $user->id,
        'agent_id' => $ats->id,
        'type' => AgentDocument::TYPE_JD,
        'title' => 'Apr Corp',
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

    $progressBefore = UserCareerTrailProgress::query()->where('user_id', $user->id)->firstOrFail();
    expect((int) ($progressBefore->max_sort_order_reached ?? 0))->toBeLessThan((int) $offerSort);

    $this->actingAs($user)
        ->patch(route('agents.interview-process.update', [$interviewAgent, $jd]), [
            'outcome' => InterviewApplicationOutcome::Approved->value,
        ])
        ->assertRedirect(route('agents.interview-preparations.index', $interviewAgent));

    $progressAfter = UserCareerTrailProgress::query()->where('user_id', $user->id)->firstOrFail();
    expect((int) ($progressAfter->max_sort_order_reached ?? 0))->toBeGreaterThanOrEqual((int) $offerSort);
});
