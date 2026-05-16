<?php

use App\Models\Agent;
use App\Models\AgentDocument;
use App\Models\AtsAnalysis;
use App\Models\AtsAnalysisItem;
use App\Models\CareerTrailStep;
use App\Models\User;
use App\Models\UserCareerTrailProgress;
use App\Models\UserCv;
use App\Services\AtsKeywordAnalysisService;
use Database\Seeders\CareerTrailStepsSeeder;

beforeEach(function () {
    $this->seed(CareerTrailStepsSeeder::class);
});

test('ats chat compact shows vagas link and step indicator', function () {
    $agent = Agent::query()->create([
        'name' => 'ATS ChatKit',
        'price' => 0,
        'model_type' => 'gpt-4o-mini',
        'integration' => Agent::INTEGRATION_CHATKIT_WORKFLOW,
        'chatkit_workflow_id' => 'wf_ats_ui',
        'is_active' => true,
    ]);
    CareerTrailStep::query()->where('slug', 'ats')->update(['agent_id' => $agent->id]);

    $user = User::factory()->create();
    UserCv::query()->create([
        'user_id' => $user->id,
        'title' => 'CV Teste',
        'body' => str_repeat('experiencia scrum agile ', 30),
        'is_default' => true,
        'source' => UserCv::SOURCE_MANUAL,
    ]);

    $this->actingAs($user)->get(route('career-trail.index'));

    $this->actingAs($user)
        ->get(route('agents.chat', $agent))
        ->assertOk()
        ->assertViewHas('compactTrailChatUi', true)
        ->assertSee('Vagas', false)
        ->assertSee('id="chatkit-ats-steps"', false)
        ->assertSee('Enviar CV', false);
});

test('user can create analysis and open workspace', function () {
    $agent = Agent::query()->create([
        'name' => 'ATS',
        'price' => 0,
        'model_type' => 'gpt-4o-mini',
        'integration' => Agent::INTEGRATION_CHATKIT_WORKFLOW,
        'chatkit_workflow_id' => 'wf_ats_ws',
        'is_active' => true,
    ]);
    CareerTrailStep::query()->where('slug', 'ats')->update(['agent_id' => $agent->id]);

    $user = User::factory()->create();
    $cv = UserCv::query()->create([
        'user_id' => $user->id,
        'title' => 'CV',
        'body' => 'Experiencia com PHP Laravel Scrum metodologia agil.',
        'is_default' => true,
        'source' => UserCv::SOURCE_MANUAL,
    ]);
    $jd = AgentDocument::query()->create([
        'user_id' => $user->id,
        'agent_id' => $agent->id,
        'type' => AgentDocument::TYPE_JD,
        'title' => 'Dev PHP',
        'body' => 'Procuramos PHP Laravel Scrum Agile Kanban Docker.',
        'user_cv_id' => $cv->id,
        'is_active' => true,
    ]);

    $this->actingAs($user)
        ->post(route('career-trail.ats.analyses.store'), [
            'jd_document_id' => $jd->id,
        ])
        ->assertRedirect();

    $analysis = AtsAnalysis::query()->where('user_id', $user->id)->where('agent_document_id', $jd->id)->first();
    expect($analysis)->not->toBeNull();
    expect($analysis->items)->not->toBeEmpty();

    $this->actingAs($user)
        ->get(route('career-trail.ats.workspace', $analysis))
        ->assertOk()
        ->assertSee('Ajustar CV')
        ->assertSee('Salvar e passar no filtro', false);
});

test('heuristic analysis service produces ranked items', function () {
    $rows = app(AtsKeywordAnalysisService::class)->buildItemRows(
        'CV com PHP.',
        'Vaga exige PHP Laravel Scrum e Docker para backend.'
    );

    expect($rows)->not->toBeEmpty();
    expect($rows[0])->toHaveKeys(['keyword', 'relevance', 'match_status', 'priority_rank']);
});

