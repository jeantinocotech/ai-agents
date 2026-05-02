<?php

namespace App\Providers;

use App\View\Composers\CareerTrailBannerComposer;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

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
        Password::defaults(function (): Password {
            /** Suite Pest: senha curta. Em desenvolvimento local use senhas fortes ou `PASSWORD_RELAX_RULES=true` no .env. */
            if (app()->environment('testing')) {
                return Password::min(8);
            }

            if (filter_var(env('PASSWORD_RELAX_RULES', false), FILTER_VALIDATE_BOOL)) {
                return Password::min(8);
            }

            return Password::min(12)->mixedCase()->numbers()->symbols();
        });

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
