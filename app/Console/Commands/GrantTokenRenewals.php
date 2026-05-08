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
        $target = $wallet->welcomeAmount();

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

            // Opt-out permanente: quem já comprou tokens não recebe renovação gratuita.
            $hasPurchase = TokenTransaction::query()
                ->where('user_id', $user->id)
                ->where('type', TokenTransaction::TYPE_PURCHASE)
                ->exists();
            if (! $hasPurchase) {
                $wallet->resetBalanceToWelcome($user, ['source' => 'scheduled', 'target' => $target]);
            }

            $wallet->scheduleNextRenewal($user->fresh());
            $count++;
        }

        $this->info("Processed {$count} user(s) for token renewal.");

        return self::SUCCESS;
    }
}
