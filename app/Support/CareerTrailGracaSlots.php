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

    /** Caixa da Graça no topo da página de chat ATS (ChatKit compacto), antes do título «ATS Filtro». */
    public const ATS_CHAT_PAGE_INTRO = 'ats_chat_page_intro';

    /** Caixa da Graça no chat compacto da carta de motivação (passo cover-letter). */
    public const COVER_LETTER_CHAT_PAGE_INTRO = 'cover_letter_chat_page_intro';

    /** Caixa da Graça no chat compacto do coach de entrevistas (passo interviews). */
    public const INTERVIEWS_CHAT_PAGE_INTRO = 'interviews_chat_page_intro';

    /** Caixa da Graça no chat compacto do assistente de CV / CV Creator (passo cv). */
    public const CV_ASSISTANT_CHAT_PAGE_INTRO = 'cv_assistant_chat_page_intro';

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
            self::ATS_CHAT_PAGE_INTRO => 'Chat ATS (página cheia) — caixa da Graça acima do assistente',
            self::COVER_LETTER_CHAT_PAGE_INTRO => 'Chat carta de motivação — caixa da Graça acima do assistente',
            self::INTERVIEWS_CHAT_PAGE_INTRO => 'Chat entrevistas — caixa da Graça acima do assistente',
            self::CV_ASSISTANT_CHAT_PAGE_INTRO => 'Chat assistente de CV — caixa da Graça acima do assistente',
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
