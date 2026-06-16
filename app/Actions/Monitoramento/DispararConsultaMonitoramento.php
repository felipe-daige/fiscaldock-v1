<?php

namespace App\Actions\Monitoramento;

use App\Jobs\ProcessarConsultaJob;
use App\Models\ConsultaLote;
use App\Models\MonitoramentoAssinatura;
use App\Models\MonitoramentoConsulta;
use App\Services\Consultas\FecharLoteService;
use App\Services\CreditService;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Str;

/**
 * Dispara um ciclo de monitoramento contínuo reusando a pipeline de consulta:
 * cria a MonitoramentoConsulta + um ConsultaLote de 1 alvo + o batch
 * ProcessarConsultaJob, com FecharLoteService + FinalizarCicloMonitoramento no
 * callback. Débito/estorno são da pipeline (FecharLoteService).
 */
class DispararConsultaMonitoramento
{
    public function __construct(private CreditService $creditService) {}

    public function execute(MonitoramentoAssinatura $assinatura, ?MonitoramentoConsulta $parent = null): MonitoramentoConsulta
    {
        $plano = $assinatura->plano;
        $custo = (int) ($plano->custo_creditos ?? 0);
        $user = $assinatura->user;
        $alvoModel = $assinatura->alvo();

        $consulta = MonitoramentoConsulta::create([
            'user_id' => $assinatura->user_id,
            'participante_id' => $assinatura->participante_id,
            'cliente_id' => $assinatura->cliente_id,
            'plano_id' => $assinatura->plano_id,
            'assinatura_id' => $assinatura->id,
            'parent_consulta_id' => $parent?->id,
            'tipo' => 'assinatura',
            'status' => 'pendente',
            'creditos_cobrados' => $custo,
        ]);

        if ($custo > 0 && ! $this->creditService->deduct($user, $custo, 'monitoramento_assinatura', "Monitoramento contínuo — assinatura #{$assinatura->id}", $consulta)) {
            $consulta->marcarErro('saldo_insuficiente', 'Saldo insuficiente no disparo do ciclo.');

            return $consulta;
        }

        $tabId = (string) Str::uuid();
        $lote = ConsultaLote::create([
            'user_id' => $user->id,
            'cliente_id' => $assinatura->cliente_id,
            'plano_id' => $plano->id,
            'status' => ConsultaLote::STATUS_PROCESSANDO,
            'total_participantes' => 1,
            'creditos_cobrados' => $custo,
            'tab_id' => $tabId,
        ]);
        if ($assinatura->participante_id) {
            $lote->participantes()->attach([$assinatura->participante_id]);
        }
        $consulta->update(['consulta_lote_id' => $lote->id]);

        $alvo = [
            'cnpj' => preg_replace('/[^0-9]/', '', (string) $alvoModel->documento),
            'uf' => $alvoModel->uf ?? null,
            'crt' => $assinatura->participante_id ? ($alvoModel->crt ?? null) : null,
        ];

        $job = new ProcessarConsultaJob(
            loteId: $lote->id,
            alvoTipo: $assinatura->alvoTipo(),
            alvoId: $alvoModel->id,
            userId: $user->id,
            tabId: $tabId,
            consultasIncluidas: $plano->consultas_incluidas,
            alvo: $alvo,
            etapas: $plano->resolvedEtapas(),
            alvoIndice: 1,
            totalAlvos: 1,
        );

        $consultaId = $consulta->id;
        Bus::batch([$job])
            ->name("monitoramento-lote-{$lote->id}")
            ->then(function () use ($lote, $consultaId) {
                app(FecharLoteService::class)->fechar($lote->id, resumo: ['engine' => 'laravel', 'origem' => 'monitoramento']);
                app(FinalizarCicloMonitoramento::class)->execute($consultaId);
            })
            ->dispatch();

        return $consulta;
    }
}
