<?php

namespace App\Providers;

use App\View\Composers\CareerTrailBannerComposer;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void {}

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('layouts.app', CareerTrailBannerComposer::class);

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
