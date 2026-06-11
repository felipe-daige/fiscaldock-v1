<?php

namespace App\Services\Consultas;

use App\Models\ConsultaLote;
use App\Models\ConsultaResultado;
use App\Services\CreditService;
use App\Services\RiskScoreService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FecharLoteService
{
    public function __construct(
        private CreditService $creditService,
        private RiskScoreService $riskScoreService,
        private AtualizarFichaCadastralService $fichaCadastral,
    ) {}

    public function fechar(int $loteId, array $resumo = []): void
    {
        // Estorno preciso: soma o total por alvo (participante OU cliente) acumulado pelos
        // jobs (cache, overwrite por alvo → idempotente). Só fontes em falha estornável contam.
        $alvos = ConsultaResultado::where('consulta_lote_id', $loteId)
            ->get(['participante_id', 'cliente_id']);

        $creditosFalhos = 0;
        foreach ($alvos as $alvo) {
            [$tipo, $id] = $alvo->cliente_id
                ? ['cliente', $alvo->cliente_id]
                : ['participante', $alvo->participante_id];
            $creditosFalhos += (int) Cache::pull("consulta_estorno:{$loteId}:{$tipo}:{$id}", 0);
        }

        DB::transaction(function () use ($loteId, $creditosFalhos, $resumo) {
            /** @var ConsultaLote $lote */
            $lote = ConsultaLote::lockForUpdate()->findOrFail($loteId);

            $lote->status = ConsultaLote::STATUS_CONCLUIDO;
            $lote->resultado_resumo = $resumo;
            $lote->processado_em = now();
            $lote->save();

            if ($creditosFalhos > 0) {
                $this->creditService->add(
                    $lote->user,
                    $creditosFalhos,
                    type: 'consulta_refund',
                    description: "Estorno de {$creditosFalhos} crédito(s) — fontes com falha no lote #{$lote->id}",
                    source: $lote,
                );
            }
        });

        // Score Fiscal: recalcula/persiste o score de cada participante consultado a partir
        // do resultado_dados. Fora da transação de crédito — falha aqui nunca desfaz o estorno.
        $this->persistirScores($loteId);
    }

    /**
     * Calcula e persiste o Score de Regularidade por alvo consultado no lote —
     * participantes (contrapartes) E clientes (empresas geridas/própria).
     */
    private function persistirScores(int $loteId): void
    {
        $resultados = ConsultaResultado::query()
            ->where('consulta_lote_id', $loteId)
            ->whereNotNull('resultado_dados')
            ->with(['participante', 'cliente'])
            ->get();

        foreach ($resultados as $resultado) {
            $dados = (array) $resultado->resultado_dados;

            try {
                if ($resultado->participante) {
                    $this->riskScoreService->atualizarScore($resultado->participante, $dados);
                    $this->fichaCadastral->aplicar($resultado->participante, $dados);
                } elseif ($resultado->cliente) {
                    $this->riskScoreService->atualizarScoreCliente($resultado->cliente, $dados);
                    $this->fichaCadastral->aplicar($resultado->cliente, $dados);
                }
            } catch (\Throwable $e) {
                Log::warning('Falha ao persistir score do alvo da consulta', [
                    'lote_id' => $loteId,
                    'participante_id' => $resultado->participante_id,
                    'cliente_id' => $resultado->cliente_id,
                    'erro' => $e->getMessage(),
                ]);
            }
        }
    }
}
