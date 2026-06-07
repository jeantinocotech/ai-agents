<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\EmailVerificationCode;
use App\Support\GoogleAnalytics;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class VerifyEmailCodeController extends Controller
{
    public function create(): View
    {
        return view('auth.verify-email-code');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
            'code' => ['required', 'string', 'size:6', 'regex:/^[0-9]+$/'],
        ]);

        $email = strtolower(trim($data['email']));
        $user = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();

        if ($user === null || $user->hasVerifiedEmail()) {
            throw ValidationException::withMessages([
                'email' => __('Não encontrámos uma conta pendente de confirmação com este e-mail.'),
            ]);
        }

        if (! EmailVerificationCode::isValid((int) $user->getKey(), $data['code'])) {
            throw ValidationException::withMessages([
                'code' => __('Código inválido ou expirado. Peça um novo e-mail de confirmação.'),
            ]);
        }

        EmailVerificationCode::forget((int) $user->getKey());

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
            GoogleAnalytics::flash('email_verified', ['method' => 'code']);
        }

        return redirect()
            ->route('login')
            ->with('status', 'E-mail confirmado. Já pode iniciar sessão.');
    }
}
