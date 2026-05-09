<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GamificationBadgeDefinition extends Model
{
    protected $table = 'gamification_badge_definitions';

    protected $fillable = [
        'key',
        'label',
        'counter_label',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function levels(): HasMany
    {
        return $this->hasMany(GamificationBadgeLevel::class, 'badge_definition_id')
            ->orderBy('threshold');
    }
}
