<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class GuestEmailVerificationResendController extends Controller
{
    /**
     * Reenvia e-mail de confirmação sem sessão (apenas e-mail conhecido).
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
        ]);

        $email = strtolower(trim($data['email']));
        $user = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();

        if ($user !== null && ! $user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();
        }

        return back()->with('status', 'Se existir uma conta pendente com este e-mail, enviámos uma nova mensagem de confirmação.');
    }
}
