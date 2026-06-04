<?php

namespace App\Services;

use App\Models\XmlNota;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;

class BiService
{
    public function __construct(
        protected EfdAgregadorService $efd
    ) {}

    /**
     * Janela [inicio, fim] para gráficos mensais de EFD. Quando o período não é
     * informado, deriva de MIN/MAX(data_emissao) do próprio acervo — senão o
     * default fixo de 12 meses zera dados antigos (ex.: massa de 2024 vista em
     * 2026 cai fora da janela). Ver F1 / A11.
     */
    private function janelaEfd(int $userId, ?string $dataInicio, ?string $dataFim): array
    {
        $inicio = $dataInicio ? Carbon::parse($dataInicio) : null;
        $fim = $dataFim ? Carbon::parse($dataFim) : null;

        if (! $inicio || ! $fim) {
            $min = DB::table('efd_notas')->where('user_id', $userId)->min('data_emissao');
            $max = DB::table('efd_notas')->where('user_id', $userId)->max('data_emissao');
            $inicio ??= ($min ? Carbon::parse($min)->startOfMonth() : Carbon::now()->subMonths(11)->startOfMonth());
            $fim ??= ($max ? Carbon::parse($max) : Carbon::now());
        }

        return [$inicio, $fim];
    }

    /**
     * Faturamento por período (mensal).
     */
    public function getFaturamentoPorPeriodo(int $userId, ?string $dataInicio = null, ?string $dataFim = null, ?int $clienteId = null): array
    {
        $query = XmlNota::where('user_id', $userId)
            ->where('tipo_nota', XmlNota::TIPO_SAIDA)
            ->where('finalidade', '!=', XmlNota::FINALIDADE_DEVOLUCAO);

        if ($dataInicio) {
            $query->where('data_emissao', '>=', $dataInicio);
        }

        if ($dataFim) {
            $query->where('data_emissao', '<=', $dataFim);
        }

        if ($clienteId) {
            $query->where('cliente_id', $clienteId);
        }

        $xmlRows = $query->select(
            DB::raw("DATE_TRUNC('month', data_emissao) as mes"),
            DB::raw('SUM(valor_total) as faturamento'),
            DB::raw('COUNT(*) as qtd_notas')
        )
            ->groupBy(DB::raw("DATE_TRUNC('month', data_emissao)"))
            ->orderBy('mes')
            ->get()
            ->keyBy('mes');

        // EFD: faturamento de saída canônico (dedup origem P1, exclui cancelada P4,
        // inclui NFS-e serviços). Ver EfdAgregadorService / docs F1.
        $efdRows = collect($this->efd->faturamentoMensal($userId, 'saida', $dataInicio, $dataFim, $clienteId))
            ->mapWithKeys(fn ($r) => [(string) $r['mes'] => (object) ['faturamento' => $r['valor'], 'qtd_notas' => $r['qtd']]]);

        $keys = $xmlRows->keys()->merge($efdRows->keys())->unique()->sort()->values();

        return $keys->map(function ($mes) use ($xmlRows, $efdRows) {
            $xml = $xmlRows->get($mes);
            $efd = $efdRows->get((string) $mes);
            $faturamento = (float) ($xml->faturamento ?? 0) + (float) ($efd->faturamento ?? 0);
            $qtd = (int) ($xml->qtd_notas ?? 0) + (int) ($efd->qtd_notas ?? 0);

            return [
                'mes' => $mes,
                'mes_formatado' => $mes ? date('m/Y', strtotime($mes)) : null,
                'faturamento' => $faturamento,
                'qtd_notas' => $qtd,
            ];
        })->values()->toArray();
    }

    /**
     * Top clientes por valor de venda.
     */
    public function getTopClientes(int $userId, int $limit = 10, ?string $dataInicio = null, ?string $dataFim = null, ?int $clienteId = null): array
    {
        $query = XmlNota::where('user_id', $userId)
            ->where('tipo_nota', XmlNota::TIPO_SAIDA)
            ->where('finalidade', '!=', XmlNota::FINALIDADE_DEVOLUCAO);

        if ($dataInicio) {
            $query->where('data_emissao', '>=', $dataInicio);
        }

        if ($dataFim) {
            $query->where('data_emissao', '<=', $dataFim);
        }

        if ($clienteId) {
            $query->where('cliente_id', $clienteId);
        }

        $xmlRows = $query->select(
            'dest_documento',
            'dest_razao_social',
            DB::raw('SUM(valor_total) as total'),
            DB::raw('COUNT(*) as qtd_notas')
        )
            ->groupBy('dest_documento', 'dest_razao_social')
            ->orderByDesc('total')
            ->get()
            ->keyBy('dest_documento');

        $efdRows = $this->efd->notasDedup($userId, 'saida', $dataInicio, $dataFim, $clienteId)
            ->join('participantes as p', 'p.id', '=', 'n.participante_id')
            ->select([
                'p.documento as dest_documento',
                'p.razao_social as dest_razao_social',
                DB::raw('SUM(n.valor_total) as total'),
                DB::raw('COUNT(n.id) as qtd_notas'),
            ])
            ->groupBy('p.documento', 'p.razao_social')
            ->get()
            ->keyBy('dest_documento');

        $cnpjs = $xmlRows->keys()->merge($efdRows->keys())->unique();

        return $cnpjs->map(function ($cnpj) use ($xmlRows, $efdRows) {
            $xml = $xmlRows->get($cnpj);
            $efd = $efdRows->get($cnpj);
            $razaoSocial = $xml->dest_razao_social ?? $efd->dest_razao_social ?? null;

            return [
                'cnpj' => $cnpj,
                'razao_social' => $razaoSocial,
                'total' => (float) ($xml->total ?? 0) + (float) ($efd->total ?? 0),
                'qtd_notas' => (int) ($xml->qtd_notas ?? 0) + (int) ($efd->qtd_notas ?? 0),
            ];
        })
            ->sortByDesc('total')
            ->take($limit)
            ->values()
            ->toArray();
    }

    /**
     * Top fornecedores por valor de compra.
     */
    public function getTopFornecedores(int $userId, int $limit = 10, ?string $dataInicio = null, ?string $dataFim = null, ?int $clienteId = null): array
    {
        $query = XmlNota::where('user_id', $userId)
            ->where('tipo_nota', XmlNota::TIPO_ENTRADA)
            ->where('finalidade', '!=', XmlNota::FINALIDADE_DEVOLUCAO);

        if ($dataInicio) {
            $query->where('data_emissao', '>=', $dataInicio);
        }

        if ($dataFim) {
            $query->where('data_emissao', '<=', $dataFim);
        }

        if ($clienteId) {
            $query->where('cliente_id', $clienteId);
        }

        $xmlRows = $query->select(
            'emit_documento',
            'emit_razao_social',
            DB::raw('SUM(valor_total) as total'),
            DB::raw('COUNT(*) as qtd_notas')
        )
            ->groupBy('emit_documento', 'emit_razao_social')
            ->orderByDesc('total')
            ->get()
            ->keyBy('emit_documento');

        $efdRows = $this->efd->notasDedup($userId, 'entrada', $dataInicio, $dataFim, $clienteId)
            ->join('participantes as p', 'p.id', '=', 'n.participante_id')
            ->select([
                'p.documento as emit_documento',
                'p.razao_social as emit_razao_social',
                DB::raw('SUM(n.valor_total) as total'),
                DB::raw('COUNT(n.id) as qtd_notas'),
            ])
            ->groupBy('p.documento', 'p.razao_social')
            ->get()
            ->keyBy('emit_documento');

        $cnpjs = $xmlRows->keys()->merge($efdRows->keys())->unique();

        return $cnpjs->map(function ($cnpj) use ($xmlRows, $efdRows) {
            $xml = $xmlRows->get($cnpj);
            $efd = $efdRows->get($cnpj);
            $razaoSocial = $xml->emit_razao_social ?? $efd->emit_razao_social ?? null;

            return [
                'cnpj' => $cnpj,
                'razao_social' => $razaoSocial,
                'total' => (float) ($xml->total ?? 0) + (float) ($efd->total ?? 0),
                'qtd_notas' => (int) ($xml->qtd_notas ?? 0) + (int) ($efd->qtd_notas ?? 0),
            ];
        })
            ->sortByDesc('total')
            ->take($limit)
            ->values()
            ->toArray();
    }

