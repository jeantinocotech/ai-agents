<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAcceptedLegalDocuments
{
    /**
     * Garante política de privacidade e termos na versão actual (LGPD).
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user === null) {
            return $next($request);
        }

        if ($this->shouldBypass($request)) {
            return $next($request);
        }

        if ($user->hasAcceptedCurrentLegalDocuments()) {
            return $next($request);
        }

        return redirect()
            ->route('legal.consent.show')
            ->with('warning', 'Aceite a política de privacidade e os termos de uso na versão actual para continuar.');
    }

    private function shouldBypass(Request $request): bool
    {
        return $request->routeIs(
            'privacidade',
            'termos-uso',
            'legal.consent.*',
            'two-factor.*',
            'logout',
            'verification.*',
            'password.*',
        );
    }
}
