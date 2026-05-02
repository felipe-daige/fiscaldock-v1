<?php

namespace App\Services\Catalogo;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Visão analítica unificada de itens de notas, agnóstica à fonte (XML ou EFD).
 *
 * Regra de dedup: quando uma nota está em ambas as fontes (mesma chave de
 * acesso), conta APENAS pela EFD. EFD é fonte oficial de apuração; XML duplicaria
 * o item sem ganho. Itens só-XML continuam contando.
 *
 * Chave do join EFD↔XML: efd_notas.chave_acesso = xml_notas.nfe_id (legado do
 * rename notas_fiscais → xml_notas).
 *
 * NCM no lado EFD: efd_notas_itens não tem coluna própria; vem do
 * efd_catalogo_itens via (cliente_id, cod_item). Pode ser null se o catálogo
 * 0200 ainda não foi importado pra esse cliente.
 */
final class NotaItemUnificadoService
{
    /**
     * Itens unificados (raw) — útil pra paginação ou drill-down.
     *
     * Cada linha tem: origem ('efd'|'xml'), codigo_item, descricao, ncm, cfop,
     * cst_icms, aliquota_icms, quantidade, valor_total, chave_acesso, data_emissao.
     *
     * @param  array{data_inicio?: ?string, data_fim?: ?string, cliente_id?: ?int, tipo_operacao?: ?string}  $filtros
     */
    public function itensUnificados(int $userId, array $filtros = []): Collection
    {
        $efd = $this->queryEfd($userId, $filtros);
        $xml = $this->queryXml($userId, $filtros);

        return collect($efd->unionAll($xml)->get());
    }

    /**
     * Agregação consolidada por codigo_item.
     *
     * Cada entrada: codigo_item, descricao, quantidade_total, valor_total,
     * cfops[], csts_icms[], aliquota_icms_media (ponderada por valor), ncms[],
     * notas (count distinct chave), origens[], ultima_movimentacao.
     *
     * @param  array{data_inicio?: ?string, data_fim?: ?string, cliente_id?: ?int, tipo_operacao?: ?string}  $filtros
     */
    public function agregadoPorItem(int $userId, array $filtros = []): Collection
    {
        return $this->itensUnificados($userId, $filtros)
            ->groupBy('codigo_item')
            ->map(function (Collection $itens, string $codigo): array {
                $primeiro = $itens->first();
                $valorTotal = (float) $itens->sum('valor_total');
                $somaPonderada = $itens
                    ->filter(fn ($i) => $i->aliquota_icms !== null)
                    ->sum(fn ($i) => (float) $i->aliquota_icms * (float) $i->valor_total);

                return [
                    'codigo_item' => $codigo,
                    'descricao' => $primeiro->descricao,
                    'quantidade_total' => (float) $itens->sum('quantidade'),
                    'valor_total' => $valorTotal,
                    'cfops' => $itens->pluck('cfop')->filter()->unique()->values()->all(),
                    'csts_icms' => $itens->pluck('cst_icms')->filter()->unique()->values()->all(),
                    'aliquota_icms_media' => $valorTotal > 0 ? round($somaPonderada / $valorTotal, 2) : null,
                    'ncms' => $itens->pluck('ncm')->filter()->unique()->values()->all(),
                    'notas' => $itens->pluck('chave_acesso')->filter()->unique()->count(),
                    'origens' => $itens->pluck('origem')->unique()->values()->all(),
                    'ultima_movimentacao' => $itens->max('data_emissao'),
                ];
            })
            ->values();
    }

