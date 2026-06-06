<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'google_analytics' => [
        'measurement_id' => env('GA_MEASUREMENT_ID'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'ats_analysis_model' => env('OPENAI_ATS_ANALYSIS_MODEL', 'gpt-4o-mini'),
    ],

    /*
    | ChatKit (Agent Builder): header beta pode mudar — ver documentação atual.
    | https://developers.openai.com/api/docs/guides/chatkit
    */
    'chatkit' => [
        'beta_header' => env('CHATKIT_OPENAI_BETA', 'chatkit_beta=v1'),
        'integration_api_secret' => env('CHATKIT_INTEGRATION_API_SECRET'),
        /*
         * Timeout (s) ao criar sessão em POST https://api.openai.com/v1/chatkit/sessions
         * (servidores atrás de rede lenta/firewall beneficiam de valor mais alto).
         */
        'http_timeout' => (int) env('CHATKIT_HTTP_TIMEOUT', 60),
        /*
         * Alertas por email quando o browser reporta erros ChatKit (LOG_ALERT_MAIL, vírgulas).
         */
        'alert_mail_raw' => env('LOG_ALERT_MAIL', ''),
        'alert_throttle_seconds' => (int) env('CHATKIT_ALERT_THROTTLE_SECONDS', 21600),
        'alert_only_production' => filter_var(env('CHATKIT_ALERT_ONLY_PRODUCTION', true), FILTER_VALIDATE_BOOL),
    ],

];
