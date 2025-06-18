<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AgentController;
use App\Http\Controllers\HotmartWebhookController;


Route::middleware('auth:sanctum')->group(function () {
    // Protected API routes go here
});

Route::post('/cart/hotmart/webhook', [HotmartWebhookController::class, 'handle']);