test('user can patch workspace item addressed', function () {
    $user = User::factory()->create();
    $cv = UserCv::query()->create([
        'user_id' => $user->id,
        'title' => 'CV',
        'body' => str_repeat('a', 400),
        'is_default' => true,
        'source' => UserCv::SOURCE_MANUAL,
    ]);
    $agent = Agent::query()->create([
        'name' => 'Agent',
        'price' => 0,
        'model_type' => 'gpt-4o-mini',
        'is_active' => true,
    ]);
    $jd = AgentDocument::query()->create([
        'user_id' => $user->id,
        'agent_id' => $agent->id,
        'type' => AgentDocument::TYPE_JD,
        'title' => 'JD',
        'body' => 'PHP',
        'user_cv_id' => $cv->id,
        'is_active' => true,
    ]);
    $analysis = AtsAnalysis::query()->create([
        'user_id' => $user->id,
        'agent_document_id' => $jd->id,
        'user_cv_id' => $cv->id,
        'status' => AtsAnalysis::STATUS_READY,
        'source' => AtsAnalysis::SOURCE_MANUAL,
    ]);
    $item = $analysis->items()->create([
        'keyword' => 'PHP',
        'relevance' => AtsAnalysisItem::RELEVANCE_HIGH,
        'match_status' => AtsAnalysisItem::MATCH_PARTIAL,
        'priority_rank' => 10,
        'sort_order' => 0,
    ]);

    $this->actingAs($user)
        ->patchJson(route('career-trail.ats.workspace.item', $item), ['is_addressed' => true])
        ->assertOk()
        ->assertJsonPath('item.is_addressed', true);
});

test('chatkit pair status returns workspace url when analysis exists', function () {
    $user = User::factory()->create();
    $cv = UserCv::query()->create([
        'user_id' => $user->id,
        'title' => 'CV',
        'body' => 'body',
        'is_default' => true,
        'source' => UserCv::SOURCE_MANUAL,
    ]);
    $agent = Agent::query()->create([
        'name' => 'ATS',
        'price' => 0,
        'model_type' => 'gpt-4o-mini',
        'integration' => Agent::INTEGRATION_CHATKIT_WORKFLOW,
        'chatkit_workflow_id' => 'wf_pair',
        'is_active' => true,
    ]);
    CareerTrailStep::query()->where('slug', 'ats')->update(['agent_id' => $agent->id]);
    $jd = AgentDocument::query()->create([
        'user_id' => $user->id,
        'agent_id' => $agent->id,
        'type' => AgentDocument::TYPE_JD,
        'title' => 'JD',
        'body' => 'body',
        'user_cv_id' => $cv->id,
        'is_active' => true,
    ]);
    $analysis = AtsAnalysis::query()->create([
        'user_id' => $user->id,
        'agent_document_id' => $jd->id,
        'user_cv_id' => $cv->id,
        'status' => AtsAnalysis::STATUS_READY,
        'source' => AtsAnalysis::SOURCE_CHATKIT_TOOL,
        'ats_score' => 55,
    ]);

    $this->actingAs($user)
        ->getJson(route('chat.chatkit.ats-pair-status', [
            'jd_document_id' => $jd->id,
            'user_cv_id' => $cv->id,
        ]))
        ->assertOk()
        ->assertJsonPath('ok', true)
        ->assertJsonPath('pair_valid', true)
        ->assertJsonPath('analysis_id', $analysis->id)
        ->assertJsonPath('workspace_url', route('career-trail.ats.workspace', $analysis));
});

test('chatkit pair status rejects mismatched cv for jd', function () {
    $user = User::factory()->create();
    $cvA = UserCv::query()->create([
        'user_id' => $user->id,
        'title' => 'CV A',
        'body' => 'a',
        'is_default' => true,
        'source' => UserCv::SOURCE_MANUAL,
    ]);
    $cvB = UserCv::query()->create([
        'user_id' => $user->id,
        'title' => 'CV B',
        'body' => 'b',
        'source' => UserCv::SOURCE_MANUAL,
    ]);
    $agent = Agent::query()->create([
        'name' => 'ATS',
        'price' => 0,
        'model_type' => 'gpt-4o-mini',
        'integration' => Agent::INTEGRATION_CHATKIT_WORKFLOW,
        'chatkit_workflow_id' => 'wf_mismatch',
        'is_active' => true,
    ]);
    CareerTrailStep::query()->where('slug', 'ats')->update(['agent_id' => $agent->id]);
    $jd = AgentDocument::query()->create([
        'user_id' => $user->id,
        'agent_id' => $agent->id,
        'type' => AgentDocument::TYPE_JD,
        'title' => 'JD',
        'body' => 'body',
        'user_cv_id' => $cvA->id,
        'is_active' => true,
    ]);

    $this->actingAs($user)
        ->getJson(route('chat.chatkit.ats-pair-status', [
            'jd_document_id' => $jd->id,
            'user_cv_id' => $cvB->id,
        ]))
        ->assertOk()
        ->assertJsonPath('ok', true)
        ->assertJsonPath('pair_valid', false)
        ->assertJsonPath('allows_ats_flow', true);
});

