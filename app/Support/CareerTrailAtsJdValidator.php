<?php

namespace App\Support;

use App\Models\AgentDocument;
use App\Models\CareerTrailStep;
use App\Models\User;
use App\Models\UserCv;

final class CareerTrailAtsJdValidator
{
    /**
     * JD na biblioteca ATS da trilha, do utilizador, com CV de perfil associado.
     */
    public static function validatedJdForUser(int $jdId, User $user): AgentDocument
    {
        $jd = AgentDocument::query()
            ->whereKey($jdId)
            ->where('user_id', $user->id)
            ->where('type', AgentDocument::TYPE_JD)
            ->firstOrFail();

        abort_unless($jd->is_active, 422, 'Esta vaga está arquivada; reative-a na biblioteca ATS antes de continuar.');

        $atsStep = CareerTrailStep::query()->where('slug', 'ats')->where('is_active', true)->first();
        $atsAgent = $atsStep?->resolvedAgent();
        abort_if($atsAgent === null || (int) $jd->agent_id !== (int) $atsAgent->id, 422, 'A vaga tem de pertencer à biblioteca ATS da trilha.');
        abort_if($jd->user_cv_id === null, 422, 'Associe um CV de perfil a esta vaga na biblioteca ATS antes de continuar.');

        abort_if(UserCv::query()->whereKey($jd->user_cv_id)->where('user_id', $user->id)->doesntExist(), 422);

        return $jd;
    }
}
