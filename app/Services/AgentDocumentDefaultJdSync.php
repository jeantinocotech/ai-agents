<?php

namespace App\Services;

use App\Models\AgentDocument;
use App\Models\AgentDocumentDefault;

/**
 * Mantém default_jd_document_id alinhado ao último JD «activo» (guardado mais recentemente),
 * para o ChatKit quando o utilizador não abre o fluxo com jd_document_id explícito na URL.
 */
final class AgentDocumentDefaultJdSync
{
    public static function sync(int $userId, int $agentId, ?int $preferredJdId = null): void
    {
        if ($preferredJdId !== null) {
            $exists = AgentDocument::query()
                ->whereKey($preferredJdId)
                ->where('user_id', $userId)
                ->where('agent_id', $agentId)
                ->where('type', AgentDocument::TYPE_JD)
                ->where('is_active', true)
                ->exists();
            if ($exists) {
                self::writeDefault($userId, $agentId, $preferredJdId);

                return;
            }
        }

        $latest = AgentDocument::query()
            ->where('user_id', $userId)
            ->where('agent_id', $agentId)
            ->where('type', AgentDocument::TYPE_JD)
            ->where('is_active', true)
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->first();

        $defaults = AgentDocumentDefault::query()->firstOrCreate(
            ['user_id' => $userId, 'agent_id' => $agentId],
            []
        );

        if ($latest === null) {
            $defaults->default_jd_document_id = null;
            $defaults->save();

            return;
        }

        $defaults->default_jd_document_id = (int) $latest->getKey();
        $defaults->save();
    }

    private static function writeDefault(int $userId, int $agentId, int $jdId): void
    {
        $defaults = AgentDocumentDefault::query()->firstOrCreate(
            ['user_id' => $userId, 'agent_id' => $agentId],
            []
        );
        $defaults->default_jd_document_id = $jdId;
        $defaults->save();
    }
}
