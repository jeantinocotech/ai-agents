<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AgentController as AdminAgentController;
use App\Http\Controllers\Admin\CareerTrailGracaMessageAdminController;
use App\Http\Controllers\Admin\CareerTrailStepAdminController;
use App\Http\Controllers\Admin\GamificationController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\TestimonialAdminController;
use App\Http\Controllers\AgentController;
use App\Http\Controllers\AgentDocumentsController;
use App\Http\Controllers\AgentsPublicController;
use App\Http\Controllers\AgentStepController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Auth\TwoFactorSettingsController;
use App\Http\Controllers\CareerTrailController;
use App\Http\Controllers\CareerTrailCvController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\ChatKitSessionController;
use App\Http\Controllers\CvAnalysisController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GamificationNotificationController;
use App\Http\Controllers\InterviewPreparationController;
use App\Http\Controllers\InterviewProcessOutcomeController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\LegalConsentController;
use App\Http\Controllers\MotivationLetterController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\RatingController;
use App\Http\Controllers\TestimonialController;
use App\Http\Controllers\TokenPackController;
use App\Http\Controllers\WebCvAnalysisController;
use App\Http\Middleware\AdminMiddleware;
use Illuminate\Support\Facades\Route;

// Testimonials
Route::get('/depoimento', [TestimonialController::class, 'create'])->name('testimonials.create');
Route::post('/depoimento', [TestimonialController::class, 'store'])->name('testimonials.store');
Route::get('/meus-depoimentos', [TestimonialController::class, 'mine'])->name('testimonials.mine')->middleware(['auth', 'verified']);

// Página inicial — foco na trilha de carreira (visitantes e utilizadores autenticados)
Route::get('/', [LandingController::class, 'index'])->name('home');

// Rotas públicas de visualização
Route::get('/agents/{agent}/ratings', [RatingController::class, 'agentRatings'])
    ->name('agents.ratings');

// API Routes
Route::get('/api/agents/{agent}/rating-stats', [RatingController::class, 'getAgentRatingStats'])
    ->name('api.agent.rating-stats');

