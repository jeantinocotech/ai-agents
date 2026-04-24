<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\AgentDocument;
use App\Models\AgentDocumentDefault;
use App\Models\UserCv;
use App\Support\AgentDocumentLimits;

final class ChatKitDocumentLibraryService
{
    /**
     * @return array<string, mixed>|null
     */
    public static function forUserAndAgent(int $userId, Agent $agent): ?array
    {
        if (! $agent->isChatKitWorkflow()) {
            return null;
        }

        $cvs = AgentDocument::query()
            ->where('user_id', $userId)
            ->where('agent_id', $agent->id)
            ->where('type', AgentDocument::TYPE_CV)
            ->orderByDesc('updated_at')
            ->get(['id', 'title']);

        $profileCv = UserCv::defaultForUserId($userId);
        $cvsMapped = $cvs->map(fn (AgentDocument $d) => [
            'id' => $d->id,
            'title' => $d->title !== null && trim((string) $d->title) !== ''
                ? (string) $d->title
                : 'CV #'.$d->id,
        ])->values();
        if ($profileCv) {
            $cvsMapped = collect([
                [
                    'id' => 'p'.$profileCv->id,
                    'title' => ($profileCv->title !== null && trim((string) $profileCv->title) !== ''
                        ? (string) $profileCv->title
                        : 'CV do perfil').' (perfil)',
                ],
            ])->concat($cvsMapped)->values();
        }

        $jds = AgentDocument::query()
            ->where('user_id', $userId)
            ->where('agent_id', $agent->id)
            ->where('type', AgentDocument::TYPE_JD)
            ->with(['pairedCv:id,title'])
            ->orderByDesc('updated_at')
            ->get(['id', 'title', 'paired_cv_document_id']);

        $defaultsRow = AgentDocumentDefault::query()
            ->where('user_id', $userId)
            ->where('agent_id', $agent->id)
            ->first(['default_cv_document_id', 'default_jd_document_id']);

        return [
            'cvs' => $cvsMapped->all(),
            'jds' => $jds->map(function (AgentDocument $d) {
                $paired = $d->pairedCv;
                $pairedLabel = null;
                if ($paired) {
                    $pairedLabel = $paired->title !== null && trim((string) $paired->title) !== ''
                        ? (string) $paired->title
                        : 'CV #'.$paired->id;
                }

                return [
                    'id' => $d->id,
                    'title' => $d->title !== null && trim((string) $d->title) !== ''
                        ? (string) $d->title
                        : 'Vaga #'.$d->id,
                    'paired_cv_document_id' => $d->paired_cv_document_id,
                    'paired_cv_label' => $pairedLabel,
                ];
            })->values()->all(),
            'defaults' => [
                'cv_document_id' => $defaultsRow?->default_cv_document_id,
                'jd_document_id' => $defaultsRow?->default_jd_document_id,
            ],
            'max_cv_body_chars' => AgentDocumentLimits::maxCharsForType(AgentDocument::TYPE_CV),
            'max_jd_body_chars' => AgentDocumentLimits::maxCharsForType(AgentDocument::TYPE_JD),
        ];
    }
}
