<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Purchase;
use App\Models\PurchaseEvent;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class PurchaseController extends Controller
{
    public function pause(Purchase $purchase)
    {
        Log::info('PurchaseController::pause', [
            'purchase_id' => $purchase->id,
            'user_id' => auth()->id(),
        ]);
        
        if ($purchase->user_id !== auth()->id()) {
            abort(403);
        }
    
        if ($purchase->paused) {
            return back()->with('info', 'Esta assinatura já está pausada.');
        }

        // Pausa na Hotmart, se aplicável
        if ($purchase->hotmart_subscription_code) {
            $success = $this->pauseHotmartSubscription($purchase->hotmart_subscription_code);
            if (!$success) {
                return back()->with('error', 'Erro ao pausar assinatura na Hotmart. Tente novamente.');
            }
        }
        
        $purchase->update([
            'paused' => true,
            'paused_at' => now(),
        ]);

        PurchaseEvent::create([
            'purchase_id' => $purchase->id,
            'event_type' => 'paused',
            'event_time' => now(),
            'note' => 'Pausa solicitada pelo usuário.',
        ]);
    
        return back()->with('success', 'Assinatura pausada com sucesso.');
    }

    public function resume(Purchase $purchase)
    {
        if ($purchase->user_id !== auth()->id()) {
            abort(403);
        }

        if (!$purchase->paused) {
            return back()->with('info', 'Esta assinatura já está ativa.');
        }

        Log::info('PurchaseController::resume', [
            'purchase_id' => $purchase->id,
            'user_id' => auth()->id(),
        ]);

        // Retoma na Hotmart, se aplicável
        if ($purchase->hotmart_subscription_code) {
            $success = $this->resumeHotmartSubscription($purchase->hotmart_subscription_code);
            if (!$success) {
                return back()->with('error', 'Erro ao reativar assinatura na Hotmart. Tente novamente.');
            }
        }

        $purchase->update([
            'paused' => false,
            'paused_at' => null,
        ]);
            
        PurchaseEvent::create([
            'purchase_id' => $purchase->id,
            'event_type' => 'resumed',
            'event_time' => now(),
            'note' => 'Assinatura retomada pelo usuário.',
        ]);

        return back()->with('success', 'Assinatura reativada com sucesso!');
    }

    private function pauseHotmartSubscription($subscriptionCode): bool
    {
        try {
            $accessToken = $this->getHotmartAccessToken();
            
            if (!$accessToken) {
                Log::error('Falha ao obter token de acesso da Hotmart');
                return false;
            }

            $response = Http::withHeaders([
                'Authorization' => "Bearer $accessToken",
                'Content-Type' => 'application/json',
            ])->post("https://api.hotmart.com/payments/api/v1/subscriptions/{$subscriptionCode}/actions/suspend");

            Log::info('Hotmart Pause Response', [
                'subscription_code' => $subscriptionCode,
                'status' => $response->status(),
                'response' => $response->json(),
            ]);

            return $response->successful();

        } catch (\Exception $e) {
            Log::error('Erro ao pausar assinatura Hotmart', [
                'subscription_code' => $subscriptionCode,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    private function resumeHotmartSubscription($subscriptionCode): bool
    {
        try {
            $accessToken = $this->getHotmartAccessToken();
            
            if (!$accessToken) {
                Log::error('Falha ao obter token de acesso da Hotmart');
                return false;
            }

            $response = Http::withHeaders([
                'Authorization' => "Bearer $accessToken",
                'Content-Type' => 'application/json',
            ])->post("https://api.hotmart.com/payments/api/v1/subscriptions/{$subscriptionCode}/actions/reactivate");

            Log::info('Hotmart Resume Response', [
                'subscription_code' => $subscriptionCode,
                'status' => $response->status(),
                'response' => $response->json(),
            ]);

            return $response->successful();

        } catch (\Exception $e) {
            Log::error('Erro ao reativar assinatura Hotmart', [
                'subscription_code' => $subscriptionCode,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    private function getHotmartAccessToken(): ?string
    {
        try {
            // Verifica se já temos um token válido em cache/session
            $cachedToken = cache('HOTMART_ACCESS_TOKEN');
            if ($cachedToken) {
                return $cachedToken;
            }

            $clientId = env('HOTMART_CLIENT_ID');
            $clientSecret = env('HOTMART_CLIENT_SECRET');
            $basicAuth = env('HOTMART_BASIC_AUTH'); // Alternativa se usar Basic Auth

            if (!$clientId || !$clientSecret) {
                Log::error('Credenciais da Hotmart não configuradas');
                return null;
            }

            $response = Http::asForm()->post('https://api.hotmart.com/security/oauth/token', [
                'grant_type' => 'client_credentials',
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $token = $data['access_token'];
                $expiresIn = $data['expires_in'] ?? 3600;

                // Cache o token por um tempo menor que o tempo de expiração
                cache(['HOTMART_ACCESS_TOKEN' => $token], now()->addSeconds($expiresIn - 300));

                return $token;
            }

            Log::error('Falha ao obter token Hotmart', [
                'status' => $response->status(),
                'response' => $response->json()
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error('Exceção ao obter token Hotmart', ['error' => $e->getMessage()]);
            return null;
        }
    }
}
