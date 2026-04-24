<?php

namespace App\Support;

use App\Models\CareerTrailStep;
use App\Models\User;
use App\Models\UserCv;

final class CareerTrailStepCompletion
{
    /**
     * Indica se os critérios locais sugerem que a etapa atual está pronta para avançar.
     *
     * @return array{ready: bool, reason: string|null}
     */
    public static function readiness(User $user, CareerTrailStep $step): array
    {
        return match ($step->slug) {
            'cv' => self::cvReadiness($user),
            default => ['ready' => false, 'reason' => null],
        };
    }

    /**
     * @return array{ready: bool, reason: string|null}
     */
    private static function cvReadiness(User $user): array
    {
        $cv = UserCv::defaultForUserId((int) $user->id);
        if ($cv === null) {
            return ['ready' => false, 'reason' => null];
        }

        return [
            'ready' => true,
            'reason' => 'Já tem um CV de perfil guardado. Pode avançar para a etapa seguinte (ATS) quando se sentir preparado.',
        ];
    }
}
