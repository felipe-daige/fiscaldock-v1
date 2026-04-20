<?php

namespace App\Services;

use App\Models\CreditTransaction;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CreditService
{
    /**
     * Retorna o saldo atual de creditos do usuario.
     */
    public function getBalance(User $user): int
    {
        return (int) $user->credits;
    }

    /**
     * Verifica se o usuario tem creditos suficientes.
     */
    public function hasEnough(User $user, float $amount): bool
    {
        return $this->getBalance($user) >= $amount;
    }

    /**
     * Desconta creditos do usuario.
     * Retorna true se a operacao foi bem-sucedida, false caso contrario.
     */
    public function deduct(User $user, float $amount, string $type = 'consulta_lote', ?string $description = null, ?Model $source = null): bool
    {
        $amount = (int) floor($amount);

        if ($amount <= 0) {
            return true;
        }

        if (!$this->hasEnough($user, $amount)) {
            Log::warning('Tentativa de desconto de creditos insuficientes', [
                'user_id' => $user->id,
                'credits_atual' => $user->credits,
                'amount_solicitado' => $amount,
            ]);
            return false;
        }

        return DB::transaction(function () use ($user, $amount, $type, $description, $source) {
            $freshUser = User::lockForUpdate()->find($user->id);

            if (!$freshUser || $freshUser->credits < $amount) {
                return false;
            }

            $freshUser->credits = (int) floor($freshUser->credits - $amount);

            if ((int) $freshUser->trial_credits_remaining > 0) {
                $trialConsumed = min($amount, (int) $freshUser->trial_credits_remaining);
                $freshUser->trial_credits_remaining = max(0, (int) $freshUser->trial_credits_remaining - $trialConsumed);
            }

            $freshUser->save();

            $user->credits = $freshUser->credits;

            $this->logTransaction($user, (int) -$amount, $freshUser->credits, $type, $description, $source);

            Log::info('Creditos descontados com sucesso', [
                'user_id' => $user->id,
                'amount' => $amount,
                'novo_saldo' => $freshUser->credits,
            ]);

            return true;
        });
    }

    /**
     * Adiciona creditos ao usuario.
     */
    public function add(User $user, float $amount, string $type = 'manual_add', ?string $description = null, ?Model $source = null): void
    {
        $amount = (int) floor($amount);

        if ($amount <= 0) {
            return;
        }

        DB::transaction(function () use ($user, $amount, $type, $description, $source) {
            $freshUser = User::lockForUpdate()->find($user->id);

            if (!$freshUser) {
                return;
            }

            $freshUser->credits = (int) floor($freshUser->credits + $amount);
            $freshUser->save();

            $user->credits = $freshUser->credits;

            $this->logTransaction($user, (int) $amount, $freshUser->credits, $type, $description, $source);

            Log::info('Creditos adicionados com sucesso', [
                'user_id' => $user->id,
                'amount' => $amount,
                'novo_saldo' => $freshUser->credits,
            ]);
        });
    }

    public function grantTrial(User $user, int $amount, CarbonInterface $expiresAt, string $source = 'landing_signup'): void
    {
        if ($amount <= 0) {
            return;
        }

        DB::transaction(function () use ($user, $amount, $expiresAt, $source) {
            $freshUser = User::lockForUpdate()->find($user->id);

            if (! $freshUser) {
                return;
            }

            $freshUser->credits = (int) floor($freshUser->credits + $amount);
            $freshUser->trial_used = true;
            $freshUser->trial_started_at = now();
            $freshUser->trial_expires_at = $expiresAt;
            $freshUser->trial_credits_granted = $amount;
            $freshUser->trial_credits_remaining = $amount;
            $freshUser->trial_credits_expired = 0;
            $freshUser->trial_source = $source;
            $freshUser->save();

            $user->credits = $freshUser->credits;
            $user->trial_used = $freshUser->trial_used;
            $user->trial_started_at = $freshUser->trial_started_at;
            $user->trial_expires_at = $freshUser->trial_expires_at;
            $user->trial_credits_granted = $freshUser->trial_credits_granted;
            $user->trial_credits_remaining = $freshUser->trial_credits_remaining;
            $user->trial_credits_expired = $freshUser->trial_credits_expired;
            $user->trial_source = $freshUser->trial_source;

            $this->logTransaction(
                $user,
                $amount,
                $freshUser->credits,
                'trial_bonus',
                sprintf('Bônus de boas-vindas: %d créditos grátis válidos até %s.', $amount, $expiresAt->format('d/m/Y H:i')),
                null
            );
        });
    }

    public function expireTrialCredits(User $user): int
    {
        return DB::transaction(function () use ($user) {
            $freshUser = User::lockForUpdate()->find($user->id);

            if (! $freshUser || (int) $freshUser->trial_credits_remaining <= 0) {
                return 0;
            }

            if (! $freshUser->trial_expires_at || now()->lt($freshUser->trial_expires_at)) {
                return 0;
            }

            $amount = min((int) $freshUser->trial_credits_remaining, (int) $freshUser->credits);

            if ($amount <= 0) {
                $freshUser->trial_credits_remaining = 0;
                $freshUser->save();
                return 0;
            }

            $freshUser->credits = max(0, (int) $freshUser->credits - $amount);
            $freshUser->trial_credits_remaining = max(0, (int) $freshUser->trial_credits_remaining - $amount);
            $freshUser->trial_credits_expired = (int) $freshUser->trial_credits_expired + $amount;
            $freshUser->save();

            $user->credits = $freshUser->credits;
            $user->trial_credits_remaining = $freshUser->trial_credits_remaining;
            $user->trial_credits_expired = $freshUser->trial_credits_expired;

            $this->logTransaction(
                $user,
                -$amount,
                $freshUser->credits,
                'trial_expiration',
                'Expiração automática do saldo promocional restante do trial.',
                null
            );

            return $amount;
        });
    }

    /**
     * Registra transacao no historico.
     */
    private function logTransaction(User $user, int $amount, int $balanceAfter, string $type, ?string $description = null, ?Model $source = null): void
    {
        CreditTransaction::create([
            'user_id' => $user->id,
            'amount' => $amount,
            'balance_after' => $balanceAfter,
            'type' => $type,
            'description' => $description,
            'source_type' => $source ? get_class($source) : null,
            'source_id' => $source?->id,
        ]);
    }
}
