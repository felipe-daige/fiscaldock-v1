<?php

namespace App\Services\Catalogo;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Cruzamentos analíticos cross-source (XML+EFD com dedup) usando o
 * `NotaItemUnificadoService` como fonte. Foco em insight estratégico:
 * concentração de NCMs, ciclo entrada×saída de produtos, dispersão de
 * alíquota (sinal de erro de tributação) e itens vendidos sem catálogo
 * (auditoria fiscal).
 *
 * Entregue na Fase 5 da integração itens × catálogo
 * (`docs/catalogo/integracao-itens-catalogo.md`).
 */
final class BiCatalogoItensService
{
    public function __construct(private readonly NotaItemUnificadoService $itens) {}

    /**
     * Top N NCMs por valor movimentado (XML+EFD com dedup).
     *
     * Cada entrada: ncm, valor_total, total_itens (linhas), notas_distintas, percentual.
     *
     * @param  array{data_inicio?: ?string, data_fim?: ?string, cliente_id?: ?int, tipo_operacao?: ?string}  $filtros
     */
    public function topNcms(int $userId, int $limite = 10, array $filtros = []): array
    {
        $linhas = $this->itens->itensUnificados($userId, $filtros)
            ->filter(fn ($l) => filled($l->ncm));

        if ($linhas->isEmpty()) {
            return [];
        }

        $valorGeral = (float) $linhas->sum('valor_total');

        return $linhas
            ->groupBy('ncm')
            ->map(fn (Collection $grupo, $ncm) => [
                'ncm' => (string) $ncm,
                'valor_total' => (float) $grupo->sum('valor_total'),
                'total_itens' => $grupo->count(),
                'notas_distintas' => $grupo->pluck('chave_acesso')->filter()->unique()->count(),
                'percentual' => $valorGeral > 0
                    ? round(((float) $grupo->sum('valor_total') / $valorGeral) * 100, 2)
                    : 0.0,
            ])
            ->sortByDesc('valor_total')
            ->take($limite)
            ->values()
            ->all();
    }

    /**
     * CFOPs por NCM, separando entrada × saída. Mostra o ciclo do produto.
     *
     * Retorna até $limite NCMs, e pra cada um array com 'entradas' e 'saidas',
     * cada lado listando CFOPs ordenados por count.
     *
     * @param  array{data_inicio?: ?string, data_fim?: ?string, cliente_id?: ?int}  $filtros
     */
    public function cfopsPorNcm(int $userId, int $limite = 10, array $filtros = []): array
    {
        // CFOPs entrada×saída por NCM exige ambos os lados — não filtra por tipo_operacao
        $filtros = array_diff_key($filtros, ['tipo_operacao' => null]);

        $linhas = $this->itens->itensUnificados($userId, $filtros)
            ->filter(fn ($l) => filled($l->ncm) && filled($l->cfop));

        if ($linhas->isEmpty()) {
            return [];
        }

        return $linhas
            ->groupBy('ncm')
            ->sortByDesc(fn (Collection $g) => (float) $g->sum('valor_total'))
            ->take($limite)
            ->map(function (Collection $grupoNcm, $ncm) {
                [$entradas, $saidas] = $grupoNcm->partition(fn ($l) => $l->tipo_operacao === 'entrada');

                $resumir = fn (Collection $sub) => $sub
                    ->groupBy(fn ($l) => (string) $l->cfop)
                    ->map(fn (Collection $g, $cfop) => [
                        'cfop' => (string) $cfop,
                        'count' => $g->count(),
                        'valor' => (float) $g->sum('valor_total'),
                    ])
                    ->sortByDesc('count')
                    ->values()
                    ->all();

                return [
                    'ncm' => (string) $ncm,
                    'valor_total' => (float) $grupoNcm->sum('valor_total'),
                    'entradas' => $resumir($entradas),
                    'saidas' => $resumir($saidas),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Itens com maior dispersão de alíquota — sinal de erro de tributação.
     * Ordena por (max - min) descendente; ignora itens com só 1 alíquota.
     *
     * Cada entrada: codigo_item, descricao, aliq_min, aliq_max, dispersao,
     * total_linhas, valor_total.
     *
     * @param  array<string, mixed>  $filtros
     */
    public function dispersaoAliquota(int $userId, int $limite = 10, array $filtros = []): array
    {
        $linhas = $this->itens->itensUnificados($userId, $filtros)
            ->filter(fn ($l) => $l->aliquota_icms !== null);

        if ($linhas->isEmpty()) {
            return [];
        }

        return $linhas
            ->groupBy('codigo_item')
            ->filter(fn (Collection $g) => $g->pluck('aliquota_icms')->unique()->count() > 1)
            ->map(function (Collection $grupo, $codigo) {
                $aliquotas = $grupo->pluck('aliquota_icms')->map(fn ($a) => (float) $a);

                return [
                    'codigo_item' => (string) $codigo,
                    'descricao' => (string) ($grupo->first()->descricao ?? ''),
                    'aliq_min' => round((float) $aliquotas->min(), 2),
                    'aliq_max' => round((float) $aliquotas->max(), 2),
                    'dispersao' => round((float) $aliquotas->max() - (float) $aliquotas->min(), 2),
                    'total_linhas' => $grupo->count(),
                    'valor_total' => (float) $grupo->sum('valor_total'),
                ];
            })
            ->sortByDesc('dispersao')
            ->take($limite)
            ->values()
            ->all();
    }

    /**
     * Itens vendidos (saídas) sem registro no catálogo (registro 0200).
     * Auditoria fiscal: você está emitindo nota com produto que não consta
     * no seu cadastro — é mais grave que comprar sem catálogo.
     *
     * Cada entrada: codigo_item, descricao, total_linhas, valor_total,
     * notas_distintas.
     *
     * @param  array<string, mixed>  $filtros
     */
    public function itensSaidaSemCatalogo(int $userId, int $limite = 10, array $filtros = []): array
    {
        $filtros['tipo_operacao'] = 'saida';

        $linhas = $this->itens->itensUnificados($userId, $filtros)
            ->filter(fn ($l) => filled($l->codigo_item));

        if ($linhas->isEmpty()) {
            return [];
        }

        $codigos = $linhas->pluck('codigo_item')->unique()->all();

        $codigosCadastrados = DB::table('efd_catalogo_itens')
            ->where('user_id', $userId)
            ->whereIn('cod_item', $codigos)
            ->pluck('cod_item')
            ->unique()
            ->flip(); // O(1) lookup

        $semCatalogo = $linhas->filter(fn ($l) => ! $codigosCadastrados->has($l->codigo_item));

        if ($semCatalogo->isEmpty()) {
            return [];
        }

        return $semCatalogo
            ->groupBy('codigo_item')
            ->map(fn (Collection $grupo, $codigo) => [
                'codigo_item' => (string) $codigo,
                'descricao' => (string) ($grupo->first()->descricao ?? ''),
                'total_linhas' => $grupo->count(),
                'valor_total' => (float) $grupo->sum('valor_total'),
                'notas_distintas' => $grupo->pluck('chave_acesso')->filter()->unique()->count(),
            ])
            ->sortByDesc('valor_total')
            ->take($limite)
            ->values()
            ->all();
    }
}
