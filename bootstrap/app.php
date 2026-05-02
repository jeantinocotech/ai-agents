<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',

    )
    ->withMiddleware(function (Middleware $middleware) {
        $trustedProxies = env('TRUSTED_PROXIES');
        if ($trustedProxies !== null && $trustedProxies !== '' && $trustedProxies !== 'false') {
            $middleware->trustProxies(
                at: $trustedProxies === '*'
                    ? '*'
                    : array_values(array_filter(array_map('trim', explode(',', $trustedProxies))))
            );
        }

        $middleware->alias([
            'chatkit.integration' => \App\Http\Middleware\VerifyChatKitIntegrationSecret::class,
        ]);

        $middleware->appendToGroup('web', [
            \App\Http\Middleware\EnsureAcceptedLegalDocuments::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
