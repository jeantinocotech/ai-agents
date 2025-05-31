<?php

// app/Http/Controllers/HotmartWebhookController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Agent;
use App\Models\Purchase;
use Illuminate\Support\Facades\Log;

class HotmartWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $event = $request->input('event');
        $buyerEmail = $request->input('buyer.email');
        $productId = $request->input('product.id');
        $subscriptionCode = $request->input('subscription.code');

        if (!$buyerEmail || !$productId) {
            return response()->json(['status' => 'missing data'], 400);
        }

        Log::info('Hotmart Webhook recebido', [
            'event' => $event,
            'email' => $buyerEmail,
            'product_id' => $productId,
            'subscription_code' => $subscriptionCode,
        ]);

        $user = User::where('email', $buyerEmail)->first();
        $agent = Agent::where('hotmart_product_id', $productId)->first();

        if (!$user || !$agent) {
            Log::warning('Usuário ou agente não encontrado para Webhook da Hotmart.', [
                'email' => $buyerEmail,
                'product_id' => $productId,
            ]);
            return response()->json(['status' => 'not found'], 404);
        }

        if ($event === 'PURCHASE_APPROVED') {
            Purchase::updateOrCreate([
                'user_id' => $user->id,
                'agent_id' => $agent->id,
            ], [
                'active' => true,
                'paused' => false,
                'paused_at' => null,
                'hotmart_subscription_code' => $subscriptionCode,
            ]);
            Log::info('Compra aprovada e registrada com sucesso.');
        }

        if (in_array($event, ['SUBSCRIPTION_CANCELED', 'PURCHASE_REFUNDED', 'CHARGEBACK'])) {
            Purchase::where('user_id', $user->id)
                ->where('agent_id', $agent->id)
                ->update([
                    'active' => false,
                    'paused' => true,
                    'paused_at' => now(),
                ]);
                
                Log::info('Assinatura desativada.', ['event' => $event]);
        }

        return response()->json(['status' => 'ok']);
    }
}

