<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

class AgentDocument extends Model
{
    public const TYPE_CV = 'cv';

    public const TYPE_JD = 'jd';

    protected $fillable = [
        'user_id',
        'agent_id',
        'type',
        'title',
        'body',
        'paired_cv_document_id',
    ];

    protected static function booted(): void
    {
        static::saving(function (AgentDocument $doc) {
            if ($doc->type === self::TYPE_CV) {
                $doc->paired_cv_document_id = null;
            }
            if ($doc->type === self::TYPE_JD && $doc->paired_cv_document_id !== null) {
                $pair = self::query()->whereKey($doc->paired_cv_document_id)->first();
                if (
                    ! $pair
                    || $pair->type !== self::TYPE_CV
                    || (int) $pair->user_id !== (int) $doc->user_id
                    || (int) $pair->agent_id !== (int) $doc->agent_id
                ) {
                    throw ValidationException::withMessages([
                        'paired_cv_document_id' => 'O CV associado é inválido.',
                    ]);
                }
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function pairedCv(): BelongsTo
    {
        return $this->belongsTo(self::class, 'paired_cv_document_id');
    }

    public function jdsUsingThisCv(): HasMany
    {
        return $this->hasMany(self::class, 'paired_cv_document_id');
    }
}