test('ats chat includes dynamic workspace cta shell', function () {
    $agent = Agent::query()->create([
        'name' => 'ATS ChatKit',
        'price' => 0,
        'model_type' => 'gpt-4o-mini',
        'integration' => Agent::INTEGRATION_CHATKIT_WORKFLOW,
        'chatkit_workflow_id' => 'wf_ats_cta',
        'is_active' => true,
    ]);
    $atsStep = CareerTrailStep::query()->where('slug', 'ats')->first();
    CareerTrailStep::query()->where('slug', 'ats')->update(['agent_id' => $agent->id]);

    $user = User::factory()->create();
    UserCv::query()->create([
        'user_id' => $user->id,
        'title' => 'CV Teste',
        'body' => str_repeat('experiencia scrum agile ', 30),
        'is_default' => true,
        'source' => UserCv::SOURCE_MANUAL,
    ]);

    UserCareerTrailProgress::query()->updateOrCreate(
        ['user_id' => $user->id],
        [
            'current_step_id' => $atsStep->id,
            'max_sort_order_reached' => (int) $atsStep->sort_order,
            'started_at' => now(),
        ]
    );

    $this->actingAs($user)->get(route('career-trail.index'));

    $this->actingAs($user)
        ->get(route('agents.chat', $agent))
        ->assertOk()
        ->assertSee('id="chatkit-ats-workspace-cta"', false)
        ->assertSee('chatkit-ats-workspace-cta-actions', false)
        ->assertSee('persist_ats_analysis', false);
});

test('store redirects to existing chatkit analysis instead of regenerating', function () {
    $agent = Agent::query()->create([
        'name' => 'ATS',
        'price' => 0,
        'model_type' => 'gpt-4o-mini',
        'integration' => Agent::INTEGRATION_CHATKIT_WORKFLOW,
        'chatkit_workflow_id' => 'wf_store_skip',
        'is_active' => true,
    ]);
    CareerTrailStep::query()->where('slug', 'ats')->update(['agent_id' => $agent->id]);

    $user = User::factory()->create();
    $cv = UserCv::query()->create([
        'user_id' => $user->id,
        'title' => 'CV',
        'body' => 'body',
        'is_default' => true,
        'source' => UserCv::SOURCE_MANUAL,
    ]);
    $jd = AgentDocument::query()->create([
        'user_id' => $user->id,
        'agent_id' => $agent->id,
        'type' => AgentDocument::TYPE_JD,
        'title' => 'JD',
        'body' => 'body',
        'user_cv_id' => $cv->id,
        'is_active' => true,
    ]);
    $analysis = AtsAnalysis::query()->create([
        'user_id' => $user->id,
        'agent_document_id' => $jd->id,
        'user_cv_id' => $cv->id,
        'status' => AtsAnalysis::STATUS_READY,
        'source' => AtsAnalysis::SOURCE_CHATKIT_TOOL,
        'ats_score' => 80,
    ]);
    $analysis->items()->create([
        'keyword' => 'ChatKit',
        'relevance' => AtsAnalysisItem::RELEVANCE_HIGH,
        'match_status' => AtsAnalysisItem::MATCH_FULL,
        'priority_rank' => 1,
        'sort_order' => 0,
    ]);

    $this->actingAs($user)
        ->post(route('career-trail.ats.analyses.store'), ['jd_document_id' => $jd->id])
        ->assertRedirect(route('career-trail.ats.workspace', $analysis));

    expect($analysis->fresh()->items)->toHaveCount(1);
    expect($analysis->fresh()->items->first()->keyword)->toBe('ChatKit');
});

