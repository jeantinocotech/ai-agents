<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TokenPackOrder extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    public const PAYMENT_PIX = 'pix';

    public const PAYMENT_CREDIT_CARD = 'credit_card';

    public const PAYMENT_BOLETO = 'boleto';

    protected $fillable = [
        'user_id',
        'tokens_amount',
        'amount_brl',
        'asaas_payment_id',
        'status',
        'payment_method',
        'bank_slip_url',
    ];

    protected function casts(): array
    {
        return [
            'amount_brl' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function paymentMethodLabel(): string
    {
        return match ($this->payment_method) {
            self::PAYMENT_PIX => 'PIX',
            self::PAYMENT_CREDIT_CARD => 'Cartão de crédito',
            self::PAYMENT_BOLETO => 'Boleto bancário',
            default => '—',
        };
    }

    public function statusLabel(): string
    {
        if ($this->status === self::STATUS_COMPLETED) {
            return 'Creditado';
        }

        if ($this->status === self::STATUS_FAILED) {
            return 'Falhou';
        }

        return match ($this->payment_method) {
            self::PAYMENT_PIX => 'Aguardando PIX',
            self::PAYMENT_BOLETO => 'Boleto emitido',
            self::PAYMENT_CREDIT_CARD => 'Processando cartão',
            default => 'Pendente',
        };
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }
}
