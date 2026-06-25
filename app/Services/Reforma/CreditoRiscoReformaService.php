<?php

namespace App\Services\Reforma;

use App\Models\EfdNota;
use App\Services\RiskScoreService;
use Illuminate\Database\Eloquent\Model;

/**
 * Camada financeira do Score de Crédito IBS/CBS: converte o fator de regime (adimensional)
 * em exposição em R$, multiplicando pelo volume de entradas escriturado no EFD.
 *
 *   crédito potencial = volume entradas × alíquota de referência
 *   crédito em risco   = potencial × (1 − fator de crédito do regime do fornecedor)
 *
 * Ver docs/score-fiscal/credito-reforma.md (§3) e credito-reforma-fundamentacao-legal.md.
 */
class CreditoRiscoReformaService
{
    public function __construct(private RiskScoreService $score) {}

    /**
     * Exposição de crédito por fornecedor, para um cliente, no período (opcional).
     * Ordenado desc por crédito em risco (maior perda primeiro).
     *
     * @return array<int, array<string, mixed>>
     */
    public function exposicaoPorFornecedor(int $clienteId, ?string $dataIni = null, ?string $dataFim = null, ?int $ano = null): array
    {
        $notas = EfdNota::query()
            ->entradas()
            ->where('cliente_id', $clienteId)
            ->where('cancelada', false)
            ->whereNotNull('participante_id')
            ->when($dataIni !== null, fn ($q) => $q->whereDate('data_emissao', '>=', $dataIni))
            ->when($dataFim !== null, fn ($q) => $q->whereDate('data_emissao', '<=', $dataFim))
            ->groupBy('participante_id')
            ->selectRaw('participante_id, SUM(valor_total) as volume')
            ->with('participante.score')
            ->get();

        $linhas = [];

        foreach ($notas as $nota) {
            $participante = $nota->participante;

            if ($participante === null) {
                continue;
            }

            $volume = (float) $nota->volume;

            $linhas[] = array_merge([
                'participante_id' => $participante->id,
                'razao_social' => $participante->razao_social,
                'documento' => $participante->documento,
                'volume' => $volume,
            ], $this->creditoParticipante($participante, $volume, $participante->score?->score_credito_reforma, $ano));
        }

        usort($linhas, fn ($a, $b) => ($b['credito_em_risco'] ?? -1) <=> ($a['credito_em_risco'] ?? -1));

        return $linhas;
    }

    /**
     * Crédito IBS/CBS de UM fornecedor dado seu volume de entradas — usado na tela de detalhe.
     *   potencial = volume × alíquota ; em risco = potencial × (1 − fator do regime).
     *
     * O regime mora no resultado da consulta (persistido em `score_credito_reforma` OU nos
     * `$dados` do `resultado_dados`), não nas colunas do participante (Laravel não atualiza
     * participante). Precedência: `$scoreCredito` persistido → `$dados` da consulta → colunas
     * do alvo. Sem nenhum → "regime não identificado" (fator null, cinza).
     *
     * @param  array<string, mixed>  $dados  resultado_dados da consulta (regime_tributario/crt/mei/...)
     * @return array{score: int|null, fator: float|null, gera_credito: string, credito_potencial: float, credito_em_risco: float|null, flag: string}
     */
    public function creditoParticipante(?Model $alvo, float $volume, ?int $scoreCredito = null, ?int $ano = null, array $dados = []): array
    {
        $aliquota = $this->aliquotaPara($ano);
        $fator = $scoreCredito !== null
            ? (100 - $scoreCredito) / 100.0
            : $this->score->fatorCreditoRegime($alvo, $dados);
        $potencial = round($volume * $aliquota, 2);

        return [
            'score' => $scoreCredito ?? $this->score->scoreCreditoReforma($dados, $alvo),
            'aliquota' => $aliquota,
            'fator' => $fator,
            'gera_credito' => $this->geraLabel($fator),
            'credito_potencial' => $potencial,
            'credito_em_risco' => $fator === null ? null : round($potencial * (1 - $fator), 2),
            'flag' => $this->flag($fator),
        ];
    }

    /**
     * Alíquota total IBS+CBS aplicável. `$ano` null = estado pleno (`aliquota_referencia`).
     * Por ano: usa `aliquotas_por_fase`; antes da tabela = 0 (sem IBS/CBS); depois (>=2033) = pleno.
     */
    public function aliquotaPara(?int $ano = null): float
    {
        $pleno = (float) config('reforma.aliquota_referencia');

        if ($ano === null) {
            return $pleno;
        }

        $fases = (array) config('reforma.aliquotas_por_fase', []);

        if (isset($fases[$ano])) {
            return (float) $fases[$ano];
        }

        if ($fases === []) {
            return $pleno;
        }

        return $ano < min(array_keys($fases)) ? 0.0 : $pleno;
    }

    private function geraLabel(?float $fator): string
    {
        return match (true) {
            $fator === null => 'Regime não identificado',
            $fator >= 1.0 => 'Gera crédito integral',
            $fator <= 0.0 => 'Não gera crédito',
            default => 'Gera crédito parcial',
        };
    }

    /**
     * Agregado da carteira: total em risco, potencial, nº de fornecedores e quantos sem regime.
     *
     * @return array<string, mixed>
     */
    public function resumo(int $clienteId, ?string $dataIni = null, ?string $dataFim = null): array
    {
        $linhas = $this->exposicaoPorFornecedor($clienteId, $dataIni, $dataFim);

        return [
            'total_em_risco' => round(array_sum(array_map(fn ($l) => $l['credito_em_risco'] ?? 0, $linhas)), 2),
            'total_potencial' => round(array_sum(array_map(fn ($l) => $l['credito_potencial'], $linhas)), 2),
            'fornecedores' => count($linhas),
            'sem_regime' => count(array_filter($linhas, fn ($l) => $l['fator'] === null)),
        ];
    }

    /** Semáforo por fator de crédito do regime. */
    private function flag(?float $fator): string
    {
        return match (true) {
            $fator === null => 'cinza',   // regime não identificado
            $fator >= 1.0 => 'verde',     // gera crédito integral
            $fator <= 0.0 => 'vermelho',  // não gera crédito
            default => 'amarelo',         // crédito parcial (Simples sem opção)
        };
    }
}
