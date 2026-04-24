<?php

namespace App\Services;

use App\Models\CareerTrailStep;
use App\Models\User;
use App\Models\UserCareerTrailProgress;
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

        return [
            'progress' => $progress->fresh(['currentStep']),
            'steps' => $steps,
            'current' => $current,
        ];
    }
}
