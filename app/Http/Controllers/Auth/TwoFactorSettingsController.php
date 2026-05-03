<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\TwoFactorAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorSettingsController extends Controller
{
    public function __construct(
        private TwoFactorAuthService $twoFactor
    ) {}

    public function start(Request $request): RedirectResponse|View
    {
        $user = $request->user();
        if ($user->two_factor_confirmed_at !== null) {
            return redirect()->route('profile.edit')->with('info', 'Autenticação em dois passos já está ativa.');
        }

        $secret = (new Google2FA)->generateSecretKey();
        $request->session()->put('two_factor.enable.secret', encrypt($secret));

        $qrUrl = $this->twoFactor->qrCodeUrl($user, $secret);

        return view('profile.two-factor-setup', [
            'secret' => $secret,
            'qrUrl' => $qrUrl,
        ]);
    }

    public function confirm(Request $request): RedirectResponse
    {
        $user = $request->user();
        if ($user->two_factor_confirmed_at !== null) {
            return redirect()->route('profile.edit');
        }

        $request->validate([
            'code' => ['required', 'string'],
        ]);

        $enc = session('two_factor.enable.secret');
        if (! is_string($enc)) {
            return redirect()
                ->route('profile.two-factor.start')
                ->withErrors(['code' => 'Configure de novo o leitor TOTP.']);
        }

        $secret = decrypt($enc);

        if (! $this->twoFactor->google2fa()->verifyKey($secret, trim((string) $request->input('code')), 2)) {
            return back()->withErrors(['code' => 'Código inválido.']);
        }

        $request->session()->forget('two_factor.enable.secret');

        $plainRecovery = $this->twoFactor->generateRecoveryPlainCodes();
        $this->twoFactor->confirmAndEnable($user, $secret, $plainRecovery);

        return redirect()
            ->route('profile.two-factor.recovery-show')
            ->with('recovery_codes', $plainRecovery);
    }

    public function recoveryShow(Request $request): View|RedirectResponse
    {
        $codes = $request->session()->get('recovery_codes');
        if (! is_array($codes) || $codes === []) {
            return redirect()->route('profile.edit');
        }

        return view('profile.two-factor-recovery-codes', [
            'recoveryCodes' => $codes,
        ]);
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('disableTwoFactor', [
            'password' => ['required', 'current_password'],
        ]);

        $this->twoFactor->disable($request->user());

        return redirect()->route('profile.edit')->with('status', 'two-factor-disabled');
    }
}
