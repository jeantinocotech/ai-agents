<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AgentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\AgentsPublicController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\ChatController as ApiChatController;
use App\Http\Controllers\Admin\AgentController as AdminAgentController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\AgentStepController;
use App\Http\Middleware\AdminMiddleware;
use App\Http\Controllers\CvAnalysisController;
use App\Http\Controllers\WebCvAnalysisController;
use App\Http\Controllers\PurchaseController;


// Página inicial - listagem pública de agentes
Route::get('/', [AgentController::class, 'index'])->name('home');

// Grupo de rotas protegidas por autenticação
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/agents/{agent}', [AgentController::class, 'show'])->name('agents.show');
    Route::get('/agents/{agent}/chat', [AgentController::class, 'chat'])->name('agents.chat');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Carrinho
Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart/add/{id}', [CartController::class, 'addToCart'])->name('cart.add');
Route::post('/cart/remove/{id}', [CartController::class, 'removeFromCart'])->name('cart.remove');
Route::post('/cart/clear', [CartController::class, 'clearCart'])->name('cart.clear');

// Checkout (requer login)
Route::middleware(['auth'])->group(function () {
    Route::get('/cart/checkout', [CartController::class, 'checkout'])->name('cart.checkout');
    Route::post('/cart/checkout/process', [CartController::class, 'processCheckout'])->name('cart.processCheckout');
    Route::get('/cart/checkout/success', [CartController::class, 'checkoutSuccess'])->name('checkout.success');
});

Route::get('/agentes', [AgentsPublicController::class, 'index'])->name('agents.index');
Route::post('/agentes/{id}/adicionar-carrinho', [AgentsPublicController::class, 'addToCart'])->name('agents.addToCart');


// Rotas Chat
Route::post('/chat/send', [AgentController::class, 'sendChat'])->name('chat.send');
Route::post('/chat/sendfile', [AgentController::class, 'sendFile'])->name('chat.sendfile');
//Route::get('/chat/{agent}/finalize', [AgentController::class, 'finalizeSession'])->name('chat.finalize');
Route::post('/chat/{agentId}/finalize', [AgentController::class, 'finalizeSession'])->name('chat.finalize');
Route::get('/agents/{agent}/current-step', [AgentController::class, 'getCurrentStep'])->name('agent.currentStep');
Route::get('/agents/{id}/instructions', [AgentController::class, 'getAgentInstructions'])->name('agent.AgentInstructions');



Route::middleware(['auth', AdminMiddleware::class])->prefix('admin')->name('admin.')->group(function () {

    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

    Route::resource('agents', AdminAgentController::class);

    // Steps dos agents (fica dentro também)
    Route::prefix('agents/{agent}/steps')->name('agents.steps.')->group(function () {
        Route::get('/', [AgentStepController::class, 'index'])->name('index');
        Route::get('/create', [AgentStepController::class, 'create'])->name('create');
        Route::post('/', [AgentStepController::class, 'store'])->name('store');
        Route::get('/{step}/edit', [AgentStepController::class, 'edit'])->name('edit');
        Route::put('/{step}', [AgentStepController::class, 'update'])->name('update');
        Route::delete('/{step}', [AgentStepController::class, 'destroy'])->name('destroy');
    });

});


    // Anthropic API
    Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');
    Route::post('/analyze-cv', [CvAnalysisController::class, 'analyze']);
    Route::post('/analyze-cv-files', [CvAnalysisController::class, 'analyzeFiles']);
    Route::get('/cv-analysis', [WebCvAnalysisController::class, 'showForm']);
    Route::post('/cv-analysis', [WebCvAnalysisController::class, 'processForm']);


    // Pausar compra
    Route::post('/purchase/pause/{purchase}', [PurchaseController::class, 'pause'])
    ->middleware('auth')
    ->name('purchase.pause');
    Route::post('/purchase/resume/{purchase}', [PurchaseController::class, 'resume'])
    ->middleware('auth')
    ->name('purchase.resume');



    Route::get('/clear-log', function () {
    file_put_contents(storage_path('logs/laravel.log'), '');
    return 'Log limpo com sucesso!';
    });

require __DIR__.'/auth.php';
