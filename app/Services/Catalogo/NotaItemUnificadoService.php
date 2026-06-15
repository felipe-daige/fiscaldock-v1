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
              AND xn.chave_acesso NOT IN (SELECT chave_acesso FROM efd_notas WHERE user_id = :uid){$xmlWhere}";

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
                   string_agg(DISTINCT m.fonte, ',') AS fontes_raw
            FROM movimento m
            GROUP BY m.codigo_item
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
            'ncm' => $r->ncm_item,
            'fontes' => $this->normalizarFontes($r->fontes_raw),
        ]);
    }

    /** 'efd,xml' / 'xml,efd' → 'ambas'; senão a fonte única. */
    private function normalizarFontes(?string $raw): string
    {
        $set = array_filter(explode(',', (string) $raw));

        return count($set) > 1 ? 'ambas' : ($set[array_key_first($set)] ?? 'efd');
    }
}
