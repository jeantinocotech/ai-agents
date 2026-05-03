<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\AgentDocument;
use App\Models\AgentDocumentDefault;
use App\Models\User;
use App\Models\UserCv;
use App\Support\AgentDocumentLimits;

/**
 * Copia o CV de perfil predefinido para a biblioteca de um agente (documento CV + predefinição na biblioteca).
 */
final class ProfileCvAgentLibrary
{
    /**
     * Cria ou actualiza o documento CV predefinido na biblioteca do agente a partir do UserCv predefinido.
     *
     * @param  bool  $requireTrailAgentAccess  Se falso, ignora o bloqueio «avance na trilha» (usado no push automático para o ATS a partir do CV de perfil).
     * @return null quando não há CV de perfil predefinido
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public static function upsertDefaultProfileCv(User $user, Agent $agent, bool $abortUnlessTrailAccess = true, bool $requireTrailAgentAccess = true): ?AgentDocument
    {
        if ($requireTrailAgentAccess && ! CareerTrailAgentAccess::userCanAccessTrailAgent($user, $agent)) {
            if ($abortUnlessTrailAccess) {
                CareerTrailAgentAccess::abortUnlessCanAccess($user, $agent);
            }

            return null;
        }

        $cv = UserCv::defaultForUserId((int) $user->id);
        if ($cv === null) {
            return null;
        }

        AgentDocumentLimits::assertBodyWithinLimit(AgentDocument::TYPE_CV, (string) $cv->body);

        $defaults = AgentDocumentDefault::firstOrNew([
            'user_id' => $user->id,
            'agent_id' => $agent->id,
        ]);

        $doc = null;
        if ($defaults->exists && $defaults->default_cv_document_id) {
            $doc = AgentDocument::query()
                ->whereKey((int) $defaults->default_cv_document_id)
                ->where('user_id', $user->id)
                ->where('agent_id', $agent->id)
                ->where('type', AgentDocument::TYPE_CV)
                ->first();
        }

        if ($doc !== null) {
            $doc->update([
                'title' => $cv->title ?: 'CV do perfil',
                'body' => $cv->body,
            ]);

            return $doc->fresh();
        }

        $doc = AgentDocument::query()->create([
            'user_id' => $user->id,
            'agent_id' => $agent->id,
            'type' => AgentDocument::TYPE_CV,
            'title' => $cv->title ?: 'CV do perfil',
            'body' => $cv->body,
            'paired_cv_document_id' => null,
        ]);

        $defaults->default_cv_document_id = $doc->id;
        $defaults->save();

        return $doc;
    }
}
