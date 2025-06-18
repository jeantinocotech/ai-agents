<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\HotmartServiceUpdated;

class HotmartTestUpdated extends Command
{
    protected $signature = 'hotmart:test-updated';
    protected $description = 'Test the updated Hotmart service with detailed diagnostics';

    protected $hotmartService;

    public function __construct(HotmartServiceUpdated $hotmartService)
    {
        parent::__construct();
        $this->hotmartService = $hotmartService;
    }

    public function handle()
    {
        $this->info('Testing updated Hotmart service with detailed diagnostics...');
        
        // Test authentication
        $this->info('Testing authentication...');
        $token = $this->hotmartService->getAccessToken();
        
        if ($token) {
            $this->info('✅ Authentication successful!');
            $this->info('Token: ' . substr($token, 0, 10) . '...' . substr($token, -10));
            
            // Check token format
            if (strpos($token, '.') !== false) {
                $this->info('Token format: JWT (JSON Web Token)');
            } else if (preg_match('/^[A-Za-z0-9+\/=]+$/', $token)) {
                $this->info('Token format: Base64 encoded string');
            } else {
                $this->info('Token format: Unknown');
            }
            
            // Test API endpoints if we have a token
            $this->testApiEndpoints();
        } else {
            $this->error('❌ Authentication failed. No token received.');
        }
        
        return Command::SUCCESS;
    }
    
    protected function testApiEndpoints()
    {
        $this->info("\nTesting API endpoints with detailed diagnostics...");
        
        // Get product ID from agent or use default
        $productId = $this->getProductIdFromAgent() ?? '5599944';
        $this->info("Using product ID: $productId");
        
        // Test each endpoint individually
        $this->testEndpoint('payments', "/payments/api/v1/products/$productId", 'https://api-sec-vlc.hotmart.com');
        $this->testEndpoint('marketplace', "/products/api/v1/products/$productId", 'https://developers.hotmart.com');
        $this->testEndpoint('api', "/v2/product/$productId", 'https://api-hot-connect.hotmart.com');
        
        // Test price endpoints
        $this->testEndpoint('payments price', "/payments/api/v1/products/$productId/price", 'https://api-sec-vlc.hotmart.com');
        $this->testEndpoint('marketplace price', "/products/api/v1/products/$productId/price", 'https://developers.hotmart.com');
        
        // Test getProduct (which tries all endpoints)
        $this->info("\nTesting getProduct with product ID: $productId");
        $product = $this->hotmartService->getProduct($productId);
        
        if ($product) {
            $this->info('✅ Successfully retrieved product information.');
            
            // Display product details
            $this->info('Product details:');
            $this->displayArray($product);
            
            // Test price extraction
            $this->info("\nTesting price extraction from product data:");
            $priceFields = ['price', 'Price', 'value', 'amount', 'basePrice'];
            $priceFound = false;
            
            foreach ($priceFields as $field) {
                if (isset($product[$field])) {
                    $this->info("✅ Found price in field '$field': " . $product[$field]);
                    $priceFound = true;
                }
            }
            
            // Check for nested price
            if (isset($product['price']) && is_array($product['price']) && isset($product['price']['value'])) {
                $this->info("✅ Found nested price in 'price.value': " . $product['price']['value']);
                $priceFound = true;
            }
            
            if (!$priceFound) {
                $this->warn("⚠️ No price field found in product data");
            }
        } else {
            $this->error('❌ Failed to retrieve product information from all endpoints.');
        }
        
        // Test getProductPrice
        $this->info("\nTesting getProductPrice with product ID: $productId");
        $price = $this->hotmartService->getProductPrice($productId);
        
        if ($price !== null) {
            $this->info('✅ Successfully retrieved product price: ' . $price);
        } else {
            $this->error('❌ Failed to retrieve product price from all endpoints.');
        }
    }
    
    /**
     * Test a specific API endpoint
     */
    protected function testEndpoint($name, $endpoint, $baseUrl)
    {
        $this->info("\nTesting $name endpoint: $endpoint");
        
        try {
            // Use reflection to access protected method
            $reflection = new \ReflectionClass($this->hotmartService);
            $method = $reflection->getMethod('makeRequest');
            $method->setAccessible(true);
            
            $result = $method->invokeArgs($this->hotmartService, ['get', $endpoint, [], $baseUrl]);
            
            if ($result) {
                $this->info('✅ Endpoint responded with data');
                $this->info('Response preview:');
                $this->displayArray($result, 3);
            } else {
                $this->warn('⚠️ Endpoint returned no data or error');
            }
        } catch (\Exception $e) {
            $this->error('❌ Exception testing endpoint: ' . $e->getMessage());
        }
    }
    
    /**
     * Display an array in a readable format
     */
    protected function displayArray($array, $maxItems = 10)
    {
        if (!is_array($array)) {
            $this->line("Not an array: " . json_encode($array));
            return;
        }
        
        $count = 0;
        foreach ($array as $key => $value) {
            if ($count >= $maxItems) {
                $this->line('... (more items not shown)');
                break;
            }
            
            if (is_array($value)) {
                $this->line("$key: [array]");
            } else {
                $this->line("$key: $value");
            }
            
            $count++;
        }
    }
    
    /**
     * Try to get a product ID from an agent in the database
     */
    protected function getProductIdFromAgent()
    {
        try {
            $agent = \App\Models\Agent::whereNotNull('hotmart_product_id')->first();
            return $agent ? $agent->hotmart_product_id : null;
        } catch (\Exception $e) {
            return null;
        }
    }
}