<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class HotmartTestToken extends Command
{
    protected $signature = 'hotmart:test-token';
    protected $description = 'Test Hotmart token generation and dump full response for debugging';

    public function handle()
    {
        $this->info('Testing Hotmart token generation...');
        
        $clientId = env('HOTMART_CLIENT_ID');
        $clientSecret = env('HOTMART_CLIENT_SECRET');
        
        $this->info('Using Client ID: ' . substr($clientId, 0, 5) . '...' . substr($clientId, -5));
        
        // Try with different endpoints
        $endpoints = [
            'production' => 'https://api.hotmart.com/security/oauth/token',
            'sandbox' => 'https://sandbox.api.hotmart.com/security/oauth/token',
            'developers' => 'https://developers.hotmart.com/security/oauth/token'
        ];
        
        foreach ($endpoints as $name => $endpoint) {
            $this->info("\nTrying $name endpoint: $endpoint");
            
            try {
                $response = Http::asForm()->post($endpoint, [
                    'grant_type' => 'client_credentials',
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
                ]);
                
                $this->info('Status: ' . $response->status());
                $this->info('Headers:');
                foreach ($response->headers() as $header => $values) {
                    $this->info("  $header: " . implode(', ', $values));
                }
                
                $this->info('Body:');
                $this->line(json_encode($response->json(), JSON_PRETTY_PRINT));
                
                if ($response->successful()) {
                    $data = $response->json();
                    if (isset($data['access_token'])) {
                        $this->info('✅ Found access_token in response!');
                    } else {
                        $this->warn('⚠️ No access_token found in response.');
                        
                        // Check for other possible token keys
                        $possibleKeys = ['token', 'accessToken', 'auth_token', 'id_token'];
                        foreach ($possibleKeys as $key) {
                            if (isset($data[$key])) {
                                $this->info("Found alternative token key: $key");
                                $this->line($data[$key]);
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                $this->error('Exception: ' . $e->getMessage());
            }
        }
        
        return Command::SUCCESS;
    }
}