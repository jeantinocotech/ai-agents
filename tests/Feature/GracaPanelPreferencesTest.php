<?php

use App\Models\Agent;
use App\Models\AgentDocument;
use App\Models\User;
use App\Support\GracaPanelPreferences;
use Database\Seeders\CareerTrailStepsSeeder;

beforeEach(function () {
    $this->seed(CareerTrailStepsSeeder::class);
});

test('ats flow block reason explains missing profile cv on draft jd', function () {
    $user = User::factory()->create();
    $agent = Agent::query()->create([
        'name' => 'ATS',
        'price' => 0,
        'model_type' => 'gpt-4o-mini',
        'integration' => Agent::INTEGRATION_CHATKIT_WORKFLOW,
        'chatkit_workflow_id' => 'wf_cv_block',
        'is_active' => true,
    ]);
    $jd = AgentDocument::query()->create([
        'user_id' => $user->id,
        'agent_id' => $agent->id,
        'type' => AgentDocument::TYPE_JD,
        'title' => 'Sem CV',
        'body' => 'body',
        'is_active' => true,
        'application_status' => \App\Enums\JobApplicationStatus::Draft,
    ]);

    expect($jd->allowsAtsFlow())->toBeFalse()
        ->and($jd->atsFlowBlockReason())->toBe(AgentDocument::ATS_FLOW_NEEDS_CV_MESSAGE);
});

test('graca panel defaults to expanded when no preference stored', function () {
    $user = User::factory()->create();

    expect(GracaPanelPreferences::isCollapsed($user, GracaPanelPreferences::PAGE_TRAIL_ATS))->toBeFalse();
});

test('global collapsed hides all pages unless page override expands', function () {
    $user = User::factory()->create();
    $user->ui_preferences = [
        'graca_panel' => [
            'global_collapsed' => true,
            'pages' => [
                GracaPanelPreferences::PAGE_TRAIL_ATS => false,
            ],
            'dismissed_global_prompt' => true,
        ],
    ];
    $user->save();

    expect(GracaPanelPreferences::isCollapsed($user, GracaPanelPreferences::PAGE_TRAIL_CV))->toBeTrue()
        ->and(GracaPanelPreferences::isCollapsed($user, GracaPanelPreferences::PAGE_TRAIL_ATS))->toBeFalse();
});

test('authenticated user can persist graca panel preference via api', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patchJson(route('profile.graca-panel.update'), [
            'page_key' => GracaPanelPreferences::PAGE_CHAT_ATS,
            'collapsed' => true,
            'apply_global' => true,
        ])
        ->assertOk()
        ->assertJsonPath('graca_panel.global_collapsed', true);

    $user->refresh();
    expect(GracaPanelPreferences::isCollapsed($user, GracaPanelPreferences::PAGE_TRAIL_MAP))->toBeTrue();
});

test('apply global false only dismisses prompt without forcing global', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patchJson(route('profile.graca-panel.update'), [
            'page_key' => GracaPanelPreferences::PAGE_TRAIL_CV,
            'collapsed' => true,
            'apply_global' => false,
            'dismiss_global_prompt' => true,
        ])
        ->assertOk()
        ->assertJsonPath('graca_panel.dismissed_global_prompt', true)
        ->assertJsonPath('graca_panel.global_collapsed', false);

    $user->refresh();
    expect(GracaPanelPreferences::isCollapsed($user, GracaPanelPreferences::PAGE_TRAIL_CV))->toBeTrue()
        ->and(GracaPanelPreferences::isCollapsed($user, GracaPanelPreferences::PAGE_TRAIL_ATS))->toBeFalse();
});
