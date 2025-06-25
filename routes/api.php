<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HotmartWebhookController;
use App\Http\Controllers\AsaasWebhookController;

Route::post('/cart/hotmart/webhook', [HotmartWebhookController::class, 'handle']);
Route::post('/cart/asaas/webhook', [AsaasWebhookController::class, 'handle']);

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
