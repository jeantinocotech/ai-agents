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
        
        PurchaseEvent::create([
            'purchase_id' => $purchase->id,
            'event_type' => 'resumed',
            'event_time' => now(),
            'note' => 'Assinatura retomada pelo usuário.',
        ]);

        return back()->with('success', 'Assinatura reativada com sucesso!');
    }

    
}
