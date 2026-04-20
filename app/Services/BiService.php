<?php

namespace App\Services;

use App\Models\XmlNota;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;

class BiService
{
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

        $efdRows = DB::table('efd_notas')
            ->where('user_id', $userId)
            ->where('tipo_operacao', 'saida')
            ->when($dataInicio, fn ($q) => $q->where('data_emissao', '>=', $dataInicio))
            ->when($dataFim, fn ($q) => $q->where('data_emissao', '<=', $dataFim))
            ->select([
                DB::raw("DATE_TRUNC('month', data_emissao) as mes"),
                DB::raw('SUM(valor_total) as faturamento'),
                DB::raw('COUNT(*) as qtd_notas'),
            ])
            ->groupBy(DB::raw("DATE_TRUNC('month', data_emissao)"))
            ->orderBy('mes')
            ->get()
            ->keyBy('mes');

        $keys = $xmlRows->keys()->merge($efdRows->keys())->unique()->sort()->values();

        return $keys->map(function ($mes) use ($xmlRows, $efdRows) {
            $xml = $xmlRows->get($mes);
            $efd = $efdRows->get($mes);
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
            'dest_cnpj',
            'dest_razao_social',
            DB::raw('SUM(valor_total) as total'),
            DB::raw('COUNT(*) as qtd_notas')
        )
            ->groupBy('dest_cnpj', 'dest_razao_social')
            ->orderByDesc('total')
            ->get()
            ->keyBy('dest_cnpj');

        $efdRows = DB::table('efd_notas as n')
            ->join('participantes as p', 'p.id', '=', 'n.participante_id')
            ->where('n.user_id', $userId)
            ->where('n.tipo_operacao', 'saida')
            ->when($dataInicio, fn ($q) => $q->where('n.data_emissao', '>=', $dataInicio))
            ->when($dataFim, fn ($q) => $q->where('n.data_emissao', '<=', $dataFim))
            ->select([
                'p.documento as dest_cnpj',
                'p.razao_social as dest_razao_social',
                DB::raw('SUM(n.valor_total) as total'),
                DB::raw('COUNT(n.id) as qtd_notas'),
            ])
            ->groupBy('p.documento', 'p.razao_social')
            ->get()
            ->keyBy('dest_cnpj');

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
            'emit_cnpj',
            'emit_razao_social',
            DB::raw('SUM(valor_total) as total'),
            DB::raw('COUNT(*) as qtd_notas')
        )
            ->groupBy('emit_cnpj', 'emit_razao_social')
            ->orderByDesc('total')
            ->get()
            ->keyBy('emit_cnpj');

        $efdRows = DB::table('efd_notas as n')
            ->join('participantes as p', 'p.id', '=', 'n.participante_id')
            ->where('n.user_id', $userId)
            ->where('n.tipo_operacao', 'entrada')
            ->when($dataInicio, fn ($q) => $q->where('n.data_emissao', '>=', $dataInicio))
            ->when($dataFim, fn ($q) => $q->where('n.data_emissao', '<=', $dataFim))
            ->select([
                'p.documento as emit_cnpj',
                'p.razao_social as emit_razao_social',
                DB::raw('SUM(n.valor_total) as total'),
                DB::raw('COUNT(n.id) as qtd_notas'),
            ])
            ->groupBy('p.documento', 'p.razao_social')
            ->get()
            ->keyBy('emit_cnpj');

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

        $efdRows = DB::table('efd_notas as n')
            ->leftJoin('efd_notas_itens as i', 'i.efd_nota_id', '=', 'n.id')
            ->where('n.user_id', $userId)
            ->when($dataInicio, fn ($q) => $q->where('n.data_emissao', '>=', $dataInicio))
            ->when($dataFim, fn ($q) => $q->where('n.data_emissao', '<=', $dataFim))
            ->select([
                DB::raw("DATE_TRUNC('month', n.data_emissao) as mes"),
                DB::raw('SUM(n.valor_total) as faturamento'),
                DB::raw('SUM(COALESCE(i.valor_icms, 0)) as icms'),
                DB::raw('0::numeric as icms_st'),
                DB::raw('SUM(COALESCE(i.valor_pis, 0)) as pis'),
                DB::raw('SUM(COALESCE(i.valor_cofins, 0)) as cofins'),
                DB::raw('0::numeric as ipi'),
            ])
            ->groupBy(DB::raw("DATE_TRUNC('month', n.data_emissao)"))
            ->get()
            ->keyBy('mes');

