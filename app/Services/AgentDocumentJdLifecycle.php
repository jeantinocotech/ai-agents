<?php

namespace App\Services;

use App\Enums\InterviewApplicationOutcome;
use App\Models\AgentDocument;
use App\Models\InterviewPreparation;
use App\Models\InterviewProcess;

final class AgentDocumentJdLifecycle
{
    /**
     * Arquiva a vaga: remove rondas de entrevista, encerra o processo como «não prosseguiu», desactiva o registo.
     */
    public static function deactivate(AgentDocument $jd): void
    {
        abort_unless($jd->type === AgentDocument::TYPE_JD && $jd->is_active, 404);

        $userId = (int) $jd->user_id;
        $jdId = (int) $jd->id;

        InterviewPreparation::query()
            ->where('user_id', $userId)
            ->where('jd_document_id', $jdId)
            ->delete();

        InterviewProcess::query()->updateOrCreate(
            [
                'user_id' => $userId,
                'jd_document_id' => $jdId,
            ],
            [
                'outcome' => InterviewApplicationOutcome::DidNotProceed,
            ]
        );

        InterviewProcessOutcomeService::syncAfterPreparationMutation($userId, $jdId);

        $jd->is_active = false;
        $jd->save();

        AgentDocumentDefaultJdSync::sync($userId, (int) $jd->agent_id, null);
    }

    public static function reactivate(AgentDocument $jd): void
    {
        abort_unless($jd->type === AgentDocument::TYPE_JD && ! $jd->is_active, 404);

        $userId = (int) $jd->user_id;
        $jdId = (int) $jd->id;

        $proc = InterviewProcess::query()
            ->where('user_id', $userId)
            ->where('jd_document_id', $jdId)
            ->first();

        $hasPrep = InterviewPreparation::query()
            ->where('user_id', $userId)
            ->where('jd_document_id', $jdId)
            ->exists();

        if ($proc !== null
            && $proc->outcome === InterviewApplicationOutcome::DidNotProceed
            && ! $hasPrep) {
            $proc->delete();
        }

        $jd->is_active = true;
        $jd->save();

        AgentDocumentDefaultJdSync::sync($userId, (int) $jd->agent_id, $jdId);
    }
}
