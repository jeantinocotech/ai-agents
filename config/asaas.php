<?php

$sandboxRaw = env('ASAAS_SANDBOX', false);

/**
 * Preferir o primeiro valor não vazio entre fontes típicas de PaaS (Coolify/Docker env).
 *
 * Um ficheiro .env gerido pelo painel com `ASAAS_API_KEY=` sem valor pode regista-se vazio
 * no Laravel e esconder a variável injectada no contentor; isto cobre também Apache/mod_php.
 */
$pickAsaasSecret = static function (string $key): ?string {
    $candidates = [
        $_SERVER[$key] ?? null,
        $_ENV[$key] ?? null,
    ];
    $fromGetenv = getenv($key);
    if ($fromGetenv !== false) {
        $candidates[] = $fromGetenv;
    }
    $candidates[] = env($key);

    foreach ($candidates as $value) {
        if (is_string($value)) {
            $trimmed = trim($value);
            if ($trimmed !== '') {
                return $trimmed;
            }
        }
    }

    return null;
};

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
    'api_key' => $pickAsaasSecret('ASAAS_API_KEY'),

    // Webhook token for validating incoming webhook requests
    'webhook_token' => $pickAsaasSecret('ASAAS_WEBHOOK_TOKEN'),

    // Somente desenvolvimento: regista payloads completos (PII — nunca activar em produção)
    'log_webhook_debug' => env('ASAAS_LOG_WEBHOOK_DEBUG', false),

    // Whether to use the sandbox environment (.env em string; evitar "false" interpretado como activo)
    'sandbox' => filter_var($sandboxRaw, FILTER_VALIDATE_BOOLEAN),

    // API URLs
    'api_url' => [
        // Base URL da API (v3). Em produção o domínio é api.asaas.com e o path é /v3
        // Sandbox usa api-sandbox.asaas.com (não sandbox.asaas.com).
        'production' => 'https://api.asaas.com/v3',
        'sandbox' => 'https://api-sandbox.asaas.com/v3',
    ],

    // URL a configurar no painel Asaas (POST) — coincide com routes/api.php
    'webhook_url' => rtrim((string) env('APP_URL', ''), '/').'/api/cart/asaas/webhook',

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
