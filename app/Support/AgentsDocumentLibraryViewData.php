<?php

namespace App\Support;

use App\Enums\InterviewApplicationOutcome;
use App\Models\Agent;
use App\Models\AgentDocument;
use App\Models\AgentDocumentDefault;
use App\Models\User;
use App\Models\UserCv;
use App\Services\JobApplicationStatusSync;

final class AgentsDocumentLibraryViewData
{
    /**
     * @return array{
     *     profileCvs: \Illuminate\Support\Collection<int, UserCv>,
     *     jds: \Illuminate\Support\Collection<int, AgentDocument>,
     *     activeJds: \Illuminate\Support\Collection<int, AgentDocument>,
     *     inactiveJds: \Illuminate\Support\Collection<int, AgentDocument>,
     *     defaults: AgentDocumentDefault|null,
     *     maxCvBodyChars: int,
     *     maxJdBodyChars: int,
     *     inactiveJdCount: int
     * }
     */
    public static function payload(User $user, Agent $agent): array
    {
        $userId = (int) $user->id;

        $profileCvs = UserCv::query()
            ->where('user_id', $user->id)
            ->orderByDesc('is_default')
            ->orderByDesc('updated_at')
            ->get();

        $activeJds = AgentDocument::query()
            ->where('user_id', $user->id)
            ->where('agent_id', $agent->id)
            ->where('type', AgentDocument::TYPE_JD)
            ->where('is_active', true)
            ->with('userCv')
            ->withCount([
                'interviewPreparations' => fn ($q) => $q->where('user_id', $userId),
            ])
            ->withExists([
                'interviewProcesses' => fn ($q) => $q
                    ->where('user_id', $userId)
                    ->where('outcome', InterviewApplicationOutcome::Ongoing),
            ])
            ->orderByDesc('updated_at')
            ->get()
            ->each(function (AgentDocument $jd): void {
                JobApplicationStatusSync::reconcile($jd);
            });

        $inactiveJds = AgentDocument::query()
            ->where('user_id', $user->id)
            ->where('agent_id', $agent->id)
            ->where('type', AgentDocument::TYPE_JD)
            ->where('is_active', false)
            ->with('userCv')
            ->withCount([
                'interviewPreparations' => fn ($q) => $q->where('user_id', $userId),
            ])
            ->withExists([
                'interviewProcesses' => fn ($q) => $q
                    ->where('user_id', $userId)
                    ->where('outcome', InterviewApplicationOutcome::Ongoing),
            ])
            ->orderByDesc('updated_at')
            ->get();

        $defaults = AgentDocumentDefault::query()
            ->where('user_id', $user->id)
            ->where('agent_id', $agent->id)
            ->with(['defaultJdDocument'])
            ->first();

        return [
            'profileCvs' => $profileCvs,
            'jds' => $activeJds,
            'activeJds' => $activeJds,
            'inactiveJds' => $inactiveJds,
            'defaults' => $defaults,
            'maxCvBodyChars' => AgentDocumentLimits::maxCharsForType(AgentDocument::TYPE_CV),
            'maxJdBodyChars' => AgentDocumentLimits::maxCharsForType(AgentDocument::TYPE_JD),
            'inactiveJdCount' => $inactiveJds->count(),
        ];
    }
}
