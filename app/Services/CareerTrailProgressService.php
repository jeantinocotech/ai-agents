<?php

namespace App\Services;

use App\Models\CareerTrailStep;
use App\Models\User;
use App\Models\UserCareerTrailProgress;
use App\Support\CareerTrailStepCompletion;
use Illuminate\Support\Collection;

final class CareerTrailProgressService
{
    /**
     * @return Collection<int, CareerTrailStep>
     */
    public static function activeSteps(): Collection
    {
        return CareerTrailStep::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Garante progresso e etapa atual válidos; devolve null se não houver etapas ativas.
     *
     * @return array{progress: UserCareerTrailProgress, steps: Collection<int, CareerTrailStep>, current: CareerTrailStep}|null
     */
    public static function ensureProgress(User $user): ?array
    {
        $steps = self::activeSteps();
        if ($steps->isEmpty()) {
            return null;
        }

        $first = $steps->first();
        if (! $first instanceof CareerTrailStep) {
            return null;
        }

        $progress = UserCareerTrailProgress::query()->firstOrCreate(
            ['user_id' => $user->id],
            [
                'current_step_id' => $first->id,
                'started_at' => now(),
            ]
        );

        if (! $steps->contains('id', $progress->current_step_id)) {
            $progress->current_step_id = $first->id;
            $progress->save();
        }

        $progress->load('currentStep');
        $current = $progress->currentStep;
        if (! $current || ! $current->is_active) {
            $progress->current_step_id = $first->id;
            $progress->save();
            $progress->load('currentStep');
            $current = $progress->currentStep;
        }

        if (! $current instanceof CareerTrailStep) {
            return null;
        }

        if ($progress->max_sort_order_reached === null) {
            $progress->max_sort_order_reached = $current->sort_order;
            $progress->save();
        }

        /**
         * Auto-avanço: ao cumprir o passo 1 (CV), a trilha passa automaticamente para o ATS.
         * Isso evita a sensação de “nada acontece” após guardar um CV válido.
         */
        if ($current->slug === 'cv') {
            $gate = CareerTrailStepCompletion::readiness($user, $current);
            if (($gate['ready'] ?? false) === true) {
                $next = CareerTrailStep::query()
                    ->where('is_active', true)
                    ->where('sort_order', '>', $current->sort_order)
                    ->orderBy('sort_order')
                    ->first();

                if ($next) {
                    $progress->current_step_id = $next->id;
                    $progress->max_sort_order_reached = max(
                        (int) ($progress->max_sort_order_reached ?? 0),
                        (int) $next->sort_order
                    );
                    $progress->save();
                    $progress->load('currentStep');
                    $current = $progress->currentStep;
                }
            }
        }

        /**
         * ATS concluído: desbloqueia Motivação e Entrevista e alinha current_step ao mesmo destino que «Avançar»
         * (prioridade Entrevistas), tal como no auto-avanço CV → ATS.
         */
        if ($current->slug === 'ats') {
            $gate = CareerTrailStepCompletion::readiness($user, $current);
            if (($gate['ready'] ?? false) === true) {
                $motivationStep = CareerTrailStep::query()
                    ->where('is_active', true)
                    ->where('slug', 'cover-letter')
                    ->first();
                $interviewStep = CareerTrailStep::query()
                    ->where('is_active', true)
                    ->where('slug', 'interviews')
                    ->first();

                $newMax = (int) ($progress->max_sort_order_reached ?? 0);
                foreach ([$motivationStep, $interviewStep] as $stepGate) {
                    if ($stepGate !== null) {
                        $newMax = max($newMax, (int) $stepGate->sort_order);
                    }
                }

                $progress->max_sort_order_reached = $newMax;
                $landing = CareerTrailStep::landingStepAfterCompletedAts();
                if ($landing !== null && (int) $progress->current_step_id === (int) $current->id) {
                    $progress->current_step_id = $landing->id;
                }
                $progress->save();
            }
        }

        $progress->refresh();
        $progress->load('currentStep');
        $currentOut = $progress->currentStep;
        if (! $currentOut instanceof CareerTrailStep) {
            $currentOut = $steps->firstWhere('id', $progress->current_step_id) ?? $first;
        }

        InterviewProcessOutcomeService::syncOfferTrailUnlock($user);

        return [
            'progress' => $progress,
            'steps' => $steps,
            'current' => $currentOut,
        ];
    }
}
