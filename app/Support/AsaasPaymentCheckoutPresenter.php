<?php

namespace App\Support;

/**
 * Normaliza a resposta bruta do Asaas (POST /payments) para o checkout de tokens no site.
 */
class AsaasPaymentCheckoutPresenter
{
    public const PAID_STATUSES = ['RECEIVED', 'CONFIRMED'];

    public static function isPaid(?string $status): bool
    {
        return in_array((string) $status, self::PAID_STATUSES, true);
    }

    /**
     * @param  array<string, mixed>  $asaas
     * @return array<string, mixed>
     */
    public static function presentForCheckout(array $asaas, string $paymentMethod): array
    {
        $status = (string) ($asaas['status'] ?? 'PENDING');
        $paymentId = (string) ($asaas['id'] ?? '');

        $base = [
            'success' => true,
            'payment_id' => $paymentId,
            'payment_status' => $status,
            'is_paid' => self::isPaid($status),
        ];

        if ($paymentMethod === 'pix') {
            return array_merge($base, [
                'checkout_mode' => 'pix',
                'is_pix' => true,
            ]);
        }

        if ($paymentMethod === 'boleto') {
            $barcode = $asaas['identificationField']
                ?? $asaas['barCode']
                ?? $asaas['nossoNumero']
                ?? null;

            return array_merge($base, [
                'checkout_mode' => 'boleto',
                'due_date' => $asaas['dueDate'] ?? null,
                'identification_field' => $barcode !== null ? (string) $barcode : null,
                'bank_slip_url' => $asaas['bankSlipUrl'] ?? null,
                'value' => $asaas['value'] ?? null,
                'message' => 'Boleto gerado. Pague até o vencimento; os tokens serão creditados após a confirmação do pagamento.',
            ]);
        }

        if (self::isPaid($status)) {
            return array_merge($base, [
                'checkout_mode' => 'card_confirmed',
                'message' => 'Pagamento com cartão confirmado. Os tokens serão creditados em instantes.',
            ]);
        }

        $secondaryUrl = $asaas['invoiceUrl'] ?? null;

        return array_merge($base, [
            'checkout_mode' => 'card_pending',
            'message' => 'Pagamento em processamento. Esta página atualiza automaticamente quando o banco confirmar.',
            'secondary_url' => $secondaryUrl ? (string) $secondaryUrl : null,
            'secondary_url_label' => $secondaryUrl ? 'Concluir verificação do cartão (se solicitado pelo banco)' : null,
        ]);
    }
}
