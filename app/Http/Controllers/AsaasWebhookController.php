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

    private function handlePaymentConfirmed($paymentData)
    {
        // Extract customer and metadata
        $customerId = $paymentData['customer'] ?? null;
        $metadata = json_decode($paymentData['externalReference'] ?? '{}', true);
        
        if (!$customerId || !isset($metadata['user_id']) || !isset($metadata['agent_id'])) {
            Log::warning('Missing required data in payment webhook', [
                'payment_id' => $paymentData['id'] ?? null,
                'customer_id' => $customerId,
                'metadata' => $metadata
            ]);
            return response()->json(['status' => 'missing data'], 400);
        }
        
        $userId = $metadata['user_id'];
        $agentId = $metadata['agent_id'];
        
        // Find user and agent
        $user = User::find($userId);
        $agent = Agent::find($agentId);
        
        if (!$user || !$agent) {
            Log::warning('User or agent not found', [
                'user_id' => $userId,
                'agent_id' => $agentId
            ]);
            return response()->json(['status' => 'user or agent not found'], 404);
        }
        
        // Create or update purchase record
        $purchase = Purchase::updateOrCreate([
            'user_id' => $user->id,
            'agent_id' => $agent->id,
        ], [
            'active' => true,
            'paused' => false,
            'paused_at' => null,
            'asaas_subscription_id' => $metadata['subscription_id'] ?? null,
        ]);
        
        // Record the event
        PurchaseEvent::create([
            'purchase_id' => $purchase->id,
            'event_type' => 'payment_confirmed',
            'event_time' => now(),
            'note' => 'Payment confirmed via Asaas webhook.',
            'metadata' => json_encode($paymentData)
        ]);
        
        Log::info('Payment confirmed and recorded', [
            'purchase_id' => $purchase->id,
            'user_id' => $user->id,
            'agent_id' => $agent->id,
            'payment_id' => $paymentData['id'] ?? null
        ]);
        
        return response()->json(['status' => 'payment confirmed']);
    }

    private function handlePaymentOverdue($paymentData)
    {
        // Extract customer and metadata
        $customerId = $paymentData['customer'] ?? null;
        $metadata = json_decode($paymentData['externalReference'] ?? '{}', true);
        
        if (!$customerId || !isset($metadata['user_id']) || !isset($metadata['agent_id'])) {
            Log::warning('Missing required data in payment webhook', [
                'payment_id' => $paymentData['id'] ?? null,
                'customer_id' => $customerId,
                'metadata' => $metadata
            ]);
            return response()->json(['status' => 'missing data'], 400);
        }
        
        $userId = $metadata['user_id'];
        $agentId = $metadata['agent_id'];
        
        // Find user and agent
        $user = User::find($userId);
        $agent = Agent::find($agentId);
        
        if (!$user || !$agent) {
            Log::warning('User or agent not found', [
                'user_id' => $userId,
                'agent_id' => $agentId
            ]);
            return response()->json(['status' => 'user or agent not found'], 404);
        }
        
        // Find purchase record
        $purchase = Purchase::where('user_id', $user->id)
            ->where('agent_id', $agent->id)
            ->first();
            
        if ($purchase) {
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
                'user_id' => $user->id,
                'agent_id' => $agent->id,
                'payment_id' => $paymentData['id'] ?? null
            ]);
        }
        
        return response()->json(['status' => 'payment overdue recorded']);
    }

    private function handlePaymentRefunded($paymentData)
    {
        // Extract customer and metadata
        $customerId = $paymentData['customer'] ?? null;
        $metadata = json_decode($paymentData['externalReference'] ?? '{}', true);
        
        if (!$customerId || !isset($metadata['user_id']) || !isset($metadata['agent_id'])) {
            Log::warning('Missing required data in payment webhook', [
                'payment_id' => $paymentData['id'] ?? null,
                'customer_id' => $customerId,
                'metadata' => $metadata
            ]);
            return response()->json(['status' => 'missing data'], 400);
        }
        
        $userId = $metadata['user_id'];
        $agentId = $metadata['agent_id'];
        
        // Find user and agent
        $user = User::find($userId);
        $agent = Agent::find($agentId);
        
        if (!$user || !$agent) {
            Log::warning('User or agent not found', [
                'user_id' => $userId,
                'agent_id' => $agentId
            ]);
            return response()->json(['status' => 'user or agent not found'], 404);
        }
        
        // Find purchase record
        $purchase = Purchase::where('user_id', $user->id)
            ->where('agent_id', $agent->id)
            ->first();
            
        if ($purchase) {
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
                'user_id' => $user->id,
                'agent_id' => $agent->id,
                'payment_id' => $paymentData['id'] ?? null
            ]);
        }
        
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
        // Extract customer and metadata
        $customerId = $subscriptionData['customer'] ?? null;
        $externalReference = $subscriptionData['externalReference'] ?? '';
        
        // Try to parse the external reference
        list($userId, $agentId) = $this->parseExternalReference($externalReference);
        
        if (!$customerId || !$userId || !$agentId) {
            Log::warning('Missing required data in subscription webhook', [
                'subscription_id' => $subscriptionData['id'] ?? null,
                'customer_id' => $customerId,
                'external_reference' => $externalReference
            ]);
            return response()->json(['status' => 'missing data'], 400);
        }
        
        // Find user and agent
        $user = User::find($userId);
        $agent = Agent::find($agentId);
        
        if (!$user || !$agent) {
            Log::warning('User or agent not found', [
                'user_id' => $userId,
                'agent_id' => $agentId
            ]);
            return response()->json(['status' => 'user or agent not found'], 404);
        }
        
        // Create or update purchase record
        $purchase = Purchase::updateOrCreate([
            'user_id' => $user->id,
            'agent_id' => $agent->id,
        ], [
            'active' => false, // Set to false initially, will be activated when payment is confirmed
            'paused' => false,
            'paused_at' => null,
            'asaas_subscription_id' => $subscriptionData['id'] ?? null,
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
            'user_id' => $user->id,
            'agent_id' => $agent->id,
            'subscription_id' => $subscriptionData['id'] ?? null
        ]);
        
        return response()->json(['status' => 'subscription created']);
    }

    private function handleSubscriptionCancelled($subscriptionData)
    {
        // Extract customer and metadata
        $customerId = $subscriptionData['customer'] ?? null;
        $externalReference = $subscriptionData['externalReference'] ?? '';
        
        // Try to parse the external reference
        list($userId, $agentId) = $this->parseExternalReference($externalReference);
        
        if (!$customerId || !$userId || !$agentId) {
            Log::warning('Missing required data in subscription webhook', [
                'subscription_id' => $subscriptionData['id'] ?? null,
                'customer_id' => $customerId,
                'external_reference' => $externalReference
            ]);
            return response()->json(['status' => 'missing data'], 400);
        }
        
        // Find user and agent
        $user = User::find($userId);
        $agent = Agent::find($agentId);
        
        if (!$user || !$agent) {
            Log::warning('User or agent not found', [
                'user_id' => $userId,
                'agent_id' => $agentId
            ]);
            return response()->json(['status' => 'user or agent not found'], 404);
        }
        
        // Find purchase record
        $purchase = Purchase::where('user_id', $user->id)
            ->where('agent_id', $agent->id)
            ->first();
            
        if ($purchase) {
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
                'user_id' => $user->id,
                'agent_id' => $agent->id,
                'subscription_id' => $subscriptionData['id'] ?? null
            ]);
        }
        
        return response()->json(['status' => 'subscription cancelled']);
    }

    private function handleSubscriptionPaused($subscriptionData)
    {
        // Extract customer and metadata
        $customerId = $subscriptionData['customer'] ?? null;
        $externalReference = $subscriptionData['externalReference'] ?? '';
        
        // Try to parse the external reference
        list($userId, $agentId) = $this->parseExternalReference($externalReference);
        
        if (!$customerId || !$userId || !$agentId) {
            Log::warning('Missing required data in subscription webhook', [
                'subscription_id' => $subscriptionData['id'] ?? null,
                'customer_id' => $customerId,
                'external_reference' => $externalReference
            ]);
            return response()->json(['status' => 'missing data'], 400);
        }
        
        // Find user and agent
        $user = User::find($userId);
        $agent = Agent::find($agentId);
        
        if (!$user || !$agent) {
            Log::warning('User or agent not found', [
                'user_id' => $userId,
                'agent_id' => $agentId
            ]);
            return response()->json(['status' => 'user or agent not found'], 404);
        }
        
        // Find purchase record
        $purchase = Purchase::where('user_id', $user->id)
            ->where('agent_id', $agent->id)
            ->first();
            
        if ($purchase) {
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
                'user_id' => $user->id,
                'agent_id' => $agent->id,
                'subscription_id' => $subscriptionData['id'] ?? null
            ]);
        }
        
        return response()->json(['status' => 'subscription paused']);
    }

    private function handleSubscriptionResumed($subscriptionData)
    {
        // Extract customer and metadata
        $customerId = $subscriptionData['customer'] ?? null;
        $externalReference = $subscriptionData['externalReference'] ?? '';
        
        // Try to parse the external reference
        list($userId, $agentId) = $this->parseExternalReference($externalReference);
        
        if (!$customerId || !$userId || !$agentId) {
            Log::warning('Missing required data in subscription webhook', [
                'subscription_id' => $subscriptionData['id'] ?? null,
                'customer_id' => $customerId,
                'external_reference' => $externalReference
            ]);
            return response()->json(['status' => 'missing data'], 400);
        }
        
        // Find user and agent
        $user = User::find($userId);
        $agent = Agent::find($agentId);
        
        if (!$user || !$agent) {
            Log::warning('User or agent not found', [
                'user_id' => $userId,
                'agent_id' => $agentId
            ]);
            return response()->json(['status' => 'user or agent not found'], 404);
        }
        
        // Find purchase record
        $purchase = Purchase::where('user_id', $user->id)
            ->where('agent_id', $agent->id)
            ->first();
            
        if ($purchase) {
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
                'user_id' => $user->id,
                'agent_id' => $agent->id,
                'subscription_id' => $subscriptionData['id'] ?? null
            ]);
        }
        
        return response()->json(['status' => 'subscription resumed']);
    }
}