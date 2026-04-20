<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Participante;
use App\Models\ParticipanteScore;
use App\Services\RiskScoreService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RiskScoreController extends Controller
{
    private const AUTH_VIEW_PREFIX = 'autenticado.risk.';
    private const AUTH_LAYOUT_VIEW = 'autenticado.layouts.app';

    public function __construct(
        protected RiskScoreService $riskScoreService
    ) {}

    /**
     * Dashboard de Risk Score.
     */
    public function index(Request $request)
    {
        if (! Auth::check()) {
            return $this->redirectToLogin($request);
        }

        $userId = Auth::id();

        // Estatisticas gerais
        $estatisticas = $this->riskScoreService->getEstatisticas($userId);

        // Participantes com scores (paginado)
        $participantesQuery = Participante::where('user_id', $userId)
            ->with('score')
            ->orderBy('razao_social');

        // Filtro por classificacao
        if ($request->has('classificacao') && $request->classificacao !== 'todos') {
            $participantesQuery->whereHas('score', function ($q) use ($request) {
                $q->where('classificacao', $request->classificacao);
            });
        }

        // Filtro por busca
        if ($request->has('busca') && ! empty($request->busca)) {
            $busca = $request->busca;
            $participantesQuery->where(function ($q) use ($busca) {
                $q->where('razao_social', 'ilike', "%{$busca}%")
                  ->orWhere('documento', 'like', "%{$busca}%");
            });
        }

        $participantes = $participantesQuery->paginate(20);

        // Participantes em risco critico (para alerta)
        $emRiscoCritico = ParticipanteScore::where('user_id', $userId)
            ->where('classificacao', 'critico')
            ->with('participante')
            ->limit(5)
            ->get();

        $data = [
            'estatisticas' => $estatisticas,
            'participantes' => $participantes,
            'emRiscoCritico' => $emRiscoCritico,
            'filtroClassificacao' => $request->classificacao ?? 'todos',
            'filtroBusca' => $request->busca ?? '',
        ];

        return $this->render($request, 'index', $data);
    }

    /**
     * Detalhes do score de um participante.
     */
    public function show(Request $request, int $id)
    {
        if (! Auth::check()) {
            return $this->redirectToLogin($request);
        }

        $userId = Auth::id();

        $participante = Participante::where('user_id', $userId)
            ->where('id', $id)
            ->with(['score', 'notasComoEmitente', 'notasComoDestinatario'])
            ->firstOrFail();

        // Calcular volume de transacoes
        $volumeEmitente = $participante->notasComoEmitente()->sum('valor_total');
        $volumeDestinatario = $participante->notasComoDestinatario()->sum('valor_total');

        $data = [
            'participante' => $participante,
            'score' => $participante->score,
            'pesos' => $this->riskScoreService->getPesos(),
            'volumeEmitente' => $volumeEmitente,
            'volumeDestinatario' => $volumeDestinatario,
        ];

        return $this->render($request, 'show', $data);
    }

    /**
     * Executa consulta de risco para um participante.
     * Por enquanto simula os dados - integracao com InfoSimples sera feita via n8n.
     */
    public function consultar(Request $request, int $id)
    {
        if (! Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $userId = Auth::id();

        $participante = Participante::where('user_id', $userId)
            ->where('id', $id)
            ->first();

        if (! $participante) {
            return response()->json([
                'success' => false,
                'message' => 'Participante nao encontrado',
            ], 404);
        }

        // Por enquanto, simular dados baseados na situacao cadastral existente
        // A integracao real sera via webhook n8n
        $dadosSimulados = $this->simularDadosConsulta($participante);

        // Atualizar score
        $score = $this->riskScoreService->atualizarScore($participante, $dadosSimulados);

        return response()->json([
            'success' => true,
            'message' => 'Score atualizado com sucesso',
            'score' => [
                'total' => $score->score_total,
                'classificacao' => $score->classificacao,
                'classificacao_label' => $score->classificacao_label,
                'detalhes' => $score->scores_detalhados,
            ],
        ]);
    }

    /**
     * Dashboard resumido de risco (para AJAX).
     */
    public function dashboard(Request $request)
    {
        if (! Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $userId = Auth::id();

        $estatisticas = $this->riskScoreService->getEstatisticas($userId);

        // Top 10 participantes de maior risco
        $maiorRisco = ParticipanteScore::where('user_id', $userId)
            ->with('participante:id,cnpj,razao_social')
            ->orderByDesc('score_total')
            ->limit(10)
            ->get()
            ->map(function ($score) {
                return [
                    'id' => $score->participante_id,
                    'cnpj' => $score->participante->documento ?? '',
                    'razao_social' => $score->participante->razao_social ?? '',
                    'score' => $score->score_total,
                    'classificacao' => $score->classificacao,
                ];
            });

        // Distribuicao por classificacao (para grafico)
        $distribuicao = [
            ['classificacao' => 'Baixo Risco', 'quantidade' => $estatisticas['baixo_risco'], 'cor' => '#22c55e'],
            ['classificacao' => 'Medio Risco', 'quantidade' => $estatisticas['medio_risco'], 'cor' => '#eab308'],
            ['classificacao' => 'Alto Risco', 'quantidade' => $estatisticas['alto_risco'], 'cor' => '#f97316'],
            ['classificacao' => 'Critico', 'quantidade' => $estatisticas['critico'], 'cor' => '#ef4444'],
        ];

        return response()->json([
            'estatisticas' => $estatisticas,
            'maior_risco' => $maiorRisco,
            'distribuicao' => $distribuicao,
        ]);
    }

    /**
     * Atualiza scores em lote.
     */
    public function atualizarEmLote(Request $request)
    {
        if (! Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $userId = Auth::id();
        $ids = $request->input('participante_ids', []);

        if (empty($ids)) {
            return response()->json([
                'success' => false,
                'message' => 'Nenhum participante selecionado',
            ], 400);
        }

        $participantes = Participante::where('user_id', $userId)
            ->whereIn('id', $ids)
            ->get();

        $atualizados = 0;
        foreach ($participantes as $participante) {
            $dados = $this->simularDadosConsulta($participante);
            $this->riskScoreService->atualizarScore($participante, $dados);
            $atualizados++;
        }

        return response()->json([
            'success' => true,
            'message' => "{$atualizados} participante(s) atualizado(s)",
            'atualizados' => $atualizados,
        ]);
    }

    /**
     * Simula dados de consulta baseado nas informacoes existentes.
     * TODO: Substituir por integracao real com InfoSimples via n8n.
     */
    private function simularDadosConsulta(Participante $participante): array
    {
        return [
            'situacao_cadastral' => $participante->situacao_cadastral ?? 'ATIVA',
            'cnd_federal' => null, // Nao consultado
            'cnd_estadual' => null,
            'crf_fgts' => null,
            'cndt' => null,
            'ceis' => false,
            'cnep' => false,
            'tcu' => false,
            'trabalho_escravo' => false,
            'ibama_autuacoes' => [],
            'protestos' => [],
        ];
    }

    /**
     * Verifica se e requisicao AJAX.
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
                'message' => 'Voce nao esta logado',
                'redirect' => '/login',
            ]);
        }

        return redirect('/login');
    }
}
