<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class HotmartWebhookMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Log that we're bypassing CSRF for this request
        Log::info('Bypassing CSRF for Hotmart webhook');
        
        // Add the CSRF token to the request to bypass CSRF verification
        $request->headers->set('X-CSRF-TOKEN', csrf_token());
        
        return $next($request);
    }
}