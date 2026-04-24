<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentDocumentDefault extends Model
{
    protected $fillable = [
        'user_id',
        'agent_id',
        'default_cv_document_id',
        'default_jd_document_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function defaultCvDocument(): BelongsTo
    {
        return $this->belongsTo(AgentDocument::class, 'default_cv_document_id');
    }

    public function defaultJdDocument(): BelongsTo
    {
        return $this->belongsTo(AgentDocument::class, 'default_jd_document_id');
    }
}