test('chatkit sync accepts flexible agent payload', function () {
    $agent = Agent::query()->create([
        'name' => 'ATS',
        'price' => 0,
        'model_type' => 'gpt-4o-mini',
        'integration' => Agent::INTEGRATION_CHATKIT_WORKFLOW,
        'chatkit_workflow_id' => 'wf_flex',
        'is_active' => true,
    ]);
    CareerTrailStep::query()->where('slug', 'ats')->update(['agent_id' => $agent->id]);

    $user = User::factory()->create();
    $cv = UserCv::query()->create([
        'user_id' => $user->id,
        'title' => 'CV',
        'body' => 'body',
        'is_default' => true,
        'source' => UserCv::SOURCE_MANUAL,
    ]);
    $jd = AgentDocument::query()->create([
        'user_id' => $user->id,
        'agent_id' => $agent->id,
        'type' => AgentDocument::TYPE_JD,
        'title' => 'JD',
        'body' => 'body',
        'user_cv_id' => $cv->id,
        'is_active' => true,
    ]);

    $this->actingAs($user)
        ->postJson(route('chat.chatkit.ats-analysis-sync'), [
            'jd_document_id' => $jd->id,
            'user_cv_id' => $cv->id,
            'ats_score' => '68%',
            'items' => [
                [
                    'Keyword' => 'Agile',
                    'Relevance' => 'Alta',
                    'Include/Missing' => 'Ausente',
                    'Comments' => 'Incluir na experiência.',
                ],
            ],
        ])
        ->assertOk()
        ->assertJsonPath('source', AtsAnalysis::SOURCE_CHATKIT_TOOL);

    $analysis = AtsAnalysis::findForPair($user->id, $jd->id, $cv->id);
    expect($analysis)->not->toBeNull();
    expect((float) $analysis->ats_score)->toBe(68.0);
    expect($analysis->items->first()->keyword)->toBe('Agile');
    expect($analysis->items->first()->match_status)->toBe(AtsAnalysisItem::MATCH_MISSING);
});

test('chatkit sync endpoint upserts analysis items', function () {
    $agent = Agent::query()->create([
        'name' => 'ATS',
        'price' => 0,
        'model_type' => 'gpt-4o-mini',
        'integration' => Agent::INTEGRATION_CHATKIT_WORKFLOW,
        'chatkit_workflow_id' => 'wf_sync',
        'is_active' => true,
    ]);
    CareerTrailStep::query()->where('slug', 'ats')->update(['agent_id' => $agent->id]);

    $user = User::factory()->create();
    $cv = UserCv::query()->create([
        'user_id' => $user->id,
        'title' => 'CV',
        'body' => 'body',
        'is_default' => true,
        'source' => UserCv::SOURCE_MANUAL,
    ]);
    $jd = AgentDocument::query()->create([
        'user_id' => $user->id,
        'agent_id' => $agent->id,
        'type' => AgentDocument::TYPE_JD,
        'title' => 'JD',
        'body' => 'body',
        'user_cv_id' => $cv->id,
        'is_active' => true,
    ]);

    $this->actingAs($user)
        ->postJson(route('chat.chatkit.ats-analysis-sync'), [
            'jd_document_id' => $jd->id,
            'user_cv_id' => $cv->id,
            'ats_score' => 72.5,
            'items' => [
                [
                    'keyword' => 'Scrum',
                    'relevance' => 'high',
                    'match_status' => 'missing',
                    'suggestion' => 'Mencione Scrum na experiência.',
                ],
            ],
        ])
        ->assertOk()
        ->assertJsonPath('ok', true);

    $analysis = AtsAnalysis::findForPair($user->id, $jd->id, $cv->id);
    expect($analysis)->not->toBeNull();
    expect((float) $analysis->ats_score)->toBe(72.5);
    expect($analysis->items)->toHaveCount(1);
});

