<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class HotmartServiceUpdated
{
    // API endpoints
    private $authUrl = 'https://api-sec-vlc.hotmart.com';
    private $apiUrl = 'https://api-hot-connect.hotmart.com';
    private $marketplaceUrl = 'https://developers.hotmart.com';
    private $paymentsUrl = 'https://api-sec-vlc.hotmart.com';
    
    protected $accessToken;
    protected $basicAuth;

    public function __construct()
    {
        // Initialize basic auth string (used as an alternative to OAuth)
        $clientId = env('HOTMART_CLIENT_ID');
        $clientSecret = env('HOTMART_CLIENT_SECRET');
        $this->basicAuth = base64_encode("$clientId:$clientSecret");
        
        // Initialize access token
        $this->accessToken = $this->getAccessToken();
    }

    /**
     * Get access token using multiple methods
     */
    public function getAccessToken(): ?string
    {
        // Try to get from cache first
        $cachedToken = Cache::get('hotmart_access_token');
        if ($cachedToken) {
            Log::info('Using cached Hotmart token');
            return $cachedToken;
        }

        // Try to use the token from .env if available
        $envToken = env('HOTMART_ACCESS_TOKEN');
        if ($envToken) {
            Log::info('Using Hotmart token from .env');
            Cache::put('hotmart_access_token', $envToken, now()->addHours(1));
            return $envToken;
        }

        // Try multiple authentication methods
        return $this->getTokenWithClientCredentials() ?? 
               $this->getTokenWithBasicAuth() ?? 
               null;
    }

    /**
     * Get token using client credentials grant
     */
    private function getTokenWithClientCredentials(): ?string
    {
        try {
            $clientId = env('HOTMART_CLIENT_ID');
            $clientSecret = env('HOTMART_CLIENT_SECRET');

            if (!$clientId || !$clientSecret) {
                Log::error('Hotmart credentials not configured');
                return null;
            }

            Log::info('Attempting to get Hotmart token with client credentials');
            
            $response = Http::asForm()
                ->withHeaders([
                    'Accept' => 'application/json',
                    'User-Agent' => 'HotmartIntegration/1.0'
                ])
                ->post($this->authUrl . '/security/oauth/token', [
                    'grant_type' => 'client_credentials',
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['access_token'])) {
                    $token = $data['access_token'];
                    $expiresIn = $data['expires_in'] ?? 3600;

                    Log::info('Successfully obtained Hotmart token with client credentials');
                    Cache::put('hotmart_access_token', $token, now()->addSeconds($expiresIn - 300));
                    return $token;
                }
                
                Log::warning('Token not found in Hotmart response', [
                    'response' => $data
                ]);
            } else {
                Log::error('Failed to get Hotmart token with client credentials', [
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Exception getting Hotmart token with client credentials', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Get token using basic auth
     */
    private function getTokenWithBasicAuth(): ?string
    {
        try {
            Log::info('Attempting to get Hotmart token with basic auth');
            
            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . $this->basicAuth,
                'Accept' => 'application/json',
                'User-Agent' => 'HotmartIntegration/1.0'
            ])->get($this->apiUrl . '/token');

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['access_token'])) {
                    $token = $data['access_token'];
                    $expiresIn = $data['expires_in'] ?? 3600;

                    Log::info('Successfully obtained Hotmart token with basic auth');
                    Cache::put('hotmart_access_token', $token, now()->addSeconds($expiresIn - 300));
                    return $token;
                }
                
                Log::warning('Token not found in Hotmart basic auth response', [
                    'response' => $data
                ]);
            } else {
                Log::error('Failed to get Hotmart token with basic auth', [
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Exception getting Hotmart token with basic auth', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Make an authenticated request to Hotmart API
     */
    protected function makeRequest(string $method, string $endpoint, array $data = [], string $baseUrl = null): ?array
    {
        $token = $this->getAccessToken();
        if (!$token) {
            Log::error('Cannot make Hotmart API request: No access token available');
            return null;
        }

        try {
            // Use provided baseUrl or default to apiUrl
            $baseUrl = $baseUrl ?: $this->apiUrl;
            $url = $baseUrl . $endpoint;
            Log::info("Making Hotmart API request: $method $url");
            
            $response = Http::withToken($token)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'User-Agent' => 'HotmartIntegration/1.0'
                ])
                ->$method($url, $data);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error("Hotmart API request failed: $method $url", [
                'status' => $response->status(),
                'response' => $response->body()
            ]);
            
            return null;
        } catch (\Exception $e) {
            Log::error("Exception in Hotmart API request: $method $url", [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Create an order in Hotmart
     */
    public function createOrder(array $orderData): ?array
    {
        return $this->makeRequest('post', '/payments/api/v1/sales', $orderData);
    }

    /**
     * Get product information - try multiple endpoints
     */
    public function getProduct(string $productId): ?array
    {
        // Try multiple endpoints
        $endpoints = [
            ['url' => "/payments/api/v1/products/$productId", 'base' => $this->paymentsUrl],
            ['url' => "/products/api/v1/products/$productId", 'base' => $this->marketplaceUrl],
            ['url' => "/v2/product/$productId", 'base' => $this->apiUrl]
        ];
        
        foreach ($endpoints as $endpoint) {
            Log::info("Trying to get product from endpoint: {$endpoint['base']}{$endpoint['url']}");
            $result = $this->makeRequest('get', $endpoint['url'], [], $endpoint['base']);
            if ($result) {
                return $result;
            }
        }
        
        return null;
    }

    /**
     * Update product price
     */
    public function updateProductPrice(string $productId, float $price): bool
    {
        $result = $this->makeRequest('put', "/payments/api/v1/products/$productId/price", [
            'price' => $price
        ]);
        
        return $result !== null;
    }

    /**
     * Pause subscription
     */
    public function pauseSubscription(string $subscriptionCode): bool
    {
        $result = $this->makeRequest('post', "/payments/api/v1/subscriptions/$subscriptionCode/actions/suspend");
        return $result !== null;
    }

    /**
     * Resume subscription
     */
    public function resumeSubscription(string $subscriptionCode): bool
    {
        $result = $this->makeRequest('post', "/payments/api/v1/subscriptions/$subscriptionCode/actions/reactivate");
        return $result !== null;
    }

    /**
     * Get product price - try multiple approaches
     */
    public function getProductPrice(string $productId): ?float
    {
        // Try getting from product info first
        $product = $this->getProduct($productId);
        if ($product) {
            // Check different possible price fields
            $priceFields = ['price', 'Price', 'value', 'amount', 'basePrice'];
            foreach ($priceFields as $field) {
                if (isset($product[$field])) {
                    return floatval($product[$field]);
                }
            }
            
            // Check for nested price objects
            if (isset($product['price']) && is_array($product['price']) && isset($product['price']['value'])) {
                return floatval($product['price']['value']);
            }
            
            // Log the product structure to help debug
            Log::info("Product found but price field not identified. Product structure:", [
                'product' => $product
            ]);
        }
        
        // Try direct price endpoint as fallback
        $endpoints = [
            ['url' => "/payments/api/v1/products/$productId/price", 'base' => $this->paymentsUrl],
            ['url' => "/products/api/v1/products/$productId/price", 'base' => $this->marketplaceUrl]
        ];
        
        foreach ($endpoints as $endpoint) {
            Log::info("Trying to get price from endpoint: {$endpoint['base']}{$endpoint['url']}");
            $result = $this->makeRequest('get', $endpoint['url'], [], $endpoint['base']);
            if ($result && isset($result['price'])) {
                return floatval($result['price']);
            }
        }
        
        return null;
    }

    /**
     * Sync prices from Hotmart to local system
     */
    public function syncPricesFromHotmart(): array
    {
        $results = [];
        $agents = \App\Models\Agent::whereNotNull('hotmart_product_id')->get();

        foreach ($agents as $agent) {
            if ($agent->hotmart_product_id) {
                $productData = $this->getProduct($agent->hotmart_product_id);
                
                if ($productData && isset($productData['price'])) {
                    $hotmartPrice = $productData['price'];
                    
                    if ($agent->price != $hotmartPrice) {
                        $agent->update(['price' => $hotmartPrice]);
                        
                        $results[] = [
                            'agent_id' => $agent->id,
                            'agent_name' => $agent->name,
                            'old_price' => $agent->price,
                            'new_price' => $hotmartPrice,
                            'updated' => true
                        ];
                    }
                }
            }
        }

        return $results;
    }

    /**
     * Sync all product prices to Hotmart
     */
    public function syncAllProductPrices(): array
    {
        $results = [];
        $agents = \App\Models\Agent::whereNotNull('hotmart_product_id')->get();

        foreach ($agents as $agent) {
            if ($agent->hotmart_product_id) {
                $success = $this->updateProductPrice($agent->hotmart_product_id, $agent->price);
                $results[] = [
                    'agent_id' => $agent->id,
                    'agent_name' => $agent->name,
                    'product_id' => $agent->hotmart_product_id,
                    'price' => $agent->price,
                    'success' => $success
                ];
            }
        }

        return $results;
    }
}