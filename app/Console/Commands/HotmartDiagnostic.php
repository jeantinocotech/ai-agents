<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HotmartDiagnostic extends Command
{
    protected $signature = 'hotmart:diagnostic {--product-id=5599944 : Product ID to test}';
    protected $description = 'Run deep diagnostics on Hotmart API integration';

    // Test product IDs
    protected $testProductIds = [
        '5599944',  // Default from previous tests
        '1234567',  // Random test ID
        '2000000',  // Another random test ID
    ];

    // API endpoints to test
    protected $endpoints = [
        [
            'name' => 'Hotmart API',
            'url' => 'https://api-hot-connect.hotmart.com',
            'paths' => [
                '/v2/product/{id}',
                '/v1/product/{id}',
                '/products/{id}'
            ]
        ],
        [
            'name' => 'Payments API',
            'url' => 'https://api-sec-vlc.hotmart.com',
            'paths' => [
                '/payments/api/v1/products/{id}',
                '/payments/api/v2/products/{id}'
            ]
        ],
        [
            'name' => 'Marketplace API',
            'url' => 'https://developers.hotmart.com',
            'paths' => [
                '/products/api/v1/products/{id}',
                '/marketplace/api/v1/products/{id}'
            ]
        ]
    ];

    public function handle()
    {
        $this->info('Running deep diagnostics on Hotmart API integration...');
        
        // Get token
        $token = $this->getToken();
        if (!$token) {
            $this->error('❌ Failed to get authentication token. Cannot proceed with diagnostics.');
            return Command::FAILURE;
        }
        
        $this->info('✅ Authentication successful!');
        $this->info('Token: ' . substr($token, 0, 10) . '...' . substr($token, -10));
        
        // Get product ID from option or try multiple
        $productId = $this->option('product-id');
        $this->info("Using product ID: $productId");
        
        // Test all endpoints with the specified product ID
        $this->testAllEndpoints($token, $productId);
        
        // Try with different headers
        $this->info("\nTesting with different headers...");
        $this->testWithDifferentHeaders($token, $productId);
        
        // Try with different product IDs if specified one failed
        $this->info("\nTesting with different product IDs...");
        foreach ($this->testProductIds as $testId) {
            if ($testId != $productId) {
                $this->info("\nTrying with product ID: $testId");
                $this->testAllEndpoints($token, $testId, 1); // Limit to 1 endpoint per API for brevity
            }
        }
        
        // Check if we're using the correct API version
        $this->info("\nChecking API version information...");
        $this->checkApiVersion($token);
        
        // Suggest next steps
        $this->info("\n=== Diagnostic Summary ===");
        $this->info("1. If all endpoints failed, consider:");
        $this->line("   - Verifying your Hotmart account has API access enabled");
        $this->line("   - Checking if your product IDs are correct");
        $this->line("   - Contacting Hotmart support to confirm API endpoints");
        $this->info("2. If some endpoints worked, update the HotmartServiceUpdated.php file to use those endpoints");
        $this->info("3. Consider using the Hotmart SDK instead of direct API calls if available");
        
        return Command::SUCCESS;
    }
    
    protected function getToken()
    {
        // Try to use the token from .env if available
        $envToken = env('HOTMART_ACCESS_TOKEN');
        if ($envToken) {
            $this->info('Using Hotmart token from .env');
            return $envToken;
        }
        
        // Try to generate a new token
        $clientId = env('HOTMART_CLIENT_ID');
        $clientSecret = env('HOTMART_CLIENT_SECRET');
        
        if (!$clientId || !$clientSecret) {
            $this->error('Hotmart credentials not configured in .env');
            return null;
        }
        
        $this->info('Attempting to get Hotmart token with client credentials...');
        
        try {
            $response = Http::asForm()
                ->withHeaders([
                    'Accept' => 'application/json',
                    'User-Agent' => 'HotmartDiagnostic/1.0'
                ])
                ->post('https://api-sec-vlc.hotmart.com/security/oauth/token', [
                    'grant_type' => 'client_credentials',
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
                ]);
            
            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['access_token'])) {
                    return $data['access_token'];
                }
                
                $this->warn('Token not found in response. Response body:');
                $this->line(json_encode($data, JSON_PRETTY_PRINT));
            } else {
                $this->error('Failed to get token. Status: ' . $response->status());
                $this->line('Response: ' . $response->body());
            }
        } catch (\Exception $e) {
            $this->error('Exception getting token: ' . $e->getMessage());
        }
        
        // Try with basic auth as fallback
        $this->info('Trying with basic auth as fallback...');
        $basicAuth = base64_encode("$clientId:$clientSecret");
        
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . $basicAuth,
                'Accept' => 'application/json',
                'User-Agent' => 'HotmartDiagnostic/1.0'
            ])->get('https://api-hot-connect.hotmart.com/token');
            
            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['access_token'])) {
                    return $data['access_token'];
                }
                
                $this->warn('Token not found in basic auth response. Response body:');
                $this->line(json_encode($data, JSON_PRETTY_PRINT));
            } else {
                $this->error('Failed to get token with basic auth. Status: ' . $response->status());
                $this->line('Response: ' . $response->body());
            }
        } catch (\Exception $e) {
            $this->error('Exception getting token with basic auth: ' . $e->getMessage());
        }
        
        return null;
    }
    
    protected function testAllEndpoints($token, $productId, $limitPathsPerApi = null)
    {
        foreach ($this->endpoints as $api) {
            $this->info("\nTesting {$api['name']} endpoints:");
            
            $paths = $api['paths'];
            if ($limitPathsPerApi !== null && count($paths) > $limitPathsPerApi) {
                $paths = array_slice($paths, 0, $limitPathsPerApi);
            }
            
            foreach ($paths as $path) {
                $endpoint = str_replace('{id}', $productId, $path);
                $url = $api['url'] . $endpoint;
                
                $this->testEndpoint($token, $url);
            }
        }
    }
    
    protected function testEndpoint($token, $url)
    {
        $this->line("\nTesting: $url");
        
        try {
            $response = Http::withToken($token)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'User-Agent' => 'HotmartDiagnostic/1.0'
                ])
                ->get($url);
            
            $this->line("Status: " . $response->status());
            
            if ($response->successful()) {
                $data = $response->json();
                if ($data) {
                    $this->info('✅ Endpoint returned data:');
                    $this->line(json_encode(array_slice($data, 0, 3), JSON_PRETTY_PRINT));
                    if (count($data) > 3) {
                        $this->line('... (more data not shown)');
                    }
                } else {
                    $this->warn('⚠️ Endpoint returned empty data');
                }
            } else {
                $this->warn('⚠️ Endpoint returned error:');
                $this->line($response->body());
            }
        } catch (\Exception $e) {
            $this->error('❌ Exception testing endpoint: ' . $e->getMessage());
        }
    }
    
    protected function testWithDifferentHeaders($token, $productId)
    {
        // Test with different headers
        $headerSets = [
            [
                'name' => 'Basic headers',
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $token
                ]
            ],
            [
                'name' => 'With API version header',
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $token,
                    'X-API-Version' => '1'
                ]
            ],
            [
                'name' => 'With different auth format',
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Token ' . $token
                ]
            ]
        ];
        
        // Use the first endpoint from each API for testing headers
        foreach ($this->endpoints as $api) {
            $path = $api['paths'][0];
            $endpoint = str_replace('{id}', $productId, $path);
            $url = $api['url'] . $endpoint;
            
            $this->info("\nTesting {$api['name']} with different headers: $url");
            
            foreach ($headerSets as $headerSet) {
                $this->line("\nTrying with {$headerSet['name']}:");
                
                try {
                    $response = Http::withHeaders($headerSet['headers'])->get($url);
                    
                    $this->line("Status: " . $response->status());
                    
                    if ($response->successful()) {
                        $data = $response->json();
                        if ($data) {
                            $this->info('✅ Headers worked! Response:');
                            $this->line(json_encode(array_slice($data, 0, 3), JSON_PRETTY_PRINT));
                        } else {
                            $this->warn('⚠️ Empty response with these headers');
                        }
                    } else {
                        $this->warn('⚠️ Error with these headers: ' . $response->body());
                    }
                } catch (\Exception $e) {
                    $this->error('❌ Exception with these headers: ' . $e->getMessage());
                }
            }
        }
    }
    
    protected function checkApiVersion($token)
    {
        // Try to get API version info from common endpoints
        $versionEndpoints = [
            'https://api-hot-connect.hotmart.com/version',
            'https://api-hot-connect.hotmart.com/api/version',
            'https://api-sec-vlc.hotmart.com/version',
            'https://developers.hotmart.com/version'
        ];
        
        foreach ($versionEndpoints as $url) {
            $this->line("\nChecking version at: $url");
            
            try {
                $response = Http::withToken($token)
                    ->withHeaders([
                        'Accept' => 'application/json',
                        'User-Agent' => 'HotmartDiagnostic/1.0'
                    ])
                    ->get($url);
                
                $this->line("Status: " . $response->status());
                
                if ($response->successful()) {
                    $this->info('✅ Version endpoint responded:');
                    $this->line($response->body());
                } else {
                    $this->warn('⚠️ Version endpoint error: ' . $response->body());
                }
            } catch (\Exception $e) {
                $this->error('❌ Exception checking version: ' . $e->getMessage());
            }
        }
    }
}