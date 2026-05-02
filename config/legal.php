<?php

return [

    /**
     * Incremente quando a política de privacidade for alterada (re-aceite dos utilizadores).
     */
    'privacy_policy_version' => env('LEGAL_PRIVACY_POLICY_VERSION', '2026-05-15'),

    /**
     * Incremente quando os termos de uso forem alterados.
     */
    'terms_version' => env('LEGAL_TERMS_VERSION', '2026-05-15'),

    /** E-mail de contacto LGPD / suporte (texto público pode referenciar este valor). */
    'contact_email' => env('LEGAL_CONTACT_EMAIL', env('MAIL_FROM_ADDRESS', 'contato@gratoai.com')),
];
