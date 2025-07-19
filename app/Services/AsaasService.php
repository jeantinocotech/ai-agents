<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\ConnectionException;
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
     * Request timeout in seconds
     */
    protected $timeout = 30;


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

          // Ou timeouts diferentes para sandbox vs production
          $this->timeout = $this->isSandbox() 
          ? config('asaas.timeout.sandbox', 60) 
          : config('asaas.timeout.production', 30);

          Log::info('Asaas Service initialized', [
            'environment' => $this->isSandbox() ? 'sandbox' : 'production',
            'timeout' => $this->timeout,
            'api_url' => $this->apiUrl
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
        return $this->makeRequest('get', 'customers?limit=1', [], 3, 30);
    }

    /**
     * Check if circuit breaker is open
     */
    private function isCircuitBreakerOpen(): bool
    {
        $failureCount = Cache::get('asaas_circuit_breaker_failures', 0);
        $threshold = config('asaas.circuit_breaker.failure_threshold', 10);
        $lastFailureTime = Cache::get('asaas_circuit_breaker_last_failure');
        $recoveryTimeout = config('asaas.circuit_breaker.recovery_timeout', 300);
        
        // If circuit breaker is open, check if recovery time has passed
        if ($failureCount >= $threshold && $lastFailureTime) {
            if (time() - $lastFailureTime >= $recoveryTimeout) {
                Log::info('Circuit breaker recovery timeout reached - allowing test request');
                return false; // Allow one test request
            }
        }
        
        return $failureCount >= $threshold;
    }

    /**
     * Increment circuit breaker failure count
     */
    private function incrementCircuitBreakerFailures(): void
    {
        $failureCount = Cache::get('asaas_circuit_breaker_failures', 0);
        $recoveryTimeout = config('asaas.circuit_breaker.recovery_timeout', 300);
        
        Cache::put('asaas_circuit_breaker_failures', $failureCount + 1, $recoveryTimeout);
        Cache::put('asaas_circuit_breaker_last_failure', time(), $recoveryTimeout);
        
        Log::warning('Circuit breaker failure count incremented', [
            'failure_count' => $failureCount + 1,
            'threshold' => config('asaas.circuit_breaker.failure_threshold', 10)
        ]);
    }

      /**
     * Determine if error is retryable
     */
    private function isRetryableError($response = null, $exception = null): bool
    {
        // Non-retryable HTTP status codes (client errors)
        $nonRetryableStatusCodes = [400, 401, 403, 404, 422, 429];
        
        if ($response && in_array($response->status(), $nonRetryableStatusCodes)) {
            return false;
        }
        
        // Check for specific exception types
        if ($exception) {
            // Connection timeouts are retryable
            if ($exception instanceof ConnectionException) {
                return true;
            }
            
            // Check for timeout in message
            if (str_contains($exception->getMessage(), 'timeout') || 
                str_contains($exception->getMessage(), 'timed out')) {
                return true;
            }
        }
        
        // Server errors (5xx) are retryable
        if ($response && $response->status() >= 500) {
            return true;
        }
        
        return true; // Default to retryable
    }

     /**
     * Calculate backoff delay with jitter
     */
    private function calculateBackoffDelay(int $attempt): int
    {
        $baseDelay = config('asaas.retry.base_delay', 1);
        $backoffMultiplier = config('asaas.retry.backoff_multiplier', 2);
        $maxDelay = config('asaas.retry.max_delay', 60);
        
        $delay = $baseDelay * pow($backoffMultiplier, $attempt - 1);
        
        // Add jitter (random variation) to prevent thundering herd
        $jitter = rand(0, (int)($delay * 0.1));
        $delay += $jitter;
        
        return min($delay, $maxDelay);
    }

    /**
     * Reset circuit breaker on successful request
     */
    private function resetCircuitBreaker(): void
    {
        $hadFailures = Cache::get('asaas_circuit_breaker_failures', 0) > 0;
        
        Cache::forget('asaas_circuit_breaker_failures');
        Cache::forget('asaas_circuit_breaker_last_failure');
        
        if ($hadFailures) {
            Log::info('Circuit breaker reset after successful request');
        }
    }

    /**
     * Make an authenticated request to Asaas API
     */
    protected function makeRequest(string $method, string $endpoint, array $data = [], int $retries = null, int $customTimeout = null): ?array
    {
        if (!$this->apiKey) {
            Log::error('Cannot make Asaas API request: No API key available', [
                'environment' => $this->isSandbox() ? 'sandbox' : 'production'
            ]);
            return null;
        }

        // Check circuit breaker
        if ($this->isCircuitBreakerOpen()) {
            Log::warning('Circuit breaker is open - request blocked', [
                'endpoint' => $endpoint,
                'method' => $method
            ]);
            return null;
        }


        // Get retry config
        $retries = $retries ?? config('asaas.retry.max_attempts', 3);
        $attempt = 0;
        $lastException = null;
        $startTime = microtime(true);
        $backoffMultiplier = config('asaas.retry.backoff_multiplier', 2);
        $baseDelay = config('asaas.retry.base_delay', 1);
        $maxDelay = config('asaas.retry.max_delay', 60);

        while ($attempt < $retries) {
       
            try {
                $attempt++;
                // Ensure endpoint doesn't start with /
                $endpoint = ltrim($endpoint, '/');
                
                // Build the full URL
                $url = "{$this->apiUrl}/api/v3/{$endpoint}";

                Log::info("🔁 Request to Asaas", [
                    'method' => $method,
                    'url' => $url,
                    'data' => $data,
                    'attempt' => $attempt,
                    'max_attempts' => $retries,
                    'environment' => $this->isSandbox() ? 'sandbox' : 'production'
                ]);

                // Make the request
                $timeout = $customTimeout ?? $this->timeout;
    
                $response = Http::timeout($timeout)
                ->connectTimeout(config('asaas.timeout.connect', 10))
                ->retry(1, 500) // Single retry with 500ms delay for connection issues
                ->withHeaders([
                    'access_token' => $this->apiKey,
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])
                ->$method($url, $data);

                // Calculate request duration
                $duration = microtime(true) - $startTime;

                Log::info('🔍 Raw Asaas response', [
                    'status' => $response->status(),
                    'headers' => $response->headers(),
                    'body' => $response->body(),
                    'url' => $url,
                    'attempt' => $attempt,
                    'duration' => round($duration, 2),
                    'timeout_used' => $timeout
                ]);

                if ($response->successful()) {
                    Log::info('✅ Successful Asaas API response', [
                        'status' => $response->status(),
                        'content_type' => $response->header('Content-Type'),
                        'attempt' => $attempt,
                        'total_duration' => round($duration, 2)
                    ]);

                    // Reset circuit breaker on success
                    $this->resetCircuitBreaker();

                    return $response->json();
                }

                 // Check if error is retryable
                 if (!$this->isRetryableError($response)) {
                    Log::error('❌ Client error in Asaas API call - not retrying', [
                        'status' => $response->status(),
                        'response_body' => $response->body(),
                        'url' => $url,
                        'method' => $method,
                        'data_sent' => $this->sanitizeLogData($data),
                        'attempt' => $attempt,
                        'duration' => round($duration, 2)
                    ]);
                    return null;
                }

                // Log server errors but continue retrying
                Log::warning('⚠️ Server error in Asaas API call - will retry', [
                    'status' => $response->status(),
                    'response_body' => $response->body(),
                    'url' => $url,
                    'method' => $method,
                    'data_sent' => $this->sanitizeLogData($data),
                    'attempt' => $attempt,
                    'retries_remaining' => $retries - $attempt,
                    'duration' => round($duration, 2)
                ]);

                // Increment circuit breaker for server errors
                $this->incrementCircuitBreakerFailures();


                // Handle specific error codes that shouldn't be retried
                if (in_array($response->status(), [400, 401, 403, 404, 422])) {
                    Log::error('❌ Client error in Asaas API call - not retrying', [
                        'status' => $response->status(),
                        'headers' => $response->headers(),
                        'raw_body' => $response->body(),
                        'url' => $url,
                        'method' => $method,
                        'data_sent' => $data,
                        'attempt' => $attempt,
                        'duration' => round($duration, 2)
                    ]);
                    // Don't increment circuit breaker for client errors
                    return null;
                }

               // Wait before retrying
               if ($attempt < $retries) {
                $delay = $this->calculateBackoffDelay($attempt);
                Log::info("⏳ Waiting {$delay} seconds before retry");
                sleep($delay);
            }

            } catch (\Illuminate\Http\Client\RequestException $e) {
                $lastException = $e;
                $duration = microtime(true) - $startTime;
                $isTimeout = $this->isTimeoutException($e);
                $isRetryable = $this->isRetryableError(null, $e);

                //$isTimeout = str_contains($e->getMessage(), 'timeout') || str_contains($e->getMessage(), 'timed out');
    
                Log::warning("Request exception in Asaas API request (Attempt {$attempt})", [
                    'method' => $method,
                    'url' => $url,
                    'error' => $e->getMessage(),
                    'is_timeout' => $isTimeout,
                    'is_retryable' => $isRetryable,
                    'timeout_setting' => $timeout,
                    'duration' => round($duration, 2),
                    'environment' => $this->isSandbox() ? 'sandbox' : 'production',
                    'retries_remaining' => $retries - $attempt
                ]);

                 // Don't retry non-retryable errors
                 if (!$isRetryable) {
                    break;
                }

                // Increment circuit breaker for connection issues
                $this->incrementCircuitBreakerFailures();

                // Wait before retrying
                if ($attempt < $retries) {
                    $delay = $this->calculateBackoffDelay($attempt);
                    sleep($delay);
                }

            } catch (\Exception $e) {
                $lastException = $e;
                $duration = microtime(true) - $startTime;

                Log::error("Exception in Asaas API request (Attempt {$attempt})", [
                    'method' => $method,
                    'url' => $url,
                    'error' => $e->getMessage(),
                    'duration' => round($duration, 2),
                    'environment' => $this->isSandbox() ? 'sandbox' : 'production',
                    'retries_remaining' => $retries - $attempt
                ]);
                
                // Increment circuit breaker for general exceptions
                $this->incrementCircuitBreakerFailures();

                // Wait before retrying
                if ($attempt < $retries) {
                    $delay = $this->calculateBackoffDelay($attempt);
                    sleep($delay);
                }

            }
        } 

       // All attempts failed
       $totalDuration = microtime(true) - $startTime;
       Log::error("All attempts failed for Asaas API request: $method $url", [
           'total_attempts' => $attempt,
           'total_duration' => round($totalDuration, 2),
           'last_exception' => $lastException ? $lastException->getMessage() : 'Unknown error',
           'environment' => $this->isSandbox() ? 'sandbox' : 'production'
       ]);

        return null;
    } 


     /**
     * Check if exception is timeout-related
     */
    private function isTimeoutException(\Exception $e): bool
    {
        $message = strtolower($e->getMessage());
        return str_contains($message, 'timeout') || 
               str_contains($message, 'timed out') ||
               str_contains($message, 'connection timeout') ||
               str_contains($message, 'read timeout');
    }

    /**
     * Sanitize data for logging (remove sensitive information)
     */
    private function sanitizeLogData(array $data): array
    {
        $sensitiveKeys = ['access_token', 'password', 'token', 'secret'];
        
        foreach ($sensitiveKeys as $key) {
            if (isset($data[$key])) {
                $data[$key] = '***';
            }
        }
        
        return $data;
    }


     /**
     * Make a request with critical operations timeout
     */
    protected function makeCriticalRequest(string $method, string $endpoint, array $data = []): ?array
    {
        $criticalTimeout = config('asaas.timeout.critical_operations', 90);
        $criticalRetries = config('asaas.retry.critical_operations', 5);
        
        return $this->makeRequest($method, $endpoint, $data, $criticalRetries, $criticalTimeout);
    }


    /**
     * Create a customer in Asaas
     */
    public function createCustomer(array $customerData): ?array
    {
       // Add idempotency key to prevent duplicates
       if (!isset($customerData['idempotencyKey'])) {
        $customerData['idempotencyKey'] = 'customer_' . md5($customerData['email'] . '_' . ($customerData['cpfCnpj'] ?? ''));
        }

         return $this->makeCriticalRequest('post', 'customers', $customerData);
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
        Log::info("Customer searching for email: ", ['email' => $email]);

          // Try cache first
        $cacheKey = "asaas_customer_email_" . md5($email);
        $cacheTimeout = config('asaas.cache.customers', 3600);
        $cachedCustomer = Cache::get($cacheKey);
        
        if ($cachedCustomer) {
            Log::info("Customer found in cache", ['email' => $email]);
            return $cachedCustomer;
        }

        // Use urlencode to properly encode the email for query string
        $encodedEmail = urlencode($email);
        $response = $this->makeRequest('get', "customers?email={$encodedEmail}");
        
        if ($response && isset($response['data']) && !empty($response['data'])) {
            Log::info("Customer search response: ", [
                'total_count' => $response['totalCount'] ?? 0,
                'data_count' => count($response['data']),
                'first_customer_email' => $response['data'][0]['email'] ?? null
            ]);
            
            // Filter manually to ensure we get the exact match
            foreach ($response['data'] as $customer) {
                if (strtolower($customer['email']) === strtolower($email)) {
                    Log::info("Exact customer match found: ", [
                        'customer_id' => $customer['id'],
                        'email' => $customer['email']
                    ]);

                     // Cache the result
                     Cache::put($cacheKey, $customer, $cacheTimeout);

                    return $customer;
                }
            }
            
            Log::warning("No exact email match found", [
                'searched_email' => $email,
                'returned_emails' => array_column($response['data'], 'email')
            ]);
        }
        
        Log::info("No customer found for email", ['email' => $email]);
        return null;
    }

    public function findCustomerByExternalReference(string $externalReference): ?array
    {
       // Try cache first
       $cacheKey = "asaas_customer_ref_" . md5($externalReference);
       $cacheTimeout = config('asaas.cache.customers', 3600);
       $cachedCustomer = Cache::get($cacheKey);
       
       if ($cachedCustomer) {
           Log::info("Customer found in cache by external reference", ['ref' => $externalReference]);
           return $cachedCustomer;
       }
        
        $response = $this->makeRequest('get', 'customers', [
            'externalReference' => $externalReference
        ]);

        if ($response && isset($response['data']) && count($response['data']) > 0) {
            $customer = $response['data'][0];
            // Cache the result for 1 hour
            Cache::put($cacheKey, $customer, $cacheTimeout);
            return $customer;
        
        }

        return null;
    }

    /**
     * Create a payment in Asaas
     */
    public function createPayment(array $paymentData): ?array
    {
         // Add idempotency key to prevent duplicates
         if (!isset($paymentData['idempotencyKey'])) {
            $paymentData['idempotencyKey'] = 'payment_' . md5(json_encode($paymentData) . time());
        }

        return $this->makeCriticalRequest('post', 'payments', $paymentData);
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
        $cacheKey = "asaas_pix_keys";
        $cacheTimeout = config('asaas.cache.pix_keys', 1800);
        $cachedKeys = Cache::get($cacheKey);
        
        if ($cachedKeys) {
            return $cachedKeys;
        }

         $response = $this->makeRequest('get', 'pix/addressKeys');
        
        if ($response) {
            // Cache for 30 minutes
            Cache::put($cacheKey, $response, $cacheTimeout);
        }
        
        return $response;
    }

    /**
     * Create a PIX key
     */
    public function createPixKey(string $type = 'EVP'): ?array
    {
        // Clear cache when creating new key
        Cache::forget("asaas_pix_keys");

        return $this->makeCriticalRequest('post', 'pix/addressKeys', [
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
       // Add idempotency key to prevent duplicates
        if (!isset($subscriptionData['idempotencyKey'])) {
            $subscriptionData['idempotencyKey'] = 'subscription_' . md5(json_encode($subscriptionData) . time());
        }
        
        return $this->makeCriticalRequest('post', 'subscriptions', $subscriptionData);
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
        return $this->makeCriticalRequest('post', "subscriptions/{$subscriptionId}/cancel");
    }

    /**
     * Pause a subscription
     */
    public function pauseSubscription(string $subscriptionId): ?array
    {
        return $this->makeCriticalRequest('post', "subscriptions/{$subscriptionId}/pause");
    }

    /**
     * Resume a subscription
     */
    public function resumeSubscription(string $subscriptionId): ?array
    {
        return $this->makeCriticalRequest('post', "subscriptions/{$subscriptionId}/reactivate");
    }

    /**
     * Create a payment link
     */
    public function createPaymentLink(array $linkData): ?array
    {
        return $this->makeCriticalRequest('post', 'paymentLinks', $linkData);
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
  /**
     * Get circuit breaker status
     */
    public function getCircuitBreakerStatus(): array
    {
        $failureCount = Cache::get('asaas_circuit_breaker_failures', 0);
        $threshold = config('asaas.circuit_breaker.failure_threshold', 10);
        $lastFailureTime = Cache::get('asaas_circuit_breaker_last_failure');
        
        return [
            'failure_count' => $failureCount,
            'threshold' => $threshold,
            'is_open' => $failureCount >= $threshold,
            'last_failure_time' => $lastFailureTime ? date('Y-m-d H:i:s', $lastFailureTime) : null,
            'status' => $failureCount >= $threshold ? 'OPEN' : 'CLOSED'
        ];
    }
    
    /**
     * Reset circuit breaker manually
     */
    public function resetCircuitBreakerManually(): void
    {
        $this->resetCircuitBreaker();
        Log::info('Circuit breaker reset manually');
    }

    /**
     * Health check method
     */
    public function healthCheck(): array
    {
        $startTime = microtime(true);
        $response = $this->testConnection();
        $duration = round((microtime(true) - $startTime) * 1000, 2);
        
        return [
            'status' => $response ? 'healthy' : 'unhealthy',
            'response_time_ms' => $duration,
            'environment' => $this->isSandbox() ? 'sandbox' : 'production',
            'circuit_breaker' => $this->getCircuitBreakerStatus(),
            'timestamp' => now()->toISOString()
        ];
    }
}