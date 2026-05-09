<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GamificationScoreEvent extends Model
{
    protected $table = 'gamification_score_events';

    protected $fillable = [
        'user_id',
        'event_key',
        'reference_type',
        'reference_id',
        'meta',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'reference_id' => 'integer',
            'meta' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
