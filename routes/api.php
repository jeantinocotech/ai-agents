<?php

use App\Http\Controllers\Api\ChatKitIntegrationController;
use App\Http\Controllers\AsaasWebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/cart/asaas/webhook', [AsaasWebhookController::class, 'handle']);

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware(['throttle:120,1', 'chatkit.integration'])
    ->prefix('chatkit')
    ->group(function () {
        Route::get('profile-documents', [ChatKitIntegrationController::class, 'show']);
        Route::put('profile-documents', [ChatKitIntegrationController::class, 'update']);
        Route::post('profile-documents/defaults', [ChatKitIntegrationController::class, 'updateDefaults']);
        Route::post('profile-documents', [ChatKitIntegrationController::class, 'store']);
        Route::put('profile-documents/{document}', [ChatKitIntegrationController::class, 'updateDocument'])
            ->whereNumber('document');
        Route::delete('profile-documents/{document}', [ChatKitIntegrationController::class, 'destroyDocument'])
            ->whereNumber('document');
    });
