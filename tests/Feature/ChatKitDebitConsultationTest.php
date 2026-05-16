<?php

use App\Mail\ChatKitClientAlertMail;
use App\Models\Agent;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

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

test('chatkit client report logs warning for authenticated chatkit agent', function () {
    \Illuminate\Support\Facades\Log::spy();

    $user = User::factory()->create();
    $agent = Agent::query()->create([
        'name' => 'ChatKit report',
        'price' => 0,
        'model_type' => 'gpt-4o-mini',
        'integration' => Agent::INTEGRATION_CHATKIT_WORKFLOW,
        'chatkit_workflow_id' => 'wf_x',
        'chatkit_workflow_version' => '1',
        'is_active' => true,
    ]);

    $this->actingAs($user)
        ->postJson(route('chat.chatkit.client-report'), [
            'agent_id' => $agent->id,
            'message' => 'Command onSendUserMessage not supported',
            'source' => 'chatkit.error',
        ])
        ->assertOk()
        ->assertJson(['ok' => true]);

    \Illuminate\Support\Facades\Log::shouldHaveReceived('warning')->withArgs(
        function (string $message, array $context) {
            return $message === 'ChatKit cliente'
                && ($context['message'] ?? '') === 'Command onSendUserMessage not supported'
                && ($context['source'] ?? '') === 'chatkit.error';
        }
    )->once();
});

test('chatkit client report requires authentication', function () {
    $agent = Agent::query()->create([
        'name' => 'ChatKit',
        'price' => 0,
        'model_type' => 'gpt-4o-mini',
        'integration' => Agent::INTEGRATION_CHATKIT_WORKFLOW,
        'chatkit_workflow_id' => 'wf_x',
        'chatkit_workflow_version' => '1',
        'is_active' => true,
    ]);

    $this->postJson(route('chat.chatkit.client-report'), [
        'agent_id' => $agent->id,
        'message' => 'x',
    ])->assertUnauthorized();
});

test('chatkit client report sends alert email once per throttle window', function () {
    Mail::fake();
    Cache::flush();

    config([
        'services.chatkit.alert_mail_raw' => 'ops@example.com, invalid-not-email',
        'services.chatkit.alert_only_production' => false,
        'services.chatkit.alert_throttle_seconds' => 3600,
    ]);

    $user = User::factory()->create();
    $agent = Agent::query()->create([
        'name' => 'ChatKit alert',
        'price' => 0,
        'model_type' => 'gpt-4o-mini',
        'integration' => Agent::INTEGRATION_CHATKIT_WORKFLOW,
        'chatkit_workflow_id' => 'wf_x',
        'chatkit_workflow_version' => '1',
        'is_active' => true,
    ]);

    $payload = [
        'agent_id' => $agent->id,
        'message' => 'Command onSendUserMessage not supported',
        'source' => 'chatkit.error',
    ];

    $this->actingAs($user)
        ->postJson(route('chat.chatkit.client-report'), $payload)
        ->assertOk();

    $this->actingAs($user)
        ->postJson(route('chat.chatkit.client-report'), $payload)
        ->assertOk();

    Mail::assertSent(ChatKitClientAlertMail::class, 1);
    Mail::assertSent(ChatKitClientAlertMail::class, function (ChatKitClientAlertMail $mail) {
        return $mail->hasTo('ops@example.com')
            && $mail->source === 'chatkit.error'
            && str_contains($mail->message, 'onSendUserMessage');
    });
});

test('chatkit client report does not email for routine session 401', function () {
    Mail::fake();
    Cache::flush();

    config([
        'services.chatkit.alert_mail_raw' => 'ops@example.com',
        'services.chatkit.alert_only_production' => false,
    ]);

    $user = User::factory()->create();
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
        ->postJson(route('chat.chatkit.client-report'), [
            'agent_id' => $agent->id,
            'message' => 'session_http_401: Não autenticado',
            'source' => 'chatkit_session',
        ])
        ->assertOk();

    Mail::assertNothingSent();
});

test('chatkit client report does not email outside production when configured', function () {
    Mail::fake();
    Cache::flush();

    config([
        'services.chatkit.alert_mail_raw' => 'ops@example.com',
        'services.chatkit.alert_only_production' => true,
    ]);

    $user = User::factory()->create();
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
        ->postJson(route('chat.chatkit.client-report'), [
            'agent_id' => $agent->id,
            'message' => 'Command onSendUserMessage not supported',
            'source' => 'chatkit.error',
        ])
        ->assertOk();

    Mail::assertNothingSent();
});
