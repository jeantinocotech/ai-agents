<?php

namespace App\Console\Commands;

use App\Models\TokenTransaction;
use App\Models\User;
use App\Services\TokenWalletService;
use Illuminate\Console\Command;

class GrantTokenRenewals extends Command
{
    protected $signature = 'tokens:grant-renewal';

    protected $description = 'Credit free renewal tokens to users whose next renewal date has passed';

    public function handle(TokenWalletService $wallet): int
    {
        $amount = $wallet->renewalAmount();

        $query = User::query()
            ->where(function ($q) {
                $q->whereNull('tokens_next_renewal_at')
                    ->orWhere('tokens_next_renewal_at', '<=', now());
            });

        $count = 0;
        foreach ($query->cursor() as $user) {
            if ($user->tokens_next_renewal_at && $user->tokens_next_renewal_at->isFuture()) {
                continue;
            }

            if ($amount > 0) {
                $wallet->credit($user, $amount, TokenTransaction::TYPE_RENEWAL, ['source' => 'scheduled']);
            }

            $wallet->scheduleNextRenewal($user->fresh());
            $count++;
        }

        $this->info("Processed {$count} user(s) for token renewal.");

        return self::SUCCESS;
    }
}
