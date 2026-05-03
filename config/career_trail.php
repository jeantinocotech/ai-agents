<?php

return [

    'mentor_label' => 'Sra. Graça',

    /*
    | Caminho público do avatar (relativo a /public). Sobrescreva com CAREER_TRAIL_MENTOR_AVATAR se quiser outro arquivo.
    */
    'mentor_avatar' => env('CAREER_TRAIL_MENTOR_AVATAR', 'img/graca-avatar.png'),

    /*
    | Tamanho mínimo do texto do CV de perfil (Meu CV) para considerar o passo 1 concluído.
    */
    'min_profile_cv_chars' => (int) env('CAREER_TRAIL_MIN_PROFILE_CV_CHARS', 400),

    /*
    | IDs opcionais na tabela agents por slug de etapa (quando agent_id na BD está vazio).
    | Ex.: CAREER_TRAIL_ATS_AGENT_ID=3
    */
    'step_agent_ids' => array_filter([
        'ats' => env('CAREER_TRAIL_ATS_AGENT_ID') ? (int) env('CAREER_TRAIL_ATS_AGENT_ID') : null,
        'cover-letter' => env('CAREER_TRAIL_COVER_LETTER_AGENT_ID') ? (int) env('CAREER_TRAIL_COVER_LETTER_AGENT_ID') : null,
        'interviews' => env('CAREER_TRAIL_INTERVIEWS_AGENT_ID') ? (int) env('CAREER_TRAIL_INTERVIEWS_AGENT_ID') : null,
        'offer' => env('CAREER_TRAIL_OFFER_AGENT_ID') ? (int) env('CAREER_TRAIL_OFFER_AGENT_ID') : null,
        'first-100-days' => env('CAREER_TRAIL_FIRST_100_DAYS_AGENT_ID') ? (int) env('CAREER_TRAIL_FIRST_100_DAYS_AGENT_ID') : null,
    ], fn ($id) => $id !== null && $id !== 0),

];
