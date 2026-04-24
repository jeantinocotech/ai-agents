<?php

namespace App\Support;

final class ChatKitUserRef
{
    /**
     * @return array{user_id: int, agent_id: int}|null
     */
    public static function parse(?string $ref): ?array
    {
        if ($ref === null || $ref === '') {
            return null;
        }

        if (preg_match('/^user_(\d+)_agent_(\d+)$/', trim($ref), $m)) {
            return [
                'user_id' => (int) $m[1],
                'agent_id' => (int) $m[2],
            ];
        }

        return null;
    }

    public static function build(int $userId, int $agentId): string
    {
        return 'user_'.$userId.'_agent_'.$agentId;
    }
}
