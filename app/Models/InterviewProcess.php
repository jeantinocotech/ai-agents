<?php

namespace App\Models;

use App\Enums\InterviewApplicationOutcome;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InterviewProcess extends Model
{
    protected $fillable = [
        'user_id',
        'jd_document_id',
        'outcome',
    ];

    protected function casts(): array
    {
        return [
            'outcome' => InterviewApplicationOutcome::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function jdDocument(): BelongsTo
    {
        return $this->belongsTo(AgentDocument::class, 'jd_document_id');
    }
}
