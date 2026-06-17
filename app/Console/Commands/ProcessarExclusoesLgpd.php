<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * LGPD fase 2.2 — processa os pedidos de exclusão de conta (`deletion_requested_at`).
 *
 * Anonimiza a PII do TITULAR (nome/e-mail/telefone/CNPJ/empresa…) e desabilita o login,
 * MAS preserva os dados fiscais (clientes, participantes, SPED/XML) — retenção legal.
 *
 * Manual e conservador de propósito (host é produção): SEM scheduler. Roda em dry-run
 * por padrão (só lista); só muta com --force. `--apos-dias=N` ignora pedidos mais novos
 * que N dias (carência).
 */
class ProcessarExclusoesLgpd extends Command
{
    protected $signature = 'lgpd:processar-exclusoes
        {--force : Executa a anonimização de fato (sem isto, apenas lista — dry-run)}
        {--apos-dias=0 : Só processa pedidos com pelo menos N dias (carência)}';

    protected $description = 'Anonimiza a PII de titulares que pediram exclusão, preservando dados fiscais (LGPD).';

    public function handle(): int
    {
        $aposDias = max(0, (int) $this->option('apos-dias'));
        $force = (bool) $this->option('force');
        $limite = now()->subDays($aposDias);

        $pendentes = User::whereNotNull('deletion_requested_at')
            ->whereNull('anonimizado_em')
            ->where('deletion_requested_at', '<=', $limite)
            ->orderBy('deletion_requested_at')
            ->get();

        if ($pendentes->isEmpty()) {
            $this->info('Nenhum pedido de exclusão elegível para processamento.');

            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'E-mail', 'Pedido em'],
            $pendentes->map(fn (User $u) => [
                $u->id,
                $u->email,
                optional($u->deletion_requested_at)->format('d/m/Y'),
            ])->all()
        );

        if (! $force) {
            $this->warn("DRY-RUN: {$pendentes->count()} titular(es) seriam anonimizados. Nada foi alterado.");
            $this->line('Use --force para executar a anonimização.');

            return self::SUCCESS;
        }

        $anonimizados = 0;
        foreach ($pendentes as $user) {
            $this->anonimizar($user);
            $anonimizados++;
            Log::info('lgpd.exclusao.anonimizada', ['user_id' => $user->id]);
        }

        $this->info("{$anonimizados} titular(es) anonimizado(s). Dados fiscais preservados.");

        return self::SUCCESS;
    }

    private function anonimizar(User $user): void
    {
        $user->forceFill([
            'name' => 'Titular',
            'sobrenome' => 'anonimizado',
            'email' => 'anon-'.$user->id.'@anonimizado.invalid',
            'telefone' => '', // coluna NOT NULL — placeholder vazio em vez de null
            'empresa' => null,
            'cargo' => null,
            'cnpj' => null,
            'faturamento_anual' => null,
            'desafio_principal' => null,
            'marketing_opt_in' => false,
            'password' => Str::random(48), // cast 'hashed' rehasha; login fica impossível
            'remember_token' => null,
            'anonimizado_em' => now(),
        ])->save();
    }
}
