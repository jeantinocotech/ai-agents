<?php

namespace App\Services;

use App\Enums\InterviewApplicationOutcome;
use App\Enums\JobApplicationStatus;
use App\Models\AgentDocument;
use App\Models\InterviewPreparation;
use App\Models\InterviewProcess;
use Illuminate\Support\Collection;

/**
 * Mantém `agent_documents.application_status` alinhado ao processo de entrevistas
 * e ao envio ATS (CV+vaga), sem contradizer `InterviewProcess`.
 */
final class JobApplicationStatusSync
{
    public static function reconcileForJdId(int $userId, int $jdDocumentId): void
    {
        $jd = AgentDocument::query()
            ->whereKey($jdDocumentId)
            ->where('user_id', $userId)
            ->where('type', AgentDocument::TYPE_JD)
            ->first();

        if ($jd === null) {
            return;
        }

        self::reconcile($jd);
    }

    public static function reconcile(AgentDocument $jd): void
    {
        if ($jd->type !== AgentDocument::TYPE_JD) {
            return;
        }

        $target = self::computeTarget($jd);

        if ($jd->application_status === $target) {
            return;
        }

        $jd->application_status = $target;
        $jd->saveQuietly();
    }

    public static function computeTarget(AgentDocument $jd): JobApplicationStatus
    {
        $userId = (int) $jd->user_id;
        $jdId = (int) $jd->id;

        $process = InterviewProcess::query()
            ->where('user_id', $userId)
            ->where('jd_document_id', $jdId)
            ->first();

        if ($process !== null) {
            return match ($process->outcome) {
                InterviewApplicationOutcome::Approved => JobApplicationStatus::Accepted,
                InterviewApplicationOutcome::DidNotProceed => JobApplicationStatus::DidNotProceed,
                InterviewApplicationOutcome::Ongoing => JobApplicationStatus::Interviewing,
            };
        }

        $hasPrep = InterviewPreparation::query()
            ->where('user_id', $userId)
            ->where('jd_document_id', $jdId)
            ->exists();

        if ($hasPrep) {
            return JobApplicationStatus::Interviewing;
        }

        if ($jd->cv_sent_to_employer_at !== null) {
            return JobApplicationStatus::CvSent;
        }

        if ($jd->ats_submitted_at !== null) {
            return JobApplicationStatus::Submitted;
        }

        if ($jd->user_cv_id !== null) {
            return JobApplicationStatus::Draft;
        }

        return JobApplicationStatus::Draft;
    }

    /**
     * Lista de vagas na trilha ATS: por defeito exclui candidaturas terminal (aceite / não prosseguiu).
     *
     * @param  Collection<int, AgentDocument>  $jds
     * @return Collection<int, AgentDocument>
     */
    public static function filterJdsForVagasList(Collection $jds, bool $showFinalized): Collection
    {
        if ($showFinalized) {
            return $jds;
        }

        return $jds->filter(function (AgentDocument $jd): bool {
            $st = $jd->application_status;

            return $st === null || ! $st->isTerminal();
        })->values();
    }
}
