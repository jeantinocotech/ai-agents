<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AsaasService;

class AsaasTestConnection extends Command
{
    protected $signature = 'asaas:test-connection';
    protected $description = 'Test the connection to the Asaas API using the configured API key';

    public function handle()
    {
        $asaas = app(AsaasService::class);
        
        $this->info('Testing connection to Asaas API...');
        $this->info('Environment: ' . ($asaas->isSandbox() ? 'Sandbox' : 'Production'));
        
        // Try to make a simple API call to test the connection
        try {
            // Use the testConnection method to test the API connection
            $response = $asaas->testConnection();
            
            if ($response !== null) {
                $this->info('✅ Connection successful!');
                $this->info('API response:');
                $this->line(json_encode($response, JSON_PRETTY_PRINT));
                return 0;
            } else {
                $this->error('❌ Connection failed. The API request returned null.');
                return 1;
            }
        } catch (\Exception $e) {
            $this->error('❌ Connection failed with exception:');
            $this->error($e->getMessage());
            return 1;
        }
    }
}