// Área da conta (e-mail obrigatoriamente verificado antes de usar a plataforma)
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::prefix('notifications/gamification')->name('notifications.gamification.')->group(function () {
        Route::get('/', [GamificationNotificationController::class, 'recent'])->name('recent');
        Route::get('/unread', [GamificationNotificationController::class, 'unread'])->name('unread');
        Route::post('/read-all', [GamificationNotificationController::class, 'markAllRead'])->name('read-all');
        Route::post('/{notification}/read', [GamificationNotificationController::class, 'markRead'])
            ->whereUuid('notification')
            ->name('read');
    });

    Route::get('/trilha', [CareerTrailController::class, 'index'])->name('career-trail.index');
    Route::get('/trilha/cv', [CareerTrailCvController::class, 'show'])->name('career-trail.cv');
    Route::get('/trilha/ats', [CareerTrailController::class, 'ats'])->name('career-trail.ats');
    Route::post('/trilha/cv/extrair-arquivo', [CareerTrailCvController::class, 'extractFile'])->name('career-trail.cv.extract-file');
    Route::post('/trilha/cv', [CareerTrailCvController::class, 'store'])->name('career-trail.cv.store');
    Route::post('/trilha/cv/importar-agente', [CareerTrailCvController::class, 'importFromAgentDocument'])->name('career-trail.cv.import-agent');
    Route::delete('/trilha/cv/biblioteca-agente/{agent}/{document}', [CareerTrailCvController::class, 'destroyAgentDocument'])->name('career-trail.cv.agent-document.destroy');
    Route::patch('/trilha/cv/{userCv}', [CareerTrailCvController::class, 'update'])->name('career-trail.cv.update');
    Route::post('/trilha/cv/{userCv}/padrao', [CareerTrailCvController::class, 'setDefault'])->name('career-trail.cv.default');
    Route::delete('/trilha/cv/{userCv}', [CareerTrailCvController::class, 'destroyProfileCv'])->name('career-trail.cv.destroy');
    Route::post('/trilha/cv/{userCv}/duplicar', [CareerTrailCvController::class, 'duplicateProfileCv'])->name('career-trail.cv.duplicate');
    Route::post('/trilha/cv/sync/{agent}', [CareerTrailCvController::class, 'syncToAgent'])->name('career-trail.cv.sync');
    Route::post('/trilha/avancar', [CareerTrailController::class, 'advance'])->name('career-trail.advance');
    Route::post('/trilha/voltar', [CareerTrailController::class, 'back'])->name('career-trail.back');

    Route::get('/agents/{agent}', [AgentController::class, 'show'])->name('agents.show');
    Route::get('/agents/{agent}/chat', [AgentController::class, 'chat'])->name('agents.chat');
    Route::get('/agents/{agent}/documentos', [AgentDocumentsController::class, 'index'])
        ->name('agents.documents.index');
    Route::get('/agents/{agent}/documentos/{document}/conteudo', [AgentDocumentsController::class, 'content'])
        ->name('agents.documents.content');
    Route::get('/agents/{agent}/cv-perfil/{userCv}/conteudo', [AgentDocumentsController::class, 'profileCvContent'])
        ->name('agents.documents.profile-cv-content');
    Route::post('/agents/{agent}/documentos', [AgentDocumentsController::class, 'store'])
        ->name('agents.documents.store');
    Route::put('/agents/{agent}/documentos/{document}', [AgentDocumentsController::class, 'update'])
        ->name('agents.documents.update');
    Route::delete('/agents/{agent}/documentos/{document}', [AgentDocumentsController::class, 'destroy'])
        ->name('agents.documents.destroy');
    Route::post('/agents/{agent}/documentos/predefinidos', [AgentDocumentsController::class, 'updateDefaults'])
        ->name('agents.documents.defaults');

    Route::get('/agents/{agent}/cartas-motivacao', [MotivationLetterController::class, 'index'])
        ->name('agents.motivation-letters.index');
    Route::get('/agents/{agent}/cartas-motivacao/nova', [MotivationLetterController::class, 'create'])
        ->name('agents.motivation-letters.create');
    Route::post('/agents/{agent}/cartas-motivacao', [MotivationLetterController::class, 'store'])
        ->name('agents.motivation-letters.store');
    Route::get('/agents/{agent}/cartas-motivacao/{motivationLetter}/editar', [MotivationLetterController::class, 'edit'])
        ->name('agents.motivation-letters.edit');
    Route::put('/agents/{agent}/cartas-motivacao/{motivationLetter}', [MotivationLetterController::class, 'update'])
        ->name('agents.motivation-letters.update');
    Route::delete('/agents/{agent}/cartas-motivacao/{motivationLetter}', [MotivationLetterController::class, 'destroy'])
        ->name('agents.motivation-letters.destroy');

    Route::get('/agents/{agent}/entrevistas', [InterviewPreparationController::class, 'index'])
        ->name('agents.interview-preparations.index');
    Route::get('/agents/{agent}/entrevistas/nova', [InterviewPreparationController::class, 'create'])
        ->name('agents.interview-preparations.create');
    Route::post('/agents/{agent}/entrevistas', [InterviewPreparationController::class, 'store'])
        ->name('agents.interview-preparations.store');
    Route::get('/agents/{agent}/entrevistas/{interviewPreparation}/editar', [InterviewPreparationController::class, 'edit'])
        ->name('agents.interview-preparations.edit');
    Route::put('/agents/{agent}/entrevistas/{interviewPreparation}', [InterviewPreparationController::class, 'update'])
        ->name('agents.interview-preparations.update');
    Route::delete('/agents/{agent}/entrevistas/{interviewPreparation}', [InterviewPreparationController::class, 'destroy'])
        ->name('agents.interview-preparations.destroy');
    Route::patch('/agents/{agent}/entrevistas/processo-jd/{jdDocument}', [InterviewProcessOutcomeController::class, 'update'])
        ->name('agents.interview-process.update');

    Route::post('/agents/{agent}/cvs', [AgentDocumentsController::class, 'storeProfileCv'])
        ->name('agents.profile-cvs.store');
    Route::patch('/agents/{agent}/cvs/{userCv}', [AgentDocumentsController::class, 'updateProfileCv'])
        ->name('agents.profile-cvs.update');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/conta/consentimento', [LegalConsentController::class, 'show'])->name('legal.consent.show');
    Route::post('/conta/consentimento', [LegalConsentController::class, 'store'])->name('legal.consent.store');

    Route::get('/profile/two-factor/setup', [TwoFactorSettingsController::class, 'start'])->name('profile.two-factor.start');
    Route::post('/profile/two-factor/confirm', [TwoFactorSettingsController::class, 'confirm'])->name('profile.two-factor.confirm');
    Route::get('/profile/two-factor/recovery', [TwoFactorSettingsController::class, 'recoveryShow'])->name('profile.two-factor.recovery-show');
    Route::delete('/profile/two-factor', [TwoFactorSettingsController::class, 'destroy'])->name('profile.two-factor.destroy');

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

    Route::get('/tokens/comprar', [TokenPackController::class, 'show'])->name('tokens.purchase');
    Route::get('/tokens/historico', [TokenPackController::class, 'history'])->name('tokens.history');
    Route::post('/tokens/comprar/processar', [TokenPackController::class, 'process'])->name('tokens.purchase.process');
    Route::post('/tokens/pagamento/status', [TokenPackController::class, 'checkPaymentStatus'])->name('tokens.payment.status');

    Route::post('/chat/chatkit/session', [ChatKitSessionController::class, 'store'])
        ->middleware('throttle:40,1')
        ->name('chat.chatkit.session');

    Route::post('/chat/chatkit/debit-consultation', [ChatKitSessionController::class, 'debitConsultation'])
        ->middleware('throttle:30,1')
        ->name('chat.chatkit.debit-consultation');

    Route::post('/chat/send', [AgentController::class, 'sendChat'])->name('chat.send');
    Route::post('/chat/sendfile', [AgentController::class, 'sendFile'])->name('chat.sendfile');
    Route::post('/chat/saved-cv', [AgentController::class, 'storeSavedCv'])->name('chat.savedCv.store');
    Route::delete('/chat/saved-cv', [AgentController::class, 'destroySavedCv'])->name('chat.savedCv.destroy');
    Route::post('/chat/{agentId}/finalize', [AgentController::class, 'finalizeSession'])->name('chat.finalize');
    Route::get('/agents/{agent}/current-step', [AgentController::class, 'getCurrentStep'])->name('agent.currentStep');
    Route::get('/agents/{id}/instructions', [AgentController::class, 'getAgentInstructions'])->name('agent.AgentInstructions');

});

