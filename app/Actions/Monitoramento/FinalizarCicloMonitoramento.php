<?php

namespace App\Actions\Monitoramento;

use App\Models\ConsultaResultado;
use App\Models\MonitoramentoConsulta;
use App\Services\Consultas\ResultadoDetalhePresenter;

/**
 * Callback do batch de monitoramento: lê o resultado do lote da pipeline,
 * marca a MonitoramentoConsulta (sucesso/erro), deriva a situação geral e
 * dispara a avaliação de mudança. Substitui o antigo endpoint de retorno do n8n.
 */
class FinalizarCicloMonitoramento
{
    public function __construct(
        private AvaliarMudancaSituacao $avaliar,
        private ResultadoDetalhePresenter $presenter,
    ) {}

    public function execute(int $consultaId): void
    {
        $consulta = MonitoramentoConsulta::find($consultaId);
        if (! $consulta || ! $consulta->consulta_lote_id) {
            return;
        }

        $query = ConsultaResultado::where('consulta_lote_id', $consulta->consulta_lote_id);
        if ($consulta->cliente_id) {
            $query->where('cliente_id', $consulta->cliente_id);
        } elseif ($consulta->participante_id) {
            $query->where('participante_id', $consulta->participante_id);
        }
        $resultado = $query->first();

        if (! $resultado || $resultado->status !== ConsultaResultado::STATUS_SUCESSO) {
            $consulta->marcarErro('ciclo_falhou', 'Lote de monitoramento sem resultado bem-sucedido.');

            return;
        }

        $sit = $this->presenter->situacaoGeral($resultado);
        $consulta->marcarSucesso(
            resultado: $resultado->resultado_dados ?? [],
            situacaoGeral: $sit['situacao_geral'],
            temPendencias: $sit['tem_pendencias'],
            proximaValidade: null,
        );

        $this->avaliar->execute($consulta->fresh());
    }
}
