<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Services\BiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BiController extends Controller
{
    private const AUTH_VIEW_PREFIX = 'autenticado.bi.';

    private const AUTH_LAYOUT_VIEW = 'autenticado.layouts.app';

    public function __construct(
        protected BiService $biService
    ) {}

    /**
     * Dashboard principal de Analytics.
     */
    public function index(Request $request)
    {
        if (! Auth::check()) {
            return $this->redirectToLogin($request);
        }

        $userId = Auth::id();

        // Buscar clientes para filtro
        $clientes = Cliente::where('user_id', $userId)
            ->where('ativo', true)
            ->select('id', 'nome', 'documento', 'is_empresa_propria')
            ->orderByDesc('is_empresa_propria')
            ->orderBy('nome')
            ->get();

        // Resumo geral
        $resumo = $this->biService->getResumoGeral($userId);
        $resumoEfd = $this->biService->getKpisEfd($userId, null, null);

        $efdTotal = ($resumoEfd['total_entradas_notas'] ?? 0) + ($resumoEfd['total_saidas_notas'] ?? 0);
        $xmlTotal = $resumo['total_notas'] ?? 0;
        $defaultTab = ($efdTotal > 0 && $xmlTotal === 0) ? 'efd' : 'faturamento';

        $data = [
            'clientes' => $clientes,
            'resumo' => $resumo,
            'resumoEfd' => $resumoEfd,
            'defaultTab' => $defaultTab,
        ];

        return $this->render($request, 'index', $data);
    }

    /**
     * Dados de faturamento (para AJAX).
     */
    public function faturamento(Request $request)
    {
        if (! Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $userId = Auth::id();
        $dataInicio = $request->get('data_inicio');
        $dataFim = $request->get('data_fim');
        $clienteId = $request->get('cliente_id');

        $faturamento = $this->biService->getFaturamentoPorPeriodo($userId, $dataInicio, $dataFim, $clienteId);
        $topClientes = $this->biService->getTopClientes($userId, 10, $dataInicio, $dataFim, $clienteId);
        $faturamentoPorUf = $this->biService->getFaturamentoPorUf($userId, $dataInicio, $dataFim, $clienteId);

        return response()->json([
            'faturamento_mensal' => $faturamento,
            'top_clientes' => $topClientes,
            'faturamento_por_uf' => $faturamentoPorUf,
        ]);
    }

    /**
     * Dados de compras (para AJAX).
     */
    public function compras(Request $request)
    {
        if (! Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $userId = Auth::id();
        $dataInicio = $request->get('data_inicio');
        $dataFim = $request->get('data_fim');
        $clienteId = $request->get('cliente_id');

        $topFornecedores = $this->biService->getTopFornecedores($userId, 10, $dataInicio, $dataFim, $clienteId);
        $entradasVsSaidas = $this->biService->getEntradasVsSaidas($userId, $dataInicio, $dataFim, $clienteId);
        $devolucoes = $this->biService->getDevolucoes($userId, $dataInicio, $dataFim, $clienteId);

        return response()->json([
            'top_fornecedores' => $topFornecedores,
            'entradas_vs_saidas' => $entradasVsSaidas,
            'devolucoes' => $devolucoes,
        ]);
    }

    /**
     * Dados de tributos (para AJAX).
     */
    public function tributos(Request $request)
    {
        if (! Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $userId = Auth::id();
        $dataInicio = $request->get('data_inicio');
        $dataFim = $request->get('data_fim');
        $clienteId = $request->get('cliente_id');

        $cargaTributaria = $this->biService->getCargaTributaria($userId, $dataInicio, $dataFim, $clienteId);
        $tributosPorTipo = $this->biService->getTributosPorTipo($userId, $dataInicio, $dataFim, $clienteId);

        return response()->json([
            'carga_tributaria' => $cargaTributaria,
            'tributos_por_tipo' => $tributosPorTipo,
        ]);
    }

    /**
     * Dados EFD (para AJAX).
     */
    public function efd(Request $request)
    {
        if (! Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $userId = Auth::id();
        $dataInicio = $request->get('data_inicio');
        $dataFim = $request->get('data_fim');

        return response()->json([
            'kpis' => $this->biService->getKpisEfd($userId, $dataInicio, $dataFim),
            'fluxo_mensal' => $this->biService->getFluxoMensalEfd($userId, $dataInicio, $dataFim),
            'volume_blocos' => $this->biService->getVolumePorBlocoEfd($userId, $dataInicio, $dataFim),
            'top_fornecedores' => $this->biService->getTopParticipantesEfd($userId, 10, $dataInicio, $dataFim, 'E'),
            'top_clientes' => $this->biService->getTopParticipantesEfd($userId, 10, $dataInicio, $dataFim, 'S'),
            'tributos_por_tipo' => $this->biService->getTributosPorTipoEfd($userId, $dataInicio, $dataFim),
        ]);
    }

    /**
     * Ranking de participantes EFD (para AJAX).
     */
    public function participantes(Request $request)
    {
        if (! Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $userId = Auth::id();
        $dataInicio = $request->get('data_inicio');
        $dataFim = $request->get('data_fim');

        $fornecedores = $this->biService->getRankingParticipantes($userId, 'E', $dataInicio, $dataFim);
        $clientes = $this->biService->getRankingParticipantes($userId, 'S', $dataInicio, $dataFim);

        $calcConcentracao = function (array $lista) {
            $totalValor = array_sum(array_column($lista, 'total_valor'));
            $top5Valor = array_sum(array_column(array_slice($lista, 0, 5), 'total_valor'));
            $top5Pct = $totalValor > 0 ? round(($top5Valor / $totalValor) * 100, 1) : 0.0;

            return ['top5_percentual' => $top5Pct, 'top5_valor' => $top5Valor, 'total_valor' => $totalValor];
        };

        return response()->json([
            'fornecedores' => $fornecedores,
            'clientes' => $clientes,
            'concentracao' => [
                'fornecedores' => $calcConcentracao($fornecedores),
                'clientes' => $calcConcentracao($clientes),
            ],
        ]);
    }

    /**
     * Ficha detalhada de um participante EFD (para AJAX).
     */
    public function fichaParticipante(Request $request, int $id)
    {
        if (! Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $userId = Auth::id();
        $dataInicio = $request->get('data_inicio');
        $dataFim = $request->get('data_fim');

        $ficha = $this->biService->getFichaParticipante($userId, $id, $dataInicio, $dataFim);

        if (empty($ficha)) {
            return response()->json(['error' => 'Não encontrado'], 404);
        }

        return response()->json($ficha);
    }

    /**
     * Dados de riscos (para AJAX).
     */
    public function riscos(Request $request)
    {
        if (! Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $userId = Auth::id();
        $dataInicio = $request->get('data_inicio');
        $dataFim = $request->get('data_fim');

        return response()->json([
            'score_carteira' => $this->biService->getScoreCarteira($userId),
            'fornecedores_irregulares' => $this->biService->getFornecedoresIrregulares($userId, $dataInicio, $dataFim),
            'notas_em_risco' => $this->biService->getNotasEmRisco($userId, $dataInicio, $dataFim),
            'mudancas_regime' => $this->biService->getMudancasRegime($userId),
            'gap_importacoes' => $this->biService->getGapImportacoes($userId),
        ]);
    }

    /**
     * Dados tributários EFD (para AJAX).
     */
    public function tributarioEfd(Request $request)
    {
        if (! Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $userId = Auth::id();
        $dataInicio = $request->get('data_inicio');
        $dataFim = $request->get('data_fim');

        return response()->json([
            'consolidado' => $this->biService->getTributarioEfd($userId, $dataInicio, $dataFim),
            'mensal' => $this->biService->getTributarioMensalEfd($userId, $dataInicio, $dataFim),
            'aliquota' => $this->biService->getAliquotaEfetivaEfd($userId, $dataInicio, $dataFim),
            'por_regime' => $this->biService->getTributarioPorRegime($userId, $dataInicio, $dataFim),
        ]);
    }

    /**
     * Resumo geral (para AJAX).
     */
    public function resumo(Request $request)
    {
        if (! Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $userId = Auth::id();
        $clienteId = $request->get('cliente_id');

        $resumo = $this->biService->getResumoGeral($userId, $clienteId);

        return response()->json($resumo);
    }

    /**
     * Verifica se é requisição AJAX.
     */
    private function isAjaxRequest(Request $request): bool
    {
        if (method_exists($request, 'ajax') && $request->ajax()) {
            return true;
        }

        return $request->header('X-Requested-With') === 'XMLHttpRequest' ||
               $request->wantsJson() ||
               $request->expectsJson();
    }

    /**
     * Renderiza view com suporte a AJAX.
     */
    private function render(Request $request, string $viewName, array $data = [])
    {
        $view = self::AUTH_VIEW_PREFIX.$viewName;

        if (! view()->exists($view)) {
            abort(404);
        }

        if ($this->isAjaxRequest($request)) {
            return view($view, $data);
        }

        return view(self::AUTH_LAYOUT_VIEW, array_merge([
            'initialView' => $view,
        ], $data));
    }

    /**
     * Redireciona para login.
     */
    private function redirectToLogin(Request $request)
    {
        if ($this->isAjaxRequest($request)) {
            return response()->json([
                'success' => false,
                'message' => 'Você não está logado',
                'redirect' => '/login',
            ]);
        }

        return redirect('/login');
    }
}
