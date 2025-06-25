<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Asaas API Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration settings for the Asaas payment API.
    | These settings are used by the AsaasService to interact with the Asaas API.
    |
    */

    // API Key for authentication with Asaas
    'api_key' => env('ASAAS_API_KEY'),

    // Webhook token for validating incoming webhook requests
    'webhook_token' => env('ASAAS_WEBHOOK_TOKEN'),

    // Whether to use the sandbox environment
    'sandbox' => env('ASAAS_SANDBOX', false),

    // API URLs
    'api_url' => [
        'production' => 'https://api.asaas.com',
        'sandbox' => 'https://sandbox.asaas.com',
    ],

    // Webhook notification URL (where Asaas will send notifications)
    'webhook_url' => env('APP_URL') . '/webhook/asaas',
];