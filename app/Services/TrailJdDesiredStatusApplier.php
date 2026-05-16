<?php

namespace App\Services;

use App\Enums\InterviewApplicationOutcome;
use App\Enums\JobApplicationStatus;
use App\Models\Agent;
use App\Models\AgentDocument;
use App\Models\InterviewPreparation;
use App\Models\InterviewProcess;
use App\Models\User;
use App\Support\AgentsDocumentTrailListFilter;

final class TrailJdDesiredStatusApplier
{
    /**
     * Aplica mudanças de estado a partir da combo da lista ATS (JD já autorizada ao agente ATS).
     *
     * @return string|null URL absoluta para registar primeira ronda; null para redirect normal à biblioteca ATS
     */
    public static function apply(
        string $desired,
        AgentDocument $document,
        User $user,
        ?Agent $interviewPrepAgent,
        bool $canAccessInterviewPrep,
    ): ?string {
        abort_unless($document->type === AgentDocument::TYPE_JD, 404);

        if (! $document->is_active) {
            if ($desired === AgentsDocumentTrailListFilter::INACTIVE) {
                return null;
            }

            AgentDocumentJdLifecycle::reactivate($document);
            $document->refresh();
            JobApplicationStatusSync::reconcile($document);
            $document->refresh();

            return self::applyAfterActiveEnsured($desired, $document, $user, $interviewPrepAgent, $canAccessInterviewPrep, true);
        }

        if ($desired === AgentsDocumentTrailListFilter::INACTIVE) {
            abort_unless($document->is_active, 404);
            AgentDocumentJdLifecycle::deactivate($document);

            return null;
        }

        abort_unless($document->is_active, 404);
        JobApplicationStatusSync::reconcile($document);
        $document->refresh();

        return self::applyAfterActiveEnsured($desired, $document, $user, $interviewPrepAgent, $canAccessInterviewPrep, false);
    }

    private static function applyAfterActiveEnsured(
        string $desired,
        AgentDocument $document,
        User $user,
        ?Agent $interviewPrepAgent,
        bool $canAccessInterviewPrep,
        bool $afterReactivationFromArchive,
    ): ?string {
        $currentKey = AgentsDocumentTrailListFilter::listStatusKey($document->is_active, $document->application_status);
        if ($desired === $currentKey) {
            return null;
        }

        switch ($desired) {
            case JobApplicationStatus::Draft->value:
                if ($afterReactivationFromArchive) {
                    self::normalizeToDraftPipelineAfterUnarchive($document);

                    return null;
                }
                abort(422, 'Não é possível retroceder para «Em preparação» apenas pela lista.');
            case JobApplicationStatus::Submitted->value:
                self::ensureSubmitted($document, $user);

                return null;
            case JobApplicationStatus::CvSent->value:
                self::ensureCvSent($document, $user);

                return null;
            case JobApplicationStatus::Interviewing->value:
                return self::interviewPrepUrl($document, $interviewPrepAgent, $canAccessInterviewPrep);
            case JobApplicationStatus::DidNotProceed->value:
                self::ensureDidNotProceed($document, $user);

                return null;
            case JobApplicationStatus::Accepted->value:
                self::ensureAccepted($document, $user);

                return null;
            default:
                abort(422, 'Estado inválido.');
        }
    }

    /** Depois de reactivar pela combo, permitir voltar ao tablier «Em preparação» limpando o registo ATS (timestamps). */
    private static function normalizeToDraftPipelineAfterUnarchive(AgentDocument $document): void
    {
        abort_if(
            InterviewPreparation::query()
                ->where('user_id', $document->user_id)
                ->where('jd_document_id', $document->id)
                ->exists(),
            422,
            'Existem rondas de entrevista registadas; actualize primeiro no ecrã de entrevistas.'
        );

        $document->forceFill([
            'ats_submitted_at' => null,
            'cv_sent_to_employer_at' => null,
        ]);
        $document->saveQuietly();
        JobApplicationStatusSync::reconcile($document);
    }

