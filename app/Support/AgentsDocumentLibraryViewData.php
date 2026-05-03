<?php

namespace App\Support;

use App\Models\Agent;
use App\Models\AgentDocument;
use App\Models\AgentDocumentDefault;
use App\Models\User;
use App\Models\UserCv;

final class AgentsDocumentLibraryViewData
{
    /**
     * @return array{
     *     profileCvs: \Illuminate\Support\Collection<int, UserCv>,
     *     jds: \Illuminate\Support\Collection<int, AgentDocument>,
     *     defaults: AgentDocumentDefault|null,
     *     maxCvBodyChars: int,
     *     maxJdBodyChars: int
     * }
     */
    public static function payload(User $user, Agent $agent): array
    {
        $profileCvs = UserCv::query()
            ->where('user_id', $user->id)
            ->orderByDesc('is_default')
            ->orderByDesc('updated_at')
            ->get();

        $jds = AgentDocument::query()
            ->where('user_id', $user->id)
            ->where('agent_id', $agent->id)
            ->where('type', AgentDocument::TYPE_JD)
            ->with('userCv')
            ->orderByDesc('updated_at')
            ->get();

        $defaults = AgentDocumentDefault::query()
            ->where('user_id', $user->id)
            ->where('agent_id', $agent->id)
            ->with(['defaultJdDocument'])
            ->first();

        return [
            'profileCvs' => $profileCvs,
            'jds' => $jds,
            'defaults' => $defaults,
            'maxCvBodyChars' => AgentDocumentLimits::maxCharsForType(AgentDocument::TYPE_CV),
            'maxJdBodyChars' => AgentDocumentLimits::maxCharsForType(AgentDocument::TYPE_JD),
        ];
    }
}
