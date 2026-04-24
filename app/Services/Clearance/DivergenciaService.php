<?php

namespace App\Services\Clearance;

use App\Models\EfdNota;
use App\Models\XmlNota;
use Illuminate\Support\Collection;

class DivergenciaService
{
    public const TOLERANCIA_ABSOLUTA_RUIDO = 5.00;
    public const TOLERANCIA_PERCENTUAL_RUIDO = 0.5;
    public const LIMIAR_CRITICO_ABSOLUTO = 100.00;
    public const LIMIAR_CRITICO_PERCENTUAL = 10.0;

    /**
     * @param  Collection  $snapshots  coleção de nfe_consultas/cte_consultas já formatada por
     *                                 ClearanceController::listarConsultasDfePorLote (inclui
     *                                 chave_acesso, status_label, valor_total, emit/dest, etc.)
     */
    public function analisar(Collection $snapshots, int $userId, int $creditosCobrados): array
    {
        if ($snapshots->isEmpty()) {
            return [
                'veredito' => $this->verediticoVazio(),
                'kpis' => $this->kpisVazios($creditosCobrados),
                'breakdown' => $this->breakdownVazio(),
                'divergencias' => new Collection(),
                'sem_divergencia' => new Collection(),
                'ruido' => new Collection(),
            ];
        }

        $chaves = $snapshots->pluck('chave_acesso')->filter()->unique()->values()->all();
        $declaradoMap = $this->buscarDeclaradoPorChave($userId, $chaves);

        $divergencias = new Collection();
        $semDivergencia = new Collection();
        $ruido = new Collection();

        $kpiEncontradas = 0;
        $kpiCanceladasDeclaradas = 0;
        $kpiDenegadas = 0;
        $kpiInutilizadas = 0;
        $valorCriticoTotal = 0.0;
        $breakdown = $this->breakdownVazio();

        foreach ($snapshots as $snapshot) {
            $chave = $snapshot->chave_acesso ?? null;
            $statusSefaz = strtoupper((string) ($snapshot->status_label ?? $snapshot->status ?? ''));
            $sefazValor = $snapshot->valor_total !== null ? (float) $snapshot->valor_total : null;
            $declarado = $chave !== null && isset($declaradoMap[$chave]) ? $declaradoMap[$chave] : null;
            $declaradoValor = $declarado['valor_total'] ?? null;

            if (! in_array($statusSefaz, ['NAO_ENCONTRADA', 'ERRO_PARAMETRO', 'ERRO_PROVEDOR', 'TIMEOUT'], true)) {
                $kpiEncontradas++;
            }
            if ($statusSefaz === 'CANCELADA' && $declaradoValor !== null && $declaradoValor > 0) {
                $kpiCanceladasDeclaradas++;
            }
            if ($statusSefaz === 'DENEGADA' && $declaradoValor !== null && $declaradoValor > 0) {
                $kpiDenegadas++;
            }
            if ($statusSefaz === 'INUTILIZADA' && $declaradoValor !== null && $declaradoValor > 0) {
                $kpiInutilizadas++;
            }

            $severidade = $this->classificarSeveridade($statusSefaz, $declaradoValor, $sefazValor);
            $deltaValor = ($declaradoValor !== null && $sefazValor !== null) ? round($sefazValor - $declaradoValor, 2) : 0.0;
            $deltaPct = ($declaradoValor !== null && $declaradoValor > 0 && $sefazValor !== null)
                ? round((($sefazValor - $declaradoValor) / $declaradoValor) * 100, 2)
                : 0.0;

            $linha = (object) array_merge((array) $snapshot, [
                'declarado_valor' => $declaradoValor,
                'declarado_valor_label' => $declaradoValor !== null ? 'R$ '.number_format($declaradoValor, 2, ',', '.') : '—',
                'delta_valor' => $deltaValor,
                'delta_valor_label' => 'R$ '.number_format($deltaValor, 2, ',', '.'),
                'delta_percentual' => $deltaPct,
                'delta_percentual_label' => number_format($deltaPct, 1, ',', '.').'%',
                'severidade' => $severidade,
                'tipos_divergencia' => $this->tiposDivergencia($statusSefaz, $declaradoValor, $severidade),
                'declarado_origem' => $declarado['origem'] ?? null,
            ]);

            if ($severidade === 'critica' || $severidade === 'revisar') {
                $divergencias->push($linha);
                if ($severidade === 'critica') {
                    if ($declaradoValor !== null && $sefazValor === null && $statusSefaz === 'NAO_ENCONTRADA') {
                        $valorCriticoTotal += $declaradoValor;
                    } else {
                        $valorCriticoTotal += abs($deltaValor);
                    }
                }
            } elseif ($severidade === 'ruido') {
                $ruido->push($linha);
            } else {
                $semDivergencia->push($linha);
            }

            foreach ($linha->tipos_divergencia as $tipo) {
                if (isset($breakdown[$tipo])) {
                    $breakdown[$tipo]['count']++;
                    $breakdown[$tipo]['valor'] += abs($deltaValor);
                }
            }
        }

        $totalCriticas = $divergencias->where('severidade', 'critica')->count();
        $totalRevisar = $divergencias->where('severidade', 'revisar')->count();
        $valorDivergente = round($valorCriticoTotal, 2);

        $veredito = [
            'severidade' => $totalCriticas > 0 ? 'critica' : ($totalRevisar > 0 ? 'revisar' : 'ok'),
            'total_criticas' => $totalCriticas,
            'total_revisar' => $totalRevisar,
            'valor_divergente' => $valorDivergente,
            'mensagem' => $this->mensagemVeredito($totalCriticas, $totalRevisar, $valorCriticoTotal),
        ];

        return [
            'veredito' => $veredito,
            'kpis' => [
                'existencia' => [
                    'total' => $snapshots->count(),
                    'encontradas' => $kpiEncontradas,
                    'nao_encontradas' => $snapshots->count() - $kpiEncontradas,
                ],
                'status' => [
                    'total' => $snapshots->count(),
                    'canceladas_declaradas' => $kpiCanceladasDeclaradas,
                    'denegadas' => $kpiDenegadas,
                    'inutilizadas' => $kpiInutilizadas,
                ],
                'valor' => [
                    'notas_divergentes' => $totalCriticas,
                    'valor_divergente' => $valorDivergente,
                ],
                'roi' => [
                    'creditos' => $creditosCobrados,
                    'custo_reais' => round($creditosCobrados * 0.20, 2),
                    'exposicao_reais' => $valorDivergente,
                ],
            ],
            'breakdown' => $breakdown,
            'divergencias' => $divergencias->sortByDesc('severidade')->values(),
            'sem_divergencia' => $semDivergencia->values(),
            'ruido' => $ruido->values(),
        ];
    }

