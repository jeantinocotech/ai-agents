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
     * Garante progresso válido; desbloqueio automático via max_sort_order_reached.
     *
     * @return array{
     *     progress: UserCareerTrailProgress,
     *     steps: Collection<int, CareerTrailStep>,
     *     maxReached: int,
     *     frontierStep: CareerTrailStep|null
     * }|null
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
                'max_sort_order_reached' => (int) $first->sort_order,
            ]
        );

        if (! $steps->contains('id', $progress->current_step_id)) {
            $progress->current_step_id = $first->id;
            $progress->save();
        }

        if ($progress->max_sort_order_reached === null) {
            $progress->max_sort_order_reached = (int) $first->sort_order;
            $progress->save();
        }

        $maxReached = (int) $progress->max_sort_order_reached;

        $cvStep = $steps->firstWhere('slug', 'cv');
        if ($cvStep instanceof CareerTrailStep) {
            $gate = CareerTrailStepCompletion::readiness($user, $cvStep);
            if (($gate['ready'] ?? false) === true) {
                $atsStep = $steps->firstWhere('slug', 'ats');
                if ($atsStep instanceof CareerTrailStep) {
                    $maxReached = max($maxReached, (int) $atsStep->sort_order);
                }
            }
        }

        $atsStep = $steps->firstWhere('slug', 'ats');
        if ($atsStep instanceof CareerTrailStep) {
            $gate = CareerTrailStepCompletion::readiness($user, $atsStep);
            if (($gate['ready'] ?? false) === true) {
                foreach (['cover-letter', 'interviews'] as $slug) {
                    $unlockStep = $steps->firstWhere('slug', $slug);
                    if ($unlockStep instanceof CareerTrailStep) {
                        $maxReached = max($maxReached, (int) $unlockStep->sort_order);
                    }
                }
            }
        }

        InterviewProcessOutcomeService::syncOfferTrailUnlock($user);

        $progress->refresh();
        $maxReached = max($maxReached, (int) ($progress->max_sort_order_reached ?? $maxReached));

        if ($maxReached !== (int) $progress->max_sort_order_reached) {
            $progress->max_sort_order_reached = $maxReached;
            $progress->save();
        }

        return [
            'progress' => $progress,
            'steps' => $steps,
            'maxReached' => $maxReached,
            'frontierStep' => self::frontierStep($user, $steps, $maxReached),
        ];
    }

    /**
     * Primeira etapa desbloqueada ainda não concluída; se todas concluídas, a última desbloqueada.
     *
     * @param  Collection<int, CareerTrailStep>  $steps
     */
    public static function frontierStep(User $user, Collection $steps, int $maxReached): ?CareerTrailStep
    {
        $unlocked = $steps->filter(fn (CareerTrailStep $step) => (int) $step->sort_order <= $maxReached)->values();

        foreach ($unlocked as $step) {
            if (! CareerTrailStepCompletion::bannerShowsCompletedBadge($user, $step)) {
                return $step;
            }
        }

        return $unlocked->last();
    }
}
