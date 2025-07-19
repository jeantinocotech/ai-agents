<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Agent;
use App\Models\Purchase;
use App\Models\PurchaseEvent;
use App\Services\AsaasService;
use Illuminate\Support\Facades\Log;

class AsaasWebhookController extends Controller
{
    protected $asaasService;

    public function __construct(AsaasService $asaasService)
    {
        $this->asaasService = $asaasService;
    }

    public function handle(Request $request)
    {
        
        // Log the complete request for debugging
        Log::info('Asaas Webhook received', [
            'headers' => $request->headers->all(),
            'body' => $request->all()
        ]);

        // Validate the webhook signature if available
        if (!$this->isValidWebhook($request)) {
            Log::warning('Invalid webhook received', ['data' => $request->all()]);
            return response()->json(['status' => 'invalid webhook'], 400);
        }

        // Extract event data
        $event = $request->input('event');
        $payment = $request->input('payment');
        $subscription = $request->input('subscription');
        
        // Log the processed payload for debugging
        Log::info('Asaas Webhook processed', [
            'event' => $event,
            'payment' => $payment,
            'subscription' => $subscription
        ]);

        // Process based on event type
        switch ($event) {
            case 'PAYMENT_CONFIRMED':
            case 'PAYMENT_RECEIVED':
                return $this->handlePaymentConfirmed($payment);
                
            case 'PAYMENT_OVERDUE':
                return $this->handlePaymentOverdue($payment);
                
            case 'PAYMENT_REFUNDED':
            case 'PAYMENT_CHARGEBACK':
                return $this->handlePaymentRefunded($payment);
                
            case 'SUBSCRIPTION_CREATED':
                return $this->handleSubscriptionCreated($subscription);
                
            case 'SUBSCRIPTION_CANCELLED':
                return $this->handleSubscriptionCancelled($subscription);
                
            case 'SUBSCRIPTION_PAUSED':
                return $this->handleSubscriptionPaused($subscription);
                
            case 'SUBSCRIPTION_RESUMED':
                return $this->handleSubscriptionResumed($subscription);
                
            default:
                Log::info('Event not processed', ['event' => $event]);
                return response()->json(['status' => 'event not processed']);
        }
    }

    private function isValidWebhook(Request $request): bool
    {
        // Check for webhook token if configured
        $signature = $request->header('asaas-signature');
        
        if ($signature && config('asaas.webhook_token')) {
            return $this->asaasService->validateWebhookSignature(
                $signature,
                $request->getContent()
            );
        }
        
        // If no signature validation is configured, accept the webhook
        // This is not recommended for production
        return true;
    }

    private function findPurchaseFromData($data): ?Purchase
    {
        $externalReference = $data['externalReference'] ?? '';
        $customerId = $data['customer'] ?? null;
        
        // First try: Find by external reference (purchase ID)
        if (!empty($externalReference) && is_numeric($externalReference)) {
            $purchase = Purchase::find($externalReference);
            if ($purchase) {
                Log::info('Purchase found by external reference', [
                    'purchase_id' => $purchase->id,
                    'external_reference' => $externalReference
                ]);
                return $purchase;
            }
        }
        
        // Second try: Find by customer ID if it matches a user
        if ($customerId) {
            // Try to find user by Asaas customer ID
            $user = User::where('asaas_customer_id', $customerId)->first();
            if ($user) {
                // If subscription data has subscription ID, try to find by that
                if (isset($data['id'])) {
                    $purchase = Purchase::where('user_id', $user->id)
                        ->where('asaas_subscription_id', $data['id'])
                        ->first();
                    if ($purchase) {
                        Log::info('Purchase found by user and subscription ID', [
                            'purchase_id' => $purchase->id,
                            'user_id' => $user->id,
                            'subscription_id' => $data['id']
                        ]);
                        return $purchase;
                    }
                }
                
                // Find the most recent active purchase for this user
                $purchase = Purchase::where('user_id', $user->id)
                    ->orderBy('created_at', 'desc')
                    ->first();
                if ($purchase) {
                    Log::info('Purchase found by user (most recent)', [
                        'purchase_id' => $purchase->id,
                        'user_id' => $user->id
                    ]);
                    return $purchase;
                }
            }
        }
        
        // Third try: Parse external reference as JSON (backward compatibility)
        if (!empty($externalReference)) {
            $metadata = json_decode($externalReference, true);
            if (is_array($metadata) && isset($metadata['user_id']) && isset($metadata['agent_id'])) {
                $purchase = Purchase::where('user_id', $metadata['user_id'])
                    ->where('agent_id', $metadata['agent_id'])
                    ->first();
                if ($purchase) {
                    Log::info('Purchase found by JSON metadata', [
                        'purchase_id' => $purchase->id,
                        'user_id' => $metadata['user_id'],
                        'agent_id' => $metadata['agent_id']
                    ]);
                    return $purchase;
                }
            }
        }
        
        Log::warning('Purchase not found', [
            'external_reference' => $externalReference,
            'customer_id' => $customerId,
            'data_id' => $data['id'] ?? null
        ]);
        
        return null;
    }

