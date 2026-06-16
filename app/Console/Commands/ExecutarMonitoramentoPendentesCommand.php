<?php

namespace App\Console\Commands;

use App\Actions\Monitoramento\DispararConsultaMonitoramento;
use App\Models\MonitoramentoAssinatura;
use App\Models\MonitoramentoConsulta;
use App\Services\CreditService;
use App\Support\Monitoramento\MonitoramentoNotifier;
use Illuminate\Console\Command;

class ExecutarMonitoramentoPendentesCommand extends Command
{
    protected $signature = 'monitoramento:executar-pendentes';

    protected $description = 'Dispara as consultas recorrentes de monitoramento contínuo vencidas e os retries de erro';

    public function handle(
        DispararConsultaMonitoramento $disparar,
        CreditService $creditService,
        MonitoramentoNotifier $notifier,
    ): int {
        $this->executarVencidas($disparar, $creditService, $notifier);
        $this->executarRetries($disparar, $creditService, $notifier);

        return self::SUCCESS;
    }

    private function executarVencidas(
        DispararConsultaMonitoramento $disparar,
        CreditService $creditService,
        MonitoramentoNotifier $notifier,
    ): void {
        foreach (MonitoramentoAssinatura::pendentesExecucao() as $assinatura) {
            $custo = (int) ($assinatura->plano->custo_creditos ?? 0);

            if (! $creditService->hasEnough($assinatura->user, $custo)) {
                $assinatura->pausar();
                $notifier->assinaturaPausadaSemSaldo($assinatura);
                $this->warn("Assinatura #{$assinatura->id} pausada — saldo insuficiente.");

                continue;
            }

            $disparar->execute($assinatura);
            $assinatura->agendarProximaExecucao();
            $this->info("Assinatura #{$assinatura->id} disparada.");
        }
    }

    private function executarRetries(
        DispararConsultaMonitoramento $disparar,
        CreditService $creditService,
        MonitoramentoNotifier $notifier,
    ): void {
        $elegiveis = MonitoramentoConsulta::query()
            ->where('tipo', 'assinatura')
            ->where('status', 'erro')
            ->where('executado_em', '<=', now()->subDay())
            ->whereIn('id', function ($q) {
                $q->selectRaw('MAX(id)')
                    ->from('monitoramento_consultas')
                    ->where('tipo', 'assinatura')
                    ->whereNotNull('assinatura_id')
                    ->groupBy('assinatura_id');
            })
            ->get();

        foreach ($elegiveis as $consultaErro) {
            $assinatura = $consultaErro->assinatura;

            if (! $assinatura || ! $assinatura->isAtiva()) {
                continue;
            }

            if ($consultaErro->retryCount() >= 3) {
                $assinatura->pausar();
                $notifier->assinaturaPausadaPorFalhas($assinatura);
                $this->warn("Assinatura #{$assinatura->id} pausada — 3 retries falhos.");

                continue;
            }

            $custo = (int) ($assinatura->plano->custo_creditos ?? 0);

            if (! $creditService->hasEnough($assinatura->user, $custo)) {
                $assinatura->pausar();
                $notifier->assinaturaPausadaSemSaldo($assinatura);
                $this->warn("Assinatura #{$assinatura->id} pausada no retry — saldo insuficiente.");

                continue;
            }

            $disparar->execute($assinatura, $consultaErro);
            $this->info("Retry da assinatura #{$assinatura->id} disparado.");
        }
    }
}
