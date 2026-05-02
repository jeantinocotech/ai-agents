<?php

namespace App\Console\Commands;

use App\Models\TokenPackOrder;
use Illuminate\Console\Command;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Http\Request;

class AsaasSimulateWebhookCommand extends Command
{
    protected $signature = 'asaas:simulate-webhook
                            {--order= : ID da encomenda de tokens (TokenPackOrder) pendente}
                            {--dry-run : Mostra o payload e um exemplo curl sem enviar para a app}';

    protected $description = '[Apenas local/dev] Simula POST PAYMENT_CONFIRMED no webhook Asaas (teste de TokenPackOrder).';

    public function handle(): int
    {
        if (app()->environment('production')) {
            $this->error('Este comando está desactivado em produção.');

            return 1;
        }

        $orderId = $this->option('order');
        if ($orderId === null || $orderId === '') {
            $order = TokenPackOrder::query()
                ->where('status', TokenPackOrder::STATUS_PENDING)
                ->orderByDesc('id')
                ->first();

            if ($order === null) {
                $this->error('Não há TokenPackOrder pendente. Crie um checkout em /tokens/comprar ou use --order=ID.');
                $this->line('Nota: o painel Asaas só mostra envios de webhook depois de eventos reais (ou de um pedido de teste que chegue ao teu URL).');

                return 1;
            }
        } else {
            $order = TokenPackOrder::query()->find((int) $orderId);
            if ($order === null) {
                $this->error("TokenPackOrder #{$orderId} não encontrada.");

                return 1;
            }
            if ($order->status !== TokenPackOrder::STATUS_PENDING) {
                $this->warn("A encomenda não está pendente (status: {$order->status}). O processamento pode não creditar de novo.");
            }
        }

        $externalReference = json_encode([
            'type' => 'token_pack',
            'order_id' => $order->id,
            'user_id' => $order->user_id,
        ], JSON_THROW_ON_ERROR);

        $paymentAsaasId = $order->asaas_payment_id ?? 'sim_'.uniqid('', true);

        $payload = [
            'event' => 'PAYMENT_CONFIRMED',
            'payment' => [
                'id' => $paymentAsaasId,
                'customer' => 'cus_simulation',
                'status' => 'CONFIRMED',
                'billingType' => 'BOLETO',
                'value' => (float) $order->amount_brl,
                'externalReference' => $externalReference,
            ],
        ];

        $raw = json_encode($payload, JSON_THROW_ON_ERROR);
        $webhookPath = '/api/cart/asaas/webhook';
        $absolute = rtrim((string) config('app.url'), '/').$webhookPath;

        $this->line('Encomenda: #'.$order->id.' (user '.$order->user_id.', '.$order->tokens_amount.' tokens)');
        $this->line('URL do webhook (APP_URL): '.$absolute);
        $this->newLine();
        $this->line('Payload:');
        $this->output->writeln(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        if ($this->option('dry-run')) {
            $this->newLine();
            $this->info('Exemplo curl (local: sem header asaas-signature → assinatura não é exigida):');
            $this->line('curl -sS -X POST '.escapeshellarg($absolute).' \\');
            $this->line('  -H '.escapeshellarg('Content-Type: application/json').' \\');
            $this->line('  --data-binary '.escapeshellarg($raw));

            return 0;
        }

        /** @var HttpKernel $kernel */
        $kernel = app(HttpKernel::class);
        $request = Request::create(
            $webhookPath,
            'POST',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            $raw
        );

        $response = $kernel->handle($request);
        try {
            $this->newLine();
            $this->info('Resposta HTTP '.$response->getStatusCode());
            $this->line($response->getContent());

            return $response->isSuccessful() ? 0 : 1;
        } finally {
            $kernel->terminate($request, $response);
        }
    }
}
