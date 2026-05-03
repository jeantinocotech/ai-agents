<?php

use App\Models\Agent;
use App\Models\AgentDocument;
use App\Models\CareerTrailStep;
use App\Models\MotivationLetter;
use App\Models\User;
use App\Models\UserCareerTrailProgress;
use App\Models\UserCv;
use App\Services\ChatKitDocumentLibraryService;
use Database\Seeders\CareerTrailStepsSeeder;

beforeEach(function () {
    $this->seed(CareerTrailStepsSeeder::class);
});

function makeChatKitAgent(string $name): Agent
{
    return Agent::query()->create([
        'name' => $name,
        'price' => 0,
        'model_type' => 'gpt-4o-mini',
        'integration' => Agent::INTEGRATION_CHATKIT_WORKFLOW,
        'chatkit_workflow_id' => 'wf_test_'.md5($name),
        'is_active' => true,
    ]);
}

test('cover letter chatkit library reuses jds from ats agent bucket', function () {
    $ats = makeChatKitAgent('ATS bib');
    $motivation = makeChatKitAgent('Motivação CK');

    CareerTrailStep::query()->where('slug', 'ats')->update(['agent_id' => $ats->id]);
    CareerTrailStep::query()->where('slug', 'cover-letter')->update(['agent_id' => $motivation->id]);

    $user = User::factory()->create();
    $userCv = UserCv::query()->create([
        'user_id' => $user->id,
        'title' => 'CV',
        'body' => str_repeat('c', 400),
        'is_default' => true,
        'source' => UserCv::SOURCE_MANUAL,
    ]);

    $jd = AgentDocument::query()->create([
        'user_id' => $user->id,
        'agent_id' => $ats->id,
        'type' => AgentDocument::TYPE_JD,
        'title' => 'Vaga X',
        'body' => str_repeat('v', 120),
        'user_cv_id' => $userCv->id,
    ]);

    $lib = ChatKitDocumentLibraryService::forUserAndAgent((int) $user->id, $motivation);
    expect($lib)->not->toBeNull()
        ->and($lib['jds'])->toHaveCount(1)
        ->and((int) $lib['jds'][0]['id'])->toBe((int) $jd->id)
        ->and((int) $lib['jd_content_agent_id'])->toBe((int) $ats->id)
        ->and((int) $lib['documents_hub_agent_id'])->toBe((int) $ats->id)
        ->and((int) $lib['jd_defaults_agent_id'])->toBe((int) $ats->id);

    $motivationLocalJd = AgentDocument::query()->create([
        'user_id' => $user->id,
        'agent_id' => $motivation->id,
        'type' => AgentDocument::TYPE_JD,
        'title' => 'Orphan',
        'body' => 'ignored for trail merge',
        'user_cv_id' => $userCv->id,
    ]);

    $lib2 = ChatKitDocumentLibraryService::forUserAndAgent((int) $user->id, $motivation);
    expect($lib2['jds'])->toHaveCount(1)
        ->and((int) $lib2['jds'][0]['id'])->toBe((int) $jd->id);
});

test('motivation letter can be stored for an ats jd process from cover letter agent', function () {
    $ats = makeChatKitAgent('ATS store');
    $motivation = makeChatKitAgent('Motivação store');

    CareerTrailStep::query()->where('slug', 'ats')->update(['agent_id' => $ats->id]);
    $coverLetterStep = CareerTrailStep::query()->where('slug', 'cover-letter')->firstOrFail();
    CareerTrailStep::query()->where('slug', 'cover-letter')->update(['agent_id' => $motivation->id]);

    $user = User::factory()->create();
    $userCv = UserCv::query()->create([
        'user_id' => $user->id,
        'title' => 'CV',
        'body' => str_repeat('d', 400),
        'is_default' => true,
        'source' => UserCv::SOURCE_MANUAL,
    ]);

    $jd = AgentDocument::query()->create([
        'user_id' => $user->id,
        'agent_id' => $ats->id,
        'type' => AgentDocument::TYPE_JD,
        'title' => 'Empresa Y',
        'body' => str_repeat('j', 100),
        'user_cv_id' => $userCv->id,
    ]);

    UserCareerTrailProgress::query()->updateOrCreate(
        ['user_id' => $user->id],
        [
            'current_step_id' => $coverLetterStep->id,
            'started_at' => now(),
            'max_sort_order_reached' => (int) $coverLetterStep->sort_order,
        ]
    );

    $this->actingAs($user)->post(route('agents.motivation-letters.store', $motivation), [
        'jd_document_id' => $jd->id,
        'title' => 'Carta teste',
        'body' => 'Texto da carta.',
        'source' => MotivationLetter::SOURCE_MANUAL,
    ])
        ->assertRedirect(route('agents.motivation-letters.index', $motivation))
        ->assertSessionHas('status');

    $saved = MotivationLetter::query()->where('user_id', $user->id)->where('jd_document_id', $jd->id)->firstOrFail();
    expect($saved->body)->toContain('Texto da carta');
    expect($saved->title)->toBe('Carta teste');
});
