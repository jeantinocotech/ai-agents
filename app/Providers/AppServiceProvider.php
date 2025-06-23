<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\OpenAIService;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Route;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    /**
     * Register any application services.
     */

     public function register(): void
     {
        
     }
    
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (app()->environment('production')) {
            URL::forceScheme('https');
             // Registrar rotas normalmente
            $this->loadRoutes();
        }
    }

    protected function loadRoutes(): void
    {
        Route::middleware('web')
            ->group(base_path('routes/web.php'));

        Route::prefix('api')
            ->middleware('api') // 🔥 Isso garante que CSRF NÃO será aplicado
            ->group(base_path('routes/api.php'));
    }
}
