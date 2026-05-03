<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\CareerTrailStep;
use App\Models\User;
use App\Models\UserCareerTrailProgress;
use Illuminate\Http\JsonResponse;

final class CareerTrailAgentAccess
{
    /**
     * Etapa da trilha que associa este agente (via BD ou config), ou null se o agente não está ligado à trilha.
     */
    public static function trailStepBoundToAgent(Agent $agent): ?CareerTrailStep
    {
        foreach (CareerTrailProgressService::activeSteps() as $step) {
            $resolved = $step->resolvedAgent();
            if ($resolved && (int) $resolved->getKey() === (int) $agent->getKey()) {
                return $step;
            }
        }

        return null;
    }

    public static function userCanAccessTrailAgent(?User $user, Agent $agent): bool
    {
        $step = self::trailStepBoundToAgent($agent);
        if ($step === null) {
            return true;
        }

        if (! $user) {
            return false;
        }

        $firstSortOrder = CareerTrailStep::query()
            ->where('is_active', true)
            ->min('sort_order');
        if ($firstSortOrder !== null && (int) $step->sort_order === (int) $firstSortOrder) {
            return true;
        }

        $progress = UserCareerTrailProgress::query()->where('user_id', $user->id)->first();
        if (! $progress) {
            return false;
        }

        $max = (int) ($progress->max_sort_order_reached ?? 0);

        return $step->sort_order <= $max;
    }

    public static function abortUnlessCanAccess(User $user, Agent $agent): void
    {
        if (! self::userCanAccessTrailAgent($user, $agent)) {
            abort(403, 'Avance na trilha para desbloquear este assistente.');
        }
    }

    public static function denyJsonUnlessCanAccess(User $user, Agent $agent): ?JsonResponse
    {
        if (! self::userCanAccessTrailAgent($user, $agent)) {
            return response()->json([
                'error' => 'trail_locked',
                'message' => 'Este assistente desbloqueia quando avançar na trilha até esta etapa.',
            ], 403);
        }

        return null;
    }
}
