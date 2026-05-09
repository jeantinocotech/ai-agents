<?php

namespace App\Http\Middleware;

use App\Support\DefaultAuthRedirect;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Permite aceder ao ecrã de desafio 2FA apenas com sessão pendente (pós-password).
 */
class EnsurePendingTwoFactorChallenge
{
    /**
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            return redirect()->to(DefaultAuthRedirect::url());
        }

        if (! $request->session()->has('two_factor.login.id')) {
            return redirect()->route('login');
        }

        return $next($request);
    }
}
