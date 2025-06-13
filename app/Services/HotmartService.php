<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class HotmartService
{
    
    private $baseUrl = 'https://api.hotmart.com';
    private $sandboxUrl = 'https://sandbox.api.hotmart.com';

    protected $accessToken;

   public function __construct()
    {
        $this->baseUrl = env('HOTMART_SANDBOX', false) ? $this->sandboxUrl : $this->baseUrl;
        // Initialize accessToken from env or by getting a new one
        $this->accessToken = env('HOTMART_ACCESS_TOKEN') ?: $this->getAccessToken();
    }

    public function getAccessToken(): ?string
    {
        try {
            $cachedToken = Cache::get('hotmart_access_token');
            if ($cachedToken) {
                $this->accessToken = $cachedToken;
                return $cachedToken;
            }

            // Try to use the token from .env if available
            $envToken = env('HOTMART_ACCESS_TOKEN');
            if ($envToken) {
                $this->accessToken = $envToken;
                Cache::put('hotmart_access_token', $envToken, now()->addHours(1));
                return $envToken;
            }

            $clientId = env('HOTMART_CLIENT_ID');
            $clientSecret = env('HOTMART_CLIENT_SECRET');

            if (!$clientId || !$clientSecret) {
                Log::error('Credenciais da Hotmart não configuradas');
                return null;
            }

            $response = Http::asForm()->post($this->baseUrl . '/security/oauth/token', [
                'grant_type' => 'client_credentials',
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['access_token'])) {
                    $token = $data['access_token'];
                    $expiresIn = $data['expires_in'] ?? 3600;

                    Cache::put('hotmart_access_token', $token, now()->addSeconds($expiresIn - 300));
                    $this->accessToken = $token;
                    return $token;
                } else {
                    Log::error('Token não encontrado na resposta da Hotmart', [
                        'response' => $data
                    ]);
                    return null;
                }
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

       /**
     * Cria um pedido na Hotmart
     */
    public function createOrder(array $orderData): ?array
    {
        try {
            $token = $this->getAccessToken();
            if (!$token) {
                return null;
            }

            $response = Http::withHeaders([
                'Authorization' => "Bearer $token",
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/payments/api/v1/sales', $orderData);

            Log::info('Hotmart Create Order Response', [
                'status' => $response->status(),
                'response' => $response->json(),
                'order_data' => $orderData
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            return null;

        } catch (\Exception $e) {
            Log::error('Erro ao criar pedido na Hotmart', [
                'error' => $e->getMessage(),
                'order_data' => $orderData
            ]);
            return null;
        }
    }

 /**
     * Busca informações do produto na Hotmart
     */
    public function getProduct(string $productId): ?array
    {
        try {
            $token = $this->getAccessToken();
            if (!$token) {
                return null;
            }

            $response = Http::withHeaders([
                'Authorization' => "Bearer $token",
            ])->get($this->baseUrl . "/payments/api/v1/products/{$productId}");

            if ($response->successful()) {
                return $response->json();
            }

            Log::warning('Produto não encontrado na Hotmart', [
                'product_id' => $productId,
                'status' => $response->status()
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error('Erro ao buscar produto na Hotmart', [
                'product_id' => $productId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

      /**
     * Atualiza preço do produto na Hotmart
     */
    public function updateProductPrice(string $productId, float $price): bool
    {
        try {
            $token = $this->getAccessToken();
            if (!$token) {
                return false;
            }

            $response = Http::withHeaders([
                'Authorization' => "Bearer $token",
                'Content-Type' => 'application/json',
            ])->put($this->baseUrl . "/payments/api/v1/products/{$productId}/price", [
                'price' => $price
            ]);

            Log::info('Hotmart Update Price Response', [
                'product_id' => $productId,
                'price' => $price,
                'status' => $response->status(),
                'response' => $response->json()
            ]);

            return $response->successful();

        } catch (\Exception $e) {
            Log::error('Erro ao atualizar preço na Hotmart', [
                'product_id' => $productId,
                'price' => $price,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

      /**
     * Sincroniza preços de todos os produtos
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

     /**
     * Sincroniza preços da Hotmart para o sistema local
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
     * Pausa assinatura
     */
    public function pauseSubscription(string $subscriptionCode): bool
    {
        try {
            $token = $this->getAccessToken();
            if (!$token) {
                return false;
            }

            $response = Http::withHeaders([
                'Authorization' => "Bearer $token",
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . "/payments/api/v1/subscriptions/{$subscriptionCode}/actions/suspend");

            Log::info('Hotmart Pause Subscription Response', [
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

       /**
     * Reativa assinatura
     */
    public function resumeSubscription(string $subscriptionCode): bool
    {
        try {
            $token = $this->getAccessToken();
            if (!$token) {
                return false;
            }

            $response = Http::withHeaders([
                'Authorization' => "Bearer $token",
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . "/payments/api/v1/subscriptions/{$subscriptionCode}/actions/reactivate");

            Log::info('Hotmart Resume Subscription Response', [
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

    public function getProductPrice(string $productId): ?float
    {
        // Ensure we have a token
        if (!$this->accessToken) {
            $this->accessToken = $this->getAccessToken();
            if (!$this->accessToken) {
                Log::error('Não foi possível obter token para buscar preço do produto');
                return null;
            }
        }

        $response = Http::withToken($this->accessToken)
            ->get("https://developers.hotmart.com/products/api/v1/products/{$productId}");

        Log::info("Token: {$this->accessToken}");
        Log::info("Response status: {$response->status()}");
        Log::info("Response json:", ['data' => $response->json()]);
        
        if ($response->successful()) {
            $data = $response->json();

            // Ajuste conforme a resposta real
            return floatval($data['price']['value'] ?? 0);
        }

        return null;
    }
}