    /**
     * Carga tributária por período.
     */
    public function getCargaTributaria(int $userId, ?string $dataInicio = null, ?string $dataFim = null, ?int $clienteId = null): array
    {
        $query = XmlNota::where('user_id', $userId);

        if ($dataInicio) {
            $query->where('data_emissao', '>=', $dataInicio);
        }

        if ($dataFim) {
            $query->where('data_emissao', '<=', $dataFim);
        }

        if ($clienteId) {
            $query->where('cliente_id', $clienteId);
        }

        $xmlRows = $query->select(
            DB::raw("DATE_TRUNC('month', data_emissao) as mes"),
            DB::raw('SUM(valor_total) as faturamento'),
            DB::raw('SUM(COALESCE(icms_valor, 0)) as icms'),
            DB::raw('SUM(COALESCE(icms_st_valor, 0)) as icms_st'),
            DB::raw('SUM(COALESCE(pis_valor, 0)) as pis'),
            DB::raw('SUM(COALESCE(cofins_valor, 0)) as cofins'),
            DB::raw('SUM(COALESCE(ipi_valor, 0)) as ipi'),
            DB::raw('SUM(COALESCE(icms_valor, 0) + COALESCE(icms_st_valor, 0) + COALESCE(pis_valor, 0) + COALESCE(cofins_valor, 0) + COALESCE(ipi_valor, 0)) as tributos_total')
        )
            ->groupBy(DB::raw("DATE_TRUNC('month', data_emissao)"))
            ->orderBy('mes')
            ->get()
            ->keyBy('mes');

        // EFD canônico: faturamento dedup (Q-MOV) + carga = débito-saída por mês
        // (ICMS/ST/IPI do C190, PIS/COFINS dos itens contribuicoes). Sem join
        // explosivo (P3), sem dobra de origem (P1), sem itens-fiscais-lixo (P2).
        $efdFat = collect($this->efd->faturamentoMensal($userId, 'saida', $dataInicio, $dataFim, $clienteId))
            ->keyBy(fn ($r) => (string) $r['mes']);
        $efdCarga = collect($this->efd->cargaTributariaDebitoSaidaMensal($userId, $dataInicio, $dataFim, $clienteId))
            ->keyBy(fn ($r) => (string) $r['mes']);
        $efdRows = $efdFat->keys()->merge($efdCarga->keys())->unique()
            ->mapWithKeys(function ($mes) use ($efdFat, $efdCarga) {
                $f = $efdFat->get($mes);
                $c = $efdCarga->get($mes);

                return [$mes => (object) [
                    'faturamento' => $f['valor'] ?? 0,
                    'icms' => $c['icms'] ?? 0,
                    'icms_st' => $c['icms_st'] ?? 0,
                    'pis' => $c['pis'] ?? 0,
                    'cofins' => $c['cofins'] ?? 0,
                    'ipi' => $c['ipi'] ?? 0,
                ]];
            });

        $keys = $xmlRows->keys()->merge($efdRows->keys())->unique()->sort()->values();

        return $keys->map(function ($mes) use ($xmlRows, $efdRows) {
            $xml = $xmlRows->get($mes);
            $efd = $efdRows->get((string) $mes);

            $faturamento = (float) ($xml->faturamento ?? 0) + (float) ($efd->faturamento ?? 0);
            $icms = (float) ($xml->icms ?? 0) + (float) ($efd->icms ?? 0);
            $icmsSt = (float) ($xml->icms_st ?? 0) + (float) ($efd->icms_st ?? 0);
            $pis = (float) ($xml->pis ?? 0) + (float) ($efd->pis ?? 0);
            $cofins = (float) ($xml->cofins ?? 0) + (float) ($efd->cofins ?? 0);
            $ipi = (float) ($xml->ipi ?? 0) + (float) ($efd->ipi ?? 0);
            $tributos = $icms + $icmsSt + $pis + $cofins + $ipi;
            $aliquotaEfetiva = $faturamento > 0 ? round(($tributos / $faturamento) * 100, 2) : 0;

            return [
                'mes' => $mes,
                'mes_formatado' => $mes ? date('m/Y', strtotime($mes)) : null,
                'faturamento' => $faturamento,
                'icms' => $icms,
                'icms_st' => $icmsSt,
                'pis' => $pis,
                'cofins' => $cofins,
                'ipi' => $ipi,
                'tributos_total' => $tributos,
                'aliquota_efetiva' => $aliquotaEfetiva,
            ];
        })->values()->toArray();
    }

    /**
     * Entradas vs Saídas por período.
     */
    public function getEntradasVsSaidas(int $userId, ?string $dataInicio = null, ?string $dataFim = null, ?int $clienteId = null): array
    {
        $query = XmlNota::where('user_id', $userId)
            ->where('finalidade', '!=', XmlNota::FINALIDADE_DEVOLUCAO);

        if ($dataInicio) {
            $query->where('data_emissao', '>=', $dataInicio);
        }

        if ($dataFim) {
            $query->where('data_emissao', '<=', $dataFim);
        }

        if ($clienteId) {
            $query->where('cliente_id', $clienteId);
        }

        $xmlRows = $query->select(
            DB::raw("DATE_TRUNC('month', data_emissao) as mes"),
            DB::raw('SUM(CASE WHEN tipo_nota = 0 THEN valor_total ELSE 0 END) as entradas'),
            DB::raw('SUM(CASE WHEN tipo_nota = 1 THEN valor_total ELSE 0 END) as saidas'),
            DB::raw('COUNT(CASE WHEN tipo_nota = 0 THEN 1 END) as qtd_entradas'),
            DB::raw('COUNT(CASE WHEN tipo_nota = 1 THEN 1 END) as qtd_saidas')
        )
            ->groupBy(DB::raw("DATE_TRUNC('month', data_emissao)"))
            ->orderBy('mes')
            ->get()
            ->keyBy('mes');

        // EFD canônico: entradas/saídas dedup de origem (P1), sem cancelada (P4).
        $efdEnt = collect($this->efd->faturamentoMensal($userId, 'entrada', $dataInicio, $dataFim, $clienteId))->keyBy(fn ($r) => (string) $r['mes']);
        $efdSai = collect($this->efd->faturamentoMensal($userId, 'saida', $dataInicio, $dataFim, $clienteId))->keyBy(fn ($r) => (string) $r['mes']);
        $efdRows = $efdEnt->keys()->merge($efdSai->keys())->unique()
            ->mapWithKeys(function ($mes) use ($efdEnt, $efdSai) {
                $e = $efdEnt->get($mes);
                $s = $efdSai->get($mes);

                return [$mes => (object) [
                    'entradas' => $e['valor'] ?? 0, 'saidas' => $s['valor'] ?? 0,
                    'qtd_entradas' => $e['qtd'] ?? 0, 'qtd_saidas' => $s['qtd'] ?? 0,
                ]];
            });

        $keys = $xmlRows->keys()->merge($efdRows->keys())->unique()->sort()->values();

        return $keys->map(function ($mes) use ($xmlRows, $efdRows) {
            $xml = $xmlRows->get($mes);
            $efd = $efdRows->get((string) $mes);
            $entradas = (float) ($xml->entradas ?? 0) + (float) ($efd->entradas ?? 0);
            $saidas = (float) ($xml->saidas ?? 0) + (float) ($efd->saidas ?? 0);

            return [
                'mes' => $mes,
                'mes_formatado' => $mes ? date('m/Y', strtotime($mes)) : null,
                'entradas' => $entradas,
                'saidas' => $saidas,
                'saldo' => $saidas - $entradas,
                'qtd_entradas' => (int) ($xml->qtd_entradas ?? 0) + (int) ($efd->qtd_entradas ?? 0),
                'qtd_saidas' => (int) ($xml->qtd_saidas ?? 0) + (int) ($efd->qtd_saidas ?? 0),
            ];
        })->values()->toArray();
    }

