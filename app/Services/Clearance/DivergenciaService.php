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
        return [
            'veredito' => $this->verediticoVazio(),
            'kpis' => $this->kpisVazios($creditosCobrados),
            'breakdown' => $this->breakdownVazio(),
            'divergencias' => new Collection(),
            'sem_divergencia' => new Collection(),
            'ruido' => new Collection(),
        ];
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
