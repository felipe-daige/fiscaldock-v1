<?php

namespace App\Services\Catalogo;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Visão unificada (movement-centric) dos itens de nota das duas fontes (EFD + XML),
 * keyed pelo codigo_item do contribuinte. Dedup EFD × XML por chave_acesso (EFD vence);
 * itens não duplicam entre fiscal/contribuicoes (disjuntos por chave), logo a metade EFD
 * soma efd_notas_itens direto. Procedência por item (efd/xml/ambas).
 */
class NotaItemUnificadoService
{
    /**
     * @param  array{periodo_de?:string,periodo_ate?:string,cliente_id?:int,fonte?:string}  $filtros
     * @return Collection<int,array<string,mixed>>
     */
    public function itensAgregados(int $userId, array $filtros = []): Collection
    {
        $bind = ['uid' => $userId];
        $fonte = $filtros['fonte'] ?? 'ambas';

        $efdWhere = '';
        $xmlWhere = '';
        if (! empty($filtros['cliente_id'])) {
            $bind['cli'] = (int) $filtros['cliente_id'];
            $efdWhere .= ' AND n.cliente_id = :cli';
            $xmlWhere .= ' AND xn.cliente_id = :cli';
        }
        if (! empty($filtros['periodo_de'])) {
            $bind['de'] = $filtros['periodo_de'];
            $efdWhere .= ' AND n.data_emissao >= :de';
            $xmlWhere .= ' AND xn.data_emissao >= :de';
        }
        if (! empty($filtros['periodo_ate'])) {
            $bind['ate'] = $filtros['periodo_ate'];
            $efdWhere .= ' AND n.data_emissao <= :ate';
            $xmlWhere .= ' AND xn.data_emissao <= :ate';
        }

        $efdSelect = "
            SELECT ni.codigo_item, ni.descricao, ni.quantidade, ni.valor_total, ni.cfop::text AS cfop,
                   ni.cst_icms, ni.aliquota_icms, NULL::text AS ncm_item, 'efd' AS fonte
            FROM efd_notas_itens ni
            JOIN efd_notas n ON n.id = ni.efd_nota_id AND n.cancelada = false
            WHERE ni.user_id = :uid{$efdWhere}";

        $xmlSelect = "
            SELECT xi.codigo_item, xi.descricao, xi.quantidade, xi.valor_total, xi.cfop::text AS cfop,
                   xi.cst_icms, xi.aliquota_icms, xi.ncm AS ncm_item, 'xml' AS fonte
            FROM xml_notas_itens xi
            JOIN xml_notas xn ON xn.id = xi.xml_nota_id
            WHERE xi.user_id = :uid
              AND NOT EXISTS (
                  SELECT 1 FROM efd_notas en
                  WHERE en.user_id = :uid AND en.chave_acesso = xn.chave_acesso
              ){$xmlWhere}";

        if ($fonte === 'efd') {
            $movimento = $efdSelect;
        } elseif ($fonte === 'xml') {
            $movimento = $xmlSelect;
        } else {
            $movimento = $efdSelect.' UNION ALL '.$xmlSelect;
        }

        $sql = "
            WITH movimento AS ({$movimento})
            SELECT m.codigo_item,
                   MAX(m.descricao) AS descricao,
                   COUNT(*) AS ocorrencias,
                   COALESCE(SUM(m.quantidade), 0) AS quantidade,
                   COALESCE(SUM(m.valor_total), 0) AS valor_total,
                   string_agg(DISTINCT m.cfop, ',') AS cfops,
                   string_agg(DISTINCT m.cst_icms, ',') AS csts,
                   AVG(m.aliquota_icms) FILTER (WHERE m.aliquota_icms > 0) AS aliquota_media,
                   MAX(m.ncm_item) AS ncm_item,
                   string_agg(DISTINCT m.fonte, ',') AS fontes_raw,
                   cat.cod_item AS cat_cod_item, cat.descr_item AS cat_descr, cat.cod_ncm AS cat_ncm, cat.aliq_icms AS cat_aliq
            FROM movimento m
            LEFT JOIN (
                SELECT DISTINCT ON (cod_item) cod_item, descr_item, cod_ncm, aliq_icms
                FROM efd_catalogo_itens
                WHERE user_id = :uid
                ORDER BY cod_item, id DESC
            ) cat ON cat.cod_item = m.codigo_item
            GROUP BY m.codigo_item, cat.cod_item, cat.descr_item, cat.cod_ncm, cat.aliq_icms
            ORDER BY valor_total DESC";

        return collect(DB::select($sql, $bind))->map(fn ($r) => [
            'codigo_item' => $r->codigo_item,
            'descricao' => $r->descricao,
            'ocorrencias' => (int) $r->ocorrencias,
            'quantidade' => (float) $r->quantidade,
            'valor_total' => (float) $r->valor_total,
            'cfops' => $r->cfops,
            'csts' => $r->csts,
            'aliquota_media' => $r->aliquota_media !== null ? (float) $r->aliquota_media : null,
            'ncm' => $r->ncm_item ?: $r->cat_ncm,
            'fontes' => $this->normalizarFontes($r->fontes_raw),
            'tem_catalogo' => $r->cat_cod_item !== null,
            'catalogo' => $r->cat_cod_item !== null ? [
                'descr_item' => $r->cat_descr,
                'cod_ncm' => $r->cat_ncm,
                'aliq_icms' => $r->cat_aliq !== null ? (float) $r->cat_aliq : null,
            ] : null,
        ]);
    }