test('reanalyze chat url includes analysis id and reanalyze flag', function () {
    $agent = Agent::query()->create([
        'name' => 'ATS',
        'price' => 0,
        'model_type' => 'gpt-4o-mini',
        'integration' => Agent::INTEGRATION_CHATKIT_WORKFLOW,
        'chatkit_workflow_id' => 'wf_reanalyze',
        'is_active' => true,
    ]);
    CareerTrailStep::query()->where('slug', 'ats')->update(['agent_id' => $agent->id]);

    $user = User::factory()->create();
    $cv = UserCv::query()->create([
        'user_id' => $user->id,
        'title' => 'CV',
        'body' => 'body',
        'is_default' => true,
        'source' => UserCv::SOURCE_MANUAL,
    ]);
    $jd = AgentDocument::query()->create([
        'user_id' => $user->id,
        'agent_id' => $agent->id,
        'type' => AgentDocument::TYPE_JD,
        'title' => 'JD',
        'body' => 'body',
        'user_cv_id' => $cv->id,
        'is_active' => true,
    ]);
    $analysis = AtsAnalysis::query()->create([
        'user_id' => $user->id,
        'agent_document_id' => $jd->id,
        'user_cv_id' => $cv->id,
        'status' => AtsAnalysis::STATUS_READY,
        'source' => AtsAnalysis::SOURCE_CHATKIT_TOOL,
    ]);

    $url = CareerTrailStep::atsAnalyzeChatUrlForJd($user, $agent, (int) $jd->id, (int) $analysis->id);

    expect($url)->not->toBeNull();
    expect($url)->toContain('reanalyze=1');
    expect($url)->toContain('ats_analysis_id='.$analysis->id);
    expect($url)->toContain('auto_ats_pair=1');
});

test('save and reanalyze redirects to chat with reanalyze query', function () {
    $agent = Agent::query()->create([
        'name' => 'ATS',
        'price' => 0,
        'model_type' => 'gpt-4o-mini',
        'integration' => Agent::INTEGRATION_CHATKIT_WORKFLOW,
        'chatkit_workflow_id' => 'wf_reanalyze_redirect',
        'is_active' => true,
    ]);
    CareerTrailStep::query()->where('slug', 'ats')->update(['agent_id' => $agent->id]);

    $user = User::factory()->create();
    $cv = UserCv::query()->create([
        'user_id' => $user->id,
        'title' => 'CV',
        'body' => 'body original',
        'is_default' => true,
        'source' => UserCv::SOURCE_MANUAL,
    ]);
    $jd = AgentDocument::query()->create([
        'user_id' => $user->id,
        'agent_id' => $agent->id,
        'type' => AgentDocument::TYPE_JD,
        'title' => 'JD',
        'body' => 'body',
        'user_cv_id' => $cv->id,
        'is_active' => true,
    ]);
    $analysis = AtsAnalysis::query()->create([
        'user_id' => $user->id,
        'agent_document_id' => $jd->id,
        'user_cv_id' => $cv->id,
        'status' => AtsAnalysis::STATUS_READY,
        'source' => AtsAnalysis::SOURCE_CHATKIT_TOOL,
    ]);

    $response = $this->actingAs($user)->put(route('career-trail.ats.workspace.cv', $analysis), [
        'body' => 'body actualizado com novas keywords',
        'redirect' => 'reanalyze',
    ]);

    $response->assertRedirect();
    $target = (string) $response->headers->get('Location');
    expect($target)->toContain('reanalyze=1');
    expect($target)->toContain('ats_analysis_id='.$analysis->id);

    expect($cv->fresh()->body)->toBe('body actualizado com novas keywords');
});

test('chatkit sync estimates ats score when tool omits score field', function () {
    $agent = Agent::query()->create([
        'name' => 'ATS',
        'price' => 0,
        'model_type' => 'gpt-4o-mini',
        'integration' => Agent::INTEGRATION_CHATKIT_WORKFLOW,
        'chatkit_workflow_id' => 'wf_est_score',
        'is_active' => true,
    ]);
    CareerTrailStep::query()->where('slug', 'ats')->update(['agent_id' => $agent->id]);

    $user = User::factory()->create();
    $cv = UserCv::query()->create([
        'user_id' => $user->id,
        'title' => 'CV',
        'body' => 'body',
        'is_default' => true,
        'source' => UserCv::SOURCE_MANUAL,
    ]);
    $jd = AgentDocument::query()->create([
        'user_id' => $user->id,
        'agent_id' => $agent->id,
        'type' => AgentDocument::TYPE_JD,
        'title' => 'JD',
        'body' => 'body',
        'user_cv_id' => $cv->id,
        'is_active' => true,
    ]);

    $this->actingAs($user)
        ->postJson(route('chat.chatkit.ats-analysis-sync'), [
            'jd_document_id' => $jd->id,
            'user_cv_id' => $cv->id,
            'items' => [
                ['keyword' => 'Scrum', 'relevance' => 'high', 'match_status' => 'full'],
                ['keyword' => 'Docker', 'relevance' => 'high', 'match_status' => 'missing'],
            ],
        ])
        ->assertOk()
        ->assertJsonPath('score_estimated', true)
        ->assertJsonPath('ats_score', 50);

    $analysis = AtsAnalysis::findForPair($user->id, $jd->id, $cv->id);
    expect((float) $analysis->ats_score)->toBe(50.0);
});

