<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PrivacyController extends Controller
{
    public function accept(Request $request)
    {
        $user = Auth::user();

        // Só atualiza se ainda não foi aceito
        if (!$user->privacy_accepted_at) {
            $user->privacy_accepted_at = now();
            $user->privacy_ip = $request->ip();
            $user->privacy_user_agent = $request->header('User-Agent');
            $user->save();
        }

        return response()->json(['success' => true]);
    }
    /**
     * Verifica se existe consentimento no localStorage e sincroniza com o banco
     */
    public function syncConsent(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Not authenticated'], 401);
        }

        $user = Auth::user();
        
        // Se o usuário não tem aceite no banco, mas informou que aceitou no localStorage
        if (!$user->privacy_accepted_at && $request->input('localStorage_consent') === 'accepted') {
            $user->privacy_accepted_at = now();
            $user->privacy_ip = $request->ip();
            $user->privacy_user_agent = $request->header('User-Agent');
            $user->save();

            // Remove o flag da sessão após sincronizar
            $request->session()->forget('check_privacy_consent');

            return response()->json(['success' => true, 'message' => 'Consent synced']);
        }

        return response()->json(['success' => true, 'message' => 'No sync needed']);
    }

}