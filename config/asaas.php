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

      // Timeout configurations (in seconds)
      'timeout' => [
        'sandbox' => env('ASAAS_TIMEOUT_SANDBOX', 60),
        'production' => env('ASAAS_TIMEOUT_PRODUCTION', 30),
        'connect' => env('ASAAS_TIMEOUT_CONNECT', 10),
        'critical_operations' => env('ASAAS_TIMEOUT_CRITICAL', 90),
    ],
    
    // Retry configurations
    'retry' => [
        'max_attempts' => env('ASAAS_RETRY_MAX_ATTEMPTS', 3),
        'backoff_multiplier' => env('ASAAS_RETRY_BACKOFF_MULTIPLIER', 2),
        'base_delay' => env('ASAAS_RETRY_BASE_DELAY', 1),
        'critical_operations' => env('ASAAS_RETRY_CRITICAL_OPERATIONS', 5),
        'max_delay' => env('ASAAS_RETRY_MAX_DELAY', 60),
    ],

    // Cache configurations (in seconds)
    'cache' => [
        'customers' => env('ASAAS_CACHE_CUSTOMERS', 3600), // 1 hour
        'pix_keys' => env('ASAAS_CACHE_PIX_KEYS', 1800),  // 30 minutes
        'general' => env('ASAAS_CACHE_GENERAL', 1800),     // 30 minutes
    ],

    // Circuit breaker configurations
    'circuit_breaker' => [
        'failure_threshold' => env('ASAAS_CIRCUIT_BREAKER_THRESHOLD', 10),
        'recovery_timeout' => env('ASAAS_CIRCUIT_BREAKER_RECOVERY', 300), // 5 minutes
    ],
];