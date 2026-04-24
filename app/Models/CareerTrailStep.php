<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CareerTrailStep extends Model
{
    protected $fillable = [
        'slug',
        'sort_order',
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

    public static function firstActiveByOrder(): ?self
    {
        return static::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->first();
    }
}
