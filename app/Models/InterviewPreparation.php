<?php

namespace App\Models;

use App\Enums\InterviewPersona;
use App\Enums\InterviewProcessStatus;
use App\Services\InterviewProcessOutcomeService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InterviewPreparation extends Model
{
    protected static function booted(): void
    {
        static::saved(static function (InterviewPreparation $model): void {
            InterviewProcessOutcomeService::syncAfterPreparationMutation(
                (int) $model->user_id,
                (int) $model->jd_document_id
            );
        });

        static::deleted(static function (InterviewPreparation $model): void {
            InterviewProcessOutcomeService::syncAfterPreparationMutation(
                (int) $model->user_id,
                (int) $model->jd_document_id
            );
        });
    }

    protected $fillable = [
        'user_id',
        'jd_document_id',
        'sequence',
        'persona',
        'status',
        'chat_prep_messages',
        'learnings',
    ];

    protected function casts(): array
    {
        return [
            'sequence' => 'integer',
            'persona' => InterviewPersona::class,
            'status' => InterviewProcessStatus::class,
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
