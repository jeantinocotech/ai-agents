<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\TwoFactorAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request, TwoFactorAuthService $twoFactor): RedirectResponse
    {
        $remember = $request->boolean('remember');

        $request->authenticate();

        $user = Auth::user();
        if ($user === null) {
            return redirect()->route('login');
        }

        if (! $user->hasVerifiedEmail()) {
            Auth::guard('web')->logout();

            throw ValidationException::withMessages([
                'email' => 'Confirme o seu e-mail antes de iniciar sessão. Utilize o link ou o código enviados na mensagem (verifique o spam).',
            ]);
        }

        if ($twoFactor->needsChallenge($user)) {
            Auth::logout();

            $request->session()->put('two_factor.login.id', Crypt::encryptString((string) $user->getKey()));
            $request->session()->put('two_factor.login.remember', $remember);

            return redirect()->route('two-factor.challenge');
        }

        $request->session()->regenerate();

        if (! $user->hasAcceptedCurrentLegalDocuments()) {
            return redirect()->route('legal.consent.show');
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        $welcome = max(0, (int) \App\Models\Setting::get('tokens_welcome_amount', 0));
        $days = max(1, (int) \App\Models\Setting::get('tokens_renewal_interval_days', 30));
        $msg = 'Você ganha '.$welcome.' tokens para testar. A cada '.$days.' dias, renovamos para '.$welcome.'. Se você decidir comprar tokens, o acesso passa a ser via compra de pacotes.';

        return redirect('/')
            ->with('info', $msg);
    }
}
