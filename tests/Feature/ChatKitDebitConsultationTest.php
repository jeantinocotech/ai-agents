<?php

use App\Models\Agent;
use App\Models\Setting;
use App\Models\User;

beforeEach(function () {
    Setting::set('chatkit_tokens_per_session', '10');
    Setting::set('tokens_minimum_per_request', '1');
});

test('chatkit debit with cv_turn context debits and labels billing', function () {
    $user = User::factory()->create(['token_balance' => 100]);
    $agent = Agent::query()->create([
        'name' => 'ChatKit CV',
        'price' => 0,
        'model_type' => 'gpt-4o-mini',
        'integration' => Agent::INTEGRATION_CHATKIT_WORKFLOW,
        'chatkit_workflow_id' => 'wf_x',
        'chatkit_workflow_version' => '1',
        'is_active' => true,
    ]);

    $this->actingAs($user)
        ->postJson(route('chat.chatkit.debit-consultation'), [
            'agent_id' => $agent->id,
            'context' => 'cv_turn',
        ])
        ->assertOk()
        ->assertJsonPath('tokens_debited', 10);

    expect((int) $user->fresh()->token_balance)->toBe(90);
});

test('chatkit debit rejects invalid context', function () {
    $user = User::factory()->create(['token_balance' => 100]);
    $agent = Agent::query()->create([
        'name' => 'ChatKit',
        'price' => 0,
        'model_type' => 'gpt-4o-mini',
        'integration' => Agent::INTEGRATION_CHATKIT_WORKFLOW,
        'chatkit_workflow_id' => 'wf_x',
        'chatkit_workflow_version' => '1',
        'is_active' => true,
    ]);

    $this->actingAs($user)
        ->postJson(route('chat.chatkit.debit-consultation'), [
            'agent_id' => $agent->id,
            'context' => 'invalid',
        ])
        ->assertStatus(422);
});