    /** @return array<int, string> */
    private function tiposDivergencia(string $statusSefaz, ?float $declarado, string $severidade): array
    {
        $tipos = [];

        if ($statusSefaz === 'NAO_ENCONTRADA' && $declarado !== null && $declarado > 0) {
            $tipos[] = 'notas_frias';
        }
        if (in_array($statusSefaz, ['CANCELADA', 'DENEGADA', 'INUTILIZADA'], true) && $declarado !== null && $declarado > 0) {
            $tipos[] = 'canceladas_declaradas';
        }
        if ($severidade === 'critica' && ! in_array($statusSefaz, ['NAO_ENCONTRADA', 'CANCELADA', 'DENEGADA', 'INUTILIZADA'], true)) {
            $tipos[] = 'valor_divergente';
        }

        return $tipos;
    }

    private function mensagemVeredito(int $criticas, int $revisar, float $valorDivergente): string
    {
        if ($criticas === 0 && $revisar === 0) {
            return 'Nenhuma divergência acima da tolerância neste lote.';
        }

        $valor = 'R$ '.number_format($valorDivergente, 2, ',', '.');

        if ($criticas > 0) {
            return "{$criticas} ".($criticas === 1 ? 'divergência crítica' : 'divergências críticas')." encontrada(s) — {$valor} em exposição fiscal.";
        }

        return "{$revisar} ".($revisar === 1 ? 'divergência' : 'divergências')." a revisar — {$valor}.";
    }