test('chatkit sync updates previous ats score on reanalysis', function () {
    $agent = Agent::query()->create([
        'name' => 'ATS',
        'price' => 0,
        'model_type' => 'gpt-4o-mini',
        'integration' => Agent::INTEGRATION_CHATKIT_WORKFLOW,
        'chatkit_workflow_id' => 'wf_re_score',
        'is_active' => true,
    ]);
    CareerTrailStep::query()->where('slug', 'ats')->update(['agent_id' => $agent->id]);

    $user = User::factory()->create();
    $cv = UserCv::query()->create([
        'user_id' => $user->id,
        'title' => 'CV',
        'body' => 'body',
        'is_default' => true,
        'source' => UserCv::SOURCE_MANUAL,
    ]);
    $jd = AgentDocument::query()->create([
        'user_id' => $user->id,
        'agent_id' => $agent->id,
        'type' => AgentDocument::TYPE_JD,
        'title' => 'JD',
        'body' => 'body',
        'user_cv_id' => $cv->id,
        'is_active' => true,
    ]);

    $this->actingAs($user)->postJson(route('chat.chatkit.ats-analysis-sync'), [
        'jd_document_id' => $jd->id,
        'user_cv_id' => $cv->id,
        'ats_score' => 70,
        'items' => [['keyword' => 'A', 'relevance' => 'high', 'match_status' => 'partial']],
    ])->assertOk();

    $this->actingAs($user)->postJson(route('chat.chatkit.ats-analysis-sync'), [
        'jd_document_id' => $jd->id,
        'user_cv_id' => $cv->id,
        'ats_score' => 82,
        'items' => [['keyword' => 'A', 'relevance' => 'high', 'match_status' => 'full']],
    ])->assertOk();

    $analysis = AtsAnalysis::findForPair($user->id, $jd->id, $cv->id);
    expect((float) $analysis->ats_score)->toBe(82.0);
    expect((float) $analysis->previous_ats_score)->toBe(70.0);
});

test('career trail ats list shows last ats percent without keywords line', function () {
    $agent = Agent::query()->create([
        'name' => 'ATS',
        'price' => 0,
        'model_type' => 'gpt-4o-mini',
        'integration' => Agent::INTEGRATION_CHATKIT_WORKFLOW,
        'chatkit_workflow_id' => 'wf_list_pct',
        'is_active' => true,
    ]);
    $atsStep = CareerTrailStep::query()->where('slug', 'ats')->first();
    CareerTrailStep::query()->where('slug', 'ats')->update(['agent_id' => $agent->id]);

    $user = User::factory()->create();
    UserCareerTrailProgress::query()->updateOrCreate(
        ['user_id' => $user->id],
        [
            'current_step_id' => $atsStep->id,
            'max_sort_order_reached' => (int) $atsStep->sort_order,
            'started_at' => now(),
        ]
    );
    $cv = UserCv::query()->create([
        'user_id' => $user->id,
        'title' => 'CV',
        'body' => 'body',
        'is_default' => true,
        'source' => UserCv::SOURCE_MANUAL,
    ]);
    $jd = AgentDocument::query()->create([
        'user_id' => $user->id,
        'agent_id' => $agent->id,
        'type' => AgentDocument::TYPE_JD,
        'title' => 'Vaga ATS',
        'body' => 'body',
        'user_cv_id' => $cv->id,
        'is_active' => true,
        'application_status' => \App\Enums\JobApplicationStatus::Submitted,
    ]);
    AtsAnalysis::query()->create([
        'user_id' => $user->id,
        'agent_document_id' => $jd->id,
        'user_cv_id' => $cv->id,
        'status' => AtsAnalysis::STATUS_READY,
        'source' => AtsAnalysis::SOURCE_CHATKIT_TOOL,
        'ats_score' => 82,
    ]);

    $this->actingAs($user)
        ->get(route('career-trail.ats'))
        ->assertOk()
        ->assertSee('82%', false)
        ->assertSee('Editar com tabela', false)
        ->assertDontSee('keyword(s) em falta', false);
});