    /**
     * Query do lado EFD — fonte oficial. Sempre conta.
     *
     * @param  array<string, mixed>  $filtros
     */
    private function queryEfd(int $userId, array $filtros): Builder
    {
        $q = DB::table('efd_notas_itens as eni')
            ->join('efd_notas as en', 'en.id', '=', 'eni.efd_nota_id')
            ->leftJoin('efd_catalogo_itens as cat', function ($join): void {
                $join->on('cat.cliente_id', '=', 'en.cliente_id')
                    ->on('cat.cod_item', '=', 'eni.codigo_item');
            })
            ->where('eni.user_id', $userId)
            ->select(
                DB::raw("'efd' as origem"),
                'eni.codigo_item',
                'eni.descricao',
                'cat.cod_ncm as ncm',
                DB::raw('CAST(eni.cfop AS varchar) as cfop'),
                'eni.cst_icms',
                'eni.aliquota_icms',
                'eni.quantidade',
                'eni.unidade_medida',
                'eni.valor_total',
                'en.chave_acesso',
                'en.data_emissao',
                'en.tipo_operacao',
                'en.cliente_id'
            );

        $this->aplicarFiltrosEfd($q, $filtros);

        return $q;
    }

    /**
     * Query do lado XML — só notas que NÃO estão em EFD do mesmo usuário.
     *
     * @param  array<string, mixed>  $filtros
     */
    private function queryXml(int $userId, array $filtros): Builder
    {
        $q = DB::table('xml_notas_itens as xni')
            ->join('xml_notas as xn', 'xn.id', '=', 'xni.xml_nota_id')
            ->where('xni.user_id', $userId)
            ->whereNotIn('xn.nfe_id', function ($sub) use ($userId): void {
                $sub->select('chave_acesso')
                    ->from('efd_notas')
                    ->whereNotNull('chave_acesso')
                    ->where('user_id', $userId);
            })
            ->select(
                DB::raw("'xml' as origem"),
                'xni.codigo_item',
                'xni.descricao',
                'xni.ncm',
                'xni.cfop',
                'xni.cst_icms',
                'xni.aliquota_icms',
                'xni.quantidade',
                'xni.unidade_medida',
                'xni.valor_total',
                DB::raw('xn.nfe_id as chave_acesso'),
                DB::raw('CAST(xn.data_emissao AS date) as data_emissao'),
                DB::raw("CASE xn.tipo_nota WHEN 0 THEN 'entrada' WHEN 1 THEN 'saida' ELSE NULL END as tipo_operacao"),
                'xn.cliente_id'
            );

        $this->aplicarFiltrosXml($q, $filtros);

        return $q;
    }

    /**
     * @param  array<string, mixed>  $filtros
     */
    private function aplicarFiltrosEfd(Builder $q, array $filtros): void
    {
        if (! empty($filtros['data_inicio'])) {
            $q->where('en.data_emissao', '>=', $filtros['data_inicio']);
        }
        if (! empty($filtros['data_fim'])) {
            $q->where('en.data_emissao', '<=', $filtros['data_fim']);
        }
        if (! empty($filtros['cliente_id'])) {
            $q->where('en.cliente_id', (int) $filtros['cliente_id']);
        }
        if (! empty($filtros['tipo_operacao'])) {
            $q->where('en.tipo_operacao', $filtros['tipo_operacao']);
        }
    }

    /**
     * @param  array<string, mixed>  $filtros
     */
    private function aplicarFiltrosXml(Builder $q, array $filtros): void
    {
        if (! empty($filtros['data_inicio'])) {
            $q->where('xn.data_emissao', '>=', $filtros['data_inicio']);
        }
        if (! empty($filtros['data_fim'])) {
            $q->where('xn.data_emissao', '<=', $filtros['data_fim']);
        }
        if (! empty($filtros['cliente_id'])) {
            $q->where(function ($sub) use ($filtros): void {
                $clienteId = (int) $filtros['cliente_id'];
                $sub->where('xn.cliente_id', $clienteId)
                    ->orWhere('xn.emit_cliente_id', $clienteId)
                    ->orWhere('xn.dest_cliente_id', $clienteId);
            });
        }
        if (! empty($filtros['tipo_operacao'])) {
            $tipoNota = $filtros['tipo_operacao'] === 'entrada' ? 0 : 1;
            $q->where('xn.tipo_nota', $tipoNota);
        }
    }
}
