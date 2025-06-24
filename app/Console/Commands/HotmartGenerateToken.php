<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Services\HotmartService;

class HotmartGenerateToken extends Command
{
    protected $signature = 'hotmart:generate-token';
    protected $description = 'Gera um novo access_token da Hotmart usando client_id e client_secret do .env';

    public function handle()
    {
        $hotmart = app(HotmartService::class);
        $token = $hotmart->getAccessToken();

        if ($token) {
            $this->info('✅ Token gerado com sucesso:');
            $this->line($token);
        } else {
            $this->error('❌ Erro ao gerar token de acesso para Hotmart.');
        }
    }
}

