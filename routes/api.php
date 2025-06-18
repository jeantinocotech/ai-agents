<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AgentController;
use App\Http\Controllers\HotmartWebhookController;

// Hotmart webhook route - excluded from CSRF protection
// Using the exact same path as before to maintain compatibility
Route::post('cart/hotmart/webhook', [HotmartWebhookController::class, 'handle']);

Route::middleware('auth:sanctum')->group(function () {
    // Protected API routes go here
});