    /**
     * Devoluções por período.
     */
    public function getDevolucoes(int $userId, ?string $dataInicio = null, ?string $dataFim = null, ?int $clienteId = null): array
    {
        $query = XmlNota::where('user_id', $userId)
            ->where('finalidade', XmlNota::FINALIDADE_DEVOLUCAO);

        if ($dataInicio) {
            $query->where('data_emissao', '>=', $dataInicio);
        }

        if ($dataFim) {
            $query->where('data_emissao', '<=', $dataFim);
        }

        if ($clienteId) {
            $query->where('cliente_id', $clienteId);
        }

        return $query->select(
            DB::raw("DATE_TRUNC('month', data_emissao) as mes"),
            DB::raw('SUM(valor_total) as valor_devolucoes'),
            DB::raw('COUNT(*) as qtd_devolucoes')
        )
            ->groupBy(DB::raw("DATE_TRUNC('month', data_emissao)"))
            ->orderBy('mes')
            ->get()
            ->map(function ($item) {
                return [
                    'mes' => $item->mes,
                    'mes_formatado' => $item->mes ? date('m/Y', strtotime($item->mes)) : null,
                    'valor_devolucoes' => (float) $item->valor_devolucoes,
                    'qtd_devolucoes' => (int) $item->qtd_devolucoes,
                ];
            })
            ->toArray();
    }

    /**
     * Resumo geral para o dashboard.
     */
    public function getResumoGeral(int $userId, ?int $clienteId = null): array
    {
        $query = XmlNota::where('user_id', $userId);

        if ($clienteId) {
            $query->where('cliente_id', $clienteId);
        }

        $totais = $query->select(
            DB::raw('COUNT(*) as total_notas'),
            DB::raw('SUM(CASE WHEN tipo_nota = 1 AND finalidade != 4 THEN valor_total ELSE 0 END) as total_vendas'),
            DB::raw('SUM(CASE WHEN tipo_nota = 0 AND finalidade != 4 THEN valor_total ELSE 0 END) as total_compras'),
            DB::raw('SUM(CASE WHEN finalidade = 4 THEN valor_total ELSE 0 END) as total_devolucoes'),
            DB::raw('SUM(COALESCE(icms_valor, 0) + COALESCE(pis_valor, 0) + COALESCE(cofins_valor, 0) + COALESCE(ipi_valor, 0)) as total_tributos'),
            DB::raw('COUNT(DISTINCT emit_documento) as total_fornecedores'),
            DB::raw('COUNT(DISTINCT dest_documento) as total_clientes')
        )->first();

        // EFD canônico: vendas/compras dedup de origem (P1), tributo = débito-saída
        // (incidência sobre receita), total de notas dedup por chave. Ver F1.
        $efdVendas = $this->efd->faturamento($userId, 'saida', null, null, $clienteId);
        $efdCompras = $this->efd->faturamento($userId, 'entrada', null, null, $clienteId);
        $efdTributos = $this->efd->cargaTributariaDebitoSaida($userId, null, null, $clienteId)['total'];
        $efdNotas = $this->efd->totalNotas($userId, null, null, $clienteId);

        // Participantes distintos não dobram (mesmo participante_id nas 2 origens).
        $efdParticipantes = DB::table('efd_notas')
            ->where('user_id', $userId)
            ->when($clienteId, fn ($q) => $q->where('cliente_id', $clienteId))
            ->where('cancelada', false)
            ->select([
                DB::raw('COUNT(DISTINCT CASE WHEN tipo_operacao = \'entrada\' THEN participante_id END) as total_fornecedores'),
                DB::raw('COUNT(DISTINCT CASE WHEN tipo_operacao = \'saida\' THEN participante_id END) as total_clientes'),
            ])
            ->first();

        $totalVendas = (float) ($totais->total_vendas ?? 0) + $efdVendas;
        $totalTributos = (float) ($totais->total_tributos ?? 0) + $efdTributos;

        return [
            'total_notas' => (int) ($totais->total_notas ?? 0) + $efdNotas,
            'total_vendas' => $totalVendas,
            'total_compras' => (float) ($totais->total_compras ?? 0) + $efdCompras,
            'total_devolucoes' => (float) ($totais->total_devolucoes ?? 0),
            'total_tributos' => $totalTributos,
            'aliquota_media' => $totalVendas > 0 ? round(($totalTributos / $totalVendas) * 100, 2) : 0,
            'total_fornecedores' => (int) ($totais->total_fornecedores ?? 0) + (int) ($efdParticipantes->total_fornecedores ?? 0),
            'total_clientes' => (int) ($totais->total_clientes ?? 0) + (int) ($efdParticipantes->total_clientes ?? 0),
        ];
    }

    /**
     * Faturamento por UF.
     */
    public function getFaturamentoPorUf(int $userId, ?string $dataInicio = null, ?string $dataFim = null, ?int $clienteId = null): array
    {
        $query = XmlNota::where('user_id', $userId)
            ->where('tipo_nota', XmlNota::TIPO_SAIDA)
            ->where('finalidade', '!=', XmlNota::FINALIDADE_DEVOLUCAO)
            ->whereNotNull('dest_uf');

        if ($dataInicio) {
            $query->where('data_emissao', '>=', $dataInicio);
        }

        if ($dataFim) {
            $query->where('data_emissao', '<=', $dataFim);
        }

        if ($clienteId) {
            $query->where('cliente_id', $clienteId);
        }

        return $query->select(
            'dest_uf as uf',
            DB::raw('SUM(valor_total) as total'),
            DB::raw('COUNT(*) as qtd_notas')
        )
            ->groupBy('dest_uf')
            ->orderByDesc('total')
            ->get()
            ->map(function ($item) {
                return [
                    'uf' => $item->uf,
                    'total' => (float) $item->total,
                    'qtd_notas' => (int) $item->qtd_notas,
                ];
            })
            ->toArray();
    }

