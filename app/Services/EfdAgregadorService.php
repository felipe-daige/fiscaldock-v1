<?php

namespace App\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Agregador canônico de dados de EFD para o consumo (BI, Dashboards, Resumo Fiscal).
 *
 * Centraliza as duas queries que TODO consumidor de EFD precisava e cada um
 * reimplementava errado (ver docs/validacao-confiabilidade-efd/f1-bi-service.md):
 *
 *  Q-MOV  — faturamento/movimento de notas, deduplicando a coexistência
 *           fiscal × contribuicoes (P1) e incluindo serviços (NFS-e modelo 00).
 *  Q-CARGA — carga tributária, em duas leituras:
 *           · débito-saída (granular, por nota/mês): ICMS/ST/IPI do C190 +
 *             PIS/COFINS dos itens da origem 'contribuicoes';
 *           · apurada (gold, a recolher): das tabelas efd_apuracoes_*.
 *
 * Peculiaridades dos dados (massa HIDRATOP, perfil B comercial), com fonte SPED:
 *  - P1: a MESMA NF-e é escriturada em EFD ICMS/IPI (origem 'fiscal') e EFD
 *        PIS/COFINS (origem 'contribuicoes'). Somar valor_total sem dedup dobra.
 *  - P2: em perfil comercial o C100 detalha por C190 (consolidado), não C170;
 *        ICMS dos itens de origem 'fiscal' é ~0. ICMS verdadeiro vive no C190.
 *  - P7: NFS-e (modelo 00) só existem em 'contribuicoes' e podem ter chave NULL.
 */
class EfdAgregadorService
{
    /**
     * Q-MOV — base de notas EFD por operação, já deduplicada por origem (P1),
     * sem canceladas (P4). A escrituração FISCAL é a base; somam-se apenas os
     * documentos que SÓ existem no PIS/COFINS (NFS-e e eventuais NF-e órfãs),
     * detectados por não terem gêmea de mesma chave na origem 'fiscal'.
     */
    /**
     * Base reusável de `efd_notas as n` já DEDUPLICADA por origem (P1) e sem
     * canceladas (P4). É o ponto único que qualquer agregação por nota deve
     * partir (faturamento, volume por bloco, ranking por participante, etc.).
     * `$tipo` opcional ('saida'|'entrada') restringe a operação.
     */
    public function notasDedup(int $userId, ?string $tipo = null, ?string $dataInicio = null, ?string $dataFim = null, ?int $clienteId = null): Builder
    {
        return DB::table('efd_notas as n')
            ->where('n.user_id', $userId)
            ->where('n.cancelada', false)
            ->when($tipo, fn ($q) => $q->where('n.tipo_operacao', $tipo))
            ->when($clienteId, fn ($q) => $q->where('n.cliente_id', $clienteId))
            ->when($dataInicio, fn ($q) => $q->where('n.data_emissao', '>=', $dataInicio))
            ->when($dataFim, fn ($q) => $q->where('n.data_emissao', '<=', $dataFim))
            ->where(function ($q) use ($userId) {
                $q->where('n.origem_arquivo', 'fiscal')
                    ->orWhereRaw('NOT EXISTS (SELECT 1 FROM efd_notas f WHERE f.user_id = ? AND f.origem_arquivo = ? AND f.chave_acesso IS NOT NULL AND f.chave_acesso = n.chave_acesso)', [$userId, 'fiscal']);
            });
    }

    private function baseMov(int $userId, string $tipo, ?string $dataInicio, ?string $dataFim, ?int $clienteId): Builder
    {
        return $this->notasDedup($userId, $tipo, $dataInicio, $dataFim, $clienteId);
    }

    /**
     * Faturamento/movimento total por operação ('saida'|'entrada'), dedup de origem.
     */
    public function faturamento(int $userId, string $tipo = 'saida', ?string $dataInicio = null, ?string $dataFim = null, ?int $clienteId = null): float
    {
        return (float) $this->baseMov($userId, $tipo, $dataInicio, $dataFim, $clienteId)
            ->sum('n.valor_total');
    }

