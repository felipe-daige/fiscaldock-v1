<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\CreditService;
use Illuminate\Console\Command;

class ExpireTrialCreditsCommand extends Command
{
    protected $signature = 'trial:expire-credits';

    protected $description = 'Expira créditos promocionais remanescentes de trials vencidos';

    public function handle(CreditService $creditService)
    {
        $expiredUsers = 0;
        $expiredCredits = 0;

        User::query()
            ->where('trial_used', true)
            ->whereNotNull('trial_expires_at')
            ->where('trial_expires_at', '<=', now())
            ->where('trial_credits_remaining', '>', 0)
            ->chunkById(100, function ($users) use ($creditService, &$expiredUsers, &$expiredCredits) {
                foreach ($users as $user) {
                    $expired = $creditService->expireTrialCredits($user);

                    if ($expired > 0) {
                        $expiredUsers++;
                        $expiredCredits += $expired;
                        $this->info("Usuário {$user->id}: expirados {$expired} créditos.");
                    }
                }
            });

        $this->newLine();
        $this->info("Usuários com créditos expirados: {$expiredUsers}");
        $this->info("Créditos expirados no total: {$expiredCredits}");

        return Command::SUCCESS;
    }
}
