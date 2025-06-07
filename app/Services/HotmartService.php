<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;

class HotmartService
{
    protected $accessToken;

    public function __construct()
    {
        $this->accessToken = env('HOTMART_ACCESS_TOKEN');
    }

    public function getProductPrice(string $productId): ?float
    {
        $response = Http::withToken($this->accessToken)
            ->get("https://api.hotmart.com/payments/api/v1/products/{$productId}");

        if ($response->successful()) {
            $data = $response->json();

            // Ajuste conforme a resposta real
            return floatval($data['price']['value'] ?? 0);
        }

        return null;
    }
}
