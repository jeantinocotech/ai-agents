<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Tamanho máximo do texto (caracteres Unicode)
    |--------------------------------------------------------------------------
    |
    | Limites para corpo de CV e JD na biblioteca e na API de integração,
    | de forma a evitar mensagens excessivas no ChatKit e em variáveis de workflow.
    |
    */
    'max_cv_body_chars' => (int) env('AGENT_DOCUMENT_MAX_CV_CHARS', 20000),

    'max_jd_body_chars' => (int) env('AGENT_DOCUMENT_MAX_JD_CHARS', 60000),

];
