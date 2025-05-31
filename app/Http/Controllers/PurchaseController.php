<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use App\Models\Purchase;
use App\Models\PurchaseEvent;
use Illuminate\Support\Facades\Log;



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
            $this->pauseHotmartSubscription($purchase->hotmart_subscription_code);
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

        $purchase->update([
            'paused' => false,
            'paused_at' => null,
        ]);

         // Retoma na Hotmart, se aplicável
        if ($purchase->hotmart_subscription_code) {
            $this->resumeHotmartSubscription($purchase->hotmart_subscription_code);
        }
            
        PurchaseEvent::create([
            'purchase_id' => $purchase->id,
            'event_type' => 'resumed',
            'event_time' => now(),
            'note' => 'Assinatura retomada pelo usuário.',
        ]);

        return back()->with('success', 'Assinatura reativada com sucesso!');
    }

    private function pauseHotmartSubscription($subscriptionCode)
    {
        $accessToken = env('HOTMART_ACCESS_TOKEN');

        $response = \Illuminate\Support\Facades\Http::withHeaders([
            'Authorization' => "Bearer $accessToken",
        ])->post("https://api.hotmart.com/payments/api/v1/subscription/{$subscriptionCode}/status", [
            'status' => 'SUSPENDED',
        ]);

        Log::info('Hotmart Pause Response', [
            'subscription_code' => $subscriptionCode,
            'response' => $response->json(),
        ]);

        return $response->successful();
    }

    private function resumeHotmartSubscription($subscriptionCode)
    {
        $accessToken = env('HOTMART_ACCESS_TOKEN');

        $response = \Illuminate\Support\Facades\Http::withHeaders([
            'Authorization' => "Bearer $accessToken",
        ])->post("https://api.hotmart.com/payments/api/v1/subscription/{$subscriptionCode}/status", [
            'status' => 'ACTIVE',
        ]);

        Log::info('Hotmart Resume Response', [
            'subscription_code' => $subscriptionCode,
            'response' => $response->json(),
        ]);

        return $response->successful();
    }

}