    private function handlePaymentConfirmed($paymentData)
    {
       
        Log::info('INICIO handlePaymentConfirmed', [
            'paymentData' => $paymentData
        ]);
    
        $subscriptionId = $paymentData['subscription'] ?? null;

        Log::info('subscriptionId extraído', ['subscriptionId' => $subscriptionId]);
    
        if ($subscriptionId) {

            
            Log::info('Buscando purchase por asaas_subscription_id...');
            $purchase = Purchase::where('asaas_subscription_id', $subscriptionId)->first();
            Log::info('Resultado da busca:', ['purchase' => $purchase]);
    

            if ($purchase) {
                $purchase->active = true;
                $purchase->paused = false;
                $purchase->paused_at = null;
                $purchase->save();

                Log::info('Purchase ativado via webhook de pagamento', [
                    'purchase_id' => $purchase->id,
                    'asaas_subscription_id' => $subscriptionId
                ]);

                PurchaseEvent::create([
                    'purchase_id' => $purchase->id,
                    'event_type' => 'payment_confirmed',
                    'event_time' => now(),
                    'note' => 'Payment confirmed via Asaas webhook.',
                    'metadata' => json_encode($paymentData)
                ]);

                return response()->json(['status' => 'payment confirmed']);
            } else {
                Log::warning('Purchase não encontrado pelo asaas_subscription_id no pagamento', [
                    'asaas_subscription_id' => $subscriptionId,
                    'payment_id' => $paymentData['id'] ?? null
                ]);
                return response()->json(['ok' => true]);
            }
        }

    
        // Fallback antigo: buscar por externalReference se não vier subscription
        $metadata = json_decode($paymentData['externalReference'] ?? '{}', true);
        if (isset($metadata['user_id']) && isset($metadata['agent_id'])) {
            $userId = $metadata['user_id'];
            $agentId = $metadata['agent_id'];
            $purchase = Purchase::where('user_id', $userId)
                ->where('agent_id', $agentId)
                ->orderBy('created_at', 'desc')
                ->first();
            if ($purchase) {
                $purchase->active = true;
                $purchase->paused = false;
                $purchase->paused_at = null;
                $purchase->save();

                Log::info('Purchase ativado via webhook de pagamento (fallback)', [
                    'purchase_id' => $purchase->id,
                    'user_id' => $userId,
                    'agent_id' => $agentId
                ]);

                PurchaseEvent::create([
                    'purchase_id' => $purchase->id,
                    'event_type' => 'payment_confirmed',
                    'event_time' => now(),
                    'note' => 'Payment confirmed via Asaas webhook.',
                    'metadata' => json_encode($paymentData)
                ]);

                return response()->json(['status' => 'payment confirmed (fallback)']);
            }
        }

        Log::warning('Purchase não encontrado para ativação no webhook de pagamento', [
            'payment_id' => $paymentData['id'] ?? null,
            'subscriptionId' => $subscriptionId,
            'externalReference' => $paymentData['externalReference'] ?? null
        ]);
    return response()->json(['ok' => true]);
}

