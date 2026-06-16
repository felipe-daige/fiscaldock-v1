<?php

namespace App\Actions\Monitoramento;

use App\Models\MonitoramentoConsulta;
use App\Support\Monitoramento\MonitoramentoNotifier;

/**
 * Compara uma consulta de monitoramento (sucesso) com a última consulta
 * sucesso anterior da mesma assinatura e registra alertas in-app de
 * piora/melhora/pendências. "Certidão vencendo" é avaliada de forma
 * independente, sem depender de consulta anterior.
 */
class AvaliarMudancaSituacao
{
    private const ORDEM_SITUACAO = ['regular' => 0, 'atencao' => 1, 'irregular' => 2];

    private const JANELA_VALIDADE_DIAS = 30;

    public function __construct(private MonitoramentoNotifier $notifier) {}

    public function execute(MonitoramentoConsulta $consulta): void
    {
        if ($consulta->status !== 'sucesso' || ! $consulta->assinatura_id) {
            return;
        }

        if ($consulta->proxima_validade
            && $consulta->proxima_validade->lte(now()->addDays(self::JANELA_VALIDADE_DIAS))) {
            $this->notifier->certidaoVencendo($consulta);
        }

        $anterior = MonitoramentoConsulta::query()
            ->where('assinatura_id', $consulta->assinatura_id)
            ->where('status', 'sucesso')
            ->where('id', '<', $consulta->id)
            ->orderByDesc('id')
            ->first();

        if (! $anterior) {
            return; // baseline
        }

        $antes = self::ORDEM_SITUACAO[$anterior->situacao_geral] ?? null;
        $agora = self::ORDEM_SITUACAO[$consulta->situacao_geral] ?? null;

        if ($antes !== null && $agora !== null) {
            if ($agora > $antes) {
                $this->notifier->situacaoPiorou($consulta, $anterior);
            } elseif ($agora < $antes) {
                $this->notifier->situacaoMelhorou($consulta, $anterior);
            }
        }

        if (! $anterior->tem_pendencias && $consulta->tem_pendencias) {
            $this->notifier->pendenciasSurgiram($consulta);
        }
    }
}
