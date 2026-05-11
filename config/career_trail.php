<?php

return [

    'mentor_label' => 'Sra. Graça',

    /*
    | Título curto no chat do ATS (página cheia) e botão na trilha (/trilha/ats).
    */
    'ats_chat_heading' => 'Passar no filtro',

    /*
    | Texto por defeito na caixa da Graça no chat ATS (compacto), se não existir mensagem em BD (slot ats_chat_page_intro).
    */
    'ats_chat_graca_fallback' => 'Use os botões «Enviar CV» e «Enviar Vaga» acima do assistente para começar. Estou também no mapa da trilha se precisar de contexto.',

    /*
    | Linha cinza no resumo colapsável da Graça (chat ATS compacto).
    */
    'ats_chat_graca_summary_line' => 'Mensagem para este passo ATS (envio de CV e vaga no chat abaixo).',

    /*
    | Chat compacto da carta de motivação (passo cover-letter): título e textos da caixa Graça (a saudação ChatKit central é a mesma que no ATS).
    */
    'cover_letter_chat_heading' => 'Carta de motivação',

    'cover_letter_chat_graca_fallback' => 'Envie CV e vaga com os botões acima do assistente, converse para moldar a carta e guarde o resultado na biblioteca. O mapa da trilha explica o passo.',

    'cover_letter_chat_graca_summary_line' => 'Mensagem para redigir a carta neste processo (CV + vaga no chat abaixo).',

    /*
    | Chat compacto do coach de entrevistas (passo interviews): título e textos da caixa Graça.
    */
    'interviews_chat_heading' => 'Entrevista',

    'interviews_chat_graca_fallback' => 'Envie o CV e a vaga com os botões acima para dar contexto ao assistente; depois pratique perguntas e registe rondas na página de entrevistas. O mapa da trilha resume o passo.',

    'interviews_chat_graca_summary_line' => 'Mensagem para preparar entregas neste processo (CV + vaga no chat abaixo).',

    /*
    | Chat compacto do assistente de CV / CV Creator (passo cv): só lista de CVs — sem vaga (JD).
    */
    'cv_assistant_chat_heading' => 'Assistente de CV',

    'cv_assistant_chat_graca_fallback' => 'Escolha um dos seus CVs na lista e envie para o chat para rever texto e estrutura. No passo «Meu CV» pode editar o perfil ou criar versões; aqui o foco é o diálogo com o assistente.',

    'cv_assistant_chat_graca_summary_line' => 'Selecione um CV acima para enviar ao assistente (revisão no chat).',

    'cv_assistant_chat_start_greeting' => 'Escolha um CV na lista acima e clique em «Enviar CV para revisão».',

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