    /**
     * Retorna map chave_acesso => ['valor_total' => float, 'origem' => 'xml'|'efd', 'id' => int].
     * XML tem precedência sobre EFD quando ambos existem (busca avulsa / upload é mais rico).
     */
    public function buscarDeclaradoPorChave(int $userId, array $chaves): array
    {
        if (empty($chaves)) {
            return [];
        }

        $map = [];

        EfdNota::query()
            ->where('user_id', $userId)
            ->whereIn('chave_acesso', $chaves)
            ->get(['id', 'chave_acesso', 'valor_total'])
            ->each(function ($nota) use (&$map) {
                $map[$nota->chave_acesso] = [
                    'valor_total' => (float) $nota->valor_total,
                    'origem' => 'efd',
                    'id' => $nota->id,
                ];
            });

        XmlNota::query()
            ->where('user_id', $userId)
            ->whereIn('nfe_id', $chaves)
            ->get(['id', 'nfe_id', 'valor_total'])
            ->each(function ($nota) use (&$map) {
                $map[$nota->nfe_id] = [
                    'valor_total' => (float) $nota->valor_total,
                    'origem' => 'xml',
                    'id' => $nota->id,
                ];
            });

        return $map;
    }

    private function verediticoVazio(): array
    {
        return [
            'severidade' => 'ok',
            'total_criticas' => 0,
            'total_revisar' => 0,
            'valor_divergente' => 0.0,
            'mensagem' => 'Sem documentos auditados neste lote.',
        ];
    }

    private function kpisVazios(int $creditosCobrados): array
    {
        return [
            'existencia' => ['total' => 0, 'encontradas' => 0, 'nao_encontradas' => 0],
            'status' => ['total' => 0, 'canceladas_declaradas' => 0, 'denegadas' => 0, 'inutilizadas' => 0],
            'valor' => ['notas_divergentes' => 0, 'valor_divergente' => 0.0],
            'roi' => [
                'creditos' => $creditosCobrados,
                'custo_reais' => round($creditosCobrados * 0.20, 2),
                'exposicao_reais' => 0.0,
            ],
        ];
    }

    private function breakdownVazio(): array
    {
        return [
            'notas_frias' => ['count' => 0, 'valor' => 0.0],
            'canceladas_declaradas' => ['count' => 0, 'valor' => 0.0],
            'valor_divergente' => ['count' => 0, 'valor' => 0.0],
            'partes_divergentes' => ['count' => 0, 'valor' => 0.0],
            'operacionais' => ['count' => 0, 'valor' => 0.0],
        ];
    }

    /**
     * @return 'critica'|'revisar'|'ruido'|'ok'
     */
    public function classificarSeveridade(string $statusSefaz, ?float $declarado, ?float $sefaz): string
    {
        $status = strtoupper($statusSefaz);

        if ($declarado !== null && $declarado > 0 && in_array($status, ['CANCELADA', 'DENEGADA', 'INUTILIZADA'], true)) {
            return 'critica';
        }

        if ($declarado !== null && $declarado > 0 && $status === 'NAO_ENCONTRADA') {
            return 'critica';
        }

        if ($declarado === null || $sefaz === null) {
            return 'ok';
        }

        $delta = abs($sefaz - $declarado);
        $deltaPct = $declarado > 0 ? ($delta / $declarado) * 100 : 0;

        if ($delta <= self::TOLERANCIA_ABSOLUTA_RUIDO || $deltaPct <= self::TOLERANCIA_PERCENTUAL_RUIDO) {
            return $delta === 0.0 ? 'ok' : 'ruido';
        }

        if ($delta > self::LIMIAR_CRITICO_ABSOLUTO && $deltaPct > self::LIMIAR_CRITICO_PERCENTUAL) {
            return 'critica';
        }

        return 'revisar';
    }
}
