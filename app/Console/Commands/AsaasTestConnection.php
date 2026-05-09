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
        $this->info('Environment: '.($asaas->isSandbox() ? 'Sandbox' : 'Production'));
        $this->line('Webhook URL (configurar no painel Asaas): '.config('asaas.webhook_url'));
        $apiKey = (string) config('asaas.api_key');
        $this->line('API key: '.($apiKey !== '' ? 'definida ('.strlen($apiKey).' chars)' : 'em falta'));
        $whToken = (string) config('asaas.webhook_token');
        if (app()->environment('production') && $whToken === '') {
            $this->warn('ASAAS_WEBHOOK_TOKEN em falta — webhooks serão rejeitados em produção.');
        }

        $this->newLine();
        
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