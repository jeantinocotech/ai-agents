<?php

namespace App\Support;

/**
 * Identificadores estáveis para blocos «avatar da Graça + texto ao lado».
 * Usados em BD, views e admin.
 */
final class CareerTrailGracaSlots
{
    /** Texto principal sob o nome na página da trilha (/trilha) e no ATS. */
    public const TRAIL_STEP_HEADER = 'trail_step_header';

    /** Bloco introdutório na página Meu CV (passo cv). */
    public const CV_PAGE_INTRO = 'cv_page_intro';

    /** Parágrafos ao lado do avatar na landing com utilizador autenticado (sem CV de perfil). */
    public const LANDING_AUTH_INTRO = 'landing_auth_intro';

    /** Parágrafo principal do herói na landing para visitantes (texto à esquerda do avatar). */
    public const LANDING_GUEST_HERO = 'landing_guest_hero';

    /**
     * @return array<string, string> value => label (admin)
     */
    public static function labels(): array
    {
        return [
            self::TRAIL_STEP_HEADER => 'Trilha / ATS — texto principal (ao lado do avatar)',
            self::CV_PAGE_INTRO => 'Meu CV — texto introdutório (ao lado do avatar)',
            self::LANDING_AUTH_INTRO => 'Landing (autenticado) — parágrafos ao lado do avatar',
            self::LANDING_GUEST_HERO => 'Landing (visitante) — parágrafo do herói',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function forAdminSelect(): array
    {
        return self::labels();
    }
}