    private static function ensureSubmitted(AgentDocument $document, User $user): void
    {
        abort_if($document->user_cv_id === null, 422, 'Associe um CV do perfil à vaga antes de registar o envio ao ATS.');

        $process = InterviewProcess::query()
            ->where('user_id', $user->id)
            ->where('jd_document_id', $document->id)
            ->first();
        abort_if(
            $process !== null && in_array($process->outcome, [
                InterviewApplicationOutcome::DidNotProceed,
                InterviewApplicationOutcome::Approved,
            ], true),
            422,
            'Este processo já está encerrado ou aprovado; não é possível registar novo envio ATS.'
        );

        if ($document->ats_submitted_at !== null) {
            if ($document->cv_sent_to_employer_at !== null) {
                $document->cv_sent_to_employer_at = null;
                $document->save();
            }
            JobApplicationStatusSync::reconcile($document);

            return;
        }

        $document->ats_submitted_at = now();
        $document->save();
    }

    private static function ensureCvSent(AgentDocument $document, User $user): void
    {
        abort_if($document->ats_submitted_at === null, 422, 'Registe primeiro o alinhamento ATS (envio ao assistente) antes de indicar o envio à empresa.');
        abort_if($document->cv_sent_to_employer_at !== null, 422, 'O envio do CV à empresa já está registado para esta vaga.');

        abort_if(
            InterviewPreparation::query()
                ->where('user_id', $user->id)
                ->where('jd_document_id', $document->id)
                ->exists(),
            422,
            'Já existem rondas de entrevista; utilize o ecrã de entrevistas para actualizar o processo.'
        );

        $process = InterviewProcess::query()
            ->where('user_id', $user->id)
            ->where('jd_document_id', $document->id)
            ->first();
        abort_if(
            $process !== null && in_array($process->outcome, [
                InterviewApplicationOutcome::DidNotProceed,
                InterviewApplicationOutcome::Approved,
            ], true),
            422,
            'Este processo já está encerrado na fase de entrevistas.'
        );

        abort_unless(
            $document->application_status === null || $document->application_status === JobApplicationStatus::Submitted,
            422,
            'Só é possível registar o envio à empresa após o estado «Alinhamento ATS».'
        );

        $document->cv_sent_to_employer_at = now();
        $document->save();
    }

    private static function ensureDidNotProceed(AgentDocument $document, User $user): void
    {
        abort_if(
            InterviewPreparation::query()
                ->where('user_id', $user->id)
                ->where('jd_document_id', $document->id)
                ->exists(),
            422,
            'Existem rondas de entrevista para esta vaga; actualize o estado nas entrevistas em vez de fechar aqui.'
        );

        $process = InterviewProcess::query()
            ->where('user_id', $user->id)
            ->where('jd_document_id', $document->id)
            ->first();
        abort_if(
            $process !== null && $process->outcome === InterviewApplicationOutcome::Approved,
            422,
            'Candidatura aprovada: utilize o ecrã de entrevistas para reabrir antes de mudar este estado.'
        );

        InterviewProcess::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'jd_document_id' => $document->id,
            ],
            [
                'outcome' => InterviewApplicationOutcome::DidNotProceed,
            ]
        );

        InterviewProcessOutcomeService::syncAfterPreparationMutation((int) $user->id, (int) $document->id);
    }

    private static function ensureAccepted(AgentDocument $document, User $user): void
    {
        InterviewProcessOutcomeService::approveForUser((int) $user->id, (int) $document->id);
    }

    private static function interviewPrepUrl(
        AgentDocument $document,
        ?Agent $interviewPrepAgent,
        bool $canAccessInterviewPrep,
    ): string {
        abort_if($interviewPrepAgent === null || ! $canAccessInterviewPrep, 422, 'Avance na trilha até ao passo Entrevistas para registar ou ver rondas.');

        abort_unless(
            in_array($document->application_status, [
                JobApplicationStatus::CvSent,
                JobApplicationStatus::Interviewing,
            ], true),
            422,
            'Indique primeiro o envio do CV à empresa (estado «CV enviado à empresa») antes de registar entrevistas.'
        );

        return route('agents.interview-preparations.create', $interviewPrepAgent).'?jd_document_id='.$document->id;
    }
}
