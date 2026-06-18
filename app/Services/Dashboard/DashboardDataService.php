<?php

namespace App\Services\Dashboard;

use App\Models\ConsultaLote;
use App\Models\ConsultaResultado;
use App\Models\EfdImportacao;
use App\Models\Participante;
use App\Models\User;
use App\Services\AlertaCentralService;
use App\Services\BiService;
use App\Services\CreditService;
use App\Services\EfdAgregadorService;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DashboardDataService
{
    public function __construct(
        protected CreditService $creditService,
        protected AlertaCentralService $alertaCentralService,
        protected EfdAgregadorService $efd,
        protected BiService $bi
    ) {}

    /** Cores (hex inline — regra do design system) por classificação de risco. */
    private const RISCO_HEX = [
        'baixo' => '#047857',
        'medio' => '#d97706',
        'alto' => '#ea580c',
        'critico' => '#dc2626',
    ];

    private const RISCO_LABEL = [
        'baixo' => 'Baixo',
        'medio' => 'Médio',
        'alto' => 'Alto',
        'critico' => 'Crítico',
    ];

    /**
     * Obtém todos os KPIs do dashboard para um usuário.
     */
    public function getKpis(int $userId, User $user): array
    {
        $mesInicio = now()->startOfMonth();
        $mesFim = now()->endOfMonth();

        // Volume processado — base canônica deduplicada (P1: mesma NF-e nas 2 origens não dobra;
        // P4: sem cancelada). Cru dava 10.118/R$53,2mi vs 7.488/R$42,6mi real.
        $volumeNotas = $this->efd->notasDedup($userId)->count();
        $volumeValor = (float) $this->efd->notasDedup($userId)->sum('n.valor_total');

        // Participantes
        $participantesTotal = $this->getTotalParticipantes($userId);
        $participantesRisco = DB::table('participante_scores')
            ->join('participantes', 'participantes.id', '=', 'participante_scores.participante_id')
            ->where('participantes.user_id', $userId)
            ->where(function ($q) {
                $q->where('participantes.origem_tipo', '!=', 'PROPRIO')
                    ->orWhereNull('participantes.origem_tipo');
            })
            ->whereIn('participante_scores.classificacao', ['alto', 'critico'])
            ->count();

        // Créditos
        $creditos = $this->creditService->getBalance($user);
        $creditosConsultas = (int) ConsultaLote::where('user_id', $userId)
            ->whereIn('status', ConsultaLote::successfulStatuses())
            ->whereBetween('created_at', [$mesInicio, $mesFim])
            ->sum('creditos_cobrados');
        $creditosImportacoes = (int) EfdImportacao::where('user_id', $userId)
            ->whereBetween('created_at', [$mesInicio, $mesFim])
            ->sum('creditos_cobrados');
        $creditosUsadosMes = $creditosConsultas + $creditosImportacoes;

        // Alertas (mesma fonte canônica da Central de Alertas)
        $resumoAlertas = $this->alertaCentralService->obterResumo($userId);
        $alertasTotal = $resumoAlertas['total_ativos'] ?? 0;
        $alertasAlta = $resumoAlertas['por_severidade']['alta'] ?? 0;

        return [
            'volume_total_notas' => $volumeNotas,
            'volume_valor_total' => $volumeValor,
            'participantes_total' => $participantesTotal,
            'participantes_risco' => $participantesRisco,
            'creditos' => $creditos,
            'creditos_usados_mes' => $creditosUsadosMes,
            'alertas_total' => $alertasTotal,
            'alertas_alta' => $alertasAlta,
        ];
    }

    /** Participantes (não-próprios) com score alto/crítico, opcionalmente por cliente. */
    public function contarRisco(int $userId, ?int $clienteId = null): int
    {
        return DB::table('participante_scores')
            ->join('participantes', 'participantes.id', '=', 'participante_scores.participante_id')
            ->where('participantes.user_id', $userId)
            ->when($clienteId, fn ($q) => $q->where('participantes.cliente_id', $clienteId))
            ->where(function ($q) {
                $q->where('participantes.origem_tipo', '!=', 'PROPRIO')
                    ->orWhereNull('participantes.origem_tipo');
            })
            ->whereIn('participante_scores.classificacao', ['alto', 'critico'])
            ->count();
    }

    /** Linhas de "precisa de atenção" (count + link), ordem fixa por severidade. */
    public function getTriagem(int $userId, ?int $clienteId = null): array
    {
        $alertasAlta = $this->alertaCentralService->obterResumo($userId)['por_severidade']['alta'] ?? 0;

        $importsErro = EfdImportacao::where('user_id', $userId)
            ->where('status', 'erro')
            ->when($clienteId, fn ($q) => $q->where('cliente_id', $clienteId))
            ->count();

        $certidoesDesatualizadas = ConsultaResultado::query()
            ->join('participantes', 'participantes.id', '=', 'consulta_resultados.participante_id')
            ->where('participantes.user_id', $userId)
            ->when($clienteId, fn ($q) => $q->where('participantes.cliente_id', $clienteId))
            ->where('consulta_resultados.status', ConsultaResultado::STATUS_SUCESSO)
            ->where('consulta_resultados.consultado_em', '<', now()->subDays(30))
            ->distinct('consulta_resultados.participante_id')
            ->count('consulta_resultados.participante_id');

        return [
            ['chave' => 'alertas_alta', 'label' => 'Alertas alta severidade',        'count' => (int) $alertasAlta,             'hex' => '#dc2626', 'url' => '/app/alertas'],
            ['chave' => 'certidoes',    'label' => 'Certidões desatualizadas (+30d)', 'count' => (int) $certidoesDesatualizadas, 'hex' => '#d97706', 'url' => '/app/score-fiscal'],
            ['chave' => 'imports_erro', 'label' => 'Importações com erro',            'count' => (int) $importsErro,             'hex' => '#d97706', 'url' => '/app/importacao/historico'],
            ['chave' => 'risco',        'label' => 'Participantes em risco',          'count' => $this->contarRisco($userId, $clienteId), 'hex' => '#ca8a04', 'url' => '/app/score-fiscal'],
        ];
    }

    /** Créditos consumidos no mês corrente (consultas + importações), igual ao KPI legado. */
    private function creditosUsadosMes(int $userId): int
    {
        $ini = now()->startOfMonth();
        $fim = now()->endOfMonth();

        $consultas = (int) ConsultaLote::where('user_id', $userId)
            ->whereIn('status', ConsultaLote::successfulStatuses())
            ->whereBetween('created_at', [$ini, $fim])
            ->sum('creditos_cobrados');
        $importacoes = (int) EfdImportacao::where('user_id', $userId)
            ->whereBetween('created_at', [$ini, $fim])
            ->sum('creditos_cobrados');

        return $consultas + $importacoes;
    }

    /** 3 KPIs enxutos do cockpit, filtráveis por cliente/período. */
    public function getCockpitKpis(int $userId, User $user, ?int $clienteId, ?string $dataInicio, ?string $dataFim): array
    {
        $volNotas = $this->efd->notasDedup($userId, null, $dataInicio, $dataFim, $clienteId)->count();
        $volValor = (float) $this->efd->notasDedup($userId, null, $dataInicio, $dataFim, $clienteId)->sum('n.valor_total');

        $alertasAlta = (int) ($this->alertaCentralService->obterResumo($userId)['por_severidade']['alta'] ?? 0);
        $risco = $this->contarRisco($userId, $clienteId);

        return [
            'volume'   => ['notas' => $volNotas, 'valor' => $volValor],
            'saude'    => ['total' => $alertasAlta + $risco, 'alertas_alta' => $alertasAlta, 'risco' => $risco],
            'creditos' => ['saldo' => $this->creditService->getBalance($user), 'usados_mes' => $this->creditosUsadosMes($userId)],
        ];
    }

    /** Distribuição de participantes por classificação de risco (donut). Ordem canônica. */
    public function getRiscoDistribuicao(int $userId, ?int $clienteId = null): array
    {
        $contagem = DB::table('participante_scores')
            ->join('participantes', 'participantes.id', '=', 'participante_scores.participante_id')
            ->where('participantes.user_id', $userId)
            ->when($clienteId, fn ($q) => $q->where('participantes.cliente_id', $clienteId))
            ->where(function ($q) {
                $q->where('participantes.origem_tipo', '!=', 'PROPRIO')
                    ->orWhereNull('participantes.origem_tipo');
            })
            ->selectRaw('participante_scores.classificacao, COUNT(*) as total')
            ->groupBy('participante_scores.classificacao')
            ->pluck('total', 'classificacao');

        $out = [];
        foreach (['baixo', 'medio', 'alto', 'critico'] as $chave) {
            $valor = (int) ($contagem[$chave] ?? 0);
            if ($valor > 0) {
                $out[] = ['label' => self::RISCO_LABEL[$chave], 'valor' => $valor, 'hex' => self::RISCO_HEX[$chave]];
            }
        }

        return $out;
    }

    /** Tendência entrada × saída por mês, alinhada num eixo único do período. */
    private function tendenciaEntradaSaida(int $userId, string $dataInicio, string $dataFim, ?int $clienteId): array
    {
        $chave = fn ($mes) => Carbon::parse($mes)->format('Y-m');
        $saida = collect($this->efd->faturamentoMensal($userId, 'saida', $dataInicio, $dataFim, $clienteId))->keyBy(fn ($r) => $chave($r['mes']));
        $entrada = collect($this->efd->faturamentoMensal($userId, 'entrada', $dataInicio, $dataFim, $clienteId))->keyBy(fn ($r) => $chave($r['mes']));

        $cursor = Carbon::parse($dataInicio)->startOfMonth();
        $fim = Carbon::parse($dataFim)->startOfMonth();
        $meses = [];
        $saidaValor = [];
        $saidaQtd = [];
        $entradaValor = [];
        $entradaQtd = [];
        while ($cursor <= $fim) {
            $k = $cursor->format('Y-m');
            $meses[] = $cursor->translatedFormat('M/y');
            $saidaValor[] = (float) ($saida[$k]['valor'] ?? 0);
            $saidaQtd[] = (int) ($saida[$k]['qtd'] ?? 0);
            $entradaValor[] = (float) ($entrada[$k]['valor'] ?? 0);
            $entradaQtd[] = (int) ($entrada[$k]['qtd'] ?? 0);
            $cursor->addMonth();
        }

        return [
            'meses' => $meses,
            'saida_valor' => $saidaValor,
            'saida_qtd' => $saidaQtd,
            'entrada_valor' => $entradaValor,
            'entrada_qtd' => $entradaQtd,
        ];
    }

    /** Assembler único do cockpit: usado pela 1ª pintura (controller) e pelo endpoint JSON. */
    public function cockpit(int $userId, User $user, ?int $clienteId, int $periodo): array
    {
        $periodo = in_array($periodo, [3, 6, 12], true) ? $periodo : 6;
        $dataInicio = now()->subMonths($periodo - 1)->startOfMonth()->toDateString();
        $dataFim = now()->endOfMonth()->toDateString();

        $bloco = fn (callable $fn, $fallback) => rescue($fn, $fallback, report: false);

        $kpis = $bloco(
            fn () => $this->getCockpitKpis($userId, $user, $clienteId, $dataInicio, $dataFim),
            ['volume' => ['notas' => 0, 'valor' => 0.0], 'saude' => ['total' => 0, 'alertas_alta' => 0, 'risco' => 0], 'creditos' => ['saldo' => 0, 'usados_mes' => 0]]
        );

        $triagem = $bloco(fn () => $this->getTriagem($userId, $clienteId), []);
        $tendencia = $bloco(fn () => $this->tendenciaEntradaSaida($userId, $dataInicio, $dataFim, $clienteId),
            ['meses' => [], 'saida_valor' => [], 'saida_qtd' => [], 'entrada_valor' => [], 'entrada_qtd' => []]);
        $topFornecedores = $bloco(fn () => $this->bi->getTopFornecedores($userId, 5, $dataInicio, $dataFim, $clienteId), []);
        $riscoDistribuicao = $bloco(fn () => $this->getRiscoDistribuicao($userId, $clienteId), []);

        return [
            'kpis' => $kpis,
            'triagem' => $triagem,
            'tendencia' => $tendencia,
            'top_fornecedores' => $topFornecedores,
            'risco_distribuicao' => $riscoDistribuicao,
            'meta' => ['cliente' => $clienteId, 'periodo' => $periodo],
        ];
    }

    /**
     * Retorna atividade recente mesclando importações e consultas.
     */
    public function getAtividadeRecente(int $userId): Collection
    {
        $importacoes = EfdImportacao::where('user_id', $userId)
            ->latest()
            ->limit(3)
            ->get()
            ->map(fn ($i) => [
                'tipo' => 'importacao',
                'descricao' => $i->filename ?? 'Importação EFD',
                'tipo_efd' => $i->tipo_efd,
                'status' => $i->status,
                'data' => $i->created_at,
            ]);

        $consultas = ConsultaLote::where('user_id', $userId)
            ->with('plano:id,nome,codigo')
            ->latest()
            ->limit(3)
            ->get()
            ->map(fn ($c) => [
                'tipo' => 'consulta',
                'descricao' => $c->plano?->nome ?? 'Consulta',
                'status' => $c->status,
                'data' => $c->created_at,
            ]);

        return $importacoes->concat($consultas)
            ->sortByDesc('data')
            ->take(5)
            ->values();
    }

    /**
     * Verifica se o usuário é novo (sem participantes e sem importações).
     */
    public function isUsuarioNovo(int $userId): bool
    {
        $participantesReais = Participante::where('user_id', $userId)
            ->where(function ($q) {
                $q->where('origem_tipo', '!=', 'PROPRIO')
                    ->orWhereNull('origem_tipo');
            })
            ->count();

        return $participantesReais === 0
            && EfdImportacao::where('user_id', $userId)->count() === 0;
    }

    /**
     * Retorna a última importação EFD do usuário.
     */
    public function getUltimaImportacao(int $userId): ?EfdImportacao
    {
        return EfdImportacao::where('user_id', $userId)
            ->latest()
            ->first();
    }

    /**
     * Retorna o total de participantes do usuário.
     */
    public function getTotalParticipantes(int $userId): int
    {
        return Participante::where('user_id', $userId)
            ->where(function ($q) {
                $q->where('origem_tipo', '!=', 'PROPRIO')
                    ->orWhereNull('origem_tipo');
            })
            ->count();
    }

    /**
     * Retorna participantes paginados com score, ordenados por risco (mais arriscados primeiro).
     */
    public function getParticipantesPaginados(int $userId, ?string $busca = null, int $perPage = 20): LengthAwarePaginator
    {
        return Participante::where('participantes.user_id', $userId)
            ->with('score')
            ->leftJoin('participante_scores', 'participantes.id', '=', 'participante_scores.participante_id')
            ->select('participantes.*')
            ->when($busca, function ($q) use ($busca) {
                $busca = trim($busca);
                $cnpjLimpo = preg_replace('/[^0-9]/', '', $busca);

                $q->where(function ($q) use ($busca, $cnpjLimpo) {
                    $q->where('participantes.documento', 'like', "%{$cnpjLimpo}%")
                        ->orWhere('participantes.razao_social', 'ilike', "%{$busca}%")
                        ->orWhere('participantes.nome_fantasia', 'ilike', "%{$busca}%");
                });
            })
            ->orderByDesc('participante_scores.score_total')
            ->orderBy('participantes.razao_social')
            ->paginate($perPage)
            ->withQueryString();
    }
}
