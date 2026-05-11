<?php

namespace App\Services;

use App\Enums\InterviewApplicationOutcome;
use App\Enums\InterviewProcessStatus;
use App\Models\CareerTrailStep;
use App\Models\InterviewPreparation;
use App\Models\InterviewProcess;
use App\Models\User;
use App\Models\UserCareerTrailProgress;

final class InterviewProcessOutcomeService
{
    /** Após criar/atualizar/apagar uma ronda, recalcula o estado global (CV/JD). */
    public static function syncAfterPreparationMutation(int $userId, int $jdDocumentId): ?InterviewProcess
    {
        $hasAnyPrep = InterviewPreparation::query()
            ->where('user_id', $userId)
            ->where('jd_document_id', $jdDocumentId)
            ->exists();

        if (! $hasAnyPrep) {
            $process = InterviewProcess::query()
                ->where('user_id', $userId)
                ->where('jd_document_id', $jdDocumentId)
                ->first();

            if ($process !== null) {
                $keepTerminal = in_array(
                    $process->outcome,
                    [
                        InterviewApplicationOutcome::Approved,
                        InterviewApplicationOutcome::DidNotProceed,
                    ],
                    true
                );
                if (! $keepTerminal) {
                    $process->delete();
                    $process = null;
                }
            }

            self::syncOfferTrailUnlock(User::query()->find($userId));
            JobApplicationStatusSync::reconcileForJdId($userId, $jdDocumentId);

            return $process;
        }

        return self::refreshOutcomeFromRounds($userId, $jdDocumentId);
    }

    /**
     * Garante registo «em curso», recalcula não prossegueu (ronda) / desistência a partir das rondas,
     * repõe estado intermédio quando as rondas deixarem de estar terminadas negativamente.
     *
     * @return InterviewProcess sempre existente quando há rondas
     */
    public static function refreshOutcomeFromRounds(int $userId, int $jdDocumentId): InterviewProcess
    {
        $terminated = InterviewPreparation::query()
            ->where('user_id', $userId)
            ->where('jd_document_id', $jdDocumentId)
            ->whereIn('status', [
                InterviewProcessStatus::Rejected,
                InterviewProcessStatus::Withdrawn,
            ])
            ->exists();

        $process = InterviewProcess::query()->firstOrCreate(
            [
                'user_id' => $userId,
                'jd_document_id' => $jdDocumentId,
            ],
            [
                'outcome' => InterviewApplicationOutcome::Ongoing,
            ]
        );

        if ($terminated) {
            $process->outcome = InterviewApplicationOutcome::DidNotProceed;
        } elseif ($process->outcome === InterviewApplicationOutcome::DidNotProceed) {
            $process->outcome = InterviewApplicationOutcome::Ongoing;
        }

        /**
         * Aprovado mantém-se se não há ronda terminada negativamente; caso contrário
         * já foi gravado DidNotProceed acima.
         */
        if ($process->isDirty()) {
            $process->save();
        }

        self::syncOfferTrailUnlock(User::query()->find($userId));
        JobApplicationStatusSync::reconcileForJdId($userId, $jdDocumentId);

        return $process->fresh() ?? $process;
    }

    /** Marcar candidatura aprovada (desbloqueia etapa «Proposta» na trilha). */
    public static function approveForUser(int $userId, int $jdDocumentId): InterviewProcess
    {
        $blocked = InterviewPreparation::query()
            ->where('user_id', $userId)
            ->where('jd_document_id', $jdDocumentId)
            ->whereIn('status', [
                InterviewProcessStatus::Rejected,
                InterviewProcessStatus::Withdrawn,
            ])
            ->exists();

        abort_if($blocked, 422, 'Não é possível aprovar: existe uma ronda com "Não prosseguiu" ou "Desistiu".');

        $process = InterviewProcess::query()->firstOrCreate(
            [
                'user_id' => $userId,
                'jd_document_id' => $jdDocumentId,
            ],
            ['outcome' => InterviewApplicationOutcome::Ongoing]
        );

        $process->outcome = InterviewApplicationOutcome::Approved;
        $process->save();

        $user = User::query()->find($userId);
        if ($user) {
            app(GamificationService::class)->recordEvent(
                $user,
                'process_approved',
                InterviewProcess::class,
                (int) $process->id,
                ['jd_document_id' => (int) $jdDocumentId]
            );
            app(GamificationService::class)->ensureFreshSnapshot($user);
        }

        self::syncOfferTrailUnlock(User::query()->find($userId));
        JobApplicationStatusSync::reconcileForJdId($userId, $jdDocumentId);

        return $process->fresh();
    }

    /** Reabrir processo quando o candidato deixa de estar aprovado (mantém regra de rondas). */
    public static function reopenToOngoing(int $userId, int $jdDocumentId): InterviewProcess
    {
        $process = InterviewProcess::query()
            ->where('user_id', $userId)
            ->where('jd_document_id', $jdDocumentId)
            ->firstOrFail();

        abort_unless(
            $process->outcome === InterviewApplicationOutcome::Approved,
            422,
            'Só é possível reabrir como "em curso" quando a candidatura está aprovada.'
        );

        $process->outcome = InterviewApplicationOutcome::Ongoing;
        $process->save();

        $user = User::query()->find($userId);
        if ($user) {
            app(GamificationService::class)->recordEvent(
                $user,
                'process_approved_reverted',
                InterviewProcess::class,
                (int) $process->id,
                ['jd_document_id' => (int) $jdDocumentId]
            );
            app(GamificationService::class)->ensureFreshSnapshot($user);
        }

        return self::refreshOutcomeFromRounds($userId, $jdDocumentId);
    }

    public static function syncOfferTrailUnlock(?User $user): void
    {
        if (! $user) {
            return;
        }

        $offerSort = CareerTrailStep::query()
            ->where('is_active', true)
            ->where('slug', 'offer')
            ->value('sort_order');

        if ($offerSort === null) {
            return;
        }

        $hasApproval = InterviewProcess::query()
            ->where('user_id', (int) $user->id)
            ->where('outcome', InterviewApplicationOutcome::Approved)
            ->exists();

        if (! $hasApproval) {
            return;
        }

        $progress = UserCareerTrailProgress::query()->firstOrCreate(
            ['user_id' => $user->id],
            [
                'current_step_id' => CareerTrailStep::query()
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->value('id'),
                'started_at' => now(),
            ]
        );

        $newMax = max((int) ($progress->max_sort_order_reached ?? 0), (int) $offerSort);
        if ($newMax !== (int) ($progress->max_sort_order_reached ?? 0)) {
            $progress->max_sort_order_reached = $newMax;
            $progress->save();
        }
    }
}
