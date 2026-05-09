<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserGamificationSnapshot extends Model
{
    protected $table = 'user_gamification_snapshots';

    protected $fillable = [
        'user_id',
        'score_total',
        'rank_id',
        'badges_state',
        'definitions_hash',
        'computed_at',
        'last_event_at',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'score_total' => 'integer',
            'rank_id' => 'integer',
            'badges_state' => 'array',
            'computed_at' => 'datetime',
            'last_event_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function rank(): BelongsTo
    {
        return $this->belongsTo(GamificationRank::class, 'rank_id');
    }
}
