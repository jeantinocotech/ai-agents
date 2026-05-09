<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GamificationBadgeLevel extends Model
{
    protected $table = 'gamification_badge_levels';

    protected $fillable = [
        'badge_definition_id',
        'threshold',
        'title',
        'icon_key',
        'color_key',
    ];

    protected function casts(): array
    {
        return [
            'badge_definition_id' => 'integer',
            'threshold' => 'integer',
        ];
    }

    public function definition(): BelongsTo
    {
        return $this->belongsTo(GamificationBadgeDefinition::class, 'badge_definition_id');
    }
}