// Carrinho

Route::prefix('cart')->name('cart.')->group(function () {
    Route::get('/', [CartController::class, 'index'])->name('index');
    Route::post('/add/{id}', [CartController::class, 'addToCart'])->name('add');
    Route::post('/remove/{id}', [CartController::class, 'removeFromCart'])->name('remove');
    Route::post('/clear', [CartController::class, 'clearCart'])->name('clear');
    Route::get('/checkout', [CartController::class, 'checkout'])->name('checkout');
    Route::get('/checkout/guest', [CartController::class, 'checkoutGuest'])->name('checkout.guest');
    Route::get('/success', [CartController::class, 'checkoutSuccess'])->name('success');
});

// Finalizar compra no carrinho (sessão + e-mail verificado)
Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('/cart/checkout/process', [CartController::class, 'processCheckout'])->name('cart.processCheckout');
    Route::post('/cart/check-payment-status', [CartController::class, 'checkPaymentStatus'])->name('cart.checkPaymentStatus');
});

Route::get('/agentes', [AgentsPublicController::class, 'index'])->name('agents.index');
Route::post('/agentes/{id}/adicionar-carrinho', [AgentsPublicController::class, 'addToCart'])->name('agents.addToCart');

Route::middleware(['auth', 'verified', AdminMiddleware::class])->prefix('admin')->name('admin.')->group(function () {

    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

    Route::get('/gamification', [GamificationController::class, 'index'])->name('gamification.index');
    Route::put('/gamification/badges', [GamificationController::class, 'updateBadges'])->name('gamification.badges.update');
    Route::put('/gamification/score', [GamificationController::class, 'updateScore'])->name('gamification.score.update');

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

    Route::get('admin/testimonials', [TestimonialAdminController::class, 'index'])->name('testimonials.index');
    Route::patch('testimonials/{testimonial}/approve', [TestimonialAdminController::class, 'approve'])->name('testimonials.approve');
    Route::patch('testimonials/{testimonial}/reject', [TestimonialAdminController::class, 'reject'])->name('testimonials.reject');
    Route::patch('testimonials/{testimonial}/feature', [TestimonialAdminController::class, 'feature'])->name('testimonials.feature');

    Route::get('/settings/tokens', [SettingsController::class, 'editTokens'])->name('settings.tokens.edit');
    Route::put('/settings/tokens', [SettingsController::class, 'updateTokens'])->name('settings.tokens.update');

    Route::get('/career-trail-steps', [CareerTrailStepAdminController::class, 'index'])->name('career-trail-steps.index');
    Route::get('/career-trail-steps/{step}/edit', [CareerTrailStepAdminController::class, 'edit'])->name('career-trail-steps.edit');
    Route::put('/career-trail-steps/{step}', [CareerTrailStepAdminController::class, 'update'])->name('career-trail-steps.update');

    Route::resource('career-trail-graca-messages', CareerTrailGracaMessageAdminController::class)->except(['show']);

});

// Anthropic API
Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');
Route::post('/analyze-cv', [CvAnalysisController::class, 'analyze']);
Route::post('/analyze-cv-files', [CvAnalysisController::class, 'analyzeFiles']);
Route::get('/cv-analysis', [WebCvAnalysisController::class, 'showForm']);
Route::post('/cv-analysis', [WebCvAnalysisController::class, 'processForm']);

// Pausar compra
Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('/purchase/pause/{purchase}', [PurchaseController::class, 'pause'])
        ->name('purchase.pause');

    Route::post('/purchase/resume/{purchase}', [PurchaseController::class, 'resume'])
        ->name('purchase.resume');
});

// Politica de privacidade
Route::view('/privacidade', 'privacidade')->name('privacidade');

// Termos de uso
Route::get('/termos-uso', function () {
    return view('termos-uso');
})->name('termos-uso');

require __DIR__.'/auth.php';
