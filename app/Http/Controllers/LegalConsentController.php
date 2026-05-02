<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LegalConsentController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        if ($request->user()?->hasAcceptedCurrentLegalDocuments()) {
            return redirect()->route('dashboard');
        }

        return view('legal.consent', [
            'privacyUrl' => route('privacidade'),
            'termsUrl' => route('termos-uso'),
            'privacyVersion' => config('legal.privacy_policy_version'),
            'termsVersion' => config('legal.terms_version'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'accept_privacy_policy' => ['accepted'],
            'accept_terms' => ['accepted'],
        ]);

        $user = $request->user();
        abort_if($user === null, 403);

        $now = now();

        $user->privacy_accepted_at = $now;
        $user->privacy_policy_accepted_version = config('legal.privacy_policy_version');
        $user->privacy_ip = $request->ip();
        $user->privacy_user_agent = (string) $request->userAgent();
        $user->terms_accepted_at = $now;
        $user->terms_accepted_version = config('legal.terms_version');
        $user->save();

        return redirect()->intended(route('dashboard', absolute: false));
    }
}
