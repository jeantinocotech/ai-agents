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
use App\Http\Controllers\RatingController;
use App\Http\Controllers\HotmartWebhookController;


// Página inicial - listagem pública de agentes
Route::get('/', [AgentController::class, 'index'])->name('home');

// Rotas públicas de visualização
Route::get('/agents/{agent}/ratings', [RatingController::class, 'agentRatings'])
    ->name('agents.ratings');

// API Routes
Route::get('/api/agents/{agent}/rating-stats', [RatingController::class, 'getAgentRatingStats'])
    ->name('api.agent.rating-stats');

// Grupo de rotas protegidas por autenticação
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/agents/{agent}', [AgentController::class, 'show'])->name('agents.show');
    Route::get('/agents/{agent}/chat', [AgentController::class, 'chat'])->name('agents.chat');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

     // Formulário de avaliação
     Route::get('/ratings/form/{chatSession}', [RatingController::class, 'showRatingForm'])
     ->name('ratings.form');
 
    // Salvar avaliação
    Route::post('/ratings', [RatingController::class, 'store'])
        ->name('ratings.store');
    
    // Atualizar avaliação
    Route::put('/ratings/{rating}', [RatingController::class, 'update'])
        ->name('ratings.update');
    
    // Remover avaliação
    Route::delete('/ratings/{rating}', [RatingController::class, 'destroy'])
        ->name('ratings.destroy');
    
    // Minhas avaliações
    Route::get('/my-ratings', [RatingController::class, 'myRatings'])
        ->name('ratings.my-ratings');
    
    // Solicitar avaliação após chat
    Route::get('/request-rating/{chatSession}', [RatingController::class, 'requestRating'])
        ->name('ratings.request');
    
    // AJAX Routes
    Route::get('/ratings/quick-modal/{chatSession}', [RatingController::class, 'quickRatingModal'])
        ->name('ratings.quick-modal');
    
    Route::post('/ratings/quick-store', [RatingController::class, 'quickStore'])
        ->name('ratings.quick-store');

});

// Carrinho

Route::prefix('cart')->name('cart.')->group(function () {
    Route::get('/', [CartController::class, 'index'])->name('index');
    Route::post('/add/{id}', [CartController::class, 'addToCart'])->name('add');
    Route::post('/remove/{id}', [CartController::class, 'removeFromCart'])->name('remove');
    Route::post('/clear', [CartController::class, 'clearCart'])->name('clear');
    Route::get('/checkout', [CartController::class, 'checkout'])->name('checkout');
    Route::get('/success', [CartController::class, 'checkoutSuccess'])->name('success');
    // Webhook route moved to api.php to bypass CSRF protection
});


// Checkout (requer login)
Route::middleware(['auth'])->group(function () {
    Route::post('/cart/checkout/process', [CartController::class, 'processCheckout'])->name('cart.processCheckout');
    //Pagamento Hotmart
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
    Route::get('agents/{agent}/ratings', [AdminAgentController::class, 'ratings'])->name('agents.ratings');

    // Estatísticas detalhadas de um agente
    Route::get('/agents/{agent}/stats', [AdminDashboardController::class, 'agentStats'])
        ->name('agents.stats');

    // Steps dos agents (fica dentro também)
    Route::prefix('agents/{agent}/steps')->name('agents.steps.')->group(function () {
        Route::get('/', [AgentStepController::class, 'index'])->name('index');
        Route::get('/create', [AgentStepController::class, 'create'])->name('create');
        Route::post('/', [AgentStepController::class, 'store'])->name('store');
        Route::get('/{step}/edit', [AgentStepController::class, 'edit'])->name('edit');
        Route::put('/{step}', [AgentStepController::class, 'update'])->name('update');
        Route::delete('/{step}', [AgentStepController::class, 'destroy'])->name('destroy');
    });

    //busca preco no hotmart e atualiza tabela de agentes
  
    Route::post('/agents/update-prices', [AdminDashboardController::class, 'updateAllHotmartPrices'])->name('agents.updatePrices');

});


    // Anthropic API
    Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');
    Route::post('/analyze-cv', [CvAnalysisController::class, 'analyze']);
    Route::post('/analyze-cv-files', [CvAnalysisController::class, 'analyzeFiles']);
    Route::get('/cv-analysis', [WebCvAnalysisController::class, 'showForm']);
    Route::post('/cv-analysis', [WebCvAnalysisController::class, 'processForm']);

    // Pausar compra
    Route::middleware(['auth'])->group(function () {
        Route::post('/purchase/pause/{purchase}', [PurchaseController::class, 'pause'])
        ->middleware('auth')
        ->name('purchase.pause');
        
        Route::post('/purchase/resume/{purchase}', [PurchaseController::class, 'resume'])
        ->middleware('auth')
        ->name('purchase.resume');
    });


    Route::get('/clear-log', function () {
    file_put_contents(storage_path('logs/laravel.log'), '');
    return 'Log limpo com sucesso!';
    });



require __DIR__.'/auth.php';