test('workspace forbidden when job application status is not draft or submitted', function () {
    $agent = Agent::query()->create([
        'name' => 'ATS',
        'price' => 0,
        'model_type' => 'gpt-4o-mini',
        'integration' => Agent::INTEGRATION_CHATKIT_WORKFLOW,
        'chatkit_workflow_id' => 'wf_ws_forbid',
        'is_active' => true,
    ]);
    $atsStep = CareerTrailStep::query()->where('slug', 'ats')->first();
    CareerTrailStep::query()->where('slug', 'ats')->update(['agent_id' => $agent->id]);

    $user = User::factory()->create();
    UserCareerTrailProgress::query()->updateOrCreate(
        ['user_id' => $user->id],
        [
            'current_step_id' => $atsStep->id,
            'max_sort_order_reached' => (int) $atsStep->sort_order,
            'started_at' => now(),
        ]
    );
    $cv = UserCv::query()->create([
        'user_id' => $user->id,
        'title' => 'CV',
        'body' => 'body',
        'is_default' => true,
        'source' => UserCv::SOURCE_MANUAL,
    ]);
    $jd = AgentDocument::query()->create([
        'user_id' => $user->id,
        'agent_id' => $agent->id,
        'type' => AgentDocument::TYPE_JD,
        'title' => 'JD',
        'body' => 'body',
        'user_cv_id' => $cv->id,
        'is_active' => true,
        'ats_submitted_at' => now()->subDay(),
        'cv_sent_to_employer_at' => now(),
        'application_status' => \App\Enums\JobApplicationStatus::CvSent,
    ]);
    $analysis = AtsAnalysis::query()->create([
        'user_id' => $user->id,
        'agent_document_id' => $jd->id,
        'user_cv_id' => $cv->id,
        'status' => AtsAnalysis::STATUS_READY,
        'source' => AtsAnalysis::SOURCE_CHATKIT_TOOL,
        'ats_score' => 75,
    ]);

    $this->actingAs($user)
        ->get(route('career-trail.ats'))
        ->assertOk()
        ->assertSee('75%', false);

    $jd->refresh();
    expect($jd->application_status)->toBe(\App\Enums\JobApplicationStatus::CvSent);

    $this->actingAs($user)
        ->get(route('career-trail.ats.workspace', $analysis))
        ->assertForbidden();
});

