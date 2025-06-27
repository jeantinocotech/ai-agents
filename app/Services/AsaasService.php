<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class AsaasService
{
    /**
     * The base URL for API requests
     */
    protected $apiUrl;

    /**
     * The API key for authentication
     */
    protected $apiKey;

    /**
     * Constructor
     */
    public function __construct()
    {
        // Determine if we're using sandbox or production
        $this->apiUrl = $this->isSandbox() 
            ? config('asaas.api_url.sandbox') 
            : config('asaas.api_url.production');
        
        // Get API key from config
        $this->apiKey = config('asaas.api_key');

        Log::info('Asaas Service initialized', [
            'environment' => $this->isSandbox() ? 'sandbox' : 'production'
        ]);
    }

    /**
     * Determine if we're in sandbox mode
     */
    public function isSandbox(): bool
    {
        return !empty(config('asaas.sandbox'));
    }

    /**
     * Test the connection to the Asaas API
     */
    public function testConnection(): ?array
    {
        return $this->makeRequest('get', 'customers?limit=1');
    }

    /**
     * Make an authenticated request to Asaas API
     */
    protected function makeRequest(string $method, string $endpoint, array $data = []): ?array
    {
        if (!$this->apiKey) {
            Log::error('Cannot make Asaas API request: No API key available', [
                'environment' => $this->isSandbox() ? 'sandbox' : 'production'
            ]);
            return null;
        }

        try {
            // Ensure endpoint doesn't start with /
            $endpoint = ltrim($endpoint, '/');
            
            // Build the full URL
            $url = "{$this->apiUrl}/api/v3/{$endpoint}";

            Log::info("🔁 Request to Asaas", [
                'method' => $method,
                'url' => $url,
                'data' => $data,
                'environment' => $this->isSandbox() ? 'sandbox' : 'production'
            ]);

            // Make the request
            $response = Http::withHeaders([
                'access_token' => $this->apiKey,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->$method($url, $data);

            Log::info('🔍 Raw Asaas response', [
                'status' => $response->status(),
                'headers' => $response->headers(),
                'body' => $response->body(),
                'url' => $url
            ]);

            if ($response->successful()) {
                Log::info('✅ Successful Asaas API response', [
                    'status' => $response->status(),
                    'content_type' => $response->header('Content-Type'),
                    'is_json' => $response->header('Content-Type') === 'application/json',
                ]);

                return $response->json();
            }

            Log::error('❌ Error in Asaas API call', [
                'status' => $response->status(),
                'headers' => $response->headers(),
                'raw_body' => $response->body(),
                'url' => $url,
                'method' => $method,
                'data_sent' => $data
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error("Exception in Asaas API request: $method $url", [
                'error' => $e->getMessage(),
                'environment' => $this->isSandbox() ? 'sandbox' : 'production'
            ]);
            return null;
        }
    }

    /**
     * Create a customer in Asaas
     */
    public function createCustomer(array $customerData): ?array
    {
        return $this->makeRequest('post', 'customers', $customerData);
    }

    /**
     * Get a customer by ID
     */
    public function getCustomer(string $customerId): ?array
    {
        return $this->makeRequest('get', "customers/{$customerId}");
    }

    /**
     * Find a customer by email
     */
    public function findCustomerByEmail(string $email): ?array
    {
        $response = $this->makeRequest('get', "customers?email={$email}");
        
        if ($response && isset($response['data']) && !empty($response['data'])) {
            return $response['data'][0];
        }
        
        return null;
    }

    /**
     * Create a payment in Asaas
     */
    public function createPayment(array $paymentData): ?array
    {
        return $this->makeRequest('post', 'payments', $paymentData);
    }

    /**
     * Get a payment by ID
     */
    public function getPayment(string $paymentId): ?array
    {
        return $this->makeRequest('get', "payments/{$paymentId}");
    }

    /**
     * List PIX keys
     */
    public function listPixKeys(): ?array
    {
        return $this->makeRequest('get', 'pix/addressKeys');
    }

    /**
     * Create a PIX key
     */
    public function createPixKey(string $type = 'EVP'): ?array
    {
        return $this->makeRequest('post', 'pix/addressKeys', [
            'type' => $type
        ]);
    }

    /**
     * Create a static PIX QR code
     */
    public function createStaticPixQrCode(array $qrCodeData): ?array
    {
        return $this->makeRequest('post', 'pix/qrCodes/static', $qrCodeData);
    }

    /**
     * Get PIX QR code for a payment
     */
    public function getPixQrCode(string $paymentId): ?array
    {
        return $this->makeRequest('get', "payments/{$paymentId}/pixQrCode");
    }

    /**
     * Create a subscription in Asaas
     */
    public function createSubscription(array $subscriptionData): ?array
    {
        return $this->makeRequest('post', 'subscriptions', $subscriptionData);
    }

    /**
     * Get a subscription by ID
     */
    public function getSubscription(string $subscriptionId): ?array
    {
        return $this->makeRequest('get', "subscriptions/{$subscriptionId}");
    }

    /**
     * Cancel a subscription
     */
    public function cancelSubscription(string $subscriptionId): ?array
    {
        return $this->makeRequest('post', "subscriptions/{$subscriptionId}/cancel");
    }

    /**
     * Pause a subscription
     */
    public function pauseSubscription(string $subscriptionId): ?array
    {
        return $this->makeRequest('post', "subscriptions/{$subscriptionId}/pause");
    }

    /**
     * Resume a subscription
     */
    public function resumeSubscription(string $subscriptionId): ?array
    {
        return $this->makeRequest('post', "subscriptions/{$subscriptionId}/reactivate");
    }

    /**
     * Create a payment link
     */
    public function createPaymentLink(array $linkData): ?array
    {
        return $this->makeRequest('post', 'paymentLinks', $linkData);
    }

    /**
     * Get a payment link by ID
     */
    public function getPaymentLink(string $linkId): ?array
    {
        return $this->makeRequest('get', "paymentLinks/{$linkId}");
    }

    /**
     * Validate webhook signature
     */
    public function validateWebhookSignature(string $signature, string $payload): bool
    {
        $webhookToken = config('asaas.webhook_token');
        
        if (!$webhookToken) {
            Log::warning('Webhook token not configured');
            return false;
        }
        
        // Asaas uses HMAC-SHA256 for webhook signatures
        $expectedSignature = hash_hmac('sha256', $payload, $webhookToken);
        
        return hash_equals($expectedSignature, $signature);
    }
}