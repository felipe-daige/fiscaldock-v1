<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Lê o histórico de mudanças do catálogo (0200) gravado pelo trigger em
 * efd_catalogo_historico. Dá a timeline por item e um resumo de insights
 * (quantos itens mudaram NCM, alíquota, etc.) — útil pra alertas de drift
 * de cadastro (NCM errado = risco fiscal).
 */
class CatalogoHistoricoService
{
    private const LABELS = [
        'cod_ncm' => 'NCM',
        'aliq_icms' => 'Alíquota ICMS',
        'unid_inv' => 'Unidade',
        'descr_item' => 'Descrição',
    ];

    /**
     * Timeline de mudanças de um item (mais recente primeiro).
     *
     * @return array<int,array{campo:string,label:string,de:?string,para:?string,importacao_id:?int,changed_at:?string}>
     */
    public function timelineItem(int $userId, string $codItem): array
    {
        return DB::table('efd_catalogo_historico')
            ->where('user_id', $userId)
            ->where('cod_item', $codItem)
            ->orderByDesc('changed_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn ($r) => [
                'campo' => $r->campo,
                'label' => self::LABELS[$r->campo] ?? $r->campo,
                'de' => $r->valor_anterior,
                'para' => $r->valor_novo,
                'importacao_id' => $r->importacao_id !== null ? (int) $r->importacao_id : null,
                'changed_at' => $r->changed_at,
            ])
            ->all();
    }

    /**
     * Reconstrói, a partir do change-log, o valor dos campos logados (NCM,
     * alíquota, unidade, descrição) COMO ESTAVAM num instante `$refTime` — usado
     * para cruzar uma nota antiga com a versão do catálogo do período dela, mesmo
     * que uma importação posterior já tenha sobrescrito a linha (DO UPDATE).
     *
     * Para cada (cod_item, campo): vale o `valor_novo` da última mudança até
     * `$refTime`; se todas as mudanças são posteriores, vale o `valor_anterior` da
     * primeira (o valor original antes de qualquer alteração). Item sem histórico
     * não entra no retorno — o chamador mantém a versão atual do catálogo.
     *
     * @param  array<int,string>  $codItems
     * @return array<string,array<string,?string>> cod_item => [campo => valor]
     */
    public function valoresNaData(int $userId, ?int $clienteId, array $codItems, string $refTime): array
    {
        if (empty($codItems)) {
            return [];
        }

        $rows = DB::table('efd_catalogo_historico')
            ->where('user_id', $userId)
            ->when($clienteId, fn ($q) => $q->where('cliente_id', $clienteId))
            ->whereIn('cod_item', $codItems)
            ->whereIn('campo', array_keys(self::LABELS))
            ->orderBy('changed_at')
            ->orderBy('id')
            ->get();

        // Agrupa por cod_item → campo → [linhas em ordem cronológica].
        $porItemCampo = [];
        foreach ($rows as $r) {
            $porItemCampo[$r->cod_item][$r->campo][] = $r;
        }

        $out = [];
        foreach ($porItemCampo as $codItem => $campos) {
            foreach ($campos as $campo => $mudancas) {
                $anteriores = array_filter($mudancas, fn ($m) => $m->changed_at <= $refTime);

                if (! empty($anteriores)) {
                    $out[$codItem][$campo] = end($anteriores)->valor_novo;
                } else {
                    $out[$codItem][$campo] = $mudancas[0]->valor_anterior;
                }
            }
        }

        return $out;
    }

    /**
     * Resumo de mudanças por campo (insight de drift). Opcionalmente de UMA importação.
     *
     * @return array{total:int,por_campo:array<string,int>, itens_afetados:int}
     */
    public function resumoMudancas(int $userId, ?int $importacaoId = null): array
    {
        $base = DB::table('efd_catalogo_historico')
            ->where('user_id', $userId)
            ->when($importacaoId, fn ($q) => $q->where('importacao_id', $importacaoId));

        $porCampo = (clone $base)
            ->selectRaw('campo, COUNT(*) as total')
            ->groupBy('campo')
            ->pluck('total', 'campo')
            ->map(fn ($v) => (int) $v)
            ->all();

        return [
            'total' => array_sum($porCampo),
            'por_campo' => $porCampo,
            'itens_afetados' => (int) (clone $base)->distinct()->count('cod_item'),
        ];
    }
}
