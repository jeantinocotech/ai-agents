<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HotmartWebhookController;

// Define webhook routes with explicit CSRF exclusion
// This completely bypasses CSRF protection for this route
Route::post('/cart/hotmart/webhook', [HotmartWebhookController::class, 'handle'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);