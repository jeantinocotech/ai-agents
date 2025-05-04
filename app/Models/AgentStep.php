<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AgentStep extends Model
{
    use HasFactory;

    protected $fillable = [
        'agent_id',
        'step_order',
        'name',
        'required_input',
        'expected_keywords',
        'system_message',
        'can_continue',
    ];

    protected $casts = [
        'expected_keywords' => 'array',
        'can_continue' => 'boolean',
    ];

    public function agent()
    {
        return $this->belongsTo(Agent::class);
    }
}
