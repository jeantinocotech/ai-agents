<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CareerTrailGracaMessage extends Model
{
    protected $table = 'career_trail_graca_messages';

    protected $fillable = [
        'process_key',
        'career_trail_step_id',
        'slot',
        'body',
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

    public function step(): BelongsTo
    {
        return $this->belongsTo(CareerTrailStep::class, 'career_trail_step_id');
    }
}
