<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AtsAnalysis extends Model
{
    public const SOURCE_LLM = 'llm';

    public const SOURCE_MANUAL = 'manual';

    public const SOURCE_CHATKIT_TOOL = 'chatkit_tool';

    public const STATUS_PENDING = 'pending';

    public const STATUS_READY = 'ready';

    protected $fillable = [
        'user_id',
        'agent_document_id',
        'user_cv_id',
        'ats_score',
        'previous_ats_score',
        'status',
        'summary',
        'source',
    ];

    protected function casts(): array
    {
        return [
            'ats_score' => 'decimal:2',
            'previous_ats_score' => 'decimal:2',
            'summary' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function jdDocument(): BelongsTo
    {
        return $this->belongsTo(AgentDocument::class, 'agent_document_id');
    }

    public function userCv(): BelongsTo
    {
        return $this->belongsTo(UserCv::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(AtsAnalysisItem::class)->orderBy('priority_rank')->orderBy('sort_order');
    }

    public function pendingItemsCount(): int
    {
        return $this->items()
            ->where('is_addressed', false)
            ->whereIn('match_status', [AtsAnalysisItem::MATCH_MISSING, AtsAnalysisItem::MATCH_PARTIAL])
            ->count();
    }

    public static function findForPair(int $userId, int $jdDocumentId, int $userCvId): ?self
    {
        return static::query()
            ->where('user_id', $userId)
            ->where('agent_document_id', $jdDocumentId)
            ->where('user_cv_id', $userCvId)
            ->first();
    }
}
