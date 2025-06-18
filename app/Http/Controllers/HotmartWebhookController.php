<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Agent;
use App\Models\Purchase;
use App\Models\PurchaseEvent;
use Illuminate\Support\Facades\Log;

class HotmartWebhookController extends Controller
{
    
    public function handle(Request $request)
    {
        // Log da requisição completa para debug
        Log::info('Hotmart Webhook recebido', [
            'headers' => $request->headers->all(),
            'body' => $request->all()
        ]);

        // Validação básica da estrutura do webhook
        if (!$this->isValidWebhook($request)) {
            Log::warning('Webhook inválido recebido', ['data' => $request->all()]);
            return response()->json(['status' => 'invalid webhook'], 400);
        }

        $event = $request->input('event');
        $data = $request->input('data');

        // Log do payload recebido para debug
        Log::info('Hotmart Webhook recebido (ajustado)', ['event' => $event, 'data' => $data]);
        
        // Estrutura pode variar, adapte conforme a documentação da Hotmart
        //$buyerEmail = $data['buyer']['email'] ?? $request->input('buyer.email');
        $buyerEmail = $data['buyer']['email'] ?? null;

        //$productId = $data['product']['id'] ?? $request->input('product.id');
        // Busca do primeiro produto digital listado (Hotmart envia uma lista)
        $productId = null;
        if (!empty($data['product']['content']['products'])) {
            $productId = $data['product']['content']['products'][0]['id'];
        }

        //$subscriptionCode = $data['subscription']['code'] ?? $request->input('subscription.code');
        // Código da assinatura (opcional, dependendo do evento)
        $subscriptionCode = $data['subscription']['subscriber']['code'] ?? null;

        if (!$buyerEmail || !$productId) {
            Log::warning('Dados obrigatórios ausentes no webhook', [
                'email' => $buyerEmail,
                'product_id' => $productId
            ]);
            return response()->json(['status' => 'missing data'], 400);
        }


        $user = User::where('email', $buyerEmail)->first();
        $agent = Agent::where('hotmart_product_id', $productId)->first();

        if (!$user) {
            Log::warning('Usuário não encontrado para o email', ['email' => $buyerEmail]);
            return response()->json(['status' => 'user not found'], 404);
        }

        if (!$agent) {
            Log::warning('Agente não encontrado para o produto', ['product_id' => $productId]);
            return response()->json(['status' => 'agent not found'], 404);
        }

        return $this->processEvent($event, $user, $agent, $subscriptionCode, $data);
    }

    private function isValidWebhook(Request $request): bool
    {
        // Adicione aqui a validação de assinatura da Hotmart se necessário
        // Por exemplo, verificação de token ou assinatura HMAC
        
        $hotmartToken = env('HOTMART_WEBHOOK_TOKEN');
        if ($hotmartToken) {
            $receivedToken = $request->header('X-Hotmart-Hottok');
            if ($receivedToken !== $hotmartToken) {
                return false;
            }
        }

        return true;
    }

    private function processEvent(string $event, User $user, Agent $agent, ?string $subscriptionCode, array $data)
    {
        Log::info('Processando evento Hotmart', [
            'event' => $event,
            'user_id' => $user->id,
            'agent_id' => $agent->id,
            'subscription_code' => $subscriptionCode
        ]);

        switch ($event) {
            case 'PURCHASE_APPROVED':
            case 'PURCHASE_COMPLETE':
                return $this->handlePurchaseApproved($user, $agent, $subscriptionCode, $data);
                
            case 'SUBSCRIPTION_CANCELED':
            case 'PURCHASE_REFUNDED':
            case 'PURCHASE_CHARGEBACK':
                return $this->handlePurchaseCanceled($user, $agent, $event);
                
            case 'SUBSCRIPTION_SUSPENDED':
                return $this->handleSubscriptionSuspended($user, $agent);
                
            case 'SUBSCRIPTION_REACTIVATED':
                return $this->handleSubscriptionReactivated($user, $agent);
                
            default:
                Log::info('Evento não processado', ['event' => $event]);
                return response()->json(['status' => 'event not processed']);
        }
    }

    private function handlePurchaseApproved(User $user, Agent $agent, ?string $subscriptionCode, array $data)
    {
        $purchase = Purchase::updateOrCreate([
            'user_id' => $user->id,
            'agent_id' => $agent->id,
        ], [
            'active' => true,
            'paused' => false,
            'paused_at' => null,
            'hotmart_subscription_code' => $subscriptionCode,
        ]);

        PurchaseEvent::create([
            'purchase_id' => $purchase->id,
            'event_type' => 'purchase_approved',
            'event_time' => now(),
            'note' => 'Compra aprovada via Hotmart webhook.',
            'metadata' => json_encode($data)
        ]);

        Log::info('Compra aprovada e registrada', [
            'purchase_id' => $purchase->id,
            'user_id' => $user->id,
            'agent_id' => $agent->id
        ]);

        return response()->json(['status' => 'purchase approved']);
    }

    private function handlePurchaseCanceled(User $user, Agent $agent, string $event)
    {
        $purchase = Purchase::where('user_id', $user->id)
            ->where('agent_id', $agent->id)
            ->first();

        if ($purchase) {
            $purchase->update([
                'active' => false,
                'paused' => true,
                'paused_at' => now(),
            ]);

            PurchaseEvent::create([
                'purchase_id' => $purchase->id,
                'event_type' => strtolower($event),
                'event_time' => now(),
                'note' => "Assinatura cancelada: {$event}",
            ]);
        }

        Log::info('Assinatura desativada', [
            'event' => $event,
            'user_id' => $user->id,
            'agent_id' => $agent->id
        ]);

        return response()->json(['status' => 'purchase canceled']);
    }

    private function handleSubscriptionSuspended(User $user, Agent $agent)
    {
        $purchase = Purchase::where('user_id', $user->id)
            ->where('agent_id', $agent->id)
            ->first();

        if ($purchase) {
            $purchase->update([
                'paused' => true,
                'paused_at' => now(),
            ]);

            PurchaseEvent::create([
                'purchase_id' => $purchase->id,
                'event_type' => 'suspended',
                'event_time' => now(),
                'note' => 'Assinatura suspensa via Hotmart.',
            ]);
        }

        return response()->json(['status' => 'subscription suspended']);
    }

    private function handleSubscriptionReactivated(User $user, Agent $agent)
    {
        $purchase = Purchase::where('user_id', $user->id)
            ->where('agent_id', $agent->id)
            ->first();

        if ($purchase) {
            $purchase->update([
                'paused' => false,
                'paused_at' => null,
            ]);

            PurchaseEvent::create([
                'purchase_id' => $purchase->id,
                'event_type' => 'reactivated',
                'event_time' => now(),
                'note' => 'Assinatura reativada via Hotmart.',
            ]);
        }

        return response()->json(['status' => 'subscription reactivated']);
    }
}