<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserCv extends Model
{
    public const SOURCE_MANUAL = 'manual';

    public const SOURCE_UPLOAD = 'upload';

    /** CV criado na conta a partir de uma cópia da biblioteca de um agente (ex.: ATS). */
    public const SOURCE_AGENT_IMPORT = 'agent_import';

    protected $fillable = [
        'user_id',
        'title',
        'body',
        'is_default',
        'source',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function defaultForUserId(int $userId): ?self
    {
        return static::query()
            ->where('user_id', $userId)
            ->where('is_default', true)
            ->orderByDesc('updated_at')
            ->first();
    }
}
