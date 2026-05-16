<?php

namespace App\Models;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CareerTrailStep extends Model
{
    protected $fillable = [
        'slug',
        'sort_order',
        'agent_id',
        'title',
        'short_description',
        'graca_guidance',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function progressRows(): HasMany
    {
        return $this->hasMany(UserCareerTrailProgress::class, 'current_step_id');
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    /**
     * Blocos de texto «Graça» (avatar + corpo) associados a este passo.
     *
     * @return HasMany<CareerTrailGracaMessage, $this>
     */
    public function gracaMessages(): HasMany
    {
        return $this->hasMany(CareerTrailGracaMessage::class, 'career_trail_step_id');
    }

    /**
     * Agente Laravel associado à etapa (coluna `agent_id` em `career_trail_steps`), com fallback por .env por slug.
     */
    public function resolvedAgent(): ?Agent
    {
        if ($this->agent_id) {
            return $this->agent ?? Agent::query()->find($this->agent_id);
        }

        $map = config('career_trail.step_agent_ids', []);
        $mappedId = $map[$this->slug] ?? null;
        if ($mappedId) {
            return Agent::query()->find((int) $mappedId);
        }

        return null;
    }

    /**
     * Texto do pill na barra fixa da trilha (sem truncar; títulos curtos vêm da coluna title / seeder).
     */
    public function trailBarLinkLabel(): string
    {
        return match ($this->slug) {
            'cv' => (string) ($this->title ?: 'Curriculum'),
            'ats' => (string) ($this->title ?: 'ATS'),
            'interviews' => (string) ($this->title ?: 'Entrevista'),
            default => $this->title ?? '',
        };
    }

    /**
     * Etapa de destino após ATS concluído (par CV/JD): Entrevistas se existir, senão Motivação.
     * Alinha com CareerTrailController::advance e com o auto-progresso em ensureProgress.
     */
    public static function landingStepAfterCompletedAts(): ?self
    {
        $interview = static::query()
            ->where('is_active', true)
            ->where('slug', 'interviews')
            ->first();
        $motivation = static::query()
            ->where('is_active', true)
            ->where('slug', 'cover-letter')
            ->first();

        return $interview ?? $motivation;
    }

    /**
     * URL do assistente ChatKit do passo «cv» (agente ligado em `career_trail_steps.agent_id` na administração).
     * Sem `no_documents`: a biblioteca mostra só CV (modo compacto da trilha), como nos outros chats de etapa.
     * Com `$forIframe` true: `embedded=1` para iframes (ex. modal em `/trilha/cv`).
     */
    public static function cvEmbeddedCreatorChatUrl(bool $forIframe = false): ?string
    {
        $step = static::query()
            ->where('is_active', true)
            ->where('slug', 'cv')
            ->first();

        $assistant = $step?->resolvedAgent();

        if (! $assistant || ! $assistant->is_active || ! $assistant->isChatKitWorkflow()) {
            return null;
        }

        $url = route('agents.chat', $assistant);

        return $forIframe ? $url.'?embedded=1' : $url;
    }

    /**
     * Chat do assistente de CV (página completa) com envio automático do CV de perfil (query user_cv_id + auto_send).
     */
    public static function cvAnalyzeChatUrlForUserCv(int $userCvId): ?string
    {
        $url = static::cvEmbeddedCreatorChatUrl(forIframe: false);
        if ($url === null) {
            return null;
        }

        $sep = str_contains($url, '?') ? '&' : '?';

        return $url.$sep.http_build_query([
            'user_cv_id' => $userCvId,
            'auto_send' => '1',
        ]);
    }

    /**
     * Chat ATS (ChatKit) com envio automático do par CV de perfil + vaga (JD): query lida em chatkit-main-scripts.
     */
    public static function atsAnalyzeChatUrlForJd(
        Authenticatable $user,
        Agent $atsAgent,
        int $jdDocumentId,
        ?int $atsAnalysisId = null,
    ): ?string {
        if (! $atsAgent->is_active || ! $atsAgent->isChatKitWorkflow()) {
            return null;
        }

        $jd = AgentDocument::query()
            ->whereKey($jdDocumentId)
            ->where('user_id', (int) $user->getAuthIdentifier())
            ->where('agent_id', (int) $atsAgent->getKey())
            ->where('type', AgentDocument::TYPE_JD)
            ->where('is_active', true)
            ->first();

        if ($jd === null || ! $jd->user_cv_id || ! $jd->allowsAtsFlow()) {
            return null;
        }

        $url = route('agents.chat', $atsAgent);
        $sep = str_contains($url, '?') ? '&' : '?';

        $query = [
            'jd_document_id' => $jdDocumentId,
            'profile_cv_id' => (int) $jd->user_cv_id,
            'auto_ats_pair' => '1',
        ];

        if ($atsAnalysisId !== null && $atsAnalysisId > 0) {
            $query['reanalyze'] = '1';
            $query['ats_analysis_id'] = $atsAnalysisId;
        }

        return $url.$sep.http_build_query($query);
    }

    /**
     * URL principal da etapa na trilha: passo CV → página «Meu CV» com assistente embutido;
     * demais passos → chat do agente quando configurado.
     */
    public function trailChatUrl(): ?string
    {
        if ($this->slug === 'cv') {
            return route('career-trail.cv');
        }

        if ($this->slug === 'ats') {
            return route('career-trail.ats');
        }

        $agent = $this->resolvedAgent();
        if (! $agent || ! $agent->is_active) {
            return null;
        }

        if ($this->slug === 'cover-letter') {
            return route('agents.motivation-letters.index', $agent);
        }

        if ($this->slug === 'interviews') {
            return route('agents.interview-preparations.index', $agent);
        }

        return route('agents.chat', $agent);
    }

    public static function firstActiveByOrder(): ?self
    {
        return static::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->first();
    }
}
