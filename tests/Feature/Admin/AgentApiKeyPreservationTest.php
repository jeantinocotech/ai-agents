<?php

use App\Models\Agent;
use App\Models\User;

test('admin agent update preserves api key when field is left blank', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $agent = Agent::query()->create([
        'name' => 'Assistente CV',
        'description' => 'Teste',
        'price' => 0,
        'model_type' => 'gpt-4o-mini',
        'integration' => Agent::INTEGRATION_CHATKIT_WORKFLOW,
        'chatkit_workflow_id' => 'wf_test',
        'chatkit_workflow_version' => '1',
        'api_key' => 'sk-test-preserve-me',
        'is_active' => true,
    ]);

    $this->actingAs($admin)
        ->put(route('admin.agents.update', $agent), [
            'name' => 'Assistente CV renomeado',
            'description' => 'Teste',
            'model_type' => 'gpt-4o-mini',
            'integration' => Agent::INTEGRATION_CHATKIT_WORKFLOW,
            'chatkit_workflow_id' => 'wf_test',
            'chatkit_workflow_version' => '1',
            'price_formatted' => '0',
            'youtube_video_id' => '',
            'assistant_id' => '',
            'is_active' => '1',
            'api_key' => '',
        ])
        ->assertRedirect(route('admin.agents.index'));

    expect($agent->fresh()->api_key)->toBe('sk-test-preserve-me')
        ->and($agent->fresh()->name)->toBe('Assistente CV renomeado');
});
