<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserCareerTrailProgress extends Model
{
    protected $table = 'user_career_trail_progress';

    protected $fillable = [
        'user_id',
        'current_step_id',
        'started_at',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function currentStep(): BelongsTo
    {
        return $this->belongsTo(CareerTrailStep::class, 'current_step_id');
    }
}