    /**
     * Faturamento por mês [{mes (date), valor, qtd}], dedup de origem.
     */
    public function faturamentoMensal(int $userId, string $tipo = 'saida', ?string $dataInicio = null, ?string $dataFim = null, ?int $clienteId = null): array
    {
        return $this->baseMov($userId, $tipo, $dataInicio, $dataFim, $clienteId)
            ->select([
                DB::raw("DATE_TRUNC('month', n.data_emissao) as mes"),
                DB::raw('SUM(n.valor_total) as valor'),
                DB::raw('COUNT(*) as qtd'),
            ])
            ->groupBy(DB::raw("DATE_TRUNC('month', n.data_emissao)"))
            ->orderBy('mes')
            ->get()
            ->map(fn ($r) => [
                'mes' => $r->mes,
                'valor' => (float) $r->valor,
                'qtd' => (int) $r->qtd,
            ])
            ->toArray();
    }

    /**
     * Qtd de notas distintas (dedup por chave; NF-e nas 2 origens = 1 nota).
     * NFS-e sem chave (P7) contam 1 cada.
     */
    public function totalNotas(int $userId, ?string $dataInicio = null, ?string $dataFim = null, ?int $clienteId = null): int
    {
        $q = DB::table('efd_notas')
            ->where('user_id', $userId)
            ->where('cancelada', false)
            ->when($clienteId, fn ($q) => $q->where('cliente_id', $clienteId))
            ->when($dataInicio, fn ($q) => $q->where('data_emissao', '>=', $dataInicio))
            ->when($dataFim, fn ($q) => $q->where('data_emissao', '<=', $dataFim));

        $comChave = (clone $q)->whereNotNull('chave_acesso')->distinct()->count('chave_acesso');
        $semChave = (clone $q)->whereNull('chave_acesso')->count();

        return $comChave + $semChave;
    }

    /**
     * Q-CARGA (débito-saída) — tributo incidente sobre as saídas, granular.
     *   ICMS/ST/IPI: efd_notas_consolidados (C190) das saídas não-canceladas.
     *   PIS/COFINS:  itens da origem 'contribuicoes' das saídas não-canceladas.
     * Em regime cumulativo (sem crédito) e com crédito de ICMS ínfimo, aproxima
     * o tributo a recolher; é a fonte usada nas quebras por mês/CFOP/participante.
     */
    public function cargaTributariaDebitoSaida(int $userId, ?string $dataInicio = null, ?string $dataFim = null, ?int $clienteId = null): array
    {
        $icms = DB::table('efd_notas_consolidados as c')
            ->join('efd_notas as n', 'n.id', '=', 'c.efd_nota_id')
            ->where('c.user_id', $userId)
            ->where('n.tipo_operacao', 'saida')
            ->where('n.cancelada', false)
            ->when($clienteId, fn ($q) => $q->where('n.cliente_id', $clienteId))
            ->when($dataInicio, fn ($q) => $q->where('n.data_emissao', '>=', $dataInicio))
            ->when($dataFim, fn ($q) => $q->where('n.data_emissao', '<=', $dataFim))
            ->selectRaw('SUM(COALESCE(c.valor_icms,0)) icms, SUM(COALESCE(c.valor_icms_st,0)) st, SUM(COALESCE(c.valor_ipi,0)) ipi')
            ->first();

        $pc = DB::table('efd_notas_itens as i')
            ->join('efd_notas as n', 'n.id', '=', 'i.efd_nota_id')
            ->where('i.user_id', $userId)
            ->where('n.origem_arquivo', 'contribuicoes')
            ->where('n.tipo_operacao', 'saida')
            ->where('n.cancelada', false)
            ->when($clienteId, fn ($q) => $q->where('n.cliente_id', $clienteId))
            ->when($dataInicio, fn ($q) => $q->where('n.data_emissao', '>=', $dataInicio))
            ->when($dataFim, fn ($q) => $q->where('n.data_emissao', '<=', $dataFim))
            ->selectRaw('SUM(COALESCE(i.valor_pis,0)) pis, SUM(COALESCE(i.valor_cofins,0)) cofins')
            ->first();

        $valICMS = (float) ($icms->icms ?? 0);
        $valST = (float) ($icms->st ?? 0);
        $valIPI = (float) ($icms->ipi ?? 0);
        $valPIS = (float) ($pc->pis ?? 0);
        $valCOFINS = (float) ($pc->cofins ?? 0);

        return [
            'icms' => $valICMS,
            'icms_st' => $valST,
            'ipi' => $valIPI,
            'pis' => $valPIS,
            'cofins' => $valCOFINS,
            'total' => $valICMS + $valST + $valIPI + $valPIS + $valCOFINS,
        ];
    }

