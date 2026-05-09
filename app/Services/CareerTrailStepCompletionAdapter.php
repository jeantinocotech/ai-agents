<?php

namespace App\Services;

use App\Models\CareerTrailStep;
use App\Models\User;

/**
 * Pequena ponte para obter dados da trilha sem importar lógica de UI no dashboard/gamificação.
 */
final class CareerTrailStepCompletionAdapter
{
    public static function atsAgentIdForUser(User $user): ?int
    {
        $atsStep = CareerTrailStep::query()->where('slug', 'ats')->first();
        if (! $atsStep) {
            return null;
        }

        $agent = $atsStep->resolvedAgent();

        return $agent?->id ? (int) $agent->id : null;
    }
}
