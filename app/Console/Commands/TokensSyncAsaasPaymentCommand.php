<?php

namespace App\Console\Commands;

use App\Services\AsaasService;
use App\Services\TokenPackOrderCompletionService;
use Illuminate\Console\Command;

class TokensSyncAsaasPaymentCommand extends Command
{
    protected $signature = 'tokens:sync-asaas-payment
                            {payment_id : ID da cobrança Asaas (ex.: pay_xxxxx)}';

    protected $description = 'Consulta uma cobrança no Asaas e, se estiver paga, credita pacote de tokens (TokenPackOrder) associado.';

    public function handle(AsaasService $asaas, TokenPackOrderCompletionService $completion): int
    {
        $id = trim((string) $this->argument('payment_id'));

        $payment = $asaas->getPayment($id);
        if (! is_array($payment) || $payment === []) {
            $this->error('Não foi possível obter esta cobrança na API Asaas (sandbox/produção conforme .env).');

            return 1;
        }

        $status = $payment['status'] ?? '';
        $this->line('Estado no Asaas: '.($status !== '' ? $status : '(desconhecido)'));

        if (! in_array($status, ['RECEIVED', 'CONFIRMED'], true)) {
            $this->warn('A cobrança ainda não está liquidada segundo o Asaas — nada a creditar aqui.');

            return 1;
        }

        $response = $completion->tryCompleteFromAsaasPayment($payment);
        if ($response === null) {
            $this->warn('Este pagamento não está associado a um pacote de tokens (externalReference sem type=token_pack).');

            return 1;
        }

        $this->info('Resposta interna: '.$response->getContent());

        return $response->getStatusCode() >= 200 && $response->getStatusCode() < 300 ? 0 : 1;
    }
}