        $keys = $xmlRows->keys()->merge($efdRows->keys())->unique()->sort()->values();

        return $keys->map(function ($mes) use ($xmlRows, $efdRows) {
            $xml = $xmlRows->get($mes);
            $efd = $efdRows->get($mes);

            $faturamento = (float) ($xml->faturamento ?? 0) + (float) ($efd->faturamento ?? 0);
            $icms = (float) ($xml->icms ?? 0) + (float) ($efd->icms ?? 0);
            $icmsSt = (float) ($xml->icms_st ?? 0);
            $pis = (float) ($xml->pis ?? 0) + (float) ($efd->pis ?? 0);
            $cofins = (float) ($xml->cofins ?? 0) + (float) ($efd->cofins ?? 0);
            $ipi = (float) ($xml->ipi ?? 0);
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

        $efdRows = DB::table('efd_notas')
            ->where('user_id', $userId)
            ->when($dataInicio, fn ($q) => $q->where('data_emissao', '>=', $dataInicio))
            ->when($dataFim, fn ($q) => $q->where('data_emissao', '<=', $dataFim))
            ->select([
                DB::raw("DATE_TRUNC('month', data_emissao) as mes"),
                DB::raw("SUM(CASE WHEN tipo_operacao = 'entrada' THEN valor_total ELSE 0 END) as entradas"),
                DB::raw("SUM(CASE WHEN tipo_operacao = 'saida' THEN valor_total ELSE 0 END) as saidas"),
                DB::raw("COUNT(CASE WHEN tipo_operacao = 'entrada' THEN 1 END) as qtd_entradas"),
                DB::raw("COUNT(CASE WHEN tipo_operacao = 'saida' THEN 1 END) as qtd_saidas"),
            ])
            ->groupBy(DB::raw("DATE_TRUNC('month', data_emissao)"))
            ->get()
            ->keyBy('mes');

        $keys = $xmlRows->keys()->merge($efdRows->keys())->unique()->sort()->values();

        return $keys->map(function ($mes) use ($xmlRows, $efdRows) {
            $xml = $xmlRows->get($mes);
            $efd = $efdRows->get($mes);
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
            DB::raw('COUNT(DISTINCT emit_cnpj) as total_fornecedores'),
            DB::raw('COUNT(DISTINCT dest_cnpj) as total_clientes')
        )->first();

        $efdTotais = DB::table('efd_notas')
            ->where('user_id', $userId)
            ->select([
                DB::raw('COUNT(*) as total_notas'),
                DB::raw("SUM(CASE WHEN tipo_operacao = 'saida' THEN valor_total ELSE 0 END) as total_vendas"),
                DB::raw("SUM(CASE WHEN tipo_operacao = 'entrada' THEN valor_total ELSE 0 END) as total_compras"),
                DB::raw('COUNT(DISTINCT CASE WHEN tipo_operacao = \'entrada\' THEN participante_id END) as total_fornecedores'),
                DB::raw('COUNT(DISTINCT CASE WHEN tipo_operacao = \'saida\' THEN participante_id END) as total_clientes'),
            ])
            ->first();

        $efdTributos = (float) DB::table('efd_notas_itens as i')
            ->join('efd_notas as n', 'n.id', '=', 'i.efd_nota_id')
            ->where('n.user_id', $userId)
            ->sum(DB::raw('COALESCE(i.valor_icms, 0) + COALESCE(i.valor_pis, 0) + COALESCE(i.valor_cofins, 0)'));

        $totalVendas = (float) ($totais->total_vendas ?? 0) + (float) ($efdTotais->total_vendas ?? 0);
        $totalTributos = (float) ($totais->total_tributos ?? 0) + $efdTributos;

        return [
            'total_notas' => (int) ($totais->total_notas ?? 0) + (int) ($efdTotais->total_notas ?? 0),
            'total_vendas' => $totalVendas,
            'total_compras' => (float) ($totais->total_compras ?? 0) + (float) ($efdTotais->total_compras ?? 0),
            'total_devolucoes' => (float) ($totais->total_devolucoes ?? 0),
            'total_tributos' => $totalTributos,
            'aliquota_media' => $totalVendas > 0 ? round(($totalTributos / $totalVendas) * 100, 2) : 0,
            'total_fornecedores' => (int) ($totais->total_fornecedores ?? 0) + (int) ($efdTotais->total_fornecedores ?? 0),
            'total_clientes' => (int) ($totais->total_clientes ?? 0) + (int) ($efdTotais->total_clientes ?? 0),
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

        $efdTotais = DB::table('efd_notas_itens as i')
            ->join('efd_notas as n', 'n.id', '=', 'i.efd_nota_id')
            ->where('n.user_id', $userId)
            ->when($dataInicio, fn ($q) => $q->where('n.data_emissao', '>=', $dataInicio))
            ->when($dataFim, fn ($q) => $q->where('n.data_emissao', '<=', $dataFim))
            ->select([
                DB::raw('SUM(COALESCE(i.valor_icms, 0)) as icms'),
                DB::raw('SUM(COALESCE(i.valor_pis, 0)) as pis'),
                DB::raw('SUM(COALESCE(i.valor_cofins, 0)) as cofins'),
            ])
            ->first();

        return [
            ['tipo' => 'ICMS', 'valor' => (float) ($xmlTotais->icms ?? 0) + (float) ($efdTotais->icms ?? 0)],
            ['tipo' => 'ICMS-ST', 'valor' => (float) ($xmlTotais->icms_st ?? 0)],
            ['tipo' => 'PIS', 'valor' => (float) ($xmlTotais->pis ?? 0) + (float) ($efdTotais->pis ?? 0)],
            ['tipo' => 'COFINS', 'valor' => (float) ($xmlTotais->cofins ?? 0) + (float) ($efdTotais->cofins ?? 0)],
            ['tipo' => 'IPI', 'valor' => (float) ($xmlTotais->ipi ?? 0)],
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

        $totais = (clone $query)->select([
            DB::raw("SUM(CASE WHEN tipo_operacao = 'entrada' THEN valor_total ELSE 0 END) as entradas_valor"),
            DB::raw("COUNT(CASE WHEN tipo_operacao = 'entrada' THEN 1 END) as entradas_notas"),
            DB::raw("SUM(CASE WHEN tipo_operacao = 'saida' THEN valor_total ELSE 0 END) as saidas_valor"),
            DB::raw("COUNT(CASE WHEN tipo_operacao = 'saida' THEN 1 END) as saidas_notas"),
            DB::raw('COUNT(DISTINCT participante_id) as participantes_ativos'),
        ])->first();

        $notasEmRisco = DB::table('efd_notas as n')
            ->join('participantes as p', 'p.id', '=', 'n.participante_id')
            ->where('n.user_id', $userId)
            ->when($dataInicio, fn ($q) => $q->where('n.data_emissao', '>=', $dataInicio))
            ->when($dataFim, fn ($q) => $q->where('n.data_emissao', '<=', $dataFim))
            ->whereRaw("UPPER(p.situacao_cadastral) NOT IN ('02', 'ATIVA')")
            ->whereNotNull('p.situacao_cadastral')
            ->count();

        $entradasValor = (float) ($totais->entradas_valor ?? 0);
        $saidasValor = (float) ($totais->saidas_valor ?? 0);
        $entradasNotas = (int) ($totais->entradas_notas ?? 0);
        $saidasNotas = (int) ($totais->saidas_notas ?? 0);
        $totalNotas = $entradasNotas + $saidasNotas;

        $cargaTributaria = (float) DB::table('efd_notas_itens as i')
            ->join('efd_notas as n', 'n.id', '=', 'i.efd_nota_id')
            ->where('n.user_id', $userId)
            ->when($dataInicio, fn ($q) => $q->where('n.data_emissao', '>=', $dataInicio))
            ->when($dataFim, fn ($q) => $q->where('n.data_emissao', '<=', $dataFim))
            ->sum(DB::raw('COALESCE(i.valor_icms, 0) + COALESCE(i.valor_pis, 0) + COALESCE(i.valor_cofins, 0)'));

        $notasSemItens = (clone $query)
            ->leftJoin('efd_notas_itens as i', 'i.efd_nota_id', '=', 'efd_notas.id')
            ->whereNull('i.id')
            ->where('efd_notas.modelo', '!=', '57') // CT-e não possui itens no SPED
            ->count();

        return [
            'total_entradas_valor' => $entradasValor,
            'total_entradas_notas' => $entradasNotas,
            'total_saidas_valor' => $saidasValor,
            'total_saidas_notas' => $saidasNotas,
            'saldo_liquido' => $entradasValor - $saidasValor,
            'carga_tributaria' => $cargaTributaria,
            'participantes_ativos' => (int) ($totais->participantes_ativos ?? 0),
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
        $blocoExpr = "CASE WHEN imp.tipo_efd = 'EFD PIS/COFINS' THEN 'notas_servicos' WHEN n.modelo = '57' THEN 'notas_transportes' ELSE 'notas_mercadorias' END";

        $rows = DB::table('efd_notas as n')
            ->join('efd_importacoes as imp', 'imp.id', '=', 'n.importacao_id')
            ->where('n.user_id', $userId)
            ->when($dataInicio, fn ($q) => $q->where('n.data_emissao', '>=', $dataInicio))
            ->when($dataFim, fn ($q) => $q->where('n.data_emissao', '<=', $dataFim))
            ->select([
                DB::raw("$blocoExpr as bloco"),
                DB::raw('SUM(n.valor_total) as valor'),
                DB::raw('COUNT(n.id) as notas'),
            ])
            ->groupBy(DB::raw($blocoExpr))
            ->get()
            ->keyBy('bloco');

        return [
            'notas_servicos'     => ['valor' => (float) ($rows->get('notas_servicos')->valor ?? 0), 'notas' => (int) ($rows->get('notas_servicos')->notas ?? 0)],
            'notas_mercadorias'  => ['valor' => (float) ($rows->get('notas_mercadorias')->valor ?? 0), 'notas' => (int) ($rows->get('notas_mercadorias')->notas ?? 0)],
            'notas_transportes'  => ['valor' => (float) ($rows->get('notas_transportes')->valor ?? 0), 'notas' => (int) ($rows->get('notas_transportes')->notas ?? 0)],
        ];
    }

    public function getTopParticipantesEfd(int $userId, int $limit, ?string $dataInicio, ?string $dataFim, string $tipo): array
    {
        return DB::table('efd_notas as n')
            ->join('participantes as p', 'p.id', '=', 'n.participante_id')
            ->where('n.user_id', $userId)
            ->where('n.tipo_operacao', $tipo === 'E' ? 'entrada' : 'saida')
            ->when($dataInicio, fn ($q) => $q->where('n.data_emissao', '>=', $dataInicio))
            ->when($dataFim, fn ($q) => $q->where('n.data_emissao', '<=', $dataFim))
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
        $totais = DB::table('efd_notas_itens as i')
            ->join('efd_notas as n', 'n.id', '=', 'i.efd_nota_id')
            ->where('n.user_id', $userId)
            ->when($dataInicio, fn ($q) => $q->where('n.data_emissao', '>=', $dataInicio))
            ->when($dataFim, fn ($q) => $q->where('n.data_emissao', '<=', $dataFim))
            ->select([
                DB::raw('SUM(COALESCE(i.valor_icms, 0)) as icms'),
                DB::raw('SUM(COALESCE(i.valor_pis, 0)) as pis'),
                DB::raw('SUM(COALESCE(i.valor_cofins, 0)) as cofins'),
            ])
            ->first();

        return [
            ['tipo' => 'ICMS', 'valor' => (float) ($totais->icms ?? 0)],
            ['tipo' => 'PIS', 'valor' => (float) ($totais->pis ?? 0)],
            ['tipo' => 'COFINS', 'valor' => (float) ($totais->cofins ?? 0)],
        ];
    }

    public function getRankingParticipantes(int $userId, string $tipo, ?string $dataInicio, ?string $dataFim, int $limit = 50): array
    {
        $tipoOperacao = $tipo === 'E' ? 'entrada' : 'saida';

        $totalGeral = DB::table('efd_notas')
            ->where('user_id', $userId)
            ->where('tipo_operacao', $tipoOperacao)
            ->when($dataInicio, fn ($q) => $q->where('data_emissao', '>=', $dataInicio))
            ->when($dataFim, fn ($q) => $q->where('data_emissao', '<=', $dataFim))
            ->sum('valor_total');

        $totalGeral = (float) $totalGeral ?: 1;

        $rows = DB::table('efd_notas as n')
            ->join('participantes as p', 'p.id', '=', 'n.participante_id')
            ->where('n.user_id', $userId)
            ->where('n.tipo_operacao', $tipoOperacao)
            ->when($dataInicio, fn ($q) => $q->where('n.data_emissao', '>=', $dataInicio))
            ->when($dataFim, fn ($q) => $q->where('n.data_emissao', '<=', $dataFim))
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

        $valorTotalBase = (float) DB::table('efd_notas')
            ->where('user_id', $userId)
            ->sum('valor_total');

        $valorEmRisco = (float) DB::table('efd_notas as n')
            ->join('participantes as p', 'p.id', '=', 'n.participante_id')
            ->where('n.user_id', $userId)
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
        $row = DB::table('efd_notas_itens as i')
            ->join('efd_notas as n', 'n.id', '=', 'i.efd_nota_id')
            ->where('n.user_id', $userId)
            ->when($dataInicio, fn ($q) => $q->where('n.data_emissao', '>=', $dataInicio))
            ->when($dataFim, fn ($q) => $q->where('n.data_emissao', '<=', $dataFim))
            ->select([
                DB::raw("SUM(CASE WHEN n.tipo_operacao = 'entrada' THEN COALESCE(i.valor_icms, 0) ELSE 0 END) as icms_credito"),
                DB::raw("SUM(CASE WHEN n.tipo_operacao = 'saida'   THEN COALESCE(i.valor_icms, 0) ELSE 0 END) as icms_debito"),
                DB::raw("SUM(CASE WHEN n.tipo_operacao = 'entrada' THEN COALESCE(i.valor_pis, 0) ELSE 0 END) as pis_credito"),
                DB::raw("SUM(CASE WHEN n.tipo_operacao = 'saida'   THEN COALESCE(i.valor_pis, 0) ELSE 0 END) as pis_debito"),
                DB::raw("SUM(CASE WHEN n.tipo_operacao = 'entrada' THEN COALESCE(i.valor_cofins, 0) ELSE 0 END) as cofins_credito"),
                DB::raw("SUM(CASE WHEN n.tipo_operacao = 'saida'   THEN COALESCE(i.valor_cofins, 0) ELSE 0 END) as cofins_debito"),
            ])
            ->first();

        $icmsCredito = (float) ($row->icms_credito ?? 0);
        $icmsDebito = (float) ($row->icms_debito ?? 0);
        $pisCredito = (float) ($row->pis_credito ?? 0);
        $pisDebito = (float) ($row->pis_debito ?? 0);
        $cofinsCredito = (float) ($row->cofins_credito ?? 0);
        $cofinsDebito = (float) ($row->cofins_debito ?? 0);

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
        $fim = $dataFim ? Carbon::parse($dataFim) : Carbon::now();
        $inicio = $dataInicio ? Carbon::parse($dataInicio) : $fim->copy()->subMonths(11)->startOfMonth();

        $rows = DB::table('efd_notas_itens as i')
            ->join('efd_notas as n', 'n.id', '=', 'i.efd_nota_id')
            ->where('n.user_id', $userId)
            ->whereBetween('n.data_emissao', [$inicio->toDateString(), $fim->toDateString()])
            ->select([
                DB::raw("TO_CHAR(DATE_TRUNC('month', n.data_emissao), 'YYYY-MM') as mes"),
                DB::raw('SUM(COALESCE(i.valor_icms, 0)) as icms'),
                DB::raw('SUM(COALESCE(i.valor_pis, 0)) as pis'),
                DB::raw('SUM(COALESCE(i.valor_cofins, 0)) as cofins'),
            ])
            ->groupBy(DB::raw("DATE_TRUNC('month', n.data_emissao)"))
            ->orderBy(DB::raw("DATE_TRUNC('month', n.data_emissao)"))
            ->get()
            ->keyBy('mes');

        $result = [];
        $period = CarbonPeriod::create($inicio->startOfMonth(), '1 month', $fim->copy()->startOfMonth());

        foreach ($period as $date) {
            $key = $date->format('Y-m');
            $row = $rows->get($key);
            $result[] = [
                'mes' => $key,
                'label' => $date->locale('pt_BR')->isoFormat('MMM/YY'),
                'icms' => (float) ($row->icms ?? 0),
                'pis' => (float) ($row->pis ?? 0),
                'cofins' => (float) ($row->cofins ?? 0),
            ];
        }

        return $result;
    }

    public function getAliquotaEfetivaEfd(int $userId, ?string $dataInicio, ?string $dataFim): array
    {
        $fim = $dataFim ? Carbon::parse($dataFim) : Carbon::now();
        $inicio = $dataInicio ? Carbon::parse($dataInicio) : $fim->copy()->subMonths(11)->startOfMonth();

        $rows = DB::table('efd_notas')
            ->where('user_id', $userId)
            ->whereBetween('data_emissao', [$inicio->toDateString(), $fim->toDateString()])
            ->select([
                DB::raw("TO_CHAR(DATE_TRUNC('month', data_emissao), 'YYYY-MM') as mes"),
                DB::raw("SUM(CASE WHEN tipo_operacao = 'saida' THEN valor_total ELSE 0 END) as vl_saidas"),
            ])
            ->groupBy(DB::raw("DATE_TRUNC('month', data_emissao)"))
            ->orderBy(DB::raw("DATE_TRUNC('month', data_emissao)"))
            ->get()
            ->keyBy('mes');

        $rowsTrib = DB::table('efd_notas_itens as i')
            ->join('efd_notas as n', 'n.id', '=', 'i.efd_nota_id')
            ->where('n.user_id', $userId)
            ->whereBetween('n.data_emissao', [$inicio->toDateString(), $fim->toDateString()])
            ->select([
                DB::raw("TO_CHAR(DATE_TRUNC('month', n.data_emissao), 'YYYY-MM') as mes"),
                DB::raw('SUM(COALESCE(i.valor_icms, 0) + COALESCE(i.valor_pis, 0) + COALESCE(i.valor_cofins, 0)) as tributos_total'),
            ])
            ->groupBy(DB::raw("DATE_TRUNC('month', n.data_emissao)"))
            ->get()
            ->keyBy('mes');

        $result = [];
        $period = CarbonPeriod::create($inicio->startOfMonth(), '1 month', $fim->copy()->startOfMonth());

        foreach ($period as $date) {
            $key = $date->format('Y-m');
            $row = $rows->get($key);
            $vlSaidas = (float) ($row->vl_saidas ?? 0);
            $tributosTotal = (float) ($rowsTrib->get($key)->tributos_total ?? 0);
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

        $cargaTributaria = (float) DB::table('efd_notas_itens as i')
            ->join('efd_notas as n', 'n.id', '=', 'i.efd_nota_id')
            ->where('n.user_id', $userId)
            ->where('n.participante_id', $participanteId)
            ->when($dataInicio, fn ($q) => $q->where('n.data_emissao', '>=', $dataInicio))
            ->when($dataFim, fn ($q) => $q->where('n.data_emissao', '<=', $dataFim))
            ->sum(DB::raw('COALESCE(i.valor_icms, 0) + COALESCE(i.valor_pis, 0) + COALESCE(i.valor_cofins, 0)'));

        $fim = $dataFim ? Carbon::parse($dataFim) : Carbon::now();
        $inicio = $dataInicio ? Carbon::parse($dataInicio) : $fim->copy()->subMonths(11)->startOfMonth();

        $rows = DB::table('efd_notas')
            ->where('user_id', $userId)
            ->where('participante_id', $participanteId)
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
