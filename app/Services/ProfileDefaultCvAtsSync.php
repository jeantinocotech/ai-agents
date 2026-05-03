<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\CareerTrailStep;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Quando o CV de perfil fica predefinido, reflecte automaticamente na biblioteca do agente ATS (só esse agente).
 */
final class ProfileDefaultCvAtsSync
{
    public static function sync(User $user): void
    {
        $atsStep = CareerTrailStep::query()
            ->where('is_active', true)
            ->where('slug', 'ats')
            ->first();

        if ($atsStep === null) {
            return;
        }

        $agent = $atsStep->resolvedAgent();
        if (! $agent instanceof Agent || ! $agent->is_active) {
            return;
        }

        try {
            ProfileCvAgentLibrary::upsertDefaultProfileCv($user, $agent, false, false);
        } catch (ValidationException $e) {
            Log::warning('profile_cv_ats_sync_validation', [
                'user_id' => $user->id,
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ]);
        }
    }
}
