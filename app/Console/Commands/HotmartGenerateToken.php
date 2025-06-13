<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class HotmartGenerateToken extends Command
{
    protected $signature = 'hotmart:generate-token';
    protected $description = 'Gera um novo access_token da Hotmart usando client_id e client_secret do .env';

    public function handle()
    {
        $clientId = env('HOTMART_CLIENT_ID');
        $clientSecret = env('HOTMART_CLIENT_SECRET');

        $response = Http::asForm()->post('https://api.hotmart.com/security/oauth/token', [
            'grant_type' => 'client_credentials',
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
        ]);

        if ($response->successful()) {
            $data = $response->json();
            $token = $data['access_token'] ?? null;

            if ($token) {
                $this->info('Novo token gerado com sucesso:');
                $this->line($token);
                return Command::SUCCESS;
            }

            $this->error('Token não encontrado na resposta.');
        } else {
            $this->error('Erro ao gerar token. Status: ' . $response->status());
            $this->line(json_encode($response->json()));
        }

        return Command::FAILURE;
    }
}

