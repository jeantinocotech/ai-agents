<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyChatKitIntegrationSecret
{
    public function handle(Request $request, Closure $next): Response
    {
        $secret = (string) config('services.chatkit.integration_api_secret', '');
        if ($secret === '') {
            abort(503, 'CHATKIT_INTEGRATION_API_SECRET não está configurado.');
        }

        $token = (string) $request->bearerToken();
        if ($token === '' || ! hash_equals($secret, $token)) {
            abort(401, 'Token de integração inválido.');
        }

        return $next($request);
    }
}
