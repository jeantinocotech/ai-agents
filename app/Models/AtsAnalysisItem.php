<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AtsAnalysisItem extends Model
{
    public const RELEVANCE_HIGH = 'high';

    public const RELEVANCE_MEDIUM = 'medium';

    public const RELEVANCE_LOW = 'low';

    public const MATCH_FULL = 'full';

    public const MATCH_PARTIAL = 'partial';

    public const MATCH_MISSING = 'missing';

    protected $fillable = [
        'ats_analysis_id',
        'keyword',
        'relevance',
        'match_status',
        'cv_snippet',
        'suggestion',
        'is_addressed',
        'priority_rank',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_addressed' => 'boolean',
            'priority_rank' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function analysis(): BelongsTo
    {
        return $this->belongsTo(AtsAnalysis::class, 'ats_analysis_id');
    }

    public function matchStatusLabel(): string
    {
        return match ($this->match_status) {
            self::MATCH_FULL => 'OK',
            self::MATCH_PARTIAL => 'Parcial',
            default => 'Em falta',
        };
    }

    public function relevanceLabel(): string
    {
        return match ($this->relevance) {
            self::RELEVANCE_HIGH => 'Alta',
            self::RELEVANCE_LOW => 'Baixa',
            default => 'Média',
        };
    }
}
