<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GamificationRank extends Model
{
    protected $table = 'gamification_ranks';

    protected $fillable = [
        'min_points',
        'title',
        'icon_key',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'min_points' => 'integer',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
