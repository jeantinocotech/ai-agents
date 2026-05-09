<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GamificationScoreEventDefinition extends Model
{
    protected $table = 'gamification_score_event_definitions';

    protected $fillable = [
        'key',
        'label',
        'points',
        'daily_cap',
        'is_active',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'points' => 'integer',
            'daily_cap' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
