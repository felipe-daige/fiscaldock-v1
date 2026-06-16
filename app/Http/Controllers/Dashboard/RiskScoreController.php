<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Concerns\RespondeAjax;
use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Participante;
use App\Models\ParticipanteScore;
use App\Services\RiskScoreService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RiskScoreController extends Controller
{
    use RespondeAjax;

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

        $busca = trim((string) $request->input('busca', ''));
        $filtroClassificacao = $request->input('classificacao', 'todos');

        // SÓ CNPJ (14 dígitos) — CPF fica de fora das listas.
        $cnpjRaw = "length(regexp_replace(coalesce(documento, ''), '[^0-9]', '', 'g')) = 14";

        // Filtro de visualização por cliente. Começa em "Todos os CNPJs" por padrão; só
        // restringe quando um cliente específico é escolhido. Escopo de um cliente = o próprio
        // CNPJ + os participantes daquele cliente.
        $clientes = Cliente::where('user_id', $userId)
            ->orderByDesc('is_empresa_propria')
            ->orderBy('razao_social')
            ->get();

        $clienteParam = $request->query('cliente_id');
        $clienteSelecionadoId = null;
        if ($clienteParam !== null && $clienteParam !== 'todos' && ctype_digit((string) $clienteParam)) {
            $clienteSelecionadoId = optional($clientes->firstWhere('id', (int) $clienteParam))->id;
        }
        $verTodos = $clienteSelecionadoId === null;

        // Escopo de cliente para ParticipanteScore (alvo = cliente OU participante daquele cliente).
        $escopoClienteScore = function ($query) use ($verTodos, $clienteSelecionadoId) {
            if (! $verTodos && $clienteSelecionadoId) {
                $query->where(function ($q) use ($clienteSelecionadoId) {
                    $q->where('cliente_id', $clienteSelecionadoId)
                        ->orWhereHas('participante', fn ($p) => $p->where('cliente_id', $clienteSelecionadoId));
                });
            }
        };

        // Estatisticas (KPIs) — escopadas pelo cliente selecionado.
        $estatisticas = $this->riskScoreService->getEstatisticas($userId, $escopoClienteScore);

        // Score ligado a um participante "PROPRIO" (a própria empresa, materializada como
        // participante pela tela Minha Empresa) é duplicata do cliente empresa própria, que já
        // aparece nas listas pelo lado `cliente`. Mesma regra do DashboardDataService.
        $excluirParticipanteProprio = fn ($q) => $q->whereDoesntHave('participante', fn ($p) => $p->where('origem_tipo', 'PROPRIO'));

        // CONSULTADOS: têm score (participante OU cliente). Ordem por risco (maior 1º), null ao fim.
        $consultadosQuery = ParticipanteScore::where('user_id', $userId)
            ->with(['participante', 'cliente'])
            ->tap($excluirParticipanteProprio)
            ->orderByRaw('score_total desc nulls last')
            ->orderByDesc('ultima_consulta_em');

        $escopoClienteScore($consultadosQuery);

        if ($filtroClassificacao !== 'todos') {
            $consultadosQuery->where('classificacao', $filtroClassificacao);
        }

        if ($busca !== '') {
            $filtroAlvo = function ($q) use ($busca) {
                $q->where('razao_social', 'ilike', "%{$busca}%")->orWhere('documento', 'like', "%{$busca}%");
            };
            $consultadosQuery->where(function ($q) use ($filtroAlvo) {
                $q->whereHas('participante', $filtroAlvo)->orWhereHas('cliente', $filtroAlvo);
            });
        }

        $consultados = $consultadosQuery->paginate(20, ['*'], 'page')->withQueryString();

        // NÃO CONSULTADOS: participantes + clientes (só CNPJ) ainda sem score — lista de "a consultar".
        // Escopo por cliente: participantes pela coluna cliente_id; o próprio cliente pela coluna id.
        $buildNaoConsultados = function (string $model, string $tipo) use ($userId, $cnpjRaw, $busca, $verTodos, $clienteSelecionadoId) {
            $query = $model::where('user_id', $userId)
                ->whereRaw($cnpjRaw)
                ->whereDoesntHave('score')
                // O participante "PROPRIO" é a própria empresa duplicada; ela já aparece pelo
                // lado `cliente`. Não filtra o lado cliente (empresa própria deve permanecer).
                ->when($tipo === 'participante', fn ($q) => $q->where(fn ($w) => $w->where('origem_tipo', '!=', 'PROPRIO')->orWhereNull('origem_tipo')))
                ->when($busca !== '', fn ($q) => $q->where(fn ($w) => $w->where('razao_social', 'ilike', "%{$busca}%")->orWhere('documento', 'like', "%{$busca}%")));

            if (! $verTodos && $clienteSelecionadoId) {
                $coluna = $tipo === 'cliente' ? 'id' : 'cliente_id';
                $query->where($coluna, $clienteSelecionadoId);
            }

            return $query->selectRaw("'{$tipo}' as tipo, id, razao_social, nome_fantasia, documento, uf");
        };

        $uniao = $buildNaoConsultados(Participante::class, 'participante')
            ->unionAll($buildNaoConsultados(Cliente::class, 'cliente'));

        $naoConsultados = \Illuminate\Support\Facades\DB::query()
            ->fromSub($uniao, 'nc')
            ->orderBy('razao_social')
            ->paginate(20, ['*'], 'nc')
            ->withQueryString();

        // Papel comercial dos PARTICIPANTES exibidos, pelas notas EFD: entrada = nós compramos
        // dele (Fornecedor); saida = nós vendemos pra ele (Comprador); os dois = Ambos.
        $idsParticipantes = collect();
        foreach ($consultados as $sc) {
            if ($sc->participante_id) {
                $idsParticipantes->push($sc->participante_id);
            }
        }
        foreach ($naoConsultados as $item) {
            if (($item->tipo ?? null) === 'participante') {
                $idsParticipantes->push($item->id);
            }
        }

        $papeisParticipante = [];
        if ($idsParticipantes->isNotEmpty()) {
            $linhas = \Illuminate\Support\Facades\DB::table('efd_notas')
                ->select('participante_id', 'tipo_operacao')
                ->where('user_id', $userId)
                ->whereIn('participante_id', $idsParticipantes->unique()->values()->all())
                ->groupBy('participante_id', 'tipo_operacao')
                ->get();
            foreach ($linhas as $linha) {
                $papeisParticipante[$linha->participante_id][$linha->tipo_operacao] = true;
            }
        }

        // Em risco critico (para alerta) — participante ou cliente, escopado pelo cliente
        $emRiscoCriticoQuery = ParticipanteScore::where('user_id', $userId)
            ->where('classificacao', 'critico')
            ->with(['participante', 'cliente'])
            ->tap($excluirParticipanteProprio)
            ->limit(5);
        $escopoClienteScore($emRiscoCriticoQuery);
        $emRiscoCritico = $emRiscoCriticoQuery->get();

        $data = [
            'estatisticas' => $estatisticas,
            'consultados' => $consultados,
            'naoConsultados' => $naoConsultados,
            'papeisParticipante' => $papeisParticipante,
            'emRiscoCritico' => $emRiscoCritico,
            'filtroClassificacao' => $filtroClassificacao,
            'filtroBusca' => $busca,
            'clientes' => $clientes,
            'clienteSelecionadoId' => $clienteSelecionadoId,
            'verTodosCnpjs' => $verTodos,
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
            ->with('score')
            ->firstOrFail();

        // Volume movimentado no acervo EFD auditado (efd_notas tem só participante_id — sem
        // split emitente/destinatário, que é exclusivo do acervo XML).
        $volumeEfd = (float) $participante->efdNotas()->sum('valor_total');

        $data = [
            'participante' => $participante,
            'score' => $participante->score,
            'pesos' => $this->riskScoreService->getPesos(),
            'volumeEfd' => $volumeEfd,
        ];

        return $this->render($request, 'show', $data);
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
     * Verifica se e requisicao AJAX.
     */
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