    /**
     * Tributos por tipo.
     */
    public function getTributosPorTipo(int $userId, ?string $dataInicio = null, ?string $dataFim = null, ?int $clienteId = null): array
    {
        $query = XmlNota::where('user_id', $userId);

        if ($dataInicio) {
            $query->where('data_emissao', '>=', $dataInicio);
        }

        if ($dataFim) {
            $query->where('data_emissao', '<=', $dataFim);
        }

        if ($clienteId) {
            $query->where('cliente_id', $clienteId);
        }

        $xmlTotais = $query->select(
            DB::raw('SUM(COALESCE(icms_valor, 0)) as icms'),
            DB::raw('SUM(COALESCE(icms_st_valor, 0)) as icms_st'),
            DB::raw('SUM(COALESCE(pis_valor, 0)) as pis'),
            DB::raw('SUM(COALESCE(cofins_valor, 0)) as cofins'),
            DB::raw('SUM(COALESCE(ipi_valor, 0)) as ipi')
        )->first();

        // EFD canônico: débito-saída (ICMS via C190; ST/IPI via C190; PIS/COFINS
        // via itens contribuicoes). Sem mistura de origem/crédito (P1/P2). Ver F1.
        $efd = $this->efd->cargaTributariaDebitoSaida($userId, $dataInicio, $dataFim, $clienteId);

        return [
            ['tipo' => 'ICMS', 'valor' => (float) ($xmlTotais->icms ?? 0) + $efd['icms']],
            ['tipo' => 'ICMS-ST', 'valor' => (float) ($xmlTotais->icms_st ?? 0) + $efd['icms_st']],
            ['tipo' => 'PIS', 'valor' => (float) ($xmlTotais->pis ?? 0) + $efd['pis']],
            ['tipo' => 'COFINS', 'valor' => (float) ($xmlTotais->cofins ?? 0) + $efd['cofins']],
            ['tipo' => 'IPI', 'valor' => (float) ($xmlTotais->ipi ?? 0) + $efd['ipi']],
        ];
    }

