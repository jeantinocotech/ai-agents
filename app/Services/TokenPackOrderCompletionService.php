<?php

namespace App\Services;

use App\Models\TokenPackOrder;
use App\Models\TokenTransaction;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Credita pacotes de tokens a partir do payload bruto da cobrança Asaas (webhook ou confirmação por polling).
 */
class TokenPackOrderCompletionService
{
    /**
     * @param  array<string, mixed>  $paymentData
     */
    public function tryCompleteFromAsaasPayment(array $paymentData): ?JsonResponse
    {
        $externalReference = $paymentData['externalReference'] ?? '';
        $meta = json_decode((string) $externalReference, true);
        if (! is_array($meta) || ($meta['type'] ?? '') !== 'token_pack') {
            return null;
        }

        $orderId = (int) ($meta['order_id'] ?? 0);
        $userId = (int) ($meta['user_id'] ?? 0);
        if ($orderId <= 0 || $userId <= 0) {
            return null;
        }

        $order = TokenPackOrder::query()
            ->where('id', $orderId)
            ->where('user_id', $userId)
            ->first();

        if (! $order) {
            Log::warning('Token pack order not found for Asaas payment', compact('orderId', 'userId'));

            return response()->json(['ok' => true]);
        }

        if ($order->status === TokenPackOrder::STATUS_COMPLETED) {
            return response()->json(['status' => 'token pack already completed']);
        }

        $paymentId = $paymentData['id'] ?? null;
        if ($paymentId && $order->asaas_payment_id && $order->asaas_payment_id !== $paymentId) {
            Log::warning('Token pack payment id mismatch', [
                'order_id' => $order->id,
                'expected' => $order->asaas_payment_id,
                'got' => $paymentId,
            ]);

            return response()->json(['ok' => true]);
        }

        DB::transaction(function () use ($order, $paymentId, $paymentData) {
            $lockedOrder = TokenPackOrder::query()->whereKey($order->id)->lockForUpdate()->first();
            if (! $lockedOrder || $lockedOrder->status !== TokenPackOrder::STATUS_PENDING) {
                return;
            }

            $user = User::query()->whereKey($lockedOrder->user_id)->lockForUpdate()->first();
            if (! $user) {
                return;
            }

            app(TokenWalletService::class)->creditLockedUser(
                $user,
                (int) $lockedOrder->tokens_amount,
                TokenTransaction::TYPE_PURCHASE,
                ['asaas_payment' => $paymentData],
                TokenPackOrder::class,
                $lockedOrder->id
            );

            $lockedOrder->status = TokenPackOrder::STATUS_COMPLETED;
            if ($paymentId) {
                $lockedOrder->asaas_payment_id = $paymentId;
            }
            $lockedOrder->save();
        });

        Log::info('Token pack order credited from Asaas payment payload', ['order_id' => $order->id]);

        return response()->json(['status' => 'token pack credited']);
    }
}