    private function handlePaymentOverdue($paymentData)
    {
        $purchase = $this->findPurchaseFromData($paymentData);
        
        if (!$purchase) {
            Log::warning('Purchase not found for payment overdue', [
                'payment_data' => $paymentData
            ]);

            //return response()->json(['status' => 'purchase not found'], 404);
            return response()->json(['status' => 'payment not found for overdue recorded']);
        }
        
        // Record the event but don't change status yet
        PurchaseEvent::create([
            'purchase_id' => $purchase->id,
            'event_type' => 'payment_overdue',
            'event_time' => now(),
            'note' => 'Payment overdue via Asaas webhook.',
            'metadata' => json_encode($paymentData)
        ]);
        
        Log::info('Payment overdue recorded', [
            'purchase_id' => $purchase->id,
            'user_id' => $purchase->user_id,
            'agent_id' => $purchase->agent_id,
            'payment_id' => $paymentData['id'] ?? null
        ]);
        
        return response()->json(['status' => 'payment overdue recorded']);
    }

    private function handlePaymentRefunded($paymentData)
    {
        $purchase = $this->findPurchaseFromData($paymentData);
        
        if (!$purchase) {
            Log::warning('Purchase not found for payment refund', [
                'payment_data' => $paymentData
            ]);
            return response()->json(['status' => 'purchase not found'], 200);
        }
        
        // Update purchase status
        $purchase->update([
            'active' => false,
            'paused' => true,
            'paused_at' => now(),
        ]);
        
        // Record the event
        PurchaseEvent::create([
            'purchase_id' => $purchase->id,
            'event_type' => 'payment_refunded',
            'event_time' => now(),
            'note' => 'Payment refunded via Asaas webhook.',
            'metadata' => json_encode($paymentData)
        ]);
        
        Log::info('Payment refunded and purchase deactivated', [
            'purchase_id' => $purchase->id,
            'user_id' => $purchase->user_id,
            'agent_id' => $purchase->agent_id,
            'payment_id' => $paymentData['id'] ?? null
        ]);
        
        return response()->json(['status' => 'payment refunded']);
    }

    /**
     * Parse the external reference string to extract user_id and agent_id
     * Format: "user_{user_id}_agent_{agent_id}"
     */
    private function parseExternalReference(string $externalReference): array
    {
        $userId = null;
        $agentId = null;
        
        // Try to parse as JSON first (for backward compatibility)
        $metadata = json_decode($externalReference, true);
        if (is_array($metadata) && isset($metadata['user_id']) && isset($metadata['agent_id'])) {
            return [$metadata['user_id'], $metadata['agent_id']];
        }
        
        // Try to parse the new string format
        if (preg_match('/user_(\d+)_agent_(\d+)/', $externalReference, $matches)) {
            if (count($matches) === 3) {
                $userId = (int)$matches[1];
                $agentId = (int)$matches[2];
            }
        }
        
        return [$userId, $agentId];
    }
    
    private function handleSubscriptionCreated($subscriptionData)
    {
        $purchase = $this->findPurchaseFromData($subscriptionData);
        
        if (!$purchase) {
            Log::warning('Purchase not found for subscription creation', [
                'subscription_data' => $subscriptionData
            ]);
            return response()->json(['status' => 'purchase not found'], 200);
        }
        
        // Update purchase record with subscription ID
        $purchase->update([
            'asaas_subscription_id' => $subscriptionData['id'] ?? null,
            'active' => false, // Set to false initially, will be activated when payment is confirmed
            'paused' => false,
            'paused_at' => null,
        ]);
        
        // Record the event
        PurchaseEvent::create([
            'purchase_id' => $purchase->id,
            'event_type' => 'subscription_created',
            'event_time' => now(),
            'note' => 'Subscription created via Asaas webhook.',
            'metadata' => json_encode($subscriptionData)
        ]);
        
        Log::info('Subscription created and recorded', [
            'purchase_id' => $purchase->id,
            'user_id' => $purchase->user_id,
            'agent_id' => $purchase->agent_id,
            'subscription_id' => $subscriptionData['id'] ?? null
        ]);
        
        return response()->json(['status' => 'subscription created']);
    }

