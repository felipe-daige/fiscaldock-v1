<?php

namespace App\Services;

use App\Helpers\CfopHelper;
use App\Models\EfdNota;
use App\Models\XmlNota;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class NotaFiscalService
{
    private static array $cfopDevolucaoRanges = [
        [1200, 1209], [1410, 1414],
        [2200, 2209], [2410, 2414],
        [3200, 3211],
        [5200, 5210], [5410, 5415],
        [6200, 6210], [6410, 6415],
        [7200, 7211],
    ];

    public function listarUnificadas(int $userId, array $filtros, int $perPage = 25, int $page = 1, ?string $paginatorPath = null): LengthAwarePaginator
    {
        $origem = $filtros['origem'] ?? null;

        $efdNotas = collect();
        $xmlNotas = collect();

        if ($origem !== 'xml') {
            $efdNotas = $this->queryEfd($userId, $filtros)->get()->map(fn ($n) => $this->normalizarEfd($n));
        }

        if ($origem !== 'efd') {
            $xmlNotas = $this->queryXml($userId, $filtros)->get()->map(fn ($n) => $this->normalizarXml($n));
        }

        $merged = $efdNotas->concat($xmlNotas)
            ->sortByDesc('data_emissao')
            ->values();

        $total = $merged->count();
        $items = $merged->slice(($page - 1) * $perPage, $perPage)->values();

        return new LengthAwarePaginator($items, $total, $perPage, $page, [
            'path' => $paginatorPath ?? url('/app/notas'),
            'query' => array_filter($filtros),
        ]);
    }

    public function calcularKpis(int $userId, array $filtros): array
    {
        $origem = $filtros['origem'] ?? null;

        $operacoes = [
            'entradas' => ['quantidade' => 0, 'valor' => 0.0],
            'saidas' => ['quantidade' => 0, 'valor' => 0.0],
            'devolucoes' => ['quantidade' => 0, 'valor' => 0.0],
            'total' => ['quantidade' => 0, 'valor' => 0.0],
        ];

        $tributos = [
            'icms' => ['credito' => 0.0, 'debito' => 0.0],
            'pis' => ['credito' => 0.0, 'debito' => 0.0],
            'cofins' => ['credito' => 0.0, 'debito' => 0.0],
        ];

        $topCfops = [];

        if ($origem !== 'xml') {
            $efdOps = $this->calcularOperacoesEfd($userId, $filtros);
            $efdTrib = $this->calcularTributosEfd($userId, $filtros);
            $topCfops = $this->topCfops($userId, $filtros);

            $this->somarOperacoes($operacoes, $efdOps);
            $this->somarTributos($tributos, $efdTrib);
        }

        if ($origem !== 'efd') {
            $xmlData = $this->calcularXmlAgregado($userId, $filtros);

            $this->somarOperacoes($operacoes, $xmlData['operacoes']);
            $this->somarTributos($tributos, $xmlData['tributos']);
        }

        // Total = entradas + saídas (devoluções já estão contidas em entradas/saídas)
        $operacoes['total']['quantidade'] = $operacoes['entradas']['quantidade'] + $operacoes['saidas']['quantidade'];
        $operacoes['total']['valor'] = $operacoes['entradas']['valor'] + $operacoes['saidas']['valor'];

        return [
            'operacoes' => $operacoes,
            'tributos' => $tributos,
            'top_cfops' => $topCfops,
        ];
    }

    /**
     * Query base do histórico de consultas DF-e (snapshots SEFAZ) — union NF-e + CT-e.
     *
     * Cada linha representa a última verificação de um documento (UPSERT por
     * `(user_id, chave_acesso)`). Expõe colunas estáveis usadas pelos filtros
     * do histórico, do detalhe de lote e do alimentador de status: chave_acesso,
     * numero, serie, modelo, tipo_documento, status, valor_total, data_emissao,
     * emit_*, dest_*, tomador_*, cliente_nome, consulta_lote_id, fluxo_origem,
     * consultado_em, created_at, id.
     */
    public function consultaDfeHistoricoQuery(int $userId): \Illuminate\Database\Query\Builder
    {
        $nfe = DB::table('nfe_consultas as consulta')
            ->leftJoin('clientes as cliente', 'cliente.id', '=', 'consulta.cliente_id')
            ->selectRaw("
                consulta.id,
                consulta.consulta_lote_id,
                CASE WHEN consulta.consulta_lote_id IS NULL THEN 'avulsa' ELSE 'lote' END as fluxo_origem,
                consulta.chave_acesso,
                UPPER(COALESCE(consulta.tipo_documento, 'NFE')) as tipo_documento,
                COALESCE(consulta.modelo, '55') as modelo,
                consulta.numero,
                consulta.serie,
                consulta.status,
                consulta.valor_total,
                consulta.data_emissao,
                consulta.emit_nome,
                consulta.emit_cnpj,
                consulta.dest_nome,
                consulta.dest_cnpj,
                NULL::varchar as tomador_nome,
                NULL::varchar as tomador_cnpj,
                cliente.razao_social as cliente_nome,
                consulta.consultado_em,
                consulta.created_at
            ")
            ->where('consulta.user_id', $userId);

        $cte = DB::table('cte_consultas as consulta')
            ->leftJoin('clientes as cliente', 'cliente.id', '=', 'consulta.cliente_id')
            ->selectRaw("
                consulta.id,
                consulta.consulta_lote_id,
                CASE WHEN consulta.consulta_lote_id IS NULL THEN 'avulsa' ELSE 'lote' END as fluxo_origem,
                consulta.chave_acesso,
                UPPER(COALESCE(consulta.tipo_documento, 'CTE')) as tipo_documento,
                COALESCE(consulta.modelo, '57') as modelo,
                consulta.numero,
                consulta.serie,
                consulta.status,
                consulta.valor_prestacao as valor_total,
                consulta.data_emissao,
                consulta.emit_nome,
                consulta.emit_cnpj,
                consulta.dest_nome,
                consulta.dest_cnpj,
                consulta.tomador_nome,
                consulta.tomador_cnpj,
                cliente.razao_social as cliente_nome,
                consulta.consultado_em,
                consulta.created_at
            ")
            ->where('consulta.user_id', $userId);

        return DB::query()->fromSub($nfe->unionAll($cte), 'consultas');
    }

    public function normalizarEfd(EfdNota $nota): array
    {
        return [
            'id' => $nota->id,
            'origem' => 'efd',
            'chave_acesso' => $nota->chave_acesso,
            'numero' => $nota->numero,
            'serie' => $nota->serie,
            'data_emissao' => $nota->data_emissao,
            'tipo_operacao' => $nota->tipo_operacao,
            'modelo_label' => $nota->modelo_doc_formatado,
            'valor_total' => (float) $nota->valor_total,
            'participante_id' => $nota->participante_id,
            'participante_nome' => $nota->participante?->razao_social,
            'participante_doc' => $nota->participante?->cnpj_formatado,
            'cliente_id' => $nota->cliente_id,
            'cliente_nome' => $nota->cliente?->razao_social,
        ];
    }

    public function normalizarXml(XmlNota $nota): array
    {
        $modeloLabel = match (strtoupper($nota->tipo_documento ?? '')) {
            'NFE' => 'NF-e',
            'CTE' => 'CT-e',
            'NFSE' => 'NFS-e',
            default => strtoupper($nota->tipo_documento ?? 'XML'),
        };

        $tipoOperacao = $nota->tipo_nota === XmlNota::TIPO_ENTRADA ? 'entrada' : 'saida';

        // Contraparte: se emitente é empresa própria, mostrar destinatário; caso contrário, emitente
        if ($nota->relationLoaded('emitCliente') && $nota->emitCliente?->is_empresa_propria) {
            $partNome = $nota->dest_razao_social;
            $partDoc = $nota->dest_documento_formatado;
            $partId = $nota->dest_participante_id;
        } else {
            $partNome = $nota->emit_razao_social;
            $partDoc = $nota->emit_documento_formatado;
            $partId = $nota->emit_participante_id;
        }

        return [
            'id' => $nota->id,
            'origem' => 'xml',
            'chave_acesso' => $nota->chave_acesso,
            'numero' => $nota->numero_documento,
            'serie' => $nota->serie,
            'data_emissao' => $nota->data_emissao,
            'tipo_operacao' => $tipoOperacao,
            'modelo_label' => $modeloLabel,
            'valor_total' => (float) $nota->valor_total,
            'participante_id' => $partId,
            'participante_nome' => $partNome,
            'participante_doc' => $partDoc,
            'cliente_id' => $nota->cliente_id,
            'cliente_nome' => $nota->cliente?->razao_social,
        ];
    }

    // ─── KPI helpers ─────────────────────────────────────────

    private function calcularOperacoesEfd(int $userId, array $filtros): array
    {
        // Valor-de-nota → deduplica origem (P1). Sem isso as saídas dobram (medido: 36,2mi → 25,69mi).
        $base = $this->queryEfdBase($userId, $filtros);
        $this->aplicarDedupOrigem($base, $userId);

        $porTipo = (clone $base)
            ->selectRaw('tipo_operacao, COUNT(*) as qtd, SUM(valor_total) as valor')
            ->groupBy('tipo_operacao')
            ->get()
            ->keyBy('tipo_operacao');

        $entradas = $porTipo->get('entrada');
        $saidas = $porTipo->get('saida');

        // Devoluções via CFOP
        $devolucaoWhere = $this->buildCfopDevolucaoWhere();
        $devolucoes = (clone $base)
            ->whereExists(function ($sub) use ($devolucaoWhere) {
                $sub->select(DB::raw(1))
                    ->from('efd_notas_itens')
                    ->whereColumn('efd_notas_itens.efd_nota_id', 'efd_notas.id')
                    ->whereRaw($devolucaoWhere);
            })
            ->selectRaw('COUNT(*) as qtd, SUM(valor_total) as valor')
            ->first();

        return [
            'entradas' => ['quantidade' => (int) ($entradas->qtd ?? 0), 'valor' => (float) ($entradas->valor ?? 0)],
            'saidas' => ['quantidade' => (int) ($saidas->qtd ?? 0), 'valor' => (float) ($saidas->valor ?? 0)],
            'devolucoes' => ['quantidade' => (int) ($devolucoes->qtd ?? 0), 'valor' => (float) ($devolucoes->valor ?? 0)],
        ];
    }

    private function calcularTributosEfd(int $userId, array $filtros): array
    {
        // Leitura de tributo NÃO deduplica origem: ICMS vive no C190 (origem fiscal),
        // PIS/COFINS nos itens da origem 'contribuicoes' — precisa das duas origens.
        // Ler ICMS dos itens (P2) ou somar PIS/COFINS de ambas as origens (P8) infla o
        // crédito (medido: PIS crédito 34.355 → 168, COFINS 158.919 → 777).
        $noteIds = (clone $this->queryEfdBase($userId, $filtros))->select('id');

        // ICMS do C190 (efd_notas_consolidados) — só existe na escrituração fiscal.
        $icms = DB::table('efd_notas_consolidados as c')
            ->join('efd_notas as n', 'n.id', '=', 'c.efd_nota_id')
            ->whereIn('c.efd_nota_id', $noteIds)
            ->selectRaw('n.tipo_operacao, SUM(COALESCE(c.valor_icms, 0)) as icms')
            ->groupBy('n.tipo_operacao')
            ->get()
            ->keyBy('tipo_operacao');

        // PIS/COFINS apenas dos itens da origem 'contribuicoes'.
        $pc = DB::table('efd_notas_itens as i')
            ->join('efd_notas as n', 'n.id', '=', 'i.efd_nota_id')
            ->whereIn('i.efd_nota_id', $noteIds)
            ->where('n.origem_arquivo', 'contribuicoes')
            ->selectRaw('n.tipo_operacao, SUM(COALESCE(i.valor_pis, 0)) as pis, SUM(COALESCE(i.valor_cofins, 0)) as cofins')
            ->groupBy('n.tipo_operacao')
            ->get()
            ->keyBy('tipo_operacao');

        return [
            'icms' => ['credito' => (float) ($icms->get('entrada')->icms ?? 0), 'debito' => (float) ($icms->get('saida')->icms ?? 0)],
            'pis' => ['credito' => (float) ($pc->get('entrada')->pis ?? 0), 'debito' => (float) ($pc->get('saida')->pis ?? 0)],
            'cofins' => ['credito' => (float) ($pc->get('entrada')->cofins ?? 0), 'debito' => (float) ($pc->get('saida')->cofins ?? 0)],
        ];
    }

    private function calcularXmlAgregado(int $userId, array $filtros): array
    {
        $base = $this->queryXmlBase($userId, $filtros);

        $rows = (clone $base)
            ->selectRaw('
                tipo_nota, finalidade,
                COUNT(*) as qtd, SUM(valor_total) as valor,
                SUM(COALESCE(icms_valor, 0)) as icms,
                SUM(COALESCE(pis_valor, 0)) as pis,
                SUM(COALESCE(cofins_valor, 0)) as cofins
            ')
            ->groupBy('tipo_nota', 'finalidade')
            ->get();

        $operacoes = [
            'entradas' => ['quantidade' => 0, 'valor' => 0.0],
            'saidas' => ['quantidade' => 0, 'valor' => 0.0],
            'devolucoes' => ['quantidade' => 0, 'valor' => 0.0],
        ];

        $tributos = [
            'icms' => ['credito' => 0.0, 'debito' => 0.0],
            'pis' => ['credito' => 0.0, 'debito' => 0.0],
            'cofins' => ['credito' => 0.0, 'debito' => 0.0],
        ];

        foreach ($rows as $row) {
            $isDevolucao = (int) $row->finalidade === XmlNota::FINALIDADE_DEVOLUCAO;
            $isEntrada = (int) $row->tipo_nota === XmlNota::TIPO_ENTRADA;
            $tipo = $isEntrada ? 'entradas' : 'saidas';
            $tribTipo = $isEntrada ? 'credito' : 'debito';

            $operacoes[$tipo]['quantidade'] += (int) $row->qtd;
            $operacoes[$tipo]['valor'] += (float) $row->valor;

            if ($isDevolucao) {
                $operacoes['devolucoes']['quantidade'] += (int) $row->qtd;
                $operacoes['devolucoes']['valor'] += (float) $row->valor;
            }

            $tributos['icms'][$tribTipo] += (float) $row->icms;
            $tributos['pis'][$tribTipo] += (float) $row->pis;
            $tributos['cofins'][$tribTipo] += (float) $row->cofins;
        }

        return ['operacoes' => $operacoes, 'tributos' => $tributos];
    }

    private function topCfops(int $userId, array $filtros, int $limit = 5): array
    {
        $base = $this->queryEfdBase($userId, $filtros);
        $noteIds = (clone $base)->select('id');

        $baseItens = DB::table('efd_notas_itens')
            ->whereIn('efd_nota_id', $noteIds)
            ->whereNotNull('cfop');

        $totalItens = (clone $baseItens)->count();

        $rows = (clone $baseItens)
            ->selectRaw("cfop, COUNT(*) as quantidade, SUM(CASE WHEN valor_total IS NOT NULL AND valor_total::text != '' THEN valor_total ELSE 0 END) as valor_total")
            ->groupBy('cfop')
            ->orderByDesc('quantidade')
            ->limit($limit)
            ->get();

        return $rows->map(fn ($r) => [
            'cfop' => (int) $r->cfop,
            'descricao' => CfopHelper::descricao((int) $r->cfop),
            'quantidade' => (int) $r->quantidade,
            'valor_total' => (float) $r->valor_total,
            'tipo' => in_array(substr((string) $r->cfop, 0, 1), ['1', '2', '3']) ? 'entrada' : 'saida',
            'percentual' => $totalItens > 0 ? round(((int) $r->quantidade / $totalItens) * 100, 1) : 0,
        ])->toArray();
    }

    private function somarOperacoes(array &$target, array $source): void
    {
        foreach (['entradas', 'saidas', 'devolucoes'] as $tipo) {
            $target[$tipo]['quantidade'] += $source[$tipo]['quantidade'] ?? 0;
            $target[$tipo]['valor'] += $source[$tipo]['valor'] ?? 0;
        }
    }

    private function somarTributos(array &$target, array $source): void
    {
        foreach (['icms', 'pis', 'cofins'] as $tributo) {
            $target[$tributo]['credito'] += $source[$tributo]['credito'] ?? 0;
            $target[$tributo]['debito'] += $source[$tributo]['debito'] ?? 0;
        }
    }

    private function buildCfopDevolucaoWhere(): string
    {
        $conditions = array_map(
            fn ($range) => "efd_notas_itens.cfop BETWEEN {$range[0]} AND {$range[1]}",
            self::$cfopDevolucaoRanges
        );

        return '('.implode(' OR ', $conditions).')';
    }

    // ─── Query builders ──────────────────────────────────────

    private function queryEfdBase(int $userId, array $filtros)
    {
        // P4: canceladas (cod_sit 02/03/04/05) não entram em listagem, KPI nem tributo.
        $query = EfdNota::where('user_id', $userId)
            ->where('cancelada', false);

        if (! empty($filtros['data_inicio']) && ! empty($filtros['data_fim'])) {
            $query->periodo($filtros['data_inicio'], $filtros['data_fim']);
        } elseif (! empty($filtros['data_inicio'])) {
            $query->where('data_emissao', '>=', $filtros['data_inicio']);
        } elseif (! empty($filtros['data_fim'])) {
            $query->where('data_emissao', '<=', $filtros['data_fim']);
        }

        if (! empty($filtros['tipo_operacao'])) {
            if ($filtros['tipo_operacao'] === 'entrada') {
                $query->entradas();
            } elseif ($filtros['tipo_operacao'] === 'saida') {
                $query->saidas();
            }
        }

        if (! empty($filtros['modelo'])) {
            $modeloMap = [
                'nfe' => '55',
                'cte' => '57',
                'nfce' => '65',
                'nfse' => '00',
            ];
            if (isset($modeloMap[$filtros['modelo']])) {
                $query->where('modelo', $modeloMap[$filtros['modelo']]);
            }
        }

        if (! empty($filtros['cliente_id'])) {
            $query->where('cliente_id', $filtros['cliente_id']);
        }

        if (! empty($filtros['participante_id'])) {
            $query->where('participante_id', $filtros['participante_id']);
        }

        if (! empty($filtros['importacao_id'])) {
            $query->where('importacao_id', $filtros['importacao_id']);
        }

        if (! empty($filtros['busca'])) {
            $q = $filtros['busca'];
            $query->where(function ($sub) use ($q) {
                $sub->where('chave_acesso', 'ilike', "%{$q}%")
                    ->orWhere('numero', $q);
            });
        }

        return $query;
    }

    /**
     * Dedup de origem (P1) — mesma regra canônica de EfdAgregadorService::notasDedup.
     * A MESMA NF-e é escriturada em 'fiscal' e 'contribuicoes'; mantém a fiscal e
     * só inclui documentos que SÓ existem no PIS/COFINS (NFS-e, NF-e órfãs) por não
     * terem gêmea de mesma chave na origem 'fiscal'. Aplicar APENAS em métricas de
     * VALOR-de-nota (listagem, operações) — NUNCA na leitura de tributo, que precisa
     * das duas origens (ICMS no C190 fiscal, PIS/COFINS nos itens contribuicoes).
     */
    private function aplicarDedupOrigem($query, int $userId): void
    {
        $query->where(function ($q) use ($userId) {
            $q->where('origem_arquivo', 'fiscal')
                ->orWhereRaw('NOT EXISTS (SELECT 1 FROM efd_notas f WHERE f.user_id = ? AND f.origem_arquivo = ? AND f.chave_acesso IS NOT NULL AND f.chave_acesso = efd_notas.chave_acesso)', [$userId, 'fiscal']);
        });
    }

    private function queryEfd(int $userId, array $filtros)
    {
        $query = $this->queryEfdBase($userId, $filtros);
        $this->aplicarDedupOrigem($query, $userId);

        return $query
            ->with(['participante', 'cliente'])
            ->orderBy('data_emissao', 'desc');
    }

    private function queryXmlBase(int $userId, array $filtros)
    {
        $query = XmlNota::doUsuario($userId);

        // O filtro de importação aponta para uma importação EFD; notas XML não
        // pertencem a ela, então são removidas da listagem/KPIs quando ativo.
        if (! empty($filtros['importacao_id'])) {
            $query->whereRaw('1 = 0');
        }

        if (! empty($filtros['data_inicio']) && ! empty($filtros['data_fim'])) {
            $query->noPeriodo($filtros['data_inicio'], $filtros['data_fim']);
        } elseif (! empty($filtros['data_inicio'])) {
            $query->where('data_emissao', '>=', $filtros['data_inicio']);
        } elseif (! empty($filtros['data_fim'])) {
            $query->where('data_emissao', '<=', $filtros['data_fim']);
        }

        if (! empty($filtros['tipo_operacao'])) {
            if ($filtros['tipo_operacao'] === 'entrada') {
                $query->entradas();
            } elseif ($filtros['tipo_operacao'] === 'saida') {
                $query->saidas();
            }
        }

        if (! empty($filtros['modelo'])) {
            $modeloMap = [
                'nfe' => 'NFE',
                'cte' => 'CTE',
                'nfse' => 'NFSE',
            ];
            if (isset($modeloMap[$filtros['modelo']])) {
                $query->where('tipo_documento', $modeloMap[$filtros['modelo']]);
            } elseif ($filtros['modelo'] === 'nfce') {
                $query->whereRaw('1 = 0');
            }
        }

        if (! empty($filtros['cliente_id'])) {
            $query->where('cliente_id', $filtros['cliente_id']);
        }

        if (! empty($filtros['participante_id'])) {
            $id = $filtros['participante_id'];
            $query->where(function ($sub) use ($id) {
                $sub->where('emit_participante_id', $id)
                    ->orWhere('dest_participante_id', $id);
            });
        }

        if (! empty($filtros['busca'])) {
            $q = $filtros['busca'];
            $query->where(function ($sub) use ($q) {
                $sub->where('chave_acesso', 'ilike', "%{$q}%")
                    ->orWhere('numero_documento', is_numeric($q) ? (int) $q : 0);
            });
        }

        return $query;
    }

    private function queryXml(int $userId, array $filtros)
    {
        return $this->queryXmlBase($userId, $filtros)
            ->with(['emitCliente', 'destCliente', 'cliente'])
            ->orderBy('data_emissao', 'desc');
    }
}
