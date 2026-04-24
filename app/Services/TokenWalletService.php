<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\TokenTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TokenWalletService
{
    public function minimumPerRequest(): int
    {
        return max(1, (int) Setting::get('tokens_minimum_per_request', 1));
    }

    public function userHasMinimumBalance(User $user): bool
    {
        return (int) $user->token_balance >= $this->minimumPerRequest();
    }

    public function renewalIntervalDays(): int
    {
        return max(1, (int) Setting::get('tokens_renewal_interval_days', 30));
    }

    public function welcomeAmount(): int
    {
        return max(0, (int) Setting::get('tokens_welcome_amount', 0));
    }

    public function renewalAmount(): int
    {
        return max(0, (int) Setting::get('tokens_renewal_amount', 0));
    }

    public function grantWelcome(User $user): void
    {
        $amount = $this->welcomeAmount();
        if ($amount <= 0) {
            return;
        }

        DB::transaction(function () use ($user, $amount) {
            $locked = User::query()->whereKey($user->id)->lockForUpdate()->first();
            if (! $locked) {
                return;
            }

            $this->applyCredit($locked, $amount, TokenTransaction::TYPE_WELCOME, [], null, null);
        });
    }

    public function scheduleNextRenewal(User $user): void
    {
        $user->tokens_next_renewal_at = now()->addDays($this->renewalIntervalDays());
        $user->save();
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    public function credit(User $user, int $amount, string $type, array $meta = [], ?string $referenceType = null, ?int $referenceId = null): void
    {
        if ($amount <= 0) {
            return;
        }

        DB::transaction(function () use ($user, $amount, $type, $meta, $referenceType, $referenceId) {
            $locked = User::query()->whereKey($user->id)->lockForUpdate()->first();
            if (! $locked) {
                return;
            }
            $this->applyCredit($locked, $amount, $type, $meta, $referenceType, $referenceId);
        });
    }

    /**
     * Credit tokens when the caller already holds a row lock on this user (same DB transaction).
     */
    public function creditLockedUser(User $lockedUser, int $amount, string $type, array $meta = [], ?string $referenceType = null, ?int $referenceId = null): void
    {
        if ($amount <= 0) {
            return;
        }

        $this->applyCredit($lockedUser, $amount, $type, $meta, $referenceType, $referenceId);
    }

    /**
     * @param  array<string, mixed>  $meta
     * @return int Amount actually debited
     */
    public function debitUsage(User $user, int $usageTokens, array $meta = [], ?string $referenceType = null, ?int $referenceId = null): int
    {
        if ($usageTokens <= 0) {
            return 0;
        }

        return (int) DB::transaction(function () use ($user, $usageTokens, $meta, $referenceType, $referenceId) {
            $locked = User::query()->whereKey($user->id)->lockForUpdate()->first();
            if (! $locked) {
                return 0;
            }

            $balance = (int) $locked->token_balance;
            $debit = min($usageTokens, $balance);
            if ($debit <= 0) {
                return 0;
            }

            $newBalance = $balance - $debit;
            $locked->token_balance = $newBalance;
            $locked->save();

            TokenTransaction::query()->create([
                'user_id' => $locked->id,
                'delta' => -$debit,
                'balance_after' => $newBalance,
                'type' => TokenTransaction::TYPE_USAGE,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'meta' => array_merge($meta, ['requested_platform_debit' => $usageTokens]),
            ]);

            if ($debit < $usageTokens) {
                Log::warning('Token debit capped by balance', [
                    'user_id' => $locked->id,
                    'requested' => $usageTokens,
                    'debited' => $debit,
                ]);
            }

            return $debit;
        });
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    private function applyCredit(User $locked, int $amount, string $type, array $meta, ?string $referenceType, ?int $referenceId): void
    {
        $newBalance = (int) $locked->token_balance + $amount;
        $locked->token_balance = $newBalance;
        $locked->save();

        TokenTransaction::query()->create([
            'user_id' => $locked->id,
            'delta' => $amount,
            'balance_after' => $newBalance,
            'type' => $type,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'meta' => $meta,
        ]);
    }
}
