<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Agent extends Model
{
    use HasFactory;

    public const INTEGRATION_OPENAI = 'openai';

    public const INTEGRATION_CHATKIT_WORKFLOW = 'chatkit_workflow';

    protected $fillable = [
        'name',
        'description',
        'image_path',
        'youtube_video_id',
        'api_key',
        'model_type',
        'integration',
        'chatkit_workflow_id',
        'chatkit_workflow_version',
        'price',
        'organization',
        'project_id',
        'assistant_id',
        'system_prompt',
        'is_active',
        'asaas_product_id',
    ];

    /**
     * Os atributos que devem ser ocultados para arrays.
     *
     * @var array
     */
    protected $hidden = [
        'api_key',
    ];

    /**
     * Obter a URL completa do vídeo do YouTube.
     *
     * @return string
     */
    public function getYoutubeUrlAttribute()
    {
        return 'https://www.youtube.com/embed/'.$this->youtube_video_id;
    }

    public function steps()
    {
        return $this->hasMany(AgentStep::class)->orderBy('step_order');
    }

    // app/Models/Agent.php
    public function purchases()
    {
        return $this->hasMany(Purchase::class, 'agent_id');
    }

    // Relacionamento com sessões de chat
    public function chatSessions()
    {
        return $this->hasMany(ChatSession::class, 'agent_id');
    }

    // Relacionamento com avaliações
    public function ratings()
    {
        return $this->hasMany(AgentRating::class, 'agent_id');
    }

    // Obter média de avaliações
    public function getAverageRatingAttribute()
    {
        return $this->ratings()->avg('rating') ?? 0;
    }

    public function isChatKitWorkflow(): bool
    {
        return $this->integration === self::INTEGRATION_CHATKIT_WORKFLOW;
    }
}
