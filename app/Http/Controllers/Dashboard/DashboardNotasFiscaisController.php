<?php

namespace App\Http\Controllers\Dashboard;

use App\Helpers\CfopHelper;
use App\Helpers\CstIcmsHelper;
use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\EfdImportacao;
use App\Models\EfdNota;
use App\Models\Participante;
use App\Services\NotasFiscaisAlertService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardNotasFiscaisController extends Controller
{
    private const VIEW = 'autenticado.notas-fiscais.dashboard';
    private const LAYOUT = 'autenticado.layouts.app';

    public function index(Request $request)
    {
        $userId = Auth::id();

        $importacoes = EfdImportacao::where('user_id', $userId)
            ->where('status', 'concluido')
            ->orderByDesc('concluido_em')
            ->get(['id', 'filename', 'tipo_efd', 'concluido_em']);

        // Detectar range real dos dados do usuário
        $dateRange = EfdNota::where('user_id', $userId)
            ->selectRaw('MIN(data_emissao) as min_date, MAX(data_emissao) as max_date')
            ->first();

        if ($dateRange && $dateRange->min_date) {
            $inicio = Carbon::parse($dateRange->min_date)->startOfMonth()->format('Y-m');
            $fim = Carbon::parse($dateRange->max_date)->endOfMonth()->format('Y-m');
        } else {
            $inicio = Carbon::now()->subMonths(12)->startOfMonth()->format('Y-m');
            $fim = Carbon::now()->format('Y-m');
        }

        $clientes = Cliente::where('user_id', $userId)
            ->where('ativo', true)
            ->select('id', 'nome', 'razao_social', 'is_empresa_propria')
            ->orderBy('razao_social')
            ->get();

        $participantes = Participante::where('user_id', $userId)
            ->whereHas('efdNotas')
            ->select('id', 'razao_social', 'documento as cnpj')
            ->orderBy('razao_social')
            ->get();

        $data = [
            'importacoes' => $importacoes,
            'clientes' => $clientes,
            'participantes' => $participantes,
            'defaultTab' => 'visao-geral',
            'filtros' => [
                'periodo_inicio' => $inicio,
                'periodo_fim' => $fim,
                'tipo_efd' => 'todos',
                'importacao_id' => null,
                'cliente_id' => null,
                'participante_id' => null,
            ],
        ];

        if ($this->isAjaxRequest($request)) {
            return view(self::VIEW, $data);
        }

        return view(self::LAYOUT, ['initialView' => self::VIEW, ...$data]);
    }

    public function visaoGeral(Request $request)
    {
        $userId = Auth::id();
        $filtros = $this->parseFiltros($request);

        $base = EfdNota::where('user_id', $userId);
        $this->aplicarFiltros($base, $filtros);

        // KPIs agregados
        $kpis = (clone $base)
            ->selectRaw("
                COUNT(*) as total_notas,
                SUM(CASE WHEN tipo_operacao = 'entrada' THEN valor_total ELSE 0 END) as valor_entradas,
                SUM(CASE WHEN tipo_operacao = 'saida' THEN valor_total ELSE 0 END) as valor_saidas,
                COUNT(DISTINCT participante_id) as participantes_unicos
            ")
            ->first();

        // Evolução temporal por mês
        $evolucao = (clone $base)
            ->selectRaw("
                TO_CHAR(data_emissao, 'YYYY-MM') as mes,
                SUM(CASE WHEN tipo_operacao = 'entrada' THEN valor_total ELSE 0 END) as entradas,
                SUM(CASE WHEN tipo_operacao = 'saida' THEN valor_total ELSE 0 END) as saidas
            ")
            ->groupByRaw("TO_CHAR(data_emissao, 'YYYY-MM')")
            ->orderByRaw("TO_CHAR(data_emissao, 'YYYY-MM')")
            ->get();

        // Breakdown por modelo de documento (com entradas/saidas)
        $porModeloRaw = (clone $base)
            ->selectRaw("modelo, tipo_operacao, COUNT(*) as quantidade, SUM(valor_total) as valor_total")
            ->groupBy('modelo', 'tipo_operacao')
            ->get();

        $totalGeral = (float) $porModeloRaw->sum('valor_total');

        $porModelo = $porModeloRaw
            ->groupBy('modelo')
            ->map(function ($rows, $modelo) use ($totalGeral) {
                $entradas = $rows->firstWhere('tipo_operacao', 'entrada');
                $saidas = $rows->firstWhere('tipo_operacao', 'saida');

                $qtdEnt = $entradas ? (int) $entradas->quantidade : 0;
                $qtdSai = $saidas ? (int) $saidas->quantidade : 0;
                $valEnt = $entradas ? (float) $entradas->valor_total : 0;
                $valSai = $saidas ? (float) $saidas->valor_total : 0;
                $valTot = $valEnt + $valSai;

                return [
                    'modelo' => $modelo,
                    'label' => EfdNota::make(['modelo' => $modelo])->modelo_doc_formatado,
                    'quantidade' => $qtdEnt + $qtdSai,
                    'valor_total' => $valTot,
                    'entradas' => ['quantidade' => $qtdEnt, 'valor' => $valEnt],
                    'saidas' => ['quantidade' => $qtdSai, 'valor' => $valSai],
                    'percentual' => $totalGeral > 0 ? round(($valTot / $totalGeral) * 100, 1) : 0,
                ];
            })
            ->sortByDesc('valor_total')
            ->values();

        // Top 5 participantes por volume
        $topParticipantes = (clone $base)
            ->whereNotNull('participante_id')
            ->selectRaw("participante_id, COUNT(*) as total_notas, SUM(valor_total) as valor_total")
            ->groupBy('participante_id')
            ->orderByDesc('valor_total')
            ->limit(5)
            ->get();

        $partIds = $topParticipantes->pluck('participante_id');
        $participantes = Participante::whereIn('id', $partIds)
            ->get(['id', 'razao_social', 'documento as cnpj'])
            ->keyBy('id');

        $topPart = $topParticipantes->map(fn ($r) => [
            'participante_id' => $r->participante_id,
            'razao_social' => $participantes[$r->participante_id]?->razao_social ?? 'N/A',
            'cnpj' => $participantes[$r->participante_id]?->cnpj_formatado ?? '',
            'total_notas' => (int) $r->total_notas,
            'valor_total' => (float) $r->valor_total,
        ]);

        return response()->json([
            'kpis' => [
                'total_notas' => (int) $kpis->total_notas,
                'valor_entradas' => (float) $kpis->valor_entradas,
                'valor_saidas' => (float) $kpis->valor_saidas,
                'saldo' => (float) $kpis->valor_entradas - (float) $kpis->valor_saidas,
                'participantes_unicos' => (int) $kpis->participantes_unicos,
            ],
            'evolucao' => $evolucao,
            'por_modelo' => $porModelo,
            'top_participantes' => $topPart,
        ]);
    }

    public function cfop(Request $request)
    {
        $userId = Auth::id();
        $filtros = $this->parseFiltros($request);

        $baseNotas = EfdNota::where('user_id', $userId);
        $this->aplicarFiltros($baseNotas, $filtros);
        $notaIds = (clone $baseNotas)->select('id');

        $baseItens = DB::table('efd_notas_itens')
            ->whereIn('efd_nota_id', $notaIds)
            ->whereNotNull('cfop');

        $totalGeral = (clone $baseItens)->count();

        $rows = (clone $baseItens)
            ->selectRaw("cfop, COUNT(*) as qtd_itens, SUM(COALESCE(valor_total, 0)) as valor_total")
            ->groupBy('cfop')
            ->orderByDesc('valor_total')
            ->get();

        $valorGeral = (float) $rows->sum('valor_total');

        $cfops = $rows->map(fn ($r) => [
            'cfop' => (int) $r->cfop,
            'descricao' => CfopHelper::descricao($r->cfop),
            'qtd_itens' => (int) $r->qtd_itens,
            'valor_total' => (float) $r->valor_total,
            'percentual' => $valorGeral > 0 ? round(((float) $r->valor_total / $valorGeral) * 100, 1) : 0,
            'tipo' => CfopHelper::tipo($r->cfop),
            'natureza' => CfopHelper::natureza($r->cfop),
        ]);

        $entradas = $cfops->where('tipo', 'entrada');
        $saidas = $cfops->where('tipo', 'saida');

        return response()->json([
            'resumo' => [
                'entradas' => [
                    'qtd_cfops' => $entradas->count(),
                    'qtd_itens' => (int) $entradas->sum('qtd_itens'),
                    'valor_total' => (float) $entradas->sum('valor_total'),
                ],
                'saidas' => [
                    'qtd_cfops' => $saidas->count(),
                    'qtd_itens' => (int) $saidas->sum('qtd_itens'),
                    'valor_total' => (float) $saidas->sum('valor_total'),
                ],
                'total_itens' => $totalGeral,
            ],
            'cfops' => $cfops->values(),
        ]);
    }

    public function participantes(Request $request)
    {
        $userId = Auth::id();
        $filtros = $this->parseFiltros($request);
        $tipoFiltro = $request->query('tipo', 'todos');
        $busca = $request->query('busca');
        $page = max(1, (int) $request->query('page', 1));
        $perPage = 15;

        $baseNotas = EfdNota::where('user_id', $userId)->whereNotNull('participante_id');
        $this->aplicarFiltros($baseNotas, $filtros);

        $agregado = (clone $baseNotas)
            ->selectRaw("
                participante_id,
                COUNT(*) as total_notas,
                SUM(CASE WHEN tipo_operacao = 'entrada' THEN valor_total ELSE 0 END) as valor_entradas,
                SUM(CASE WHEN tipo_operacao = 'saida' THEN valor_total ELSE 0 END) as valor_saidas,
                SUM(valor_total) as valor_total,
                MIN(data_emissao) as primeira_nota,
                MAX(data_emissao) as ultima_nota,
                bool_or(tipo_operacao = 'entrada') as tem_entradas,
                bool_or(tipo_operacao = 'saida') as tem_saidas
            ")
            ->groupBy('participante_id');

        $subQuery = DB::query()->fromSub($agregado, 'agg');

        if ($tipoFiltro === 'fornecedor') {
            $subQuery->where('tem_entradas', true);
        } elseif ($tipoFiltro === 'cliente') {
            $subQuery->where('tem_saidas', true);
        }

        if (! empty($busca)) {
            $buscaTerm = '%' . $busca . '%';
            $subQuery->join('participantes', 'participantes.id', '=', 'agg.participante_id')
                ->where(function ($q) use ($buscaTerm) {
                    $q->whereRaw('LOWER(participantes.razao_social) LIKE LOWER(?)', [$buscaTerm])
                      ->orWhere('participantes.documento', 'like', $buscaTerm);
                });
        }

        $todosAgregados = (clone $subQuery)->get();
        $totalParticipantes = $todosAgregados->count();
        $totalFornecedores = $todosAgregados->where('tem_entradas', true)->count();
        $totalClientes = $todosAgregados->where('tem_saidas', true)->count();

        $concentracao = $this->calcularConcentracao($todosAgregados);

        $total = $totalParticipantes;
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = min($page, $lastPage);

        $paginados = (clone $subQuery)
            ->orderByDesc('valor_total')
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get();

        $partIds = $paginados->pluck('participante_id');
        $participantesMap = Participante::whereIn('id', $partIds)
            ->get(['id', 'razao_social', 'documento as cnpj', 'uf', 'situacao_cadastral', 'regime_tributario', 'ultima_consulta_em'])
            ->keyBy('id');

        $data = $paginados->map(function ($r) use ($participantesMap) {
            $p = $participantesMap[$r->participante_id] ?? null;
            $papel = 'ambos';
            if ($r->tem_entradas && ! $r->tem_saidas) {
                $papel = 'fornecedor';
            } elseif (! $r->tem_entradas && $r->tem_saidas) {
                $papel = 'cliente';
            }

            return [
                'participante_id' => $r->participante_id,
                'razao_social' => $p?->razao_social ?? 'N/A',
                'cnpj' => $p?->cnpj_formatado ?? '',
                'uf' => $p?->uf ?? '',
                'total_notas' => (int) $r->total_notas,
                'valor_entradas' => (float) $r->valor_entradas,
                'valor_saidas' => (float) $r->valor_saidas,
                'valor_total' => (float) $r->valor_total,
                'primeira_nota' => $r->primeira_nota,
                'ultima_nota' => $r->ultima_nota,
                'papel' => $papel,
                'situacao_cadastral' => $p?->situacao_cadastral,
                'regime_tributario' => $p?->regime_tributario,
                'ultima_consulta_em' => $p?->ultima_consulta_em?->toIso8601String(),
            ];
        });

        return response()->json([
            'resumo' => [
                'total_participantes' => $totalParticipantes,
                'total_fornecedores' => $totalFornecedores,
                'total_clientes' => $totalClientes,
                'concentracao' => $concentracao,
            ],
            'participantes' => [
                'current_page' => $page,
                'last_page' => $lastPage,
                'per_page' => $perPage,
                'total' => $total,
                'data' => $data,
            ],
        ]);
    }

    public function tributario(Request $request)
    {
        $userId = Auth::id();
        $filtros = $this->parseFiltros($request);

        $baseNotas = EfdNota::where('user_id', $userId);
        $this->aplicarFiltros($baseNotas, $filtros);
        $notaIdsSub = (clone $baseNotas)->select('id');

        // Saldos gerais agrupados por tipo_operacao
        $saldosRaw = DB::table('efd_notas_itens as i')
            ->join('efd_notas as n', 'n.id', '=', 'i.efd_nota_id')
            ->whereIn('i.efd_nota_id', $notaIdsSub)
            ->selectRaw("
                n.tipo_operacao,
                COALESCE(SUM(i.valor_icms), 0) as total_icms,
                COALESCE(SUM(i.valor_pis), 0) as total_pis,
                COALESCE(SUM(i.valor_cofins), 0) as total_cofins,
                COUNT(*) as total_itens,
                SUM(CASE WHEN i.valor_pis IS NULL OR i.valor_pis = 0 THEN 1 ELSE 0 END) as pis_vazios,
                SUM(CASE WHEN i.valor_cofins IS NULL OR i.valor_cofins = 0 THEN 1 ELSE 0 END) as cofins_vazios
            ")
            ->groupBy('n.tipo_operacao')
            ->get()
            ->keyBy('tipo_operacao');

        $vazio = (object) ['total_icms' => 0, 'total_pis' => 0, 'total_cofins' => 0, 'total_itens' => 0, 'pis_vazios' => 0, 'cofins_vazios' => 0];
        $entrada = $saldosRaw['entrada'] ?? $vazio;
        $saida = $saldosRaw['saida'] ?? $vazio;

        $icmsDebito = (float) $saida->total_icms;
        $icmsCredito = (float) $entrada->total_icms;
        $pisDebito = (float) $saida->total_pis;
        $pisCredito = (float) $entrada->total_pis;
        $cofinsDebito = (float) $saida->total_cofins;
        $cofinsCredito = (float) $entrada->total_cofins;

        // Detecção de PIS/COFINS incompleto (>70% dos itens com valor zero/null)
        $totalItens = (int) $entrada->total_itens + (int) $saida->total_itens;
        $pisVazios = (int) $entrada->pis_vazios + (int) $saida->pis_vazios;
        $cofinsVazios = (int) $entrada->cofins_vazios + (int) $saida->cofins_vazios;
        $alertaPisCofins = $totalItens > 0 && (($pisVazios / $totalItens) > 0.7 || ($cofinsVazios / $totalItens) > 0.7);

        // Evolução mensal
        $evolucao = DB::table('efd_notas_itens as i')
            ->join('efd_notas as n', 'n.id', '=', 'i.efd_nota_id')
            ->whereIn('i.efd_nota_id', (clone $baseNotas)->select('id'))
            ->selectRaw("
                TO_CHAR(n.data_emissao, 'YYYY-MM') as mes,
                SUM(CASE WHEN n.tipo_operacao = 'saida' THEN COALESCE(i.valor_icms, 0) ELSE 0 END) as icms_debito,
                SUM(CASE WHEN n.tipo_operacao = 'entrada' THEN COALESCE(i.valor_icms, 0) ELSE 0 END) as icms_credito,
                SUM(CASE WHEN n.tipo_operacao = 'saida' THEN COALESCE(i.valor_pis, 0) ELSE 0 END) as pis_debito,
                SUM(CASE WHEN n.tipo_operacao = 'entrada' THEN COALESCE(i.valor_pis, 0) ELSE 0 END) as pis_credito,
                SUM(CASE WHEN n.tipo_operacao = 'saida' THEN COALESCE(i.valor_cofins, 0) ELSE 0 END) as cofins_debito,
                SUM(CASE WHEN n.tipo_operacao = 'entrada' THEN COALESCE(i.valor_cofins, 0) ELSE 0 END) as cofins_credito
            ")
            ->groupByRaw("TO_CHAR(n.data_emissao, 'YYYY-MM')")
            ->orderByRaw("TO_CHAR(n.data_emissao, 'YYYY-MM')")
            ->get();

        // Tabela consolidada por período (reusar evolução)
        $porPeriodo = $evolucao->map(fn ($e) => [
            'mes' => $e->mes,
            'icms_debito' => (float) $e->icms_debito,
            'icms_credito' => (float) $e->icms_credito,
            'saldo_icms' => (float) $e->icms_debito - (float) $e->icms_credito,
            'pis_debito' => (float) $e->pis_debito,
            'pis_credito' => (float) $e->pis_credito,
            'saldo_pis' => (float) $e->pis_debito - (float) $e->pis_credito,
            'cofins_debito' => (float) $e->cofins_debito,
            'cofins_credito' => (float) $e->cofins_credito,
            'saldo_cofins' => (float) $e->cofins_debito - (float) $e->cofins_credito,
        ]);

        // Análise por CST ICMS
        $csts = DB::table('efd_notas_itens as i')
            ->whereIn('i.efd_nota_id', (clone $baseNotas)->select('id'))
            ->selectRaw("i.cst_icms as cst, COUNT(*) as qtd_itens, COALESCE(SUM(i.valor_total), 0) as valor_total")
            ->groupBy('i.cst_icms')
            ->orderByDesc('valor_total')
            ->get()
            ->map(fn ($r) => [
                'cst' => $r->cst,
                'descricao' => CstIcmsHelper::descricao($r->cst),
                'qtd_itens' => (int) $r->qtd_itens,
                'valor_total' => (float) $r->valor_total,
            ]);

        return response()->json([
            'saldos' => [
                'icms' => ['debito' => $icmsDebito, 'credito' => $icmsCredito, 'saldo' => $icmsDebito - $icmsCredito],
                'pis' => ['debito' => $pisDebito, 'credito' => $pisCredito, 'saldo' => $pisDebito - $pisCredito],
                'cofins' => ['debito' => $cofinsDebito, 'credito' => $cofinsCredito, 'saldo' => $cofinsDebito - $cofinsCredito],
            ],
            'alerta_pis_cofins' => $alertaPisCofins,
            'evolucao' => $evolucao,
            'por_periodo' => $porPeriodo,
            'csts' => $csts,
        ]);
    }

    public function alertas(Request $request, NotasFiscaisAlertService $alertService)
    {
        $userId = Auth::id();
        $filtros = $this->parseFiltros($request);

        return response()->json($alertService->detectar($userId, $filtros));
    }

    public function compliance(Request $request)
    {
        $userId = Auth::id();
        $filtros = $this->parseFiltros($request);

        $baseNotas = EfdNota::where('user_id', $userId);
        $this->aplicarFiltros($baseNotas, $filtros);

        $participanteIds = (clone $baseNotas)
            ->whereNotNull('participante_id')
            ->distinct()
            ->pluck('participante_id');

        if ($participanteIds->isEmpty()) {
            return response()->json([
                'kpis' => [
                    'total' => 0,
                    'consultados' => 0,
                    'consultados_pct' => 0,
                    'regulares' => 0,
                    'regulares_pct' => 0,
                    'irregulares' => 0,
                    'exposicao' => 0,
                    'nao_consultados' => 0,
                ],
                'participantes' => [],
            ]);
        }

        $volumes = (clone $baseNotas)
            ->whereNotNull('participante_id')
            ->selectRaw('participante_id, SUM(valor_total) as volume, COUNT(*) as total_notas')
            ->groupBy('participante_id')
            ->get()
            ->keyBy('participante_id');

        $participantes = Participante::whereIn('id', $participanteIds)
            ->select('id', 'documento as cnpj', 'razao_social', 'situacao_cadastral', 'regime_tributario', 'uf', 'ultima_consulta_em')
            ->get()
            ->map(function ($p) use ($volumes) {
                $vol = $volumes->get($p->id);
                $volume = $vol ? (float) $vol->volume : 0;
                $totalNotas = $vol ? (int) $vol->total_notas : 0;
                $situacao = $p->situacao_cadastral;
                $irregular = $situacao && $situacao !== 'ATIVA';

                return [
                    'id' => $p->id,
                    'cnpj' => $p->documento,
                    'razao_social' => $p->razao_social ?? 'Não informado',
                    'situacao_cadastral' => $situacao,
                    'regime_tributario' => $p->regime_tributario,
                    'uf' => $p->uf,
                    'ultima_consulta_em' => $p->ultima_consulta_em?->format('d/m/Y'),
                    'volume' => $volume,
                    'total_notas' => $totalNotas,
                    'irregular' => $irregular,
                    'exposicao' => $irregular ? $volume : 0,
                ];
            })
            ->sortByDesc('volume')
            ->values();

        $total = $participantes->count();
        $consultados = $participantes->where('ultima_consulta_em', '!=', null)->count();
        $regulares = $participantes->where('situacao_cadastral', 'ATIVA')->count();
        $irregulares = $participantes->filter(fn ($p) => $p['irregular'])->count();
        $exposicao = $participantes->sum('exposicao');
        $naoConsultados = $participantes->whereNull('ultima_consulta_em')->count();

        return response()->json([
            'kpis' => [
                'total' => $total,
                'consultados' => $consultados,
                'consultados_pct' => $total > 0 ? round(($consultados / $total) * 100, 1) : 0,
                'regulares' => $regulares,
                'regulares_pct' => $consultados > 0 ? round(($regulares / $consultados) * 100, 1) : 0,
                'irregulares' => $irregulares,
                'exposicao' => $exposicao,
                'nao_consultados' => $naoConsultados,
            ],
            'participantes' => $participantes,
        ]);
    }

    private function calcularConcentracao($agregados): array
    {
        $totalEntradas = (float) $agregados->sum('valor_entradas');
        $totalSaidas = (float) $agregados->sum('valor_saidas');

        $sortedEntradas = $agregados->sortByDesc('valor_entradas')->values();
        $top5Entradas = (float) $sortedEntradas->take(5)->sum('valor_entradas');
        $top10Entradas = (float) $sortedEntradas->take(10)->sum('valor_entradas');

        $sortedSaidas = $agregados->sortByDesc('valor_saidas')->values();
        $top5Saidas = (float) $sortedSaidas->take(5)->sum('valor_saidas');
        $top10Saidas = (float) $sortedSaidas->take(10)->sum('valor_saidas');

        return [
            'top5_entradas_pct' => $totalEntradas > 0 ? round(($top5Entradas / $totalEntradas) * 100, 1) : 0,
            'top5_saidas_pct' => $totalSaidas > 0 ? round(($top5Saidas / $totalSaidas) * 100, 1) : 0,
            'top10_entradas_pct' => $totalEntradas > 0 ? round(($top10Entradas / $totalEntradas) * 100, 1) : 0,
            'top10_saidas_pct' => $totalSaidas > 0 ? round(($top10Saidas / $totalSaidas) * 100, 1) : 0,
        ];
    }

    private function parseFiltros(Request $request): array
    {
        return [
            'periodo_inicio' => $request->query('periodo_inicio'),
            'periodo_fim' => $request->query('periodo_fim'),
            'tipo_efd' => $request->query('tipo_efd'),
            'importacao_id' => $request->query('importacao_id'),
            'cliente_id' => $request->query('cliente_id'),
            'participante_id' => $request->query('participante_id'),
        ];
    }

    private function aplicarFiltros($query, array $filtros): void
    {
        if (! empty($filtros['periodo_inicio'])) {
            $query->where('data_emissao', '>=', $filtros['periodo_inicio'] . '-01');
        }
        if (! empty($filtros['periodo_fim'])) {
            $fim = Carbon::parse($filtros['periodo_fim'] . '-01')->endOfMonth();
            $query->where('data_emissao', '<=', $fim);
        }
        if (! empty($filtros['tipo_efd']) && $filtros['tipo_efd'] !== 'todos') {
            $origemMap = [
                'EFD ICMS/IPI' => 'fiscal',
                'EFD PIS/COFINS' => 'contribuicoes',
            ];
            if (isset($origemMap[$filtros['tipo_efd']])) {
                $query->where('origem_arquivo', $origemMap[$filtros['tipo_efd']]);
            }
        }
        if (! empty($filtros['importacao_id'])) {
            $query->where('importacao_id', $filtros['importacao_id']);
        }
        if (! empty($filtros['cliente_id'])) {
            $query->where('cliente_id', $filtros['cliente_id']);
        }
        if (! empty($filtros['participante_id'])) {
            $query->where('participante_id', $filtros['participante_id']);
        }
    }

    private function isAjaxRequest(Request $request): bool
    {
        if (method_exists($request, 'ajax') && $request->ajax()) {
            return true;
        }

        return $request->header('X-Requested-With') === 'XMLHttpRequest'
            || $request->wantsJson()
            || $request->expectsJson();
    }
}
