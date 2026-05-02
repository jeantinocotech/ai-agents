<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\TokenWalletService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'accept_privacy_policy' => ['accepted'],
            'accept_terms' => ['accepted'],
        ]);

        $now = now();

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'privacy_accepted_at' => $now,
            'privacy_policy_accepted_version' => config('legal.privacy_policy_version'),
            'privacy_ip' => $request->ip(),
            'privacy_user_agent' => (string) $request->userAgent(),
            'terms_accepted_at' => $now,
            'terms_accepted_version' => config('legal.terms_version'),
        ]);

        $wallet = app(TokenWalletService::class);
        $wallet->grantWelcome($user);
        $wallet->scheduleNextRenewal($user->fresh());

        /** Refrescar para garantir dados persistidos antes do Registered (notificação de verificação). */
        $user->refresh();

        event(new Registered($user));

        return redirect()
            ->route('login')
            ->withInput($request->only('email'))
            ->with('status', 'Conta criada. Enviamos um e-mail com um link e um código de 6 dígitos. Confirme o endereço antes de iniciar sessão (verifique também o spam).');
    }
}
