<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgentRating extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'agent_id',
        'chat_session_id',
        'rating',
        'comment',
    ];

    /**
     * Obter o usuário que fez esta avaliação
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Obter o agente avaliado
     */
    public function agent()
    {
        return $this->belongsTo(Agent::class);
    }

    /**
     * Obter a sessão de chat relacionada
     */
    public function chatSession()
    {
        return $this->belongsTo(ChatSession::class, 'chat_session_id');
    }
}