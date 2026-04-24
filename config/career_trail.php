<?php

return [

    'mentor_label' => 'Sra. Graça',

    /*
    | Caminho público do avatar (relativo a /public). Sobrescreva com CAREER_TRAIL_MENTOR_AVATAR se quiser outro ficheiro.
    */
    'mentor_avatar' => env('CAREER_TRAIL_MENTOR_AVATAR', 'img/graca-avatar.png'),

    /*
    | ID do agente Laravel (tabela agents) com integração ChatKit dedicado à trilha
    | para criar CV em chat (sem biblioteca CV/JD na UI). Use um workflow ChatKit
    | distinto do agente de análise ATS se quiser fluxo e prompts diferentes.
    | .env: CAREER_TRAIL_CV_CHATKIT_AGENT_ID=1
    */
    'cv_chatkit_agent_id' => env('CAREER_TRAIL_CV_CHATKIT_AGENT_ID'),

];