    /**
     * Divergência do que foi DOCUMENTADO (item XML) vs o CADASTRADO (catálogo 0200), por codigo_item.
     *
     * Scan NÃO-deduplicado de xml_notas_itens — ao contrário de itensAgregados (que dedup EFD×XML por
     * chave e descartaria os itens XML cuja chave também está no EFD, justamente o caso do acervo real).
     * Base = versão atual do catálogo. Comparação tolerante a máscara (regexp_replace [^0-9]).
     *
     * @param  array{periodo_de?:string,periodo_ate?:string,cliente_id?:int}  $filtros
     * @return array<string, array{descricao:?string,ncm_xml:?string,ncm_divergente:bool,aliquota_divergente:bool,tem_catalogo:bool,cat_ncm:?string}>
     */
    public function divergenciasNcmPorItem(int $userId, array $filtros = []): array
    {
        $bind = ['uid' => $userId];
        $xmlWhere = '';
        if (! empty($filtros['cliente_id'])) {
            $bind['cli'] = (int) $filtros['cliente_id'];
            $xmlWhere .= ' AND xn.cliente_id = :cli';
        }
        if (! empty($filtros['periodo_de'])) {
            $bind['de'] = $filtros['periodo_de'];
            $xmlWhere .= ' AND xn.data_emissao >= :de';
        }
        if (! empty($filtros['periodo_ate'])) {
            $bind['ate'] = $filtros['periodo_ate'];
            $xmlWhere .= ' AND xn.data_emissao <= :ate';
        }

        $sql = "
            SELECT xi.codigo_item,
                   MAX(xi.descricao) AS descricao,
                   string_agg(DISTINCT regexp_replace(xi.ncm, '[^0-9]', '', 'g'), ',')
                       FILTER (WHERE xi.ncm IS NOT NULL AND xi.ncm <> '') AS ncm_xml,
                   BOOL_OR(
                       xi.ncm IS NOT NULL AND cat.cod_ncm IS NOT NULL
                       AND regexp_replace(xi.ncm, '[^0-9]', '', 'g') <> regexp_replace(cat.cod_ncm, '[^0-9]', '', 'g')
                   )::int AS ncm_divergente,
                   BOOL_OR(
                       xi.aliquota_icms IS NOT NULL AND cat.aliq_icms IS NOT NULL
                       AND ABS(xi.aliquota_icms - cat.aliq_icms) > 0.01
                   )::int AS aliquota_divergente,
                   BOOL_OR(cat.cod_item IS NOT NULL)::int AS tem_catalogo,
                   MAX(cat.cod_ncm) AS cat_ncm
            FROM xml_notas_itens xi
            JOIN xml_notas xn ON xn.id = xi.xml_nota_id AND xn.user_id = :uid{$xmlWhere}
            LEFT JOIN (
                SELECT DISTINCT ON (cod_item) cod_item, cod_ncm, aliq_icms
                FROM efd_catalogo_itens
                WHERE user_id = :uid
                ORDER BY cod_item, id DESC
            ) cat ON cat.cod_item = xi.codigo_item
            WHERE xi.user_id = :uid
            GROUP BY xi.codigo_item
            ORDER BY xi.codigo_item";

        $mapa = [];
        foreach (DB::select($sql, $bind) as $r) {
            $mapa[$r->codigo_item] = [
                'descricao' => $r->descricao,
                'ncm_xml' => $r->ncm_xml,
                'ncm_divergente' => (bool) (int) $r->ncm_divergente,
                'aliquota_divergente' => (bool) (int) $r->aliquota_divergente,
                'tem_catalogo' => (bool) (int) $r->tem_catalogo,
                'cat_ncm' => $r->cat_ncm,
            ];
        }

        return $mapa;
    }

    /** 'efd,xml' / 'xml,efd' → 'ambas'; senão a fonte única. */
    private function normalizarFontes(?string $raw): string
    {
        $set = array_filter(explode(',', (string) $raw));

        return count($set) > 1 ? 'ambas' : ($set[array_key_first($set)] ?? 'efd');
    }
}
