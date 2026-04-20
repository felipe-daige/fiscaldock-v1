<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Alerta;
use App\Models\Cliente;
use App\Models\ConsultaResultado;
use App\Models\ConsultaLote;
use App\Models\CreditTransaction;
use App\Models\Participante;
use App\Services\AlertaCentralService;
use App\Services\Dashboard\DashboardDataService;
use App\Services\NotaFiscalService;
use App\Services\PricingCatalogService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class DashboardController extends Controller
{
    public function __construct(
        protected DashboardDataService $dashboardDataService,
        protected NotaFiscalService $notaFiscalService,
        protected AlertaCentralService $alertaCentralService,
        protected PricingCatalogService $pricingCatalogService,
    ) {}

    private const AUTH_VIEW_PREFIX = 'autenticado.';

    private const AUTH_LAYOUT_VIEW = 'autenticado.layouts.app';

    public function dashboard(Request $request)
    {
        $dashboardView = self::AUTH_VIEW_PREFIX.'dashboard.index';

        if (! view()->exists($dashboardView)) {
            abort(404);
        }

        if (! Auth::check()) {
            if ($this->isAjaxRequest($request)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não está logado',
                    'redirect' => '/login',
                ]);
            }

            return redirect('/login');
        }

        $user = Auth::user();
        $userId = $user->id;

        $kpis = $this->dashboardDataService->getKpis($userId, $user);
        $atividadeRecente = $this->dashboardDataService->getAtividadeRecente($userId);
        $isUsuarioNovo = $this->dashboardDataService->isUsuarioNovo($userId);
        $ultimaImportacao = $this->dashboardDataService->getUltimaImportacao($userId);

        $data = [
            'kpis' => $kpis,
            'atividadeRecente' => $atividadeRecente,
            'isUsuarioNovo' => $isUsuarioNovo,
            'ultimaImportacao' => $ultimaImportacao,
            'trialResumo' => $this->buildTrialResumo($user),
        ];

        if ($this->isAjaxRequest($request)) {
            return view($dashboardView, $data);
        }

        return view(self::AUTH_LAYOUT_VIEW, array_merge([
            'initialView' => $dashboardView,
        ], $data));
    }

    /**
     * Verifica se a requisição é AJAX de forma compatível com Laravel 11 e 12
     */
    private function isAjaxRequest(Request $request): bool
    {
        // Verifica se o método ajax() existe (Laravel 11)
        if (method_exists($request, 'ajax') && $request->ajax()) {
            return true;
        }

        // Verifica o header X-Requested-With diretamente (compatível com Laravel 12)
        return $request->header('X-Requested-With') === 'XMLHttpRequest' ||
               $request->wantsJson() ||
               $request->expectsJson();
    }

    private function renderAutenticado(Request $request, string $viewName)
    {
        $autenticadoView = self::AUTH_VIEW_PREFIX.$viewName;

        if (! view()->exists($autenticadoView)) {
            abort(404);
        }

        if (! Auth::check()) {
            if ($this->isAjaxRequest($request)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não está logado',
                    'redirect' => '/login',
                ]);
            }

            return redirect('/login');
        }

        if ($this->isAjaxRequest($request)) {
            return view($autenticadoView);
        }

        return view(self::AUTH_LAYOUT_VIEW, [
            'initialView' => $autenticadoView,
        ]);
    }

    public function novoCliente(Request $request)
    {
        return $this->renderAutenticado($request, 'clientes.novo');
    }

    public function clientes(Request $request)
    {
        $autenticadoView = self::AUTH_VIEW_PREFIX.'clientes.index';

        if (! view()->exists($autenticadoView)) {
            abort(404);
        }

        if (! Auth::check()) {
            if ($this->isAjaxRequest($request)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não está logado',
                    'redirect' => '/login',
                ]);
            }

            return redirect('/login');
        }

        $userId = (int) Auth::id();
        $status = $request->string('status')->toString();
        $tipo = $request->string('tipo')->toString();
        $busca = trim($request->string('busca')->toString());
        $regime = trim($request->string('regime')->toString());
        $situacao = trim($request->string('situacao')->toString());
        $uf = trim($request->string('uf')->toString());

        $baseQuery = Cliente::where('user_id', $userId);

        $clientes = (clone $baseQuery)
            ->withCount('participantes')
            ->when($status !== '', function ($query) use ($status) {
                if ($status === 'ativos') {
                    $query->where('ativo', true);
                } elseif ($status === 'inativos') {
                    $query->where('ativo', false);
                }
            })
            ->when($tipo !== '', fn ($query) => $query->where('tipo_pessoa', strtoupper($tipo)))
            ->when($busca !== '', function ($query) use ($busca) {
                $query->where(function ($sub) use ($busca) {
                    $sub->where('documento', 'like', '%'.preg_replace('/\D/', '', $busca).'%')
                        ->orWhere('razao_social', 'ilike', "%{$busca}%")
                        ->orWhere('nome', 'ilike', "%{$busca}%");
                });
            })
            ->when($regime !== '', fn ($query) => $query->where('regime_tributario', 'ilike', $regime))
            ->when($situacao !== '', fn ($query) => $query->where('situacao_cadastral', 'ilike', $situacao))
            ->when($uf !== '', fn ($query) => $query->where('uf', strtoupper($uf)))
            ->orderByRaw("COALESCE(razao_social, nome, '') asc")
            ->paginate(20)
            ->withQueryString();

        $agora = Carbon::now();
        $documentosClientes = $clientes->getCollection()
            ->pluck('documento')
            ->filter()
            ->map(fn ($documento) => preg_replace('/\D/', '', (string) $documento))
            ->filter()
            ->unique()
            ->values();

        $participantesPorDocumento = Participante::query()
            ->where('user_id', $userId)
            ->whereIn('documento', $documentosClientes)
            ->get(['id', 'documento'])
            ->keyBy('documento');

        $ultimosResultadosClientes = ConsultaResultado::query()
            ->whereIn('participante_id', $participantesPorDocumento->pluck('id'))
            ->where('status', ConsultaResultado::STATUS_SUCESSO)
            ->orderBy('consultado_em', 'desc')
            ->get()
            ->unique('participante_id')
            ->keyBy('participante_id');

        $clientes->getCollection()->transform(function (Cliente $cliente) use ($agora, $participantesPorDocumento, $ultimosResultadosClientes) {
            $documento = preg_replace('/\D/', '', (string) $cliente->documento);
            $participante = $participantesPorDocumento->get($documento);
            $ultimoResultado = $participante ? $ultimosResultadosClientes->get($participante->id) : null;
            $ultimaConsulta = $ultimoResultado?->consultado_em;
            $cndFederal = $ultimoResultado?->getCndFederal() ?? [];

            $consultaStatusLabel = 'Não Consultado';
            $consultaStatusHex = '#9ca3af';
            $consultaStatusMeta = 'Sem consulta realizada';

            if ($ultimaConsulta) {
                $diasSemConsulta = $ultimaConsulta->diffInDays($agora);

                if ($diasSemConsulta > 30) {
                    $consultaStatusLabel = 'Consulta desatualizada';
                    $consultaStatusHex = '#d97706';
                } else {
                    $consultaStatusLabel = 'Consultado recentemente';
                    $consultaStatusHex = '#047857';
                }

                $consultaStatusMeta = 'Última atualização em '.$ultimaConsulta->format('d/m/Y H:i');
            }

            $cndStatusLabel = 'Não Consultado';
            $cndStatusHex = '#9ca3af';
            $cndMeta = 'Sem CND consultada';
            $cndStatus = strtoupper((string) ($cndFederal['status'] ?? ''));
            $cndValidade = $cndFederal['data_validade'] ?? null;

            if ($cndStatus !== '') {
                if (in_array($cndStatus, ['NEGATIVA', 'REGULAR', 'REGULARIDADE'])) {
                    $cndStatusLabel = 'Negativa';
                    $cndStatusHex = '#047857';
                } elseif (str_contains($cndStatus, 'POSITIVA COM EFEITO') || str_contains($cndStatus, 'EFEITO DE NEGATIVA')) {
                    $cndStatusLabel = 'Positiva c/ efeito';
                    $cndStatusHex = '#d97706';
                } elseif (in_array($cndStatus, ['POSITIVA', 'IRREGULAR', 'IRREGULARIDADE'])) {
                    $cndStatusLabel = 'Positiva';
                    $cndStatusHex = '#dc2626';
                } else {
                    $cndStatusLabel = $cndStatus;
                    $cndStatusHex = '#374151';
                }

                $cndMeta = 'Validade não informada';

                if ($cndValidade) {
                    try {
                        $dataValidade = Carbon::parse($cndValidade);
                        $diasRestantes = $agora->diffInDays($dataValidade, false);

                        if ($diasRestantes <= 0) {
                            $cndMeta = 'Vencida em '.$dataValidade->format('d/m/Y');
                        } elseif ($diasRestantes <= 7) {
                            $cndMeta = 'Vence em '.(int) $diasRestantes.' dias';
                        } else {
                            $cndMeta = 'Validade: '.$dataValidade->format('d/m/Y');
                        }
                    } catch (\Exception $e) {
                        $cndMeta = 'Validade: '.(string) $cndValidade;
                    }
                }
            }

            $cliente->setAttribute('consulta_status_label', $consultaStatusLabel);
            $cliente->setAttribute('consulta_status_hex', $consultaStatusHex);
            $cliente->setAttribute('consulta_status_meta', $consultaStatusMeta);
            $cliente->setAttribute('cnd_federal_status_label', $cndStatusLabel);
            $cliente->setAttribute('cnd_federal_status_hex', $cndStatusHex);
            $cliente->setAttribute('cnd_federal_meta', $cndMeta);

            return $cliente;
        });

        // Estatísticas
        $totalAtivos = (clone $baseQuery)->where('ativo', true)->count();
        $totalInativos = (clone $baseQuery)->where('ativo', false)->count();
        $totalPJ = (clone $baseQuery)->where('tipo_pessoa', 'PJ')->count();
        $totalPF = (clone $baseQuery)->where('tipo_pessoa', 'PF')->count();
        $ufs = (clone $baseQuery)
            ->whereNotNull('uf')
            ->where('uf', '!=', '')
            ->distinct()
            ->orderBy('uf')
            ->pluck('uf');

        $data = [
            'clientes' => $clientes,
            'totalAtivos' => $totalAtivos,
            'totalInativos' => $totalInativos,
            'totalPJ' => $totalPJ,
            'totalPF' => $totalPF,
            'ufs' => $ufs,
            'filtros' => [
                'status' => $status,
                'tipo' => strtoupper($tipo),
                'busca' => $busca,
                'regime' => $regime,
                'situacao' => $situacao,
                'uf' => strtoupper($uf),
            ],
        ];

        if ($this->isAjaxRequest($request)) {
            return view($autenticadoView, $data);
        }

        return view(self::AUTH_LAYOUT_VIEW, array_merge([
            'initialView' => $autenticadoView,
        ], $data));
    }

    public function clienteParticipantes(Request $request, int $id)
    {
        if (! Auth::check()) {
            return response('Nao autenticado', 401);
        }

        $userId = (int) Auth::id();
        $tipoDocumento = strtoupper(trim($request->string('tipo_documento')->toString()));
        $cliente = Cliente::where('id', $id)
            ->where('user_id', $userId)
            ->firstOrFail();

        $participantes = Participante::where('user_id', $userId)
            ->where('cliente_id', $cliente->id)
            ->when($tipoDocumento === 'CPF', fn ($query) => $query->somenteCpf())
            ->when($tipoDocumento === 'CNPJ', fn ($query) => $query->somenteCnpj())
            ->withCount('efdNotas')
            ->orderByRaw("COALESCE(razao_social, nome_fantasia, documento, '') asc")
            ->paginate(5)
            ->withQueryString();

        return view('autenticado.partials.relacionados-participantes', [
            'participantes' => $participantes,
            'titulo' => 'Participantes vinculados',
            'emptyMessage' => 'Nenhum participante vinculado a este cliente.',
            'scope' => 'cliente',
            'entityId' => $cliente->id,
            'ajaxBaseUrl' => "/app/cliente/{$cliente->id}/participantes",
            'filtros' => [
                'tipo_documento' => $tipoDocumento,
            ],
        ]);
    }

    public function clienteDetalhes(Request $request, int $id)
    {
        if (! Auth::check()) {
            if ($this->isAjaxRequest($request)) {
                return response()->json(['success' => false, 'message' => 'Nao autenticado'], 401);
            }

            return redirect('/login');
        }

        $cliente = Cliente::where('id', $id)
            ->where('user_id', Auth::id())
            ->first();

        if (! $cliente) {
            if ($this->isAjaxRequest($request)) {
                return response()->json(['success' => false, 'message' => 'Cliente nao encontrado'], 404);
            }
            abort(404);
        }

        // Empresa própria: redirect to /app/minha-empresa
        if ($cliente->is_empresa_propria) {
            if ($this->isAjaxRequest($request)) {
                return response()->json(['redirect' => '/app/minha-empresa']);
            }

            return redirect('/app/minha-empresa');
        }

        $totalParticipantes = Participante::where('user_id', Auth::id())
            ->where('cliente_id', $cliente->id)
            ->count();

        $notasFiscais = $this->notaFiscalService->listarUnificadas(
            (int) Auth::id(),
            ['cliente_id' => $cliente->id],
            5,
            1,
            "/app/cliente/{$id}/notas"
        );
        $totalNotas = $notasFiscais->total();

        $showView = self::AUTH_VIEW_PREFIX.'clientes.show';

        $viewData = [
            'cliente' => $cliente,
            'totalParticipantes' => $totalParticipantes,
            'totalNotas' => $totalNotas,
            'notasFiscais' => $notasFiscais,
            'totalNotasFiscais' => $totalNotas,
            'notasAjaxUrl' => "/app/cliente/{$id}/notas",
            'notasContexto' => 'cliente',
            'notasEntityId' => $cliente->id,
        ];

        if ($this->isAjaxRequest($request)) {
            // Modal requests send Accept: application/json — return JSON for the modal to populate
            if ($request->wantsJson() || $request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'cliente' => [
                        'id' => $cliente->id,
                        'nome' => $cliente->nome,
                        'razao_social' => $cliente->razao_social,
                        'documento_formatado' => $cliente->documento_formatado,
                        'tipo_pessoa' => $cliente->tipo_pessoa,
                        'email' => $cliente->email,
                        'telefone' => $cliente->telefone,
                        'ativo' => $cliente->ativo,
                        'is_empresa_propria' => $cliente->is_empresa_propria,
                        'uf' => $cliente->uf,
                        'cep' => $cliente->cep,
                        'municipio' => $cliente->municipio,
                        'created_at' => $cliente->created_at?->format('d/m/Y H:i'),
                    ],
                    'stats' => [
                        'total_participantes' => $totalParticipantes,
                        'total_notas' => $totalNotas,
                    ],
                ]);
            }

            // SPA navigation sends Accept: text/html — return HTML view
            return view($showView, $viewData);
        }

        return view(self::AUTH_LAYOUT_VIEW, array_merge([
            'initialView' => $showView,
        ], $viewData));
    }

    /**
     * Notas fiscais unificadas do cliente (AJAX pagination).
     */
    public function clienteNotas(Request $request, int $id)
    {
        if (! Auth::check()) {
            return response('Nao autenticado', 401);
        }

        $userId = (int) Auth::id();
        $cliente = Cliente::where('id', $id)
            ->where('user_id', $userId)
            ->firstOrFail();

        $page = max(1, (int) $request->get('page', 1));
        $notas = $this->notaFiscalService->listarUnificadas(
            $userId,
            ['cliente_id' => $cliente->id],
            5,
            $page,
            "/app/cliente/{$id}/notas"
        );

        return view('autenticado.partials.notas-fiscais-card', [
            'notas' => $notas,
            'totalNotas' => $notas->total(),
            'ajaxUrl' => "/app/cliente/{$id}/notas",
            'contexto' => 'cliente',
            'entityId' => $cliente->id,
        ]);
    }

    public function perfil(Request $request)
    {
        $perfilView = self::AUTH_VIEW_PREFIX.'usuario.perfil';

        if (! view()->exists($perfilView)) {
            abort(404);
        }

        if (! Auth::check()) {
            if ($this->isAjaxRequest($request)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não está logado',
                    'redirect' => '/login',
                ]);
            }

            return redirect('/login');
        }

        $user = Auth::user();

        if ($this->isAjaxRequest($request)) {
            return view($perfilView, ['user' => $user]);
        }

        return view(self::AUTH_LAYOUT_VIEW, [
            'initialView' => $perfilView,
            'user' => $user,
        ]);
    }

    /**
     * Renderiza uma página placeholder "Em construção" com dados customizados.
     */
    private function renderPlaceholder(Request $request, string $titulo, string $descricao, string $icone, array $features = [])
    {
        $placeholderView = self::AUTH_VIEW_PREFIX.'partials.placeholder';

        if (! Auth::check()) {
            if ($this->isAjaxRequest($request)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não está logado',
                    'redirect' => '/login',
                ]);
            }

            return redirect('/login');
        }

        $data = [
            'titulo' => $titulo,
            'descricao' => $descricao,
            'icone' => $icone,
            'features' => $features,
        ];

        if ($this->isAjaxRequest($request)) {
            return view($placeholderView, $data);
        }

        return view(self::AUTH_LAYOUT_VIEW, array_merge([
            'initialView' => $placeholderView,
        ], $data));
    }

    public function alertas(Request $request)
    {
        if (! Auth::check()) {
            if ($this->isAjaxRequest($request)) {
                return response()->json(['success' => false, 'redirect' => '/login']);
            }

            return redirect('/login');
        }

        $userId = Auth::id();
        $clientes = Cliente::where('user_id', $userId)
            ->select('id', 'razao_social')
            ->orderBy('razao_social')
            ->get();

        $resumo = $this->alertaCentralService->obterResumo($userId);

        $data = ['clientes' => $clientes, 'resumo' => $resumo];

        if ($this->isAjaxRequest($request)) {
            return view('autenticado.alertas.central', $data);
        }

        return view(self::AUTH_LAYOUT_VIEW, array_merge([
            'initialView' => 'autenticado.alertas.central',
        ], $data));
    }

    public function alertasDados(Request $request)
    {
        if (! Auth::check()) {
            return response()->json(['success' => false, 'redirect' => '/login']);
        }

        $userId = Auth::id();
        $filtros = [
            'status' => $request->input('status', 'ativo'),
            'severidade' => $request->input('severidade'),
            'categoria' => $request->input('categoria'),
            'cliente_id' => $request->input('cliente_id'),
        ];

        $alertas = $this->alertaCentralService->obterAlertas($userId, $filtros);

        return response()->json($alertas);
    }

    public function alertasResumo(Request $request)
    {
        if (! Auth::check()) {
            return response()->json(['success' => false, 'redirect' => '/login']);
        }

        return response()->json($this->alertaCentralService->obterResumo(Auth::id()));
    }

    public function alertasEvolucao(Request $request)
    {
        if (! Auth::check()) {
            return response()->json(['success' => false, 'redirect' => '/login']);
        }

        return response()->json($this->alertaCentralService->obterEvolucao(Auth::id()));
    }

    public function alertasMarcarStatus(Request $request, int $id)
    {
        if (! Auth::check()) {
            return response()->json(['success' => false, 'redirect' => '/login']);
        }

        $request->validate([
            'status' => 'required|in:ativo,visto,resolvido,ignorado',
            'notas' => 'nullable|string|max:1000',
        ]);

        $alerta = $this->alertaCentralService->marcarStatus(
            $id,
            Auth::id(),
            $request->input('status'),
            $request->input('notas')
        );

        return response()->json(['success' => true, 'alerta' => $alerta]);
    }

    public function alertasRecalcular(Request $request)
    {
        if (! Auth::check()) {
            return response()->json(['success' => false, 'redirect' => '/login']);
        }

        $resultado = $this->alertaCentralService->recalcular(Auth::id());
        $resumo = $this->alertaCentralService->obterResumo(Auth::id());

        return response()->json([
            'success' => true,
            'resultado' => $resultado,
            'resumo' => $resumo,
        ]);
    }

    public function alertaDetalhes(Request $request, int $id)
    {
        if (! Auth::check()) {
            if ($this->isAjaxRequest($request)) {
                return response()->json(['success' => false, 'redirect' => '/login']);
            }
            return redirect('/login');
        }

        $userId = Auth::id();
        
        $alerta = Alerta::where('id', $id)
            ->where('user_id', $userId)
            ->with(['cliente', 'participante'])
            ->firstOrFail();

        $data = ['alerta' => $alerta];
        $viewName = self::AUTH_VIEW_PREFIX . 'alertas.show';

        if ($this->isAjaxRequest($request)) {
            return view($viewName, $data);
        }

        return view(self::AUTH_LAYOUT_VIEW, array_merge([
            'initialView' => $viewName,
        ], $data));
    }

    // ==================== USUÁRIO ====================

    public function configuracoes(Request $request)
    {
        $configuracoesView = self::AUTH_VIEW_PREFIX.'usuario.configuracoes';

        if (! view()->exists($configuracoesView)) {
            abort(404);
        }

        if (! Auth::check()) {
            if ($this->isAjaxRequest($request)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não está logado',
                    'redirect' => '/login',
                ]);
            }

            return redirect('/login');
        }

        $user = Auth::user();
        $empresaAtual = $user->empresaPropria();

        $configuracoes = [
            'notificacoes' => [
                'email_ativo' => ! empty($user->email),
                'alertas_operacionais' => (bool) $user->alertas_operacionais,
                'alertas_monitoramento' => (bool) $user->alertas_monitoramento,
                'resumo_periodico' => (bool) $user->resumo_periodico,
                'canal_principal' => ! empty($user->email) ? 'E-mail' : 'Não configurado',
            ],
            'recursos' => [
                'consultas' => [
                    'label' => 'Consultas',
                    'descricao' => 'Consultas cadastrais e fiscais para acompanhamento operacional da base.',
                    'status_label' => 'Disponível',
                    'status_hex' => '#047857',
                ],
                'importacao_xml' => [
                    'label' => 'Importação XML',
                    'descricao' => 'Recepção e processamento de XMLs fiscais para composição do ambiente.',
                    'status_label' => 'Em evolução',
                    'status_hex' => '#d97706',
                ],
                'importacao_efd' => [
                    'label' => 'Importação EFD',
                    'descricao' => 'Carga de arquivos EFD para extração, auditoria e cruzamentos fiscais.',
                    'status_label' => 'Disponível',
                    'status_hex' => '#047857',
                ],
                'monitoramento' => [
                    'label' => 'Monitoramento',
                    'descricao' => 'Acompanhamento contínuo de ocorrências e sinais relevantes da operação.',
                    'status_label' => 'Em evolução',
                    'status_hex' => '#d97706',
                ],
            ],
            'preferencias' => [
                'dashboard_inicial' => 'Dashboard',
                'empresa_inicial_label' => $empresaAtual?->razao_social ?? $empresaAtual?->nome,
                'filtros_salvos' => false,
            ],
        ];

        $data = [
            'user' => $user,
            'empresaAtual' => $empresaAtual,
            'configuracoes' => $configuracoes,
            'resumo' => [
                'notificacoes_ativas' => collect($configuracoes['notificacoes'])
                    ->only(['email_ativo', 'alertas_operacionais', 'alertas_monitoramento', 'resumo_periodico'])
                    ->filter(fn ($valor) => $valor === true)
                    ->count(),
                'canais_ativos' => ! empty($user->email) ? 1 : 0,
                'recursos_disponiveis' => collect($configuracoes['recursos'])
                    ->filter(fn (array $recurso) => ($recurso['status_label'] ?? null) === 'Disponível')
                    ->count(),
                'empresa_principal_configurada' => $empresaAtual !== null,
            ],
        ];

        if ($this->isAjaxRequest($request)) {
            return view($configuracoesView, $data);
        }

        return view(self::AUTH_LAYOUT_VIEW, array_merge([
            'initialView' => $configuracoesView,
        ], $data));
    }

    public function atualizarNotificacaoConfiguracao(Request $request)
    {
        $payload = $request->validate([
            'campo' => ['required', 'string', Rule::in([
                'alertas_operacionais',
                'alertas_monitoramento',
                'resumo_periodico',
            ])],
            'valor' => ['required', 'boolean'],
        ]);

        $user = Auth::user();
        $user->{$payload['campo']} = $payload['valor'];
        $user->save();

        return response()->json([
            'success' => true,
            'campo' => $payload['campo'],
            'valor' => (bool) $user->{$payload['campo']},
        ]);
    }

    public function meuPlano(Request $request)
    {
        if (! Auth::check()) {
            if ($this->isAjaxRequest($request)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Voce nao esta logado',
                    'redirect' => '/login',
                ]);
            }

            return redirect('/login');
        }

        $user = Auth::user();
        $now = now();
        $mesInicio = $now->copy()->startOfMonth();
        $mesFim = $now->copy()->endOfMonth();
        $pricing = $this->pricingCatalogService->getCommercialSummaryForUser($user);

        // KPI 1: Saldo atual
        $saldoAtual = (int) $user->credits;

        // KPI 2: Creditos usados no mes
        $creditosUsadosMes = ConsultaLote::where('user_id', $user->id)
            ->where('status', 'concluido')
            ->whereBetween('created_at', [$mesInicio, $mesFim])
            ->sum('creditos_cobrados');

        // KPI 3: Consultas no mes
        $consultasMes = ConsultaLote::where('user_id', $user->id)
            ->whereBetween('created_at', [$mesInicio, $mesFim])
            ->count();

        // KPI 4: Media creditos/consulta
        $totalConsultas = ConsultaLote::where('user_id', $user->id)
            ->where('status', 'concluido')
            ->count();
        $totalCreditosHistorico = ConsultaLote::where('user_id', $user->id)
            ->where('status', 'concluido')
            ->sum('creditos_cobrados');
        $mediaCreditos = $totalConsultas > 0 ? round($totalCreditosHistorico / $totalConsultas, 1) : 0;

        // Ultimas 20 transacoes (consulta_lotes como fallback)
        $ultimasTransacoes = ConsultaLote::where('user_id', $user->id)
            ->with('plano:id,nome,codigo')
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        // Consumo mensal ultimos 6 meses
        $consumoMensal = [];
        for ($i = 5; $i >= 0; $i--) {
            $mes = $now->copy()->subMonths($i);
            $consumo = ConsultaLote::where('user_id', $user->id)
                ->where('status', 'concluido')
                ->whereYear('created_at', $mes->year)
                ->whereMonth('created_at', $mes->month)
                ->sum('creditos_cobrados');
            $consumoMensal[] = [
                'label' => $mes->translatedFormat('M/y'),
                'valor' => (int) $consumo,
            ];
        }

        $maxConsumo = max(array_column($consumoMensal, 'valor') ?: [1]);

        $planoView = self::AUTH_VIEW_PREFIX.'plano.index';

        $data = [
            'saldoAtual' => $saldoAtual,
            'creditosUsadosMes' => (int) $creditosUsadosMes,
            'consultasMes' => $consultasMes,
            'mediaCreditos' => $mediaCreditos,
            'ultimasTransacoes' => $ultimasTransacoes,
            'consumoMensal' => $consumoMensal,
            'maxConsumo' => $maxConsumo,
            'pricing' => $pricing,
            'trialResumo' => $this->buildTrialResumo($user),
        ];

        if ($this->isAjaxRequest($request)) {
            return view($planoView, $data);
        }

        return view(self::AUTH_LAYOUT_VIEW, array_merge([
            'initialView' => $planoView,
        ], $data));
    }

    public function creditos(Request $request)
    {
        if (! Auth::check()) {
            if ($this->isAjaxRequest($request)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Voce nao esta logado',
                    'redirect' => '/login',
                ]);
            }

            return redirect('/login');
        }

        $user = Auth::user();
        $pricing = $this->pricingCatalogService->getCommercialSummaryForUser($user);

        $saldoAtual = (int) $user->credits;

        $totalRecebido = (int) CreditTransaction::where('user_id', $user->id)
            ->where('amount', '>', 0)
            ->sum('amount');

        $totalConsumido = (int) abs(CreditTransaction::where('user_id', $user->id)
            ->where('amount', '<', 0)
            ->sum('amount'));

        $ultimaEntrada = CreditTransaction::where('user_id', $user->id)
            ->where('amount', '>', 0)
            ->orderBy('created_at', 'desc')
            ->first();

        $historicoCreditos = CreditTransaction::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(30)
            ->get();

        $creditosView = self::AUTH_VIEW_PREFIX.'creditos.index';

        $data = [
            'saldoAtual' => $saldoAtual,
            'totalRecebido' => $totalRecebido,
            'totalConsumido' => $totalConsumido,
            'ultimaEntrada' => $ultimaEntrada,
            'historicoCreditos' => $historicoCreditos,
            'pacotes' => $this->pricingCatalogService->getPackages(),
            'pricing' => $pricing,
            'trialResumo' => $this->buildTrialResumo($user),
        ];

        if ($this->isAjaxRequest($request)) {
            return view($creditosView, $data);
        }

        return view(self::AUTH_LAYOUT_VIEW, array_merge([
            'initialView' => $creditosView,
        ], $data));
    }

    public function checkout(Request $request, string $pacote)
    {
        $dados = $this->pricingCatalogService->resolveCheckoutSelection($pacote, $request->query('amount'));

        if (! $dados) {
            return redirect()
                ->route('app.creditos')
                ->withErrors([
                    'amount' => 'Informe um valor válido a partir de R$ '.number_format($this->pricingCatalogService->getMinimumDeposit(), 0, ',', '.').'.',
                ]);
        }

        $checkoutView = self::AUTH_VIEW_PREFIX.'plano.checkout';

        if ($this->isAjaxRequest($request)) {
            return view($checkoutView, [
                'pacote' => $dados,
                'pricing' => $this->pricingCatalogService->getCommercialSummaryForUser(Auth::user()),
            ]);
        }

        return view(self::AUTH_LAYOUT_VIEW, [
            'initialView' => $checkoutView,
            'pacote' => $dados,
            'pricing' => $this->pricingCatalogService->getCommercialSummaryForUser(Auth::user()),
        ]);
    }

    public function scoreFiscalPlaceholder(Request $request)
    {
        return $this->renderPlaceholder($request,
            'Score Fiscal',
            'Avaliação de risco fiscal e compliance de participantes.',
            'document-check',
            [
                'Score de risco ponderado por categoria',
                'Classificação automática (baixo a crítico)',
                'Consulta em lote de participantes',
                'Monitoramento contínuo de CNDs',
            ]
        );
    }

    public function validacaoPlaceholder(Request $request)
    {
        return $this->renderPlaceholder($request,
            'Validação Contábil',
            'Análise e validação inteligente de notas fiscais.',
            'calculator',
            [
                'Validação automática de notas fiscais',
                'Alertas por nível (bloqueante, atenção, info)',
                'Análise de CFOP, CST e NCM',
                'Score de conformidade por nota',
            ]
        );
    }

    public function biPlaceholder(Request $request)
    {
        return $this->renderPlaceholder($request,
            'BI Fiscal',
            'Dashboard gerencial para análise de faturamento, compras e tributos.',
            'chart',
            [
                'Faturamento por período e cliente',
                'Análise de compras e fornecedores',
                'Carga tributária efetiva',
                'Top 10 clientes e fornecedores',
            ]
        );
    }

    private function buildTrialResumo($user): array
    {
        $expiresAt = $user->trial_expires_at;
        $hasTrial = (bool) $user->trial_used;
        $isActive = $hasTrial && $expiresAt && now()->lt($expiresAt);
        $isExpired = $hasTrial && $expiresAt && now()->gte($expiresAt);

        return [
            'has_trial' => $hasTrial,
            'is_active' => $isActive,
            'is_expired' => $isExpired,
            'started_at' => $user->trial_started_at,
            'expires_at' => $expiresAt,
            'days_remaining' => $isActive && $expiresAt ? max(0, now()->diffInDays($expiresAt, false)) : 0,
            'granted' => (int) $user->trial_credits_granted,
            'remaining' => (int) $user->trial_credits_remaining,
            'expired' => (int) $user->trial_credits_expired,
        ];
    }
}
