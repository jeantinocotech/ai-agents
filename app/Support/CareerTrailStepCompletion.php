<?php

namespace App\Support;

use App\Models\Agent;
use App\Models\AgentDocument;
use App\Models\CareerTrailStep;
use App\Models\InterviewPreparation;
use App\Models\MotivationLetter;
use App\Models\User;
use App\Models\UserCv;

final class CareerTrailStepCompletion
{
    /**
     * Critérios para avançar a partir desta etapa.
     *
     * @return array{ready: bool, reason: string|null, blocked_message: string|null}
     */
    public static function readiness(User $user, CareerTrailStep $step): array
    {
        return match ($step->slug) {
            'cv' => self::cvReadiness($user),
            'ats' => self::atsReadiness($user, $step),
            default => [
                'ready' => true,
                'reason' => null,
                'blocked_message' => null,
            ],
        };
    }

    /**
     * Itens de checklist para a UI (passos 1 e 2). Passo 3+ trará gestão de processos em paralelo.
     *
     * @return list<array{label: string, done: bool}>
     */
    public static function checklist(User $user, CareerTrailStep $step): array
    {
        return match ($step->slug) {
            'cv' => self::cvChecklist($user),
            'ats' => self::atsChecklist($user, $step),
            default => [],
        };
    }

    /**
     * Ícone ✓ na barra fixa da trilha: critérios reais (CV/ATS), carta guardada (Motivação opcional),
     * ou posição do ponteiro da trilha para as restantes etapas.
     */
    public static function bannerShowsCompletedBadge(User $user, CareerTrailStep $step, CareerTrailStep $current): bool
    {
        return match ($step->slug) {
            'cv' => self::readiness($user, $step)['ready'],
            'ats' => self::readiness($user, $step)['ready'],
            'cover-letter' => MotivationLetter::query()
                ->where('user_id', (int) $user->id)
                ->exists(),
            'interviews' => InterviewPreparation::query()
                ->where('user_id', (int) $user->id)
                ->exists(),
            default => (int) $step->sort_order < (int) $current->sort_order,
        };
    }

    /**
     * @return array{ready: bool, reason: string|null, blocked_message: string|null}
     */
    private static function cvReadiness(User $user): array
    {
        $cv = UserCv::defaultForUserId((int) $user->id);
        $min = self::minProfileCvChars();

        if ($cv === null) {
            return [
                'ready' => false,
                'reason' => null,
                'blocked_message' => 'Guarde um CV na área «Meu CV» antes de avançar para a etapa seguinte.',
            ];
        }

        $len = mb_strlen(trim((string) $cv->body));
        if ($len < $min) {
            return [
                'ready' => false,
                'reason' => null,
                'blocked_message' => 'O texto do CV de perfil deve ter pelo menos '.$min.' caracteres. Continue na área «Meu CV».',
            ];
        }

        return [
            'ready' => true,
            'reason' => 'Já tem um CV de perfil guardado. Quando estiver pronto, avance para a etapa ATS (filtro e alinhamento com a vaga).',
            'blocked_message' => null,
        ];
    }

    /**
     * Requisito mínimo para o ATS: pelo menos uma vaga (JD) com CV associado na biblioteca do agente ATS.
     * Várias vagas em paralelo são permitidas; basta uma concluída para «fechar» a etapa antes de avançar.
     * A gestão fina por processo (feedback, entrevista) será no passo 3.
     *
     * @return array{ready: bool, reason: string|null, blocked_message: string|null}
     */
    private static function atsReadiness(User $user, CareerTrailStep $step): array
    {
        $agent = $step->resolvedAgent();
        if (! $agent) {
            return [
                'ready' => false,
                'reason' => null,
                'blocked_message' => 'O assistente ATS não está configurado. Peça ao administrador para associar um agente à etapa ATS ou defina CAREER_TRAIL_ATS_AGENT_ID.',
            ];
        }

        if (! self::hasPairedCvJdApplication($user, $agent)) {
            return [
                'ready' => false,
                'reason' => null,
                'blocked_message' => 'Na biblioteca do assistente ATS, guarde um CV e uma descrição de vaga (JD) e associe-os (uma vaga com par CV+JD). Depois pode analisar e otimizar o CV para essa vaga.',
            ];
        }

        return [
            'ready' => true,
            'reason' => 'Tem pelo menos uma vaga com JD e CV associados. Pode usar Motivação (opcional) e Entrevista em paralelo; ao avançar, a etapa inicial sugerida é Entrevista.',
            'blocked_message' => null,
        ];
    }

    /**
     * @return list<array{label: string, done: bool}>
     */
    private static function cvChecklist(User $user): array
    {
        $cv = UserCv::defaultForUserId((int) $user->id);
        $min = self::minProfileCvChars();
        $hasCv = $cv !== null;
        $lenOk = $hasCv && mb_strlen(trim((string) $cv->body)) >= $min;

        return [
            ['label' => 'CV de perfil guardado na plataforma', 'done' => $hasCv],
            ['label' => 'Texto do CV com pelo menos '.$min.' caracteres', 'done' => $lenOk],
        ];
    }

    /**
     * @return list<array{label: string, done: bool}>
     */
    private static function atsChecklist(User $user, CareerTrailStep $step): array
    {
        $agent = $step->resolvedAgent();
        $configured = $agent !== null && $agent->is_active;
        $hasPair = $configured && self::hasPairedCvJdApplication($user, $agent);

        return [
            ['label' => 'Assistente ATS configurado para esta etapa', 'done' => $configured],
            ['label' => 'Pelo menos uma vaga (JD) associada a um CV do perfil', 'done' => $hasPair],
        ];
    }

    /**
     * Pelo menos uma JD na biblioteca do agente ATS com CV associado (perfil ou par clássico).
     */
    public static function hasAtsCvJdPair(User $user, ?Agent $agent): bool
    {
        if ($agent === null) {
            return false;
        }

        return self::hasPairedCvJdApplication($user, $agent);
    }

    /**
     * True quando existe pelo menos uma JD ligada ao CV de perfil (user_cv_id) ou ao CV clássico na biblioteca
     * do mesmo agente (paired_cv_document_id), para registos ainda não regravados.
     */
    private static function hasPairedCvJdApplication(User $user, Agent $agent): bool
    {
        $uid = (int) $user->id;
        $aid = (int) $agent->id;

        return AgentDocument::query()
            ->where('agent_documents.user_id', $uid)
            ->where('agent_documents.agent_id', $aid)
            ->where('agent_documents.type', AgentDocument::TYPE_JD)
            ->where(function ($q) use ($uid, $aid) {
                $q->whereNotNull('agent_documents.user_cv_id')
                    ->orWhereExists(function ($sub) use ($uid, $aid) {
                        $sub->selectRaw('1')
                            ->from('agent_documents as adc')
                            ->whereColumn('adc.id', 'agent_documents.paired_cv_document_id')
                            ->where('adc.user_id', $uid)
                            ->where('adc.agent_id', $aid)
                            ->where('adc.type', AgentDocument::TYPE_CV);
                    });
            })
            ->exists();
    }

    private static function minProfileCvChars(): int
    {
        return max(1, (int) config('career_trail.min_profile_cv_chars', 400));
    }
}
