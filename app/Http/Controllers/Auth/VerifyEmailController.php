<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\EmailVerificationCode;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class VerifyEmailController extends Controller
{
    /**
     * Confirma o e-mail através da URL assinada (não requer sessão iniciada).
     */
    public function __invoke(Request $request): RedirectResponse
    {
        $user = User::query()->findOrFail((int) $request->route('id'));

        if (! hash_equals((string) $request->route('hash'), sha1($user->getEmailForVerification()))) {
            abort(403);
        }

        if ($user->hasVerifiedEmail()) {
            return redirect()
                ->route('login')
                ->with('status', 'Este e-mail já estava confirmado. Pode iniciar sessão.');
        }

        if ($user->markEmailAsVerified()) {
            EmailVerificationCode::forget((int) $user->getKey());
            event(new Verified($user));
        }

        return redirect()
            ->route('login')
            ->with('status', 'E-mail confirmado. Já pode iniciar sessão.');
    }
}