    /**
     * Q-CARGA (débito-saída) por mês de emissão [{mes(date), icms, icms_st, ipi, pis, cofins, total}].
     * Mesma fonte do total (C190 + itens contribuicoes), agrupada por DATE_TRUNC('month').
     */
    public function cargaTributariaDebitoSaidaMensal(int $userId, ?string $dataInicio = null, ?string $dataFim = null, ?int $clienteId = null): array
    {
        $icms = DB::table('efd_notas_consolidados as c')
            ->join('efd_notas as n', 'n.id', '=', 'c.efd_nota_id')
            ->where('c.user_id', $userId)
            ->where('n.tipo_operacao', 'saida')
            ->where('n.cancelada', false)
            ->when($clienteId, fn ($q) => $q->where('n.cliente_id', $clienteId))
            ->when($dataInicio, fn ($q) => $q->where('n.data_emissao', '>=', $dataInicio))
            ->when($dataFim, fn ($q) => $q->where('n.data_emissao', '<=', $dataFim))
            ->selectRaw("DATE_TRUNC('month', n.data_emissao) mes, SUM(COALESCE(c.valor_icms,0)) icms, SUM(COALESCE(c.valor_icms_st,0)) st, SUM(COALESCE(c.valor_ipi,0)) ipi")
            ->groupBy(DB::raw("DATE_TRUNC('month', n.data_emissao)"))
            ->get()->keyBy('mes');

        $pc = DB::table('efd_notas_itens as i')
            ->join('efd_notas as n', 'n.id', '=', 'i.efd_nota_id')
            ->where('i.user_id', $userId)
            ->where('n.origem_arquivo', 'contribuicoes')
            ->where('n.tipo_operacao', 'saida')
            ->where('n.cancelada', false)
            ->when($clienteId, fn ($q) => $q->where('n.cliente_id', $clienteId))
            ->when($dataInicio, fn ($q) => $q->where('n.data_emissao', '>=', $dataInicio))
            ->when($dataFim, fn ($q) => $q->where('n.data_emissao', '<=', $dataFim))
            ->selectRaw("DATE_TRUNC('month', n.data_emissao) mes, SUM(COALESCE(i.valor_pis,0)) pis, SUM(COALESCE(i.valor_cofins,0)) cofins")
            ->groupBy(DB::raw("DATE_TRUNC('month', n.data_emissao)"))
            ->get()->keyBy('mes');

        $meses = $icms->keys()->merge($pc->keys())->unique()->sort()->values();

        return $meses->map(function ($mes) use ($icms, $pc) {
            $i = $icms->get($mes);
            $p = $pc->get($mes);
            $valICMS = (float) ($i->icms ?? 0);
            $valST = (float) ($i->st ?? 0);
            $valIPI = (float) ($i->ipi ?? 0);
            $valPIS = (float) ($p->pis ?? 0);
            $valCOFINS = (float) ($p->cofins ?? 0);

            return [
                'mes' => $mes,
                'icms' => $valICMS, 'icms_st' => $valST, 'ipi' => $valIPI,
                'pis' => $valPIS, 'cofins' => $valCOFINS,
                'total' => $valICMS + $valST + $valIPI + $valPIS + $valCOFINS,
            ];
        })->toArray();
    }

