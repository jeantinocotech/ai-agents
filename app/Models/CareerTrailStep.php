<?php

namespace App\Models;

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
     * Agente Laravel associado à etapa.
     *
     * Passo «cv» (1): usa sempre o assistente ChatKit de criação de CV (env CAREER_TRAIL_CV_CHATKIT_AGENT_ID),
     * distinto do assistente ATS (CV+JD no passo seguinte). A coluna agent_id neste passo não deve sobrepor o env.
     */
    public function resolvedAgent(): ?Agent
    {
        if ($this->slug === 'cv') {
            $cvId = config('career_trail.cv_chatkit_agent_id');
            if ($cvId) {
                return Agent::query()->find((int) $cvId);
            }

            if ($this->agent_id) {
                return $this->agent ?? Agent::query()->find($this->agent_id);
            }

            return null;
        }

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
            'cv' => 'CV',
            'ats' => 'ATS',
            'interviews' => 'Entrevista',
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
     * URL do ChatKit do CV Creator (CAREER_TRAIL_CV_CHATKIT_AGENT_ID).
     * Por defeito só envia `no_documents=1`: usa o mesmo layout de aplicação que o chat ATS (trilha, saldo, tokens).
     * Com `$forIframe` true acrescenta `embedded=1` para usar em iframes (ex.: modal em `/trilha/cv`).
     */
    public static function cvEmbeddedCreatorChatUrl(bool $forIframe = false): ?string
    {
        $assistantAgentId = config('career_trail.cv_chatkit_agent_id');
        if (! $assistantAgentId) {
            return null;
        }

        $assistant = Agent::query()
            ->whereKey((int) $assistantAgentId)
            ->where('is_active', true)
            ->first();

        if (! $assistant || ! $assistant->isChatKitWorkflow()) {
            return null;
        }

        $url = route('agents.chat', $assistant).'?no_documents=1';

        return $forIframe ? $url.'&embedded=1' : $url;
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
