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
            'path' => $paginatorPath ?? url('/app/notas-fiscais'),
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
            $partDoc = $nota->dest_cnpj_formatado;
            $partId = $nota->dest_participante_id;
        } else {
            $partNome = $nota->emit_razao_social;
            $partDoc = $nota->emit_cnpj_formatado;
            $partId = $nota->emit_participante_id;
        }

        return [
            'id' => $nota->id,
            'origem' => 'xml',
            'chave_acesso' => $nota->nfe_id,
            'numero' => $nota->numero_nota,
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
        $base = $this->queryEfdBase($userId, $filtros);

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
        $base = $this->queryEfdBase($userId, $filtros);
        $noteIds = (clone $base)->select('id');

        $result = DB::table('efd_notas_itens')
            ->join('efd_notas', 'efd_notas.id', '=', 'efd_notas_itens.efd_nota_id')
            ->whereIn('efd_notas_itens.efd_nota_id', $noteIds)
            ->selectRaw('
                efd_notas.tipo_operacao,
                SUM(COALESCE(efd_notas_itens.valor_icms, 0)) as icms,
                SUM(COALESCE(efd_notas_itens.valor_pis, 0)) as pis,
                SUM(COALESCE(efd_notas_itens.valor_cofins, 0)) as cofins
            ')
            ->groupBy('efd_notas.tipo_operacao')
            ->get()
            ->keyBy('tipo_operacao');

        $entrada = $result->get('entrada');
        $saida = $result->get('saida');

        return [
            'icms' => ['credito' => (float) ($entrada->icms ?? 0), 'debito' => (float) ($saida->icms ?? 0)],
            'pis' => ['credito' => (float) ($entrada->pis ?? 0), 'debito' => (float) ($saida->pis ?? 0)],
            'cofins' => ['credito' => (float) ($entrada->cofins ?? 0), 'debito' => (float) ($saida->cofins ?? 0)],
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
        $query = EfdNota::where('user_id', $userId);

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

        if (! empty($filtros['busca'])) {
            $q = $filtros['busca'];
            $query->where(function ($sub) use ($q) {
                $sub->where('chave_acesso', 'ilike', "%{$q}%")
                    ->orWhere('numero', $q);
            });
        }

        return $query;
    }

    private function queryEfd(int $userId, array $filtros)
    {
        return $this->queryEfdBase($userId, $filtros)
            ->with(['participante', 'cliente'])
            ->orderBy('data_emissao', 'desc');
    }

    private function queryXmlBase(int $userId, array $filtros)
    {
        $query = XmlNota::doUsuario($userId);

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
                $sub->where('nfe_id', 'ilike', "%{$q}%")
                    ->orWhere('numero_nota', is_numeric($q) ? (int) $q : 0);
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
