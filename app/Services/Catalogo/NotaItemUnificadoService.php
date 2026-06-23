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
     * @param  array{periodo_de?:string,periodo_ate?:string,cliente_id?:int,fonte?:string,cfops?:list<string>,csts?:list<string>}  $filtros
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
        if (($cfopPh = $this->placeholders($filtros['cfops'] ?? [], 'cfop', $bind)) !== '') {
            $efdWhere .= " AND ni.cfop::text IN ({$cfopPh})";
            $xmlWhere .= " AND xi.cfop::text IN ({$cfopPh})";
        }
        if (($cstPh = $this->placeholders($filtros['csts'] ?? [], 'cst', $bind)) !== '') {
            $efdWhere .= " AND ni.cst_icms IN ({$cstPh})";
            $xmlWhere .= " AND xi.cst_icms IN ({$cstPh})";
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

        $importacoes = $this->importacoesPorItem($userId, $filtros);

        return collect(DB::select($sql, $bind))->map(fn ($r) => [
            'codigo_item' => $r->codigo_item,
            // item C170 de saída costuma vir sem descrição → cai pra descrição do catálogo (0200)
            'descricao' => $r->descricao ?: $r->cat_descr,
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
            'importacoes' => $importacoes[$r->codigo_item] ?? [],
        ]);
    }

    /**
     * Importação(ões) de origem por codigo_item (para link ao documento de origem na tabela de itens).
     * Mesma dedup EFD×XML por chave de itensAgregados; respeita os filtros cliente/período/fonte.
     *
     * @param  array{periodo_de?:string,periodo_ate?:string,cliente_id?:int,fonte?:string,cfops?:list<string>,csts?:list<string>}  $filtros
     * @return array<string, array<int, array{fonte:string,id:int,label:string}>>
     */
    public function importacoesPorItem(int $userId, array $filtros = []): array
    {
        $bind = ['uid' => $userId];
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
        if (($cfopPh = $this->placeholders($filtros['cfops'] ?? [], 'cfop', $bind)) !== '') {
            $efdWhere .= " AND ni.cfop::text IN ({$cfopPh})";
            $xmlWhere .= " AND xi.cfop::text IN ({$cfopPh})";
        }
        if (($cstPh = $this->placeholders($filtros['csts'] ?? [], 'cst', $bind)) !== '') {
            $efdWhere .= " AND ni.cst_icms IN ({$cstPh})";
            $xmlWhere .= " AND xi.cst_icms IN ({$cstPh})";
        }

        $efdSel = "
            SELECT DISTINCT ni.codigo_item AS cod, 'efd' AS fonte, n.importacao_id AS imp_id,
                   trim(coalesce(ei.tipo_efd, 'EFD') || ' ' || coalesce(to_char(ei.periodo_inicio, 'MM/YYYY'), '')) AS label
            FROM efd_notas_itens ni
            JOIN efd_notas n ON n.id = ni.efd_nota_id AND n.cancelada = false
            LEFT JOIN efd_importacoes ei ON ei.id = n.importacao_id
            WHERE ni.user_id = :uid AND n.importacao_id IS NOT NULL{$efdWhere}";

        $xmlSel = "
            SELECT DISTINCT xi.codigo_item AS cod, 'xml' AS fonte, xn.importacao_xml_id AS imp_id,
                   coalesce(NULLIF(xim.filename, ''), 'XML #'||xim.id::text) AS label
            FROM xml_notas_itens xi
            JOIN xml_notas xn ON xn.id = xi.xml_nota_id
            LEFT JOIN xml_importacoes xim ON xim.id = xn.importacao_xml_id
            WHERE xi.user_id = :uid AND xn.importacao_xml_id IS NOT NULL
              AND NOT EXISTS (SELECT 1 FROM efd_notas en WHERE en.user_id = :uid AND en.chave_acesso = xn.chave_acesso)
              {$xmlWhere}";

        $fonte = $filtros['fonte'] ?? 'ambas';
        $src = match ($fonte) {
            'efd' => $efdSel,
            'xml' => $xmlSel,
            default => $efdSel.' UNION '.$xmlSel,
        };

        $mapa = [];
        foreach (DB::select("SELECT cod, fonte, imp_id, label FROM ({$src}) s ORDER BY cod, fonte, imp_id", $bind) as $r) {
            $mapa[$r->cod][] = ['fonte' => $r->fonte, 'id' => (int) $r->imp_id, 'label' => $r->label];
        }

        return $mapa;
    }

    /**
     * Divergência do que foi DOCUMENTADO (item XML) vs o CADASTRADO (catálogo 0200), por codigo_item.
     *
     * Scan NÃO-deduplicado de xml_notas_itens — ao contrário de itensAgregados (que dedup EFD×XML por
     * chave e descartaria os itens XML cuja chave também está no EFD, justamente o caso do acervo real).
     * Base = versão atual do catálogo. Comparação tolerante a máscara (regexp_replace [^0-9]).
     *
     * @param  array{periodo_de?:string,periodo_ate?:string,cliente_id?:int}  $filtros
     * @return array<string, array{descricao:?string,ncm_xml:?string,ncm_divergente:bool,aliquota_divergente:bool,tem_catalogo:bool,cat_ncm:?string,importacoes:?string}>
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
                   MAX(cat.cod_ncm) AS cat_ncm,
                   string_agg(DISTINCT coalesce(NULLIF(xim.filename, ''), 'XML #'||xim.id::text), ' · ')
                       FILTER (WHERE xim.id IS NOT NULL) AS importacoes
            FROM xml_notas_itens xi
            JOIN xml_notas xn ON xn.id = xi.xml_nota_id AND xn.user_id = :uid{$xmlWhere}
            LEFT JOIN xml_importacoes xim ON xim.id = xn.importacao_xml_id
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
                'importacoes' => $r->importacoes,
            ];
        }

        return $mapa;
    }

    /**
     * Itens movimentados em nota SEM cadastro no catálogo 0200 (EFD + XML, dedup EFD×XML por chave),
     * com a referência da(s) importação(ões) de origem. Alimenta o painel acionável "Sem catálogo".
     *
     * @param  array{periodo_de?:string,periodo_ate?:string,cliente_id?:int}  $filtros
     * @return Collection<int, array{codigo_item:string,descricao:?string,fontes:string,importacoes:?string,ocorrencias:int}>
     */
    public function itensSemCatalogo(int $userId, array $filtros = []): Collection
    {
        $bind = ['uid' => $userId];
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

        $sql = "
            WITH mov AS (
                SELECT ni.codigo_item, ni.descricao,
                       trim(coalesce(ei.tipo_efd, 'EFD') || ' ' || coalesce(to_char(ei.periodo_inicio, 'MM/YYYY'), '')) AS imp_label,
                       'efd' AS fonte
                FROM efd_notas_itens ni
                JOIN efd_notas n ON n.id = ni.efd_nota_id AND n.cancelada = false
                LEFT JOIN efd_importacoes ei ON ei.id = n.importacao_id
                WHERE ni.user_id = :uid{$efdWhere}
                UNION ALL
                SELECT xi.codigo_item, xi.descricao,
                       coalesce(NULLIF(xim.filename, ''), 'XML #'||xim.id::text) AS imp_label,
                       'xml' AS fonte
                FROM xml_notas_itens xi
                JOIN xml_notas xn ON xn.id = xi.xml_nota_id
                LEFT JOIN xml_importacoes xim ON xim.id = xn.importacao_xml_id
                WHERE xi.user_id = :uid
                  AND NOT EXISTS (SELECT 1 FROM efd_notas en WHERE en.user_id = :uid AND en.chave_acesso = xn.chave_acesso)
                  {$xmlWhere}
            )
            SELECT m.codigo_item,
                   MAX(m.descricao) AS descricao,
                   string_agg(DISTINCT m.fonte, ',') AS fontes_raw,
                   string_agg(DISTINCT m.imp_label, ' · ') FILTER (WHERE m.imp_label IS NOT NULL AND m.imp_label <> '') AS importacoes,
                   COUNT(*) AS ocorrencias
            FROM mov m
            LEFT JOIN (
                SELECT DISTINCT ON (cod_item) cod_item
                FROM efd_catalogo_itens
                WHERE user_id = :uid
                ORDER BY cod_item, id DESC
            ) cat ON cat.cod_item = m.codigo_item
            WHERE cat.cod_item IS NULL
            GROUP BY m.codigo_item
            ORDER BY m.codigo_item";

        return collect(DB::select($sql, $bind))->map(fn ($r) => [
            'codigo_item' => $r->codigo_item,
            'descricao' => $r->descricao,
            'fontes' => $this->normalizarFontes($r->fontes_raw),
            'importacoes' => $r->importacoes,
            'ocorrencias' => (int) $r->ocorrencias,
        ]);
    }

    /**
     * CFOPs e CSTs distintos no universo de movimento do usuário — opções para os filtros da UI.
     * Respeita cliente/período/fonte (mas NÃO cfop/cst, para o conjunto de opções ficar completo).
     *
     * @param  array{periodo_de?:string,periodo_ate?:string,cliente_id?:int,fonte?:string}  $filtros
     * @return array{cfops: list<string>, csts: list<string>}
     */
    public function facetas(int $userId, array $filtros = []): array
    {
        $bind = ['uid' => $userId];
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

        $efdSel = "
            SELECT DISTINCT ni.cfop::text AS cfop, ni.cst_icms AS cst
            FROM efd_notas_itens ni
            JOIN efd_notas n ON n.id = ni.efd_nota_id AND n.cancelada = false
            WHERE ni.user_id = :uid{$efdWhere}";

        $xmlSel = "
            SELECT DISTINCT xi.cfop::text AS cfop, xi.cst_icms AS cst
            FROM xml_notas_itens xi
            JOIN xml_notas xn ON xn.id = xi.xml_nota_id
            WHERE xi.user_id = :uid
              AND NOT EXISTS (SELECT 1 FROM efd_notas en WHERE en.user_id = :uid AND en.chave_acesso = xn.chave_acesso)
              {$xmlWhere}";

        $fonte = $filtros['fonte'] ?? 'ambas';
        $src = match ($fonte) {
            'efd' => $efdSel,
            'xml' => $xmlSel,
            default => $efdSel.' UNION '.$xmlSel,
        };

        $rows = DB::select("SELECT DISTINCT cfop, cst FROM ({$src}) s", $bind);

        $pick = fn (string $field) => collect($rows)->pluck($field)
            ->map(fn ($v) => $v !== null ? trim((string) $v) : '')
            ->filter(fn ($v) => $v !== '')
            ->unique()->sort()->values()->all();

        return ['cfops' => $pick('cfop'), 'csts' => $pick('cst')];
    }

    /**
     * Monta a lista de placeholders nomeados para um IN(...) e registra os valores (saneados,
     * sem repetição) em $bind. Retorna '' quando não há nada para filtrar.
     *
     * @param  array<int,mixed>  $values
     */
    private function placeholders(array $values, string $prefix, array &$bind): string
    {
        $values = array_values(array_unique(array_filter(
            array_map(fn ($v) => trim((string) $v), $values),
            fn ($v) => $v !== '',
        )));
        if ($values === []) {
            return '';
        }

        $ph = [];
        foreach ($values as $i => $v) {
            $bind[$prefix.$i] = $v;
            $ph[] = ':'.$prefix.$i;
        }

        return implode(',', $ph);
    }

    /** 'efd,xml' / 'xml,efd' → 'ambas'; senão a fonte única. */
    private function normalizarFontes(?string $raw): string
    {
        $set = array_filter(explode(',', (string) $raw));

        return count($set) > 1 ? 'ambas' : ($set[array_key_first($set)] ?? 'efd');
    }
}
