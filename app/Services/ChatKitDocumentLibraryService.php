<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\AgentDocument;
use App\Models\AgentDocumentDefault;
use App\Models\CareerTrailStep;
use App\Models\UserCv;
use App\Support\AgentDocumentLimits;

final class ChatKitDocumentLibraryService
{
    /**
     * Biblioteca ChatKit:
     * - CV sempre de user_cvs (perfil).
     * - JD em agent_documents: nas etapas da trilha após/definidas com ATS, reusar JDs gravadas na biblioteca
     *   do agente ATS (única fonte / processo CV+JD ligado ao ATS).
     * - Agente só na trilha mas não ATS: apenas JD próprias do agente actual.
     *
     * @return array<string, mixed>|null
     */
    public static function forUserAndAgent(int $userId, Agent $agent): ?array
    {
        if (! $agent->isChatKitWorkflow()) {
            return null;
        }

        $step = CareerTrailAgentAccess::trailStepBoundToAgent($agent);
        $atsStep = CareerTrailStep::query()
            ->where('slug', 'ats')
            ->where('is_active', true)
            ->first();
        $atsResolved = $atsStep?->resolvedAgent();

        $jdQueryAgentId = (int) $agent->getKey();
        $documentsHubAgentId = $jdQueryAgentId;

        if ($step !== null && $step->slug !== 'cv') {
            if ($step->slug === 'ats') {
                $jdQueryAgentId = (int) $agent->getKey();
                $documentsHubAgentId = $jdQueryAgentId;
            } elseif ($atsResolved !== null && $atsResolved->is_active) {
                $jdQueryAgentId = (int) $atsResolved->getKey();
                $documentsHubAgentId = $jdQueryAgentId;
            }
        }

        $profileCvs = UserCv::query()
            ->where('user_id', $userId)
            ->orderByDesc('is_default')
            ->orderByDesc('updated_at')
            ->get(['id', 'title', 'is_default']);

        $cvsMapped = $profileCvs->map(function (UserCv $cv) {
            $title = $cv->title !== null && trim((string) $cv->title) !== ''
                ? (string) $cv->title
                : 'CV do perfil';
            $suffix = ' (perfil #'.$cv->id.')'.($cv->is_default ? ' — padrão' : '');

            return [
                'id' => 'p'.$cv->id,
                'title' => $title.$suffix,
            ];
        })->values();

        $jds = AgentDocument::query()
            ->where('user_id', $userId)
            ->where('agent_id', $jdQueryAgentId)
            ->where('type', AgentDocument::TYPE_JD)
            ->where('is_active', true)
            ->with(['userCv:id,title,is_default'])
            ->orderByDesc('updated_at')
            ->get(['id', 'title', 'user_cv_id', 'application_status']);

        $defaultsRow = AgentDocumentDefault::query()
            ->where('user_id', $userId)
            ->where('agent_id', $jdQueryAgentId)
            ->first(['default_jd_document_id']);

        $jdRows = $jds->map(function (AgentDocument $d) use ($jdQueryAgentId) {
            $paired = $d->userCv;
            $pairedLabel = null;
            if ($paired) {
                $pairedLabel = $paired->title !== null && trim((string) $paired->title) !== ''
                    ? (string) $paired->title
                    : 'CV do perfil #'.$paired->id;
            }

            return [
                'id' => $d->id,
                'title' => $d->title !== null && trim((string) $d->title) !== ''
                    ? (string) $d->title
                    : 'Vaga #'.$d->id,
                'user_cv_id' => $d->user_cv_id ? (int) $d->user_cv_id : null,
                'paired_cv_document_id' => $d->user_cv_id ? 'p'.$d->user_cv_id : null,
                'paired_cv_label' => $pairedLabel,
                'content_agent_id' => $jdQueryAgentId,
                'allows_ats_flow' => $d->allowsAtsFlow(),
                'ats_flow_block_message' => $d->atsFlowBlockReason(),
            ];
        })->values()->all();

        return [
            'cvs' => $cvsMapped->all(),
            'jds' => $jdRows,
            'defaults' => [
                'cv_document_id' => ($defaultProfile = UserCv::defaultForUserId($userId)) ? 'p'.$defaultProfile->id : null,
                'jd_document_id' => $defaultsRow?->default_jd_document_id,
            ],
            'max_cv_body_chars' => AgentDocumentLimits::maxCharsForType(AgentDocument::TYPE_CV),
            'max_jd_body_chars' => AgentDocumentLimits::maxCharsForType(AgentDocument::TYPE_JD),
            'jd_content_agent_id' => $jdQueryAgentId,
            'documents_hub_agent_id' => $documentsHubAgentId,
            'jd_defaults_agent_id' => $jdQueryAgentId,
        ];
    }
}