test('workspace forbidden when application status is cv sent even with ats submitted timestamp', function () {
    $agent = Agent::query()->create([
        'name' => 'ATS',
        'price' => 0,
        'model_type' => 'gpt-4o-mini',
        'integration' => Agent::INTEGRATION_CHATKIT_WORKFLOW,
        'chatkit_workflow_id' => 'wf_ws_lag',
        'is_active' => true,
    ]);
    $atsStep = CareerTrailStep::query()->where('slug', 'ats')->first();
    CareerTrailStep::query()->where('slug', 'ats')->update(['agent_id' => $agent->id]);

    $user = User::factory()->create();
    UserCareerTrailProgress::query()->updateOrCreate(
        ['user_id' => $user->id],
        [
            'current_step_id' => $atsStep->id,
            'max_sort_order_reached' => (int) $atsStep->sort_order,
            'started_at' => now(),
        ]
    );
    $cv = UserCv::query()->create([
        'user_id' => $user->id,
        'title' => 'CV',
        'body' => 'body',
        'is_default' => true,
        'source' => UserCv::SOURCE_MANUAL,
    ]);
    $jd = AgentDocument::query()->create([
        'user_id' => $user->id,
        'agent_id' => $agent->id,
        'type' => AgentDocument::TYPE_JD,
        'title' => 'JD',
        'body' => 'body',
        'user_cv_id' => $cv->id,
        'is_active' => true,
        'ats_submitted_at' => now(),
        'cv_sent_to_employer_at' => now(),
    ]);
    $jd->forceFill(['application_status' => \App\Enums\JobApplicationStatus::CvSent])->saveQuietly();
    $jd->refresh();
    expect($jd->application_status)->toBe(\App\Enums\JobApplicationStatus::CvSent)
        ->and($jd->allowsAtsFlow())->toBeFalse();

    $this->actingAs($user)
        ->getJson(route('chat.chatkit.ats-pair-status', [
            'jd_document_id' => $jd->id,
            'user_cv_id' => $cv->id,
        ]))
        ->assertOk()
        ->assertJsonPath('pair_valid', false)
        ->assertJsonPath('allows_ats_flow', false);

    $analysis = AtsAnalysis::query()->create([
        'user_id' => $user->id,
        'agent_document_id' => $jd->id,
        'user_cv_id' => $cv->id,
        'status' => AtsAnalysis::STATUS_READY,
        'source' => AtsAnalysis::SOURCE_CHATKIT_TOOL,
        'ats_score' => 80,
    ]);

    $this->actingAs($user)
        ->get(route('career-trail.ats.workspace', $analysis))
        ->assertForbidden();

    expect(CareerTrailStep::atsAnalyzeChatUrlForJd($user, $agent, (int) $jd->id))->toBeNull();
});

test('chatkit sync accepts raw_table_text without items array', function () {
    $agent = Agent::query()->create([
        'name' => 'ATS Paste',
        'price' => 0,
        'model_type' => 'gpt-4o-mini',
        'integration' => Agent::INTEGRATION_CHATKIT_WORKFLOW,
        'chatkit_workflow_id' => 'wf_paste',
        'is_active' => true,
    ]);
    CareerTrailStep::query()->where('slug', 'ats')->update(['agent_id' => $agent->id]);

    $user = User::factory()->create();
    $cv = UserCv::query()->create([
        'user_id' => $user->id,
        'title' => 'CV',
        'body' => 'body',
        'is_default' => true,
        'source' => UserCv::SOURCE_MANUAL,
    ]);
    $jd = AgentDocument::query()->create([
        'user_id' => $user->id,
        'agent_id' => $agent->id,
        'type' => AgentDocument::TYPE_JD,
        'title' => 'JD',
        'body' => 'body',
        'user_cv_id' => $cv->id,
        'is_active' => true,
    ]);

    $markdown = "| Keyword | Status |\n| --- | --- |\n| Agile | Parcial |";

    $this->actingAs($user)
        ->postJson(route('chat.chatkit.ats-analysis-sync'), [
            'jd_document_id' => $jd->id,
            'user_cv_id' => $cv->id,
            'raw_table_text' => $markdown,
        ])
        ->assertOk()
        ->assertJsonPath('source', AtsAnalysis::SOURCE_CHATKIT_TOOL);

    expect(AtsAnalysis::findForPair($user->id, $jd->id, $cv->id)?->items)->toHaveCount(1);
});

test('chatkit document defaults ignores legacy boolean cv id from client', function () {
    $agent = Agent::query()->create([
        'name' => 'ATS Defaults',
        'price' => 0,
        'model_type' => 'gpt-4o-mini',
        'integration' => Agent::INTEGRATION_CHATKIT_WORKFLOW,
        'chatkit_workflow_id' => 'wf_ats_def',
        'is_active' => true,
    ]);
    $atsStep = CareerTrailStep::query()->where('slug', 'ats')->firstOrFail();
    $atsStep->update(['agent_id' => $agent->id]);

    $user = User::factory()->create();
    UserCareerTrailProgress::query()->updateOrCreate(
        ['user_id' => $user->id],
        [
            'current_step_id' => $atsStep->id,
            'max_sort_order_reached' => (int) $atsStep->sort_order,
            'started_at' => now(),
        ]
    );

    $this->actingAs($user)
        ->postJson(route('agents.documents.defaults', $agent), [
            'default_cv_document_id' => true,
        ])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonFragment([
            'message' => 'CV de perfil não usa as preferências padrão desta biblioteca.',
        ]);
});
