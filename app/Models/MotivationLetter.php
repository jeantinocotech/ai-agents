<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MotivationLetter extends Model
{
    public const SOURCE_MANUAL = 'manual';

    public const SOURCE_GENERATED = 'generated';

    protected $fillable = [
        'user_id',
        'jd_document_id',
        'title',
        'body',
        'source',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function jdDocument(): BelongsTo
    {
        return $this->belongsTo(AgentDocument::class, 'jd_document_id');
    }
}
