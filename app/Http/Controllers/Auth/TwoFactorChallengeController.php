<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\TwoFactorAuthService;
use App\Support\DefaultAuthRedirect;
use App\Support\GoogleAnalytics;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\View\View;

class TwoFactorChallengeController extends Controller
{
    public function __construct(
        private TwoFactorAuthService $twoFactor
    ) {}

    public function create(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('two_factor.login.id')) {
            return redirect()->route('login');
        }

        return view('auth.two-factor-challenge');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string'],
        ]);

        if (! $request->session()->has('two_factor.login.id')) {
            return redirect()->route('login');
        }

        $userId = Crypt::decryptString($request->session()->get('two_factor.login.id'));
        $user = User::query()->find($userId);

        if ($user === null) {
            $request->session()->forget('two_factor.login.id');

            return redirect()->route('login')->withErrors(['code' => 'Sessão expirada; inicie sessão novamente.']);
        }

        $code = trim((string) $request->input('code'));

        if (
            ! $this->twoFactor->verifyCode($user, $code)
            && ! $this->twoFactor->verifyRecoveryCode($user, $code)
        ) {
            return back()->withErrors(['code' => 'Código inválido.']);
        }

        $request->session()->forget('two_factor.login.id');

        Auth::login($user, (bool) $request->session()->pull('two_factor.login.remember', false));
        $request->session()->regenerate();

        if (! $user->hasVerifiedEmail()) {
            Auth::guard('web')->logout();

            return redirect()->route('login')->withErrors([
                'email' => 'Confirme o seu e-mail antes de iniciar sessão.',
            ]);
        }

        if (! $user->hasAcceptedCurrentLegalDocuments()) {
            return redirect()->route('legal.consent.show');
        }

        GoogleAnalytics::flash('login', ['method' => 'email']);

        return redirect()->intended(DefaultAuthRedirect::url());
    }
}
