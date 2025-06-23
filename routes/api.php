<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HotmartWebhookController;

Route::post('/cart/hotmart/webhook', [HotmartWebhookController::class, 'handle']);

