<?php

namespace App\Support;

use App\Models\AgentDocument;
use Illuminate\Validation\ValidationException;

final class AgentDocumentLimits
{
    public static function maxCharsForType(string $type): int
    {
        return match ($type) {
            AgentDocument::TYPE_JD => max(1000, (int) config('agent_documents.max_jd_body_chars', 60000)),
            default => max(1000, (int) config('agent_documents.max_cv_body_chars', 20000)),
        };
    }

    public static function assertBodyWithinLimit(string $type, string $body): void
    {
        $max = self::maxCharsForType($type);
        if (mb_strlen($body) > $max) {
            throw ValidationException::withMessages([
                'body' => 'O texto excede o máximo permitido ('.$max.' caracteres) para '.($type === AgentDocument::TYPE_JD ? 'vagas (JD)' : 'CV').'.',
            ]);
        }
    }
}
