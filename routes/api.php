<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HotmartWebhookController;

Route::prefix('api')->group(function () {
    Route::post('/cart/hotmart/webhook', [HotmartWebhookController::class, 'handle']);
});