    public function getKpisEfd(int $userId, ?string $dataInicio, ?string $dataFim): array
    {
        $query = DB::table('efd_notas')->where('efd_notas.user_id', $userId);
        if ($dataInicio) {
            $query->where('efd_notas.data_emissao', '>=', $dataInicio);
        }
        if ($dataFim) {
            $query->where('efd_notas.data_emissao', '<=', $dataFim);
        }

        $participantesAtivos = (int) (clone $query)->distinct()->count('participante_id');

        $notasEmRisco = DB::table('efd_notas as n')
            ->join('participantes as p', 'p.id', '=', 'n.participante_id')
            ->where('n.user_id', $userId)
            ->when($dataInicio, fn ($q) => $q->where('n.data_emissao', '>=', $dataInicio))
            ->when($dataFim, fn ($q) => $q->where('n.data_emissao', '<=', $dataFim))
            ->whereRaw("UPPER(p.situacao_cadastral) NOT IN ('02', 'ATIVA')")
            ->whereNotNull('p.situacao_cadastral')
            ->count();

        // Valores canônicos: faturamento dedup de origem; entradas/saídas contadas
        // sobre a mesma base dedup (qtd vem do faturamentoMensal). Ver F1.
        $saidasMensal = $this->efd->faturamentoMensal($userId, 'saida', $dataInicio, $dataFim);
        $entradasMensal = $this->efd->faturamentoMensal($userId, 'entrada', $dataInicio, $dataFim);
        $saidasValor = array_sum(array_column($saidasMensal, 'valor'));
        $entradasValor = array_sum(array_column($entradasMensal, 'valor'));
        $saidasNotas = array_sum(array_column($saidasMensal, 'qtd'));
        $entradasNotas = array_sum(array_column($entradasMensal, 'qtd'));
        $totalNotas = $entradasNotas + $saidasNotas;

        // Carga = débito incidente sobre saídas (C190 + itens contribuicoes).
        $cargaTributaria = $this->efd->cargaTributariaDebitoSaida($userId, $dataInicio, $dataFim)['total'];

        // "Sem itens" só é real se a nota não tiver NEM C170 NEM C190 (perfil B
        // detalha por C190; CT-e por D190). Antes acusava todo C100 comercial.
        $notasSemItens = (clone $query)
            ->where('efd_notas.cancelada', false) // canceladas não têm detalhe — não é problema
            ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('efd_notas_itens as i')->whereColumn('i.efd_nota_id', 'efd_notas.id'))
            ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('efd_notas_consolidados as c')->whereColumn('c.efd_nota_id', 'efd_notas.id'))
            ->count();

        return [
            'total_entradas_valor' => $entradasValor,
            'total_entradas_notas' => $entradasNotas,
            'total_saidas_valor' => $saidasValor,
            'total_saidas_notas' => $saidasNotas,
            'saldo_liquido' => $entradasValor - $saidasValor,
            'carga_tributaria' => $cargaTributaria,
            'participantes_ativos' => $participantesAtivos,
            'notas_em_risco' => $notasEmRisco,
            'ticket_medio' => $totalNotas > 0 ? ($entradasValor + $saidasValor) / $totalNotas : 0,
            'notas_sem_itens' => $notasSemItens,
        ];
    }

    public function getFluxoMensalEfd(int $userId, ?string $dataInicio, ?string $dataFim): array
    {
        $fim = $dataFim ? Carbon::parse($dataFim) : Carbon::now();

        if ($dataInicio) {
            $inicio = Carbon::parse($dataInicio);
        } else {
            $minDate = DB::table('efd_notas')->where('user_id', $userId)->min('data_emissao');
            $inicio = $minDate
                ? Carbon::parse($minDate)->startOfMonth()
                : $fim->copy()->subMonths(11)->startOfMonth();
        }

        $rows = DB::table('efd_notas')
            ->where('user_id', $userId)
            ->whereBetween('data_emissao', [$inicio->toDateString(), $fim->toDateString()])
            ->select([
                DB::raw("TO_CHAR(DATE_TRUNC('month', data_emissao), 'YYYY-MM') as mes"),
                DB::raw("SUM(CASE WHEN tipo_operacao = 'entrada' THEN valor_total ELSE 0 END) as entradas"),
                DB::raw("SUM(CASE WHEN tipo_operacao = 'saida' THEN valor_total ELSE 0 END) as saidas"),
            ])
            ->groupBy(DB::raw("DATE_TRUNC('month', data_emissao)"))
            ->orderBy(DB::raw("DATE_TRUNC('month', data_emissao)"))
            ->get()
            ->keyBy('mes');

        $result = [];
        $period = CarbonPeriod::create($inicio->startOfMonth(), '1 month', $fim->copy()->startOfMonth());

        foreach ($period as $date) {
            $key = $date->format('Y-m');
            $row = $rows->get($key);
            $entradas = (float) ($row->entradas ?? 0);
            $saidas = (float) ($row->saidas ?? 0);
            $result[] = [
                'mes' => $key,
                'label' => $date->locale('pt_BR')->isoFormat('MMM/YY'),
                'entradas' => $entradas,
                'saidas' => $saidas,
                'saldo' => $entradas - $saidas,
            ];
        }

        return $result;
    }

    public function getVolumePorBlocoEfd(int $userId, ?string $dataInicio, ?string $dataFim): array
    {
        // Classificar por MODELO da nota (não por tipo_efd): a mesma NF-e está
        // nas 2 origens e era contada como "serviços" no lado PIS/COFINS. Modelo
        // 00=serviços (NFS-e), 57=transportes (CT-e), demais=mercadorias.
        // Base dedup (P1) evita dobrar a NF-e compartilhada entre merc/serv.
        $blocoExpr = "CASE WHEN n.modelo = '00' THEN 'notas_servicos' WHEN n.modelo = '57' THEN 'notas_transportes' ELSE 'notas_mercadorias' END";

        $rows = $this->efd->notasDedup($userId, null, $dataInicio, $dataFim)
            ->select([
                DB::raw("$blocoExpr as bloco"),
                DB::raw('SUM(n.valor_total) as valor'),
                DB::raw('COUNT(n.id) as notas'),
            ])
            ->groupBy(DB::raw($blocoExpr))
            ->get()
            ->keyBy('bloco');

        return [
            'notas_servicos' => ['valor' => (float) ($rows->get('notas_servicos')->valor ?? 0), 'notas' => (int) ($rows->get('notas_servicos')->notas ?? 0)],
            'notas_mercadorias' => ['valor' => (float) ($rows->get('notas_mercadorias')->valor ?? 0), 'notas' => (int) ($rows->get('notas_mercadorias')->notas ?? 0)],
            'notas_transportes' => ['valor' => (float) ($rows->get('notas_transportes')->valor ?? 0), 'notas' => (int) ($rows->get('notas_transportes')->notas ?? 0)],
        ];
    }

    public function getTopParticipantesEfd(int $userId, int $limit, ?string $dataInicio, ?string $dataFim, string $tipo): array
    {
        return $this->efd->notasDedup($userId, $tipo === 'E' ? 'entrada' : 'saida', $dataInicio, $dataFim)
            ->join('participantes as p', 'p.id', '=', 'n.participante_id')
            ->select([
                'n.participante_id',
                'p.documento as cnpj_cpf',
                'p.razao_social',
                'p.regime_tributario as regime',
                'p.situacao_cadastral as situacao',
                DB::raw('SUM(n.valor_total) as total_valor'),
                DB::raw('COUNT(n.id) as total_notas'),
            ])
            ->groupBy('n.participante_id', 'p.documento', 'p.razao_social', 'p.regime_tributario', 'p.situacao_cadastral')
            ->orderByDesc('total_valor')
            ->limit($limit)
            ->get()
            ->map(function ($row) {
                $situacao = strtoupper($row->situacao ?? '');

                return [
                    'participante_id' => $row->participante_id,
                    'cnpj_cpf' => $row->cnpj_cpf,
                    'razao_social' => $row->razao_social,
                    'regime' => $row->regime,
                    'situacao' => $row->situacao,
                    'irregular' => $row->situacao !== null && ! in_array($situacao, ['02', 'ATIVA']),
                    'total_valor' => (float) $row->total_valor,
                    'total_notas' => (int) $row->total_notas,
                    'ticket_medio' => $row->total_notas > 0
                        ? round((float) $row->total_valor / $row->total_notas, 2)
                        : 0.0,
                ];
            })
            ->toArray();
    }

    public function getTributosPorTipoEfd(int $userId, ?string $dataInicio, ?string $dataFim): array
    {
        // Débito incidente sobre saídas (ICMS via C190, PIS/COFINS via itens
        // contribuicoes). Antes somava itens de todas as origens misturando
        // crédito/débito → PIS/COFINS inflados ~57%. Ver F1.
        $c = $this->efd->cargaTributariaDebitoSaida($userId, $dataInicio, $dataFim);

        return [
            ['tipo' => 'ICMS', 'valor' => $c['icms']],
            ['tipo' => 'PIS', 'valor' => $c['pis']],
            ['tipo' => 'COFINS', 'valor' => $c['cofins']],
        ];
    }

    public function getRankingParticipantes(int $userId, string $tipo, ?string $dataInicio, ?string $dataFim, int $limit = 50): array
    {
        $tipoOperacao = $tipo === 'E' ? 'entrada' : 'saida';

        // Base dedup de origem (P1): volume por participante não dobra para quem
        // tem NF-e nas 2 escriturações; o percentual usa o mesmo total. Ver F1.
        $totalGeral = (float) $this->efd->notasDedup($userId, $tipoOperacao, $dataInicio, $dataFim)->sum('n.valor_total') ?: 1;

        $rows = $this->efd->notasDedup($userId, $tipoOperacao, $dataInicio, $dataFim)
            ->join('participantes as p', 'p.id', '=', 'n.participante_id')
            ->select([
                'n.participante_id',
                'p.documento as cnpj_cpf',
                'p.razao_social',
                'p.regime_tributario as regime',
                'p.situacao_cadastral as situacao',
                DB::raw('SUM(n.valor_total) as total_valor'),
                DB::raw('COUNT(n.id) as total_notas'),
                DB::raw("SUM(CASE WHEN n.tipo_operacao = 'entrada' THEN n.valor_total ELSE 0 END) as total_entradas"),
                DB::raw("SUM(CASE WHEN n.tipo_operacao = 'saida' THEN n.valor_total ELSE 0 END) as total_saidas"),
            ])
            ->groupBy('n.participante_id', 'p.documento', 'p.razao_social', 'p.regime_tributario', 'p.situacao_cadastral')
            ->orderByDesc('total_valor')
            ->limit($limit)
            ->get();

        return $rows->map(function ($row) use ($totalGeral) {
            $situacao = strtoupper($row->situacao ?? '');
            $totalValor = (float) $row->total_valor;
            $totalNotas = (int) $row->total_notas;

            return [
                'participante_id' => $row->participante_id,
                'cnpj_cpf' => $row->cnpj_cpf,
                'razao_social' => $row->razao_social,
                'regime' => $row->regime,
                'situacao' => $row->situacao,
                'irregular' => $row->situacao !== null && ! in_array($situacao, ['02', 'ATIVA']),
                'total_valor' => $totalValor,
                'total_notas' => $totalNotas,
                'ticket_medio' => $totalNotas > 0 ? round($totalValor / $totalNotas, 2) : 0.0,
                'total_entradas' => (float) $row->total_entradas,
                'total_saidas' => (float) $row->total_saidas,
                'percentual' => round(($totalValor / $totalGeral) * 100, 2),
            ];
        })->toArray();
    }

    // =========================================================================
    // Módulo Riscos
    // =========================================================================

    public function getNotasEmRisco(int $userId, ?string $dataInicio, ?string $dataFim): array
    {
        return DB::table('efd_notas as n')
            ->join('participantes as p', 'p.id', '=', 'n.participante_id')
            ->where('n.user_id', $userId)
            ->whereRaw("UPPER(p.situacao_cadastral) NOT IN ('02', 'ATIVA')")
            ->whereNotNull('p.situacao_cadastral')
            ->when($dataInicio, fn ($q) => $q->where('n.data_emissao', '>=', $dataInicio))
            ->when($dataFim, fn ($q) => $q->where('n.data_emissao', '<=', $dataFim))
            ->select([
                'n.id as nota_id',
                'n.participante_id',
                'p.documento as cnpj_cpf',
                'p.razao_social',
                'p.situacao_cadastral as situacao',
                'p.regime_tributario as regime',
                'n.tipo_operacao',
                'n.valor_total',
                'n.data_emissao',
            ])
            ->orderByDesc('n.valor_total')
            ->limit(100)
            ->get()
            ->map(fn ($row) => [
                'nota_id' => $row->nota_id,
                'participante_id' => $row->participante_id,
                'cnpj_cpf' => $row->cnpj_cpf,
                'razao_social' => $row->razao_social,
                'situacao' => $row->situacao,
                'regime' => $row->regime,
                'tipo_nota' => $row->tipo_operacao === 'entrada' ? 'E' : 'S',
                'vl_doc' => (float) $row->valor_total,
                'data_emissao' => $row->data_emissao
                    ? Carbon::parse($row->data_emissao)->format('d/m/Y')
                    : null,
                'bloco' => '—',
            ])
            ->toArray();
    }

    public function getFornecedoresIrregulares(int $userId, ?string $dataInicio, ?string $dataFim): array
    {
        return DB::table('efd_notas as n')
            ->join('participantes as p', 'p.id', '=', 'n.participante_id')
            ->where('n.user_id', $userId)
            ->whereRaw("UPPER(p.situacao_cadastral) NOT IN ('02', 'ATIVA')")
            ->whereNotNull('p.situacao_cadastral')
            ->when($dataInicio, fn ($q) => $q->where('n.data_emissao', '>=', $dataInicio))
            ->when($dataFim, fn ($q) => $q->where('n.data_emissao', '<=', $dataFim))
            ->select([
                'n.participante_id',
                'p.documento as cnpj_cpf',
                'p.razao_social',
                'p.situacao_cadastral as situacao',
                'p.regime_tributario as regime',
                DB::raw('COUNT(n.id) as total_notas'),
                DB::raw('SUM(n.valor_total) as valor_em_risco'),
                DB::raw('MAX(n.data_emissao) as ultima_nota_raw'),
            ])
            ->groupBy('n.participante_id', 'p.documento', 'p.razao_social', 'p.situacao_cadastral', 'p.regime_tributario')
            ->orderByDesc('valor_em_risco')
            ->get()
            ->map(fn ($row) => [
                'participante_id' => $row->participante_id,
                'cnpj_cpf' => $row->cnpj_cpf,
                'razao_social' => $row->razao_social,
                'situacao' => $row->situacao,
                'regime' => $row->regime,
                'total_notas' => (int) $row->total_notas,
                'valor_em_risco' => (float) $row->valor_em_risco,
                'ultima_nota' => $row->ultima_nota_raw
                    ? Carbon::parse($row->ultima_nota_raw)->format('d/m/Y')
                    : null,
            ])
            ->toArray();
    }

    public function getMudancasRegime(int $userId, int $dias = 90): array
    {
        return DB::table('participantes as p')
            ->join('efd_notas as n', 'n.participante_id', '=', 'p.id')
            ->where('n.user_id', $userId)
            ->whereRaw("p.updated_at >= NOW() - INTERVAL '{$dias} days'")
            ->select([
                'p.id as participante_id',
                'p.documento as cnpj_cpf',
                'p.razao_social',
                'p.regime_tributario as regime_atual',
                'p.situacao_cadastral as situacao_atual',
                'p.updated_at',
                DB::raw('COUNT(n.id) as total_notas'),
                DB::raw('SUM(n.valor_total) as valor_total'),
            ])
            ->groupBy('p.id', 'p.documento', 'p.razao_social', 'p.regime_tributario', 'p.situacao_cadastral', 'p.updated_at')
            ->orderByDesc('p.updated_at')
            ->limit(20)
            ->get()
            ->map(fn ($row) => [
                'participante_id' => $row->participante_id,
                'cnpj_cpf' => $row->cnpj_cpf,
                'razao_social' => $row->razao_social,
                'regime_atual' => $row->regime_atual,
                'situacao_atual' => $row->situacao_atual,
                'ultima_atualizacao' => $row->updated_at
                    ? Carbon::parse($row->updated_at)->format('d/m/Y H:i')
                    : null,
                'total_notas' => (int) $row->total_notas,
                'valor_total' => (float) $row->valor_total,
            ])
            ->toArray();
    }

    public function getScoreCarteira(int $userId): array
    {
        $totalParticipantes = DB::table('participantes')
            ->where('user_id', $userId)
            ->count();

        $participantesAtivos = DB::table('efd_notas')
            ->where('user_id', $userId)
            ->distinct('participante_id')
            ->count('participante_id');

        $irregulares = DB::table('efd_notas as n')
            ->join('participantes as p', 'p.id', '=', 'n.participante_id')
            ->where('n.user_id', $userId)
            ->whereRaw("UPPER(p.situacao_cadastral) NOT IN ('02', 'ATIVA')")
            ->whereNotNull('p.situacao_cadastral')
            ->distinct('n.participante_id')
            ->count('n.participante_id');

        // Base e valor-em-risco sobre a MESMA base dedup (P1): senão a NF-e nas 2
        // origens dobra a base e subestima o % em risco (mente pra menos). Ver F1.
        $valorTotalBase = (float) $this->efd->notasDedup($userId)->sum('n.valor_total');

        $valorEmRisco = (float) $this->efd->notasDedup($userId)
            ->join('participantes as p', 'p.id', '=', 'n.participante_id')
            ->whereRaw("UPPER(p.situacao_cadastral) NOT IN ('02', 'ATIVA')")
            ->whereNotNull('p.situacao_cadastral')
            ->sum('n.valor_total');

        $percentualRegular = $participantesAtivos > 0
            ? round((($participantesAtivos - $irregulares) / $participantesAtivos) * 100, 1)
            : 100.0;

        $percentualEmRisco = $valorTotalBase > 0
            ? round(($valorEmRisco / $valorTotalBase) * 100, 2)
            : 0.0;

        return [
            'total_participantes' => $totalParticipantes,
            'participantes_ativos' => $participantesAtivos,
            'irregulares' => $irregulares,
            'percentual_regular' => $percentualRegular,
            'valor_total_em_risco' => $valorEmRisco,
            'valor_total_base' => $valorTotalBase,
            'percentual_em_risco' => $percentualEmRisco,
        ];
    }

    public function getGapImportacoes(int $userId): array
    {
        $resultado = [];
        $inicio = Carbon::now()->subMonths(11)->startOfMonth();
        $fim = Carbon::now()->startOfMonth();

        $period = CarbonPeriod::create($inicio, '1 month', $fim);

        foreach ($period as $mes) {
            $mesStr = $mes->format('Y-m');

            $temFiscal = DB::table('efd_importacoes')
                ->where('user_id', $userId)
                ->where('tipo_efd', 'EFD ICMS/IPI')
                ->whereRaw("DATE_TRUNC('month', created_at) = ?", [$mes->startOfMonth()->toDateString()])
                ->exists();

            $temContrib = DB::table('efd_importacoes')
                ->where('user_id', $userId)
                ->where('tipo_efd', 'EFD PIS/COFINS')
                ->whereRaw("DATE_TRUNC('month', created_at) = ?", [$mes->startOfMonth()->toDateString()])
                ->exists();

            $resultado[] = [
                'mes' => $mesStr,
                'label' => $mes->locale('pt_BR')->isoFormat('MMM/YY'),
                'tem_fiscal' => $temFiscal,
                'tem_contrib' => $temContrib,
                'gap' => ! $temFiscal && ! $temContrib,
            ];
        }

        return $resultado;
    }

    // =========================================================================
    // Módulo Tributário EFD
    // =========================================================================

    public function getTributarioEfd(int $userId, ?string $dataInicio, ?string $dataFim): array
    {
        // ICMS via C190 (não itens fiscais lixo, P2); PIS/COFINS via itens
        // contribuicoes; split crédito(entrada)/débito(saída). Ver F1.
        $t = $this->efd->tributarioCreditoDebito($userId, $dataInicio, $dataFim);
        $icmsCredito = $t['icms']['credito'];
        $icmsDebito = $t['icms']['debito'];
        $pisCredito = $t['pis']['credito'];
        $pisDebito = $t['pis']['debito'];
        $cofinsCredito = $t['cofins']['credito'];
        $cofinsDebito = $t['cofins']['debito'];

        return [
            'icms' => ['credito' => $icmsCredito,   'debito' => $icmsDebito,   'saldo' => $icmsCredito - $icmsDebito],
            'pis' => ['credito' => $pisCredito,     'debito' => $pisDebito,    'saldo' => $pisCredito - $pisDebito],
            'cofins' => ['credito' => $cofinsCredito,  'debito' => $cofinsDebito, 'saldo' => $cofinsCredito - $cofinsDebito],
            'totais' => [
                'credito' => $icmsCredito + $pisCredito + $cofinsCredito,
                'debito' => $icmsDebito + $pisDebito + $cofinsDebito,
                'saldo' => ($icmsCredito + $pisCredito + $cofinsCredito) - ($icmsDebito + $pisDebito + $cofinsDebito),
            ],
        ];
    }

    public function getTributarioMensalEfd(int $userId, ?string $dataInicio, ?string $dataFim): array
    {
        [$inicio, $fim] = $this->janelaEfd($userId, $dataInicio, $dataFim);

        // Débito-saída mensal canônico (ICMS via C190, PIS/COFINS via itens
        // contribuicoes). Antes somava itens entrada+saída de origem misturada. Ver F1.
        $rows = collect($this->efd->cargaTributariaDebitoSaidaMensal($userId, $inicio->toDateString(), $fim->toDateString()))
            ->keyBy(fn ($r) => Carbon::parse($r['mes'])->format('Y-m'));

        $result = [];
        $period = CarbonPeriod::create($inicio->copy()->startOfMonth(), '1 month', $fim->copy()->startOfMonth());

        foreach ($period as $date) {
            $key = $date->format('Y-m');
            $row = $rows->get($key);
            $result[] = [
                'mes' => $key,
                'label' => $date->locale('pt_BR')->isoFormat('MMM/YY'),
                'icms' => (float) ($row['icms'] ?? 0),
                'pis' => (float) ($row['pis'] ?? 0),
                'cofins' => (float) ($row['cofins'] ?? 0),
            ];
        }

        return $result;
    }

    public function getAliquotaEfetivaEfd(int $userId, ?string $dataInicio, ?string $dataFim): array
    {
        [$inicio, $fim] = $this->janelaEfd($userId, $dataInicio, $dataFim);

        // Saídas canônicas (dedup origem) e tributos = débito-saída por mês.
        // Antes: saídas dobravam (P1) e tributos vinham de itens; com janela
        // default de 12m, dados de 2024 vistos em 2026 saíam zerados. Ver F1/A11.
        $rows = collect($this->efd->faturamentoMensal($userId, 'saida', $inicio->toDateString(), $fim->toDateString()))
            ->keyBy(fn ($r) => Carbon::parse($r['mes'])->format('Y-m'));
        $rowsTrib = collect($this->efd->cargaTributariaDebitoSaidaMensal($userId, $inicio->toDateString(), $fim->toDateString()))
            ->keyBy(fn ($r) => Carbon::parse($r['mes'])->format('Y-m'));

        $result = [];
        $period = CarbonPeriod::create($inicio->copy()->startOfMonth(), '1 month', $fim->copy()->startOfMonth());

        foreach ($period as $date) {
            $key = $date->format('Y-m');
            $vlSaidas = (float) ($rows->get($key)['valor'] ?? 0);
            $tributosTotal = (float) ($rowsTrib->get($key)['total'] ?? 0);
            $result[] = [
                'mes' => $key,
                'label' => $date->locale('pt_BR')->isoFormat('MMM/YY'),
                'vl_saidas' => $vlSaidas,
                'tributos_total' => $tributosTotal,
                'aliquota_efetiva' => $vlSaidas > 0 ? round($tributosTotal / $vlSaidas * 100, 2) : 0.0,
            ];
        }

        return $result;
    }

    public function getTributarioPorRegime(int $userId, ?string $dataInicio, ?string $dataFim): array
    {
        return DB::table('efd_notas as n')
            ->join('participantes as p', 'p.id', '=', 'n.participante_id')
            ->leftJoin(DB::raw('(SELECT efd_nota_id, SUM(COALESCE(valor_icms, 0)) as t_icms, SUM(COALESCE(valor_pis, 0)) as t_pis, SUM(COALESCE(valor_cofins, 0)) as t_cofins FROM efd_notas_itens GROUP BY efd_nota_id) as ti'), 'ti.efd_nota_id', '=', 'n.id')
            ->where('n.user_id', $userId)
            ->when($dataInicio, fn ($q) => $q->where('n.data_emissao', '>=', $dataInicio))
            ->when($dataFim, fn ($q) => $q->where('n.data_emissao', '<=', $dataFim))
            ->select([
                DB::raw("COALESCE(p.regime_tributario, 'Não Informado') as regime"),
                DB::raw('COUNT(n.id) as total_notas'),
                DB::raw('SUM(n.valor_total) as vl_total'),
                DB::raw('SUM(COALESCE(ti.t_icms, 0)) as icms_total'),
                DB::raw('SUM(COALESCE(ti.t_pis, 0)) as pis_total'),
                DB::raw('SUM(COALESCE(ti.t_cofins, 0)) as cofins_total'),
            ])
            ->groupBy(DB::raw("COALESCE(p.regime_tributario, 'Não Informado')"))
            ->orderByDesc('vl_total')
            ->get()
            ->map(function ($row) {
                $vlTotal = (float) $row->vl_total;
                $icmsTotal = (float) $row->icms_total;
                $pisTotal = (float) $row->pis_total;
                $cofinsTotal = (float) $row->cofins_total;
                $tributosTotal = $icmsTotal + $pisTotal + $cofinsTotal;

                return [
                    'regime' => $row->regime,
                    'total_notas' => (int) $row->total_notas,
                    'vl_total' => $vlTotal,
                    'icms_total' => $icmsTotal,
                    'pis_total' => $pisTotal,
                    'cofins_total' => $cofinsTotal,
                    'tributos_total' => $tributosTotal,
                    'aliquota_media' => $vlTotal > 0 ? round($tributosTotal / $vlTotal * 100, 2) : 0.0,
                ];
            })
            ->toArray();
    }

    /**
     * Dedup de origem (P1) ESCOPADO AO PARTICIPANTE. Diferente do dedup global: na
     * ficha, colapsa a mesma NF-e só quando ela está sob o MESMO participante nas duas
     * origens. A atribuição de participante difere entre fiscal e contribuicoes (medido:
     * 436 notas de contribuicoes de um participante tinham gêmea fiscal atribuída a OUTRO);
     * o dedup global dropparia essas notas legítimas da ficha. `$a` = alias da tabela.
     */
    private function dedupParticipanteSql(string $a): string
    {
        return "({$a}.origem_arquivo = 'fiscal' OR NOT EXISTS (SELECT 1 FROM efd_notas f WHERE f.user_id = {$a}.user_id AND f.origem_arquivo = 'fiscal' AND f.chave_acesso IS NOT NULL AND f.chave_acesso = {$a}.chave_acesso AND f.participante_id = {$a}.participante_id))";
    }

    public function getFichaParticipante(int $userId, int $participanteId, ?string $dataInicio, ?string $dataFim): array
    {
        $participante = DB::table('participantes as p')
            ->join('efd_notas as n', 'n.participante_id', '=', 'p.id')
            ->where('n.user_id', $userId)
            ->where('p.id', $participanteId)
            ->select('p.*')
            ->first();

        if (! $participante) {
            return [];
        }

        $situacao = strtoupper($participante->situacao_cadastral ?? '');

        $resumo = DB::table('efd_notas')
            ->where('user_id', $userId)
            ->where('participante_id', $participanteId)
            ->where('cancelada', false) // P4
            ->whereRaw($this->dedupParticipanteSql('efd_notas')) // P1 (escopado ao participante)
            ->when($dataInicio, fn ($q) => $q->where('data_emissao', '>=', $dataInicio))
            ->when($dataFim, fn ($q) => $q->where('data_emissao', '<=', $dataFim))
            ->select([
                DB::raw('COUNT(id) as total_notas'),
                DB::raw("SUM(CASE WHEN tipo_operacao = 'entrada' THEN valor_total ELSE 0 END) as total_entradas"),
                DB::raw("SUM(CASE WHEN tipo_operacao = 'saida' THEN valor_total ELSE 0 END) as total_saidas"),
            ])
            ->first();

        $totalNotas = (int) ($resumo->total_notas ?? 0);
        $totalEntradas = (float) ($resumo->total_entradas ?? 0);
        $totalSaidas = (float) ($resumo->total_saidas ?? 0);

        // Carga: ICMS do C190 (perfil B não tem ICMS no item, P2) + PIS/COFINS só dos itens
        // 'contribuicoes' (P8). NÃO deduplica (tributo vive em origens diferentes da mesma NF-e).
        $notaIdsCarga = DB::table('efd_notas')
            ->where('user_id', $userId)
            ->where('participante_id', $participanteId)
            ->where('cancelada', false)
            ->when($dataInicio, fn ($q) => $q->where('data_emissao', '>=', $dataInicio))
            ->when($dataFim, fn ($q) => $q->where('data_emissao', '<=', $dataFim))
            ->select('id');

        $cargaIcms = (float) DB::table('efd_notas_consolidados as c')
            ->whereIn('c.efd_nota_id', $notaIdsCarga)
            ->sum(DB::raw('COALESCE(c.valor_icms, 0)'));

        $cargaPisCofins = (float) DB::table('efd_notas_itens as i')
            ->join('efd_notas as n', 'n.id', '=', 'i.efd_nota_id')
            ->whereIn('i.efd_nota_id', $notaIdsCarga)
            ->where('n.origem_arquivo', 'contribuicoes')
            ->sum(DB::raw('COALESCE(i.valor_pis, 0) + COALESCE(i.valor_cofins, 0)'));

        $cargaTributaria = $cargaIcms + $cargaPisCofins;

        $fim = $dataFim ? Carbon::parse($dataFim) : Carbon::now();
        $inicio = $dataInicio ? Carbon::parse($dataInicio) : $fim->copy()->subMonths(11)->startOfMonth();

        $rows = DB::table('efd_notas')
            ->where('user_id', $userId)
            ->where('participante_id', $participanteId)
            ->where('cancelada', false) // P4
            ->whereRaw($this->dedupParticipanteSql('efd_notas')) // P1 (escopado)
            ->whereBetween('data_emissao', [$inicio->toDateString(), $fim->toDateString()])
            ->select([
                DB::raw("TO_CHAR(DATE_TRUNC('month', data_emissao), 'YYYY-MM') as mes"),
                DB::raw("SUM(CASE WHEN tipo_operacao = 'entrada' THEN valor_total ELSE 0 END) as entradas"),
                DB::raw("SUM(CASE WHEN tipo_operacao = 'saida' THEN valor_total ELSE 0 END) as saidas"),
            ])
            ->groupBy(DB::raw("DATE_TRUNC('month', data_emissao)"))
            ->orderBy(DB::raw("DATE_TRUNC('month', data_emissao)"))
            ->get()
            ->keyBy('mes');

        $evolucao = [];
        $period = CarbonPeriod::create($inicio->copy()->startOfMonth(), '1 month', $fim->copy()->startOfMonth());
        foreach ($period as $date) {
            $key = $date->format('Y-m');
            $row = $rows->get($key);
            $evolucao[] = [
                'mes' => $key,
                'label' => $date->locale('pt_BR')->isoFormat('MMM/YY'),
                'entradas' => (float) ($row->entradas ?? 0),
                'saidas' => (float) ($row->saidas ?? 0),
            ];
        }

        $ultimasNotas = DB::table('efd_notas as n')
            ->leftJoin(DB::raw("(SELECT efd_nota_id, STRING_AGG(DISTINCT cfop::text, ', ' ORDER BY cfop::text) as cfops FROM efd_notas_itens GROUP BY efd_nota_id) as ci"), 'ci.efd_nota_id', '=', 'n.id')
            ->where('n.user_id', $userId)
            ->where('n.participante_id', $participanteId)
            ->where('n.cancelada', false) // P4
            ->whereRaw($this->dedupParticipanteSql('n')) // P1 (escopado)
            ->when($dataInicio, fn ($q) => $q->where('n.data_emissao', '>=', $dataInicio))
            ->when($dataFim, fn ($q) => $q->where('n.data_emissao', '<=', $dataFim))
            ->select('n.id', 'n.tipo_operacao', 'n.valor_total', 'n.data_emissao', 'n.numero', 'n.serie', 'n.chave_acesso', 'n.modelo', 'ci.cfops')
            ->orderByDesc('n.data_emissao')
            ->limit(10)
            ->get()
            ->map(fn ($n) => [
                'id' => $n->id,
                'participante_id' => $participanteId,
                'tipo_nota' => $n->tipo_operacao === 'entrada' ? 'E' : 'S',
                'vl_doc' => (float) $n->valor_total,
                'data_emissao' => Carbon::parse($n->data_emissao)->format('d/m/Y'),
                'numero' => $n->numero,
                'serie' => $n->serie,
                'chave_acesso' => $n->chave_acesso,
                'modelo' => match ($n->modelo) {
                    '00' => 'NFS-e', '01' => 'NF', '1B' => 'NF Avulsa', '04' => 'NF Produtor',
                    '55' => 'NF-e', '57' => 'CT-e', '65' => 'NFC-e', '67' => 'CT-e OS',
                    default => $n->modelo ?? '—',
                },
                'cfop' => $n->cfops ?? '—',
            ])
            ->toArray();

        return [
            'participante' => [
                'id' => $participante->id,
                'cnpj_cpf' => $participante->documento,
                'razao_social' => $participante->razao_social,
                'regime' => $participante->regime_tributario,
                'situacao' => $participante->situacao_cadastral,
                'irregular' => $participante->situacao_cadastral !== null && ! in_array($situacao, ['02', 'ATIVA']),
                'ultima_consulta' => $participante->ultima_consulta_em
                    ? Carbon::parse($participante->ultima_consulta_em)->format('d/m/Y H:i')
                    : null,
            ],
            'resumo' => [
                'total_notas' => $totalNotas,
                'total_entradas' => $totalEntradas,
                'total_saidas' => $totalSaidas,
                'saldo' => $totalEntradas - $totalSaidas,
                'carga_tributaria' => $cargaTributaria,
                'ticket_medio' => $totalNotas > 0 ? round(($totalEntradas + $totalSaidas) / $totalNotas, 2) : 0.0,
            ],
            'evolucao_mensal' => $evolucao,
            'ultimas_notas' => $ultimasNotas,
        ];
    }
}