    private function handleSubscriptionCancelled($subscriptionData)
    {
        $purchase = $this->findPurchaseFromData($subscriptionData);
        
        if (!$purchase) {
            Log::warning('Purchase not found for subscription cancellation', [
                'subscription_data' => $subscriptionData
            ]);
            return response()->json(['status' => 'purchase not found'], 200);
        }
        
        // Update purchase status
        $purchase->update([
            'active' => false,
            'paused' => true,
            'paused_at' => now(),
        ]);
        
        // Record the event
        PurchaseEvent::create([
            'purchase_id' => $purchase->id,
            'event_type' => 'subscription_cancelled',
            'event_time' => now(),
            'note' => 'Subscription cancelled via Asaas webhook.',
            'metadata' => json_encode($subscriptionData)
        ]);
        
        Log::info('Subscription cancelled and purchase deactivated', [
            'purchase_id' => $purchase->id,
            'user_id' => $purchase->user_id,
            'agent_id' => $purchase->agent_id,
            'subscription_id' => $subscriptionData['id'] ?? null
        ]);
        
        return response()->json(['status' => 'subscription cancelled']);
    }


    private function handleSubscriptionPaused($subscriptionData)
    {
        $purchase = $this->findPurchaseFromData($subscriptionData);
        
        if (!$purchase) {
            Log::warning('Purchase not found for subscription pause', [
                'subscription_data' => $subscriptionData
            ]);
            return response()->json(['status' => 'purchase not found'], 200);
        }
        
        // Update purchase status
        $purchase->update([
            'paused' => true,
            'paused_at' => now(),
        ]);
        
        // Record the event
        PurchaseEvent::create([
            'purchase_id' => $purchase->id,
            'event_type' => 'subscription_paused',
            'event_time' => now(),
            'note' => 'Subscription paused via Asaas webhook.',
            'metadata' => json_encode($subscriptionData)
        ]);
        
        Log::info('Subscription paused', [
            'purchase_id' => $purchase->id,
            'user_id' => $purchase->user_id,
            'agent_id' => $purchase->agent_id,
            'subscription_id' => $subscriptionData['id'] ?? null
        ]);
        
        return response()->json(['status' => 'subscription paused']);
    }
    private function handleSubscriptionResumed($subscriptionData)
    {
        $purchase = $this->findPurchaseFromData($subscriptionData);
        
        if (!$purchase) {
            Log::warning('Purchase not found for subscription resume', [
                'subscription_data' => $subscriptionData
            ]);
            return response()->json(['status' => 'purchase not found'], 200);
        }
        
        // Update purchase status
        $purchase->update([
            'paused' => false,
            'paused_at' => null,
        ]);
        
        // Record the event
        PurchaseEvent::create([
            'purchase_id' => $purchase->id,
            'event_type' => 'subscription_resumed',
            'event_time' => now(),
            'note' => 'Subscription resumed via Asaas webhook.',
            'metadata' => json_encode($subscriptionData)
        ]);
        
        Log::info('Subscription resumed', [
            'purchase_id' => $purchase->id,
            'user_id' => $purchase->user_id,
            'agent_id' => $purchase->agent_id,
            'subscription_id' => $subscriptionData['id'] ?? null
        ]);
        
        return response()->json(['status' => 'subscription resumed']);
    }
}