<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Participante;
use App\Models\XmlNota;
use App\Models\ConsultaResultado;
use App\Services\RiskScoreService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MinhaEmpresaController extends Controller
{
    private const AUTH_VIEW_PREFIX = 'autenticado.minha-empresa.';
    private const AUTH_LAYOUT_VIEW = 'autenticado.layouts.app';

    public function __construct(
        protected RiskScoreService $riskScoreService
    ) {}

    /**
     * Dashboard da Minha Empresa.
     */
    public function index(Request $request)
    {
        if (! Auth::check()) {
            return $this->redirectToLogin($request);
        }

        $user = Auth::user();
        $empresa = $user->empresaPropria();

        if (! $empresa) {
            return $this->render($request, 'configurar', [
                'clientes' => $user->clientes()->where('tipo_pessoa', 'PJ')->get(),
            ]);
        }

        // Buscar ou criar participante correspondente ao CNPJ da empresa
        $cnpjLimpo = preg_replace('/\D/', '', $empresa->documento);
        $participante = Participante::firstOrCreate(
            ['user_id' => $user->id, 'documento' => $cnpjLimpo],
            [
                'razao_social' => $empresa->razao_social ?? $empresa->nome,
                'origem_tipo' => 'PROPRIO',
            ]
        );

        // Buscar score de risco
        $score = $participante->score;

        // Buscar ultima consulta RAF
        $ultimaConsulta = ConsultaResultado::where('participante_id', $participante->id)
            ->where('status', 'sucesso')
            ->latest('consultado_em')
            ->first();

        // Dados da ultima consulta para exibicao nos cards
        $dadosConsulta = $ultimaConsulta?->resultado_dados ?? [];

        // CNDs e certidoes
        $certidoes = $this->extrairCertidoes($dadosConsulta);

        // Alertas recentes
        $alertas = $this->gerarAlertas($certidoes, $score);

        // Contagens para KPIs
        $totalParticipantes = Participante::where('user_id', $user->id)->count();
        $totalNotas = XmlNota::where('user_id', $user->id)->count();

        $data = [
            'empresa' => $empresa,
            'participante' => $participante,
            'score' => $score,
            'ultimaConsulta' => $ultimaConsulta,
            'dadosConsulta' => $dadosConsulta,
            'certidoes' => $certidoes,
            'alertas' => $alertas,
            'totalParticipantes' => $totalParticipantes,
            'totalNotas' => $totalNotas,
        ];

        return $this->render($request, 'index', $data);
    }

    /**
     * Tela de configuracao para selecionar empresa principal.
     */
    public function configurar(Request $request)
    {
        if (! Auth::check()) {
            return $this->redirectToLogin($request);
        }

        $user = Auth::user();
        $clientes = $user->clientes()->where('tipo_pessoa', 'PJ')->get();
        $empresaAtual = $user->empresaPropria();

        return $this->render($request, 'configurar', [
            'clientes' => $clientes,
            'empresaAtual' => $empresaAtual,
        ]);
    }

    /**
     * Define qual empresa sera a principal do usuario.
     */
    public function definirPrincipal(Request $request)
    {
        if (! Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $request->validate([
            'cliente_id' => 'required|integer|exists:clientes,id',
        ]);

        $user = Auth::user();
        $clienteId = $request->input('cliente_id');

        // Verificar se o cliente pertence ao usuario e e PJ
        $cliente = Cliente::where('id', $clienteId)
            ->where('user_id', $user->id)
            ->where('tipo_pessoa', 'PJ')
            ->first();

        if (! $cliente) {
            return response()->json([
                'success' => false,
                'message' => 'Cliente nao encontrado ou nao e pessoa juridica',
            ], 404);
        }

        // Remover flag de todas as empresas do usuario
        Cliente::where('user_id', $user->id)
            ->update(['is_empresa_propria' => false]);

        // Marcar a empresa selecionada como propria
        $cliente->update(['is_empresa_propria' => true]);

        // Criar participante se nao existir
        $cnpjLimpo = preg_replace('/\D/', '', $cliente->documento);
        Participante::firstOrCreate(
            ['user_id' => $user->id, 'documento' => $cnpjLimpo],
            [
                'razao_social' => $cliente->razao_social ?? $cliente->nome,
                'origem_tipo' => 'PROPRIO',
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Empresa principal definida com sucesso',
            'redirect' => '/app/minha-empresa',
        ]);
    }

    /**
     * Historico de consultas da empresa propria.
     */
    public function historico(Request $request)
    {
        if (! Auth::check()) {
            return $this->redirectToLogin($request);
        }

        $user = Auth::user();
        $empresa = $user->empresaPropria();

        if (! $empresa) {
            return redirect()->route('app.minha-empresa.configurar');
        }

        $cnpjLimpo = preg_replace('/\D/', '', $empresa->documento);
        $participante = Participante::where('user_id', $user->id)
            ->where('documento', $cnpjLimpo)
            ->first();

        $consultas = collect();
        if ($participante) {
            $consultas = ConsultaResultado::where('participante_id', $participante->id)
                ->with('lote')
                ->latest('consultado_em')
                ->paginate(20);
        }

        return $this->render($request, 'historico', [
            'empresa' => $empresa,
            'participante' => $participante,
            'consultas' => $consultas,
        ]);
    }

    /**
     * Extrai informacoes de certidoes dos dados da consulta.
     */
    private function extrairCertidoes(array $dados): array
    {
        return [
            'cnd_federal' => [
                'status' => $dados['cnd_federal']['status'] ?? null,
                'validade' => $dados['cnd_federal']['validade'] ?? null,
                'consultado' => isset($dados['cnd_federal']),
            ],
            'cnd_estadual' => [
                'status' => $dados['cnd_estadual']['status'] ?? $dados['cnd_estadual'] ?? null,
                'validade' => $dados['cnd_estadual']['validade'] ?? null,
                'consultado' => isset($dados['cnd_estadual']),
            ],
            'fgts' => [
                'status' => $dados['crf_fgts']['status'] ?? $dados['crf_fgts'] ?? null,
                'validade' => $dados['crf_fgts']['validade'] ?? null,
                'consultado' => isset($dados['crf_fgts']),
            ],
            'cndt' => [
                'status' => $dados['cndt']['status'] ?? $dados['cndt'] ?? null,
                'validade' => $dados['cndt']['validade'] ?? null,
                'consultado' => isset($dados['cndt']),
            ],
            'situacao_cadastral' => $dados['situacao_cadastral'] ?? null,
            'simples_nacional' => $dados['simples_nacional'] ?? null,
            'mei' => $dados['mei'] ?? null,
        ];
    }

    /**
     * Gera alertas baseados nas certidoes e score.
     */
    private function gerarAlertas(array $certidoes, ?object $score): array
    {
        $alertas = [];

        // Alerta de situacao cadastral
        $situacao = strtoupper($certidoes['situacao_cadastral'] ?? '');
        if (in_array($situacao, ['INAPTA', 'SUSPENSA', 'BAIXADA'])) {
            $alertas[] = [
                'tipo' => 'critico',
                'mensagem' => "Situacao cadastral: {$situacao}",
                'icone' => 'alert-triangle',
            ];
        }

        // Alertas de CNDs
        $this->addAlertaCnd($alertas, 'CND Federal', $certidoes['cnd_federal']);
        $this->addAlertaCnd($alertas, 'CND Estadual', $certidoes['cnd_estadual']);
        $this->addAlertaCnd($alertas, 'CRF (FGTS)', $certidoes['fgts']);
        $this->addAlertaCnd($alertas, 'CNDT', $certidoes['cndt']);

        // Alerta de score critico
        if ($score && $score->classificacao === 'critico') {
            $alertas[] = [
                'tipo' => 'critico',
                'mensagem' => 'Score de risco critico: ' . $score->score_total . '/100',
                'icone' => 'shield-alert',
            ];
        } elseif ($score && $score->classificacao === 'alto') {
            $alertas[] = [
                'tipo' => 'atencao',
                'mensagem' => 'Score de risco alto: ' . $score->score_total . '/100',
                'icone' => 'shield-alert',
            ];
        }

        return $alertas;
    }

    /**
     * Adiciona alerta de CND se aplicavel.
     */
    private function addAlertaCnd(array &$alertas, string $nome, array $dados): void
    {
        if (! $dados['consultado']) {
            return;
        }

        $status = strtoupper($dados['status'] ?? '');

        if (in_array($status, ['POSITIVA', 'IRREGULAR'])) {
            $alertas[] = [
                'tipo' => 'critico',
                'mensagem' => "{$nome}: {$status}",
                'icone' => 'x-circle',
            ];
        } elseif (strpos($status, 'POSITIVA COM EFEITO') !== false) {
            $alertas[] = [
                'tipo' => 'atencao',
                'mensagem' => "{$nome}: Positiva com efeito de negativa",
                'icone' => 'alert-circle',
            ];
        }

        // Verificar validade proxima
        if (! empty($dados['validade'])) {
            $validade = \Carbon\Carbon::parse($dados['validade']);
            $diasRestantes = now()->diffInDays($validade, false);

            if ($diasRestantes <= 0) {
                $alertas[] = [
                    'tipo' => 'critico',
                    'mensagem' => "{$nome}: Vencida",
                    'icone' => 'clock',
                ];
            } elseif ($diasRestantes <= 7) {
                $alertas[] = [
                    'tipo' => 'atencao',
                    'mensagem' => "{$nome}: Vence em {$diasRestantes} dias",
                    'icone' => 'clock',
                ];
            }
        }
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
        $view = self::AUTH_VIEW_PREFIX . $viewName;

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