    /**
     * Tributário crédito × débito por imposto (módulo Tributário EFD).
     * ICMS: do C190 (entrada=crédito, saída=débito) — NÃO dos itens (perfil B
     * tem ICMS≈0 no item fiscal, P2). PIS/COFINS: itens da origem 'contribuicoes'
     * (entrada=crédito, saída=débito). Exclui canceladas.
     */
    public function tributarioCreditoDebito(int $userId, ?string $dataInicio = null, ?string $dataFim = null, ?int $clienteId = null): array
    {
        $icms = DB::table('efd_notas_consolidados as c')
            ->join('efd_notas as n', 'n.id', '=', 'c.efd_nota_id')
            ->where('c.user_id', $userId)
            ->where('n.cancelada', false)
            ->when($clienteId, fn ($q) => $q->where('n.cliente_id', $clienteId))
            ->when($dataInicio, fn ($q) => $q->where('n.data_emissao', '>=', $dataInicio))
            ->when($dataFim, fn ($q) => $q->where('n.data_emissao', '<=', $dataFim))
            ->selectRaw("SUM(CASE WHEN n.tipo_operacao='entrada' THEN COALESCE(c.valor_icms,0) ELSE 0 END) credito, SUM(CASE WHEN n.tipo_operacao='saida' THEN COALESCE(c.valor_icms,0) ELSE 0 END) debito")
            ->first();

        $pc = DB::table('efd_notas_itens as i')
            ->join('efd_notas as n', 'n.id', '=', 'i.efd_nota_id')
            ->where('i.user_id', $userId)
            ->where('n.origem_arquivo', 'contribuicoes')
            ->where('n.cancelada', false)
            ->when($clienteId, fn ($q) => $q->where('n.cliente_id', $clienteId))
            ->when($dataInicio, fn ($q) => $q->where('n.data_emissao', '>=', $dataInicio))
            ->when($dataFim, fn ($q) => $q->where('n.data_emissao', '<=', $dataFim))
            ->selectRaw("SUM(CASE WHEN n.tipo_operacao='entrada' THEN COALESCE(i.valor_pis,0) ELSE 0 END) pis_credito, SUM(CASE WHEN n.tipo_operacao='saida' THEN COALESCE(i.valor_pis,0) ELSE 0 END) pis_debito, SUM(CASE WHEN n.tipo_operacao='entrada' THEN COALESCE(i.valor_cofins,0) ELSE 0 END) cofins_credito, SUM(CASE WHEN n.tipo_operacao='saida' THEN COALESCE(i.valor_cofins,0) ELSE 0 END) cofins_debito")
            ->first();

        return [
            'icms' => ['credito' => (float) ($icms->credito ?? 0), 'debito' => (float) ($icms->debito ?? 0)],
            'pis' => ['credito' => (float) ($pc->pis_credito ?? 0), 'debito' => (float) ($pc->pis_debito ?? 0)],
            'cofins' => ['credito' => (float) ($pc->cofins_credito ?? 0), 'debito' => (float) ($pc->cofins_debito ?? 0)],
        ];
    }

    /**
     * Q-CARGA (apurada) — carga líquida a recolher, declarada ao fisco (gold).
     *   ICMS/ST: efd_apuracoes_icms (E110/ST), filtrável por periodo_inicio.
     *   PIS/COFINS: efd_apuracoes_contribuicoes (M). Não tem coluna de período;
     *     filtra-se via importação quando datas são informadas (cada importação
     *     PIS/COFINS é um mês único e limpo).
     * IPI a recolher vive em jsonb (E520) — fora do escopo do perfil comercial
     * (0 na massa atual); tratado como 0 até haver EFD de indústria.
     */
    public function cargaTributariaApurada(int $userId, ?string $dataInicio = null, ?string $dataFim = null): array
    {
        $icms = DB::table('efd_apuracoes_icms')
            ->where('user_id', $userId)
            ->when($dataInicio, fn ($q) => $q->where('periodo_inicio', '>=', $dataInicio))
            ->when($dataFim, fn ($q) => $q->where('periodo_inicio', '<=', $dataFim))
            ->selectRaw('SUM(COALESCE(icms_a_recolher,0)) icms, SUM(COALESCE(st_icms_recolher,0)) st')
            ->first();

        $pc = DB::table('efd_apuracoes_contribuicoes as a')
            ->where('a.user_id', $userId)
            ->when($dataInicio || $dataFim, function ($q) use ($userId, $dataInicio, $dataFim) {
                $q->whereExists(function ($sub) use ($userId, $dataInicio, $dataFim) {
                    $sub->select(DB::raw(1))
                        ->from('efd_notas as n')
                        ->whereColumn('n.importacao_id', 'a.importacao_id')
                        ->where('n.user_id', $userId)
                        ->when($dataInicio, fn ($s) => $s->where('n.data_emissao', '>=', $dataInicio))
                        ->when($dataFim, fn ($s) => $s->where('n.data_emissao', '<=', $dataFim));
                });
            })
            ->selectRaw('SUM(COALESCE(pis_total_recolher,0)) pis, SUM(COALESCE(cofins_total_recolher,0)) cofins')
            ->first();

        $valICMS = (float) ($icms->icms ?? 0);
        $valST = (float) ($icms->st ?? 0);
        $valPIS = (float) ($pc->pis ?? 0);
        $valCOFINS = (float) ($pc->cofins ?? 0);

        return [
            'icms' => $valICMS,
            'icms_st' => $valST,
            'ipi' => 0.0,
            'pis' => $valPIS,
            'cofins' => $valCOFINS,
            'total' => $valICMS + $valST + $valPIS + $valCOFINS,
        ];
    }
}
