<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Concerns\RespondeAjax;
use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\ConsultaLote;
use App\Models\ConsultaResultado;
use App\Models\MonitoramentoAssinatura;
use App\Models\MonitoramentoPlano;
use App\Models\Participante;
use App\Models\ParticipanteGrupo;
use App\Services\ConsultaReportService;
use App\Services\CreditService;
use App\Services\ParecerFiscalService;
use App\Services\PricingCatalogService;
use App\Support\CertidaoBadge;
use App\Support\CndFederal;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ConsultaController extends Controller
{
    use RespondeAjax;

    private const AUTH_LAYOUT_VIEW = 'autenticado.layouts.app';

    public function __construct(
        protected CreditService $creditService,
        protected ConsultaReportService $reportService,
        protected PricingCatalogService $pricingCatalogService
    ) {}

    private function getViewPrefix(): string
    {
        return 'autenticado.consulta.';
    }

    /**
     * Página principal de consulta.
     */
    public function index(Request $request)
    {
        $consultaView = $this->getViewPrefix().'nova';

        if (! view()->exists($consultaView)) {
            abort(404);
        }

        if (! Auth::check()) {
            return $this->redirectToLogin($request);
        }

        $user = Auth::user();

        // Buscar catálogo legado de produtos consultáveis
        $planos = MonitoramentoPlano::ativos();

        // Status do teto de teste (pool único de 5 CNPJs somado entre os planos pagos antes da 1ª compra)
        $trialCaps = [];
        foreach ($planos as $planoItem) {
            if ($this->pricingCatalogService->planoTemTetoTrial($planoItem->codigo)) {
                $trialCaps[$planoItem->codigo] = $this->pricingCatalogService->trialCapStatus($user, $planoItem);
            }
        }

        // Buscar clientes do usuário
        $clientes = Cliente::where('user_id', $user->id)
            ->orderBy('razao_social')
            ->get();

        $participantesUfs = Participante::where('user_id', $user->id)
            ->excludingEmpresaPropria()
            ->whereNotNull('uf')
            ->where('uf', '!=', '')
            ->selectRaw('upper(uf) as uf')
            ->distinct()
            ->orderBy('uf')
            ->pluck('uf');

        $clientesUfs = Cliente::where('user_id', $user->id)
            ->where('ativo', true)
            ->whereNotNull('uf')
            ->where('uf', '!=', '')
            ->selectRaw('upper(uf) as uf')
            ->distinct()
            ->orderBy('uf')
            ->pluck('uf');

        // Buscar grupos do usuário
        $grupos = ParticipanteGrupo::doUsuario($user->id)
            ->withCount('participantes')
            ->orderBy('nome')
            ->get();

        // Contagem total de participantes
        $totalParticipantes = Participante::where('user_id', $user->id)->count();

        // Últimos lotes do usuário
        $ultimosLotes = ConsultaLote::where('user_id', $user->id)
            ->with('plano')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $data = [
            'planos' => $planos,
            'clientes' => $clientes,
            'grupos' => $grupos,
            'totalParticipantes' => $totalParticipantes,
            'ultimosLotes' => $ultimosLotes,
            'participantesUfs' => $participantesUfs,
            'clientesUfs' => $clientesUfs,
            'credits' => $this->creditService->getBalance($user),
            'complianceSources' => $this->pricingCatalogService->getComplianceSources(),
            'hasMadeFirstPurchase' => $this->pricingCatalogService->userHasFirstPurchase($user),
            'firstPurchaseLockedProducts' => $this->pricingCatalogService->getFirstPurchaseLockedProducts(),
            'trialCaps' => $trialCaps,
        ];

        if ($this->isAjaxRequest($request)) {
            $renderedView = view($consultaView, $data)->render();

            return response($renderedView)->header('Content-Type', 'text/html');
        }

        return view(self::AUTH_LAYOUT_VIEW, array_merge([
            'initialView' => $consultaView,
        ], $data));
    }

    /**
     * Retorna lista de participantes com filtros (AJAX).
     */
    public function getParticipantes(Request $request): JsonResponse
    {
        if (! Auth::check()) {
            return response()->json([
                'success' => false,
                'error' => 'Usuário não autenticado.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $user = Auth::user();

        $validated = $request->validate([
            'grupo_id' => 'nullable|integer|exists:participantes_grupos,id',
            'cliente_id' => 'nullable|integer|exists:clientes,id',
            'origem_tipo' => 'nullable|string|in:NFE,NFSE,CTE,SPED_EFD_FISCAL,SPED_EFD_CONTRIB,MANUAL',
            'tipo_documento' => 'nullable|string|in:PF,PJ',
            'situacao_cadastral' => 'nullable|string|max:50',
            'uf' => 'nullable|string|size:2',
            'busca' => 'nullable|string|max:100',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:10|max:100',
        ]);

        $perPage = $validated['per_page'] ?? 50;

        $query = Participante::where('user_id', $user->id)
            ->excludingEmpresaPropria()
            ->with([
                'grupos:id,nome,cor',
                'cliente:id,razao_social,is_empresa_propria',
                'assinaturas' => fn ($q) => $q
                    ->whereIn('status', ['ativo', 'pausado'])
                    ->orderByRaw("CASE WHEN status = 'ativo' THEN 0 ELSE 1 END")
                    ->orderBy('updated_at', 'desc'),
            ]);

        // Filtro por grupo
        if (! empty($validated['grupo_id'])) {
            $query->whereHas('grupos', function ($q) use ($validated) {
                $q->where('participantes_grupos.id', $validated['grupo_id']);
            });
        }

        // Filtro por cliente
        if (! empty($validated['cliente_id'])) {
            $query->where('cliente_id', $validated['cliente_id']);
        }

        // Filtro por origem
        if (! empty($validated['origem_tipo'])) {
            $query->where('origem_tipo', $validated['origem_tipo']);
        }

        if (! empty($validated['tipo_documento'])) {
            $query->where('tipo_documento', strtoupper($validated['tipo_documento']));
        }

        if (! empty($validated['situacao_cadastral'])) {
            $query->where('situacao_cadastral', 'ILIKE', $validated['situacao_cadastral']);
        }

        if (! empty($validated['uf'])) {
            $query->where('uf', strtoupper($validated['uf']));
        }

        // Filtro por busca (CNPJ ou razão social)
        if (! empty($validated['busca'])) {
            $busca = $validated['busca'];
            $buscaLimpa = preg_replace('/[^0-9]/', '', $busca);

            $query->where(function ($q) use ($busca, $buscaLimpa) {
                // unaccent + ILIKE = busca insensível a acento E maiúscula/minúscula.
                $q->whereRaw('unaccent(razao_social) ILIKE unaccent(?)', ["%{$busca}%"])
                    ->orWhereRaw('unaccent(nome_fantasia) ILIKE unaccent(?)', ["%{$busca}%"]);

                if (strlen($buscaLimpa) >= 3) {
                    $q->orWhere('documento', 'LIKE', "%{$buscaLimpa}%");
                }
            });
        }

        $participantes = $query->orderBy('razao_social')
            ->paginate($perPage);
        $agora = Carbon::now();
        $participanteIds = $participantes->getCollection()->pluck('id')->all();
        $ultimosResultados = ConsultaResultado::query()
            ->whereIn('participante_id', $participanteIds)
            ->where('status', ConsultaResultado::STATUS_SUCESSO)
            ->orderBy('consultado_em', 'desc')
            ->get()
            ->unique('participante_id')
            ->keyBy('participante_id');

        return response()->json([
            'success' => true,
            'data' => $participantes->getCollection()->map(function ($participante) use ($ultimosResultados, $agora) {
                $ultimoResultado = $ultimosResultados->get($participante->id);
                $cndFederal = $ultimoResultado?->getCndFederal() ?? [];
                $alerta = $this->buildParticipanteAlertData($ultimoResultado, $agora);
                $assinatura = $participante->assinaturas->first();
                $ultimaConsultaEm = $participante->ultima_consulta_em;

                $consultaStatusLabel = 'Nunca foi consultado';
                $consultaStatusHex = '#9ca3af';
                $consultaStatusMeta = 'Situação cadastral: não consultada';

                if ($ultimaConsultaEm) {
                    $diasSemConsulta = $ultimaConsultaEm->diffInDays($agora);

                    if ($diasSemConsulta > 30) {
                        $consultaStatusLabel = 'Consulta desatualizada';
                        $consultaStatusHex = '#d97706';
                    } else {
                        $consultaStatusLabel = 'Consultado recentemente';
                        $consultaStatusHex = '#047857';
                    }

                    $consultaStatusMeta = 'Última atualização em '.$ultimaConsultaEm->format('d/m/Y H:i');
                }

                $cndStatus = strtoupper((string) ($cndFederal['status'] ?? ''));
                $cndValidade = $cndFederal['data_validade'] ?? null;
                $cndStatusLabel = 'Não consultada';
                $cndStatusHex = '#9ca3af';
                $cndMeta = 'CND: não consultada';

                $cndAnalise = CndFederal::analisar($cndFederal);

                if ($cndAnalise['indeterminado']) {
                    $cndStatusLabel = $cndAnalise['label'];
                    $cndStatusHex = $cndAnalise['hex'];
                    $cndMeta = $cndAnalise['motivo'] ?? 'Certidão não emitida pela fonte oficial.';
                } elseif ($cndStatus !== '') {
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

                $assinaturaLabel = null;
                $assinaturaHex = null;
                if ($assinatura instanceof MonitoramentoAssinatura) {
                    $assinaturaLabel = $assinatura->status === 'ativo' ? 'Monitoramento ativo' : 'Monitoramento pausado';
                    $assinaturaHex = $assinatura->status === 'ativo' ? '#1f2937' : '#6b7280';
                }

                return [
                    'id' => $participante->id,
                    'documento' => $participante->documento,
                    'documento_formatado' => $participante->cnpj_formatado,
                    'razao_social' => $participante->razao_social,
                    'nome_fantasia' => $participante->nome_fantasia,
                    'tipo_documento' => $participante->tipo_documento,
                    'is_cpf' => $participante->is_cpf,
                    'is_cnpj' => $participante->is_cnpj,
                    'pode_consultar' => $participante->is_cnpj,
                    'situacao_cadastral' => $participante->situacao_cadastral,
                    'ultima_consulta_em' => $ultimaConsultaEm?->toIso8601String(),
                    'consulta_status_label' => $consultaStatusLabel,
                    'consulta_status_hex' => $consultaStatusHex,
                    'consulta_status_meta' => $consultaStatusMeta,
                    'consultas_realizadas' => $alerta['consultas_realizadas'],
                    'cnd_federal_status_label' => $cndStatusLabel,
                    'cnd_federal_status_hex' => $cndStatusHex,
                    'cnd_federal_meta' => $cndMeta,
                    'alerta_nivel' => $alerta['nivel'],
                    'alerta_label' => $alerta['label'],
                    'alerta_hex' => $alerta['hex'],
                    'alerta_icone' => $alerta['icone'],
                    'alerta_detalhe' => $alerta['detalhe'],
                    'assinatura_label' => $assinaturaLabel,
                    'assinatura_hex' => $assinaturaHex,
                    'origem_tipo' => $participante->origem_tipo,
                    'created_at_formatado' => $participante->created_at?->format('d/m/Y') ?? '-',
                    'cliente' => $participante->cliente ? [
                        'id' => $participante->cliente->id,
                        'razao_social' => $participante->cliente->razao_social,
                        'is_empresa_propria' => $participante->cliente->is_empresa_propria,
                    ] : null,
                    'grupos' => $participante->grupos,
                ];
            })->values(),
            'pagination' => [
                'current_page' => $participantes->currentPage(),
                'last_page' => $participantes->lastPage(),
                'per_page' => $participantes->perPage(),
                'total' => $participantes->total(),
            ],
        ]);
    }

    /**
     * Deriva o alerta operacional exibido na lista de participantes.
     *
     * @return array{
     *     nivel: string,
     *     label: string,
     *     detalhe: string,
     *     hex: string,
     *     icone: string,
     *     consultas_realizadas: array<int, string>
     * }
     */
    private function buildParticipanteAlertData(?ConsultaResultado $resultado, Carbon $agora): array
    {
        $consultasRealizadas = $this->normalizeConsultasRealizadas($resultado);

        if (! $resultado) {
            return [
                'nivel' => 'grave',
                'label' => 'Nunca consultado',
                'detalhe' => 'Participante sem histórico de consulta.',
                'hex' => '#ea580c',
                'icone' => 'alert-triangle',
                'consultas_realizadas' => [],
            ];
        }

        $dados = $resultado->resultado_dados ?? [];
        $situacao = strtoupper((string) ($dados['situacao_cadastral'] ?? ''));

        if (in_array($situacao, ['BAIXADA', 'INAPTA', 'SUSPENSA', 'NULA'], true)) {
            return [
                'nivel' => 'super_grave',
                'label' => 'Risco crítico',
                'detalhe' => 'Situação cadastral '.$situacao.'.',
                'hex' => '#dc2626',
                'icone' => 'x-circle',
                'consultas_realizadas' => $consultasRealizadas,
            ];
        }

        $cndFederal = $resultado->getCndFederal() ?? [];
        $cndStatus = strtoupper(trim((string) ($cndFederal['status'] ?? '')));
        $cndValidade = $cndFederal['data_validade'] ?? $cndFederal['validade'] ?? null;

        if ($cndStatus !== '') {
            if (
                in_array($cndStatus, ['POSITIVA', 'IRREGULAR', 'IRREGULARIDADE'], true) ||
                str_contains($cndStatus, 'SUSPENS')
            ) {
                return [
                    'nivel' => 'super_grave',
                    'label' => 'Risco crítico',
                    'detalhe' => 'CND Federal com pendência: '.$cndStatus.'.',
                    'hex' => '#dc2626',
                    'icone' => 'x-circle',
                    'consultas_realizadas' => $consultasRealizadas,
                ];
            }

            if ($cndValidade) {
                try {
                    $dataValidade = Carbon::parse($cndValidade);

                    if ($agora->greaterThanOrEqualTo($dataValidade)) {
                        return [
                            'nivel' => 'super_grave',
                            'label' => 'Risco crítico',
                            'detalhe' => 'CND Federal vencida em '.$dataValidade->format('d/m/Y').'.',
                            'hex' => '#dc2626',
                            'icone' => 'x-circle',
                            'consultas_realizadas' => $consultasRealizadas,
                        ];
                    }
                } catch (\Exception $e) {
                    // Mantém o fluxo sem promover alerta em caso de formato inesperado.
                }
            }
        }

        $somenteSituacaoCadastral = in_array('situacao_cadastral', $consultasRealizadas, true) &&
            count(array_diff($consultasRealizadas, ['situacao_cadastral'])) === 0;

        if ($somenteSituacaoCadastral) {
            return [
                'nivel' => 'medio',
                'label' => 'Consulta inicial apenas',
                'detalhe' => 'Apenas a situação cadastral foi consultada.',
                'hex' => '#6b7280',
                'icone' => 'info-circle',
                'consultas_realizadas' => $consultasRealizadas,
            ];
        }

        return [
            'nivel' => 'ok',
            'label' => 'Consultado sem alertas críticos',
            'detalhe' => 'Última consulta sem alertas críticos.',
            'hex' => '#047857',
            'icone' => 'check-circle',
            'consultas_realizadas' => $consultasRealizadas,
        ];
    }

    /**
     * Normaliza consultas realizadas e aplica fallback com base nos dados retornados.
     *
     * @return array<int, string>
     */
    private function normalizeConsultasRealizadas(?ConsultaResultado $resultado): array
    {
        if (! $resultado) {
            return [];
        }

        $consultas = collect($resultado->getConsultasRealizadas())
            ->map(function ($item) {
                if (! is_string($item)) {
                    return null;
                }

                return strtolower(trim($item));
            })
            ->filter()
            ->values()
            ->all();

        if (! empty($consultas)) {
            return array_values(array_unique($consultas));
        }

        $dados = $resultado->resultado_dados ?? [];
        $mapaFallback = [
            'situacao_cadastral',
            'dados_cadastrais',
            'endereco',
            'cnaes',
            'qsa',
            'simples_nacional',
            'mei',
            'sintegra',
            'tcu_consolidada',
            'cnd_federal',
            'crf_fgts',
            'cnd_estadual',
            'cndt',
            'protestos',
            'lista_devedores_pgfn',
            'trabalho_escravo',
            'ibama_autuacoes',
            'processos_cnj',
        ];

        return collect($mapaFallback)
            ->filter(fn ($chave) => array_key_exists($chave, $dados) && ! empty($dados[$chave]))
            ->values()
            ->all();
    }

    /**
     * Retorna IDs de participantes de um grupo específico.
     */
    public function getParticipantesGrupo(Request $request, int $grupoId): JsonResponse
    {
        if (! Auth::check()) {
            return response()->json([
                'success' => false,
                'error' => 'Usuário não autenticado.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $user = Auth::user();

        $grupo = ParticipanteGrupo::where('id', $grupoId)
            ->where('user_id', $user->id)
            ->first();

        if (! $grupo) {
            return response()->json([
                'success' => false,
                'error' => 'Grupo não encontrado.',
            ], Response::HTTP_NOT_FOUND);
        }

        $participanteIds = $grupo->participantes()
            ->somenteCnpj()
            ->pluck('participantes.id')
            ->toArray();

        return response()->json([
            'success' => true,
            'grupo_id' => $grupoId,
            'grupo_nome' => $grupo->nome,
            'participante_ids' => $participanteIds,
            'total' => count($participanteIds),
        ]);
    }

    /**
     * Calcula custo da consulta antes de executar.
     */
    public function calcularCusto(Request $request): JsonResponse
    {
        if (! Auth::check()) {
            return response()->json([
                'success' => false,
                'error' => 'Usuário não autenticado.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $user = Auth::user();

        $validated = $request->validate([
            'participante_ids' => 'required|array|min:1',
            'participante_ids.*' => 'integer|exists:participantes,id',
            'plano_id' => 'required|integer|exists:monitoramento_planos,id',
        ]);

        // Verificar que os participantes pertencem ao usuário
        $participantesValidos = Participante::where('user_id', $user->id)
            ->somenteCnpj()
            ->whereIn('id', $validated['participante_ids'])
            ->count();

        if ($participantesValidos !== count($validated['participante_ids'])) {
            return response()->json([
                'success' => false,
                'error' => 'Alguns participantes selecionados são inválidos para consulta.',
            ], Response::HTTP_FORBIDDEN);
        }

        $plano = MonitoramentoPlano::find($validated['plano_id']);

        if (! $plano || ! $plano->is_active) {
            return response()->json([
                'success' => false,
                'error' => 'Produto de consulta não disponível.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $totalParticipantes = count($validated['participante_ids']);

        $trialCap = $this->pricingCatalogService->trialCapStatus($user, $plano, $totalParticipantes);

        if ($trialCap['bloqueado']) {
            return response()->json([
                'success' => false,
                'error' => sprintf(
                    'Você usou %d de %d consultas liberadas no período de teste. Faça um depósito para liberar consultas ilimitadas.',
                    $trialCap['usados'],
                    $trialCap['limite']
                ),
                'trial_cap_atingido' => true,
                'trial_cap' => $trialCap,
                'produto_codigo' => $plano->codigo,
            ], Response::HTTP_FORBIDDEN);
        }

        $custoUnitario = $this->pricingCatalogService->getProductCreditsByPlan($plano, $user);
        $custoTotal = $totalParticipantes * $custoUnitario;
        $saldoAtual = $this->creditService->getBalance($user);
        $saldoApos = $saldoAtual - $custoTotal;

        return response()->json([
            'success' => true,
            'trial_cap' => $trialCap,
            'calculo' => [
                'total_participantes' => $totalParticipantes,
                'produto_codigo' => $plano->codigo,
                'produto_nome' => $plano->nome,
                'plano_codigo' => $plano->codigo,
                'plano_nome' => $plano->nome,
                'custo_unitario' => $custoUnitario,
                'custo_total' => $custoTotal,
                'is_gratuito' => $plano->is_gratuito,
                'saldo_atual' => $saldoAtual,
                'saldo_apos' => $saldoApos,
                'creditos_suficientes' => $saldoApos >= 0,
            ],
        ]);
    }

    /**
     * Executa a consulta de lote.
     */
    public function executar(Request $request): JsonResponse
    {
        if (! Auth::check()) {
            return response()->json([
                'success' => false,
                'error' => 'Usuário não autenticado.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $user = Auth::user();

        $validated = $request->validate([
            // Consulta serve dois escopos: participantes (contrapartes) e/ou clientes (empresas
            // geridas, incl. empresa própria). Pelo menos um dos dois precisa vir preenchido.
            'participante_ids' => 'nullable|array|max:1000',
            'participante_ids.*' => 'integer|exists:participantes,id',
            'cliente_ids' => 'nullable|array|max:1000',
            'cliente_ids.*' => 'integer|exists:clientes,id',
            'plano_id' => 'required|integer|exists:monitoramento_planos,id',
            'cliente_id' => 'nullable|integer|exists:clientes,id',
            'tab_id' => 'required|string|max:36',
        ]);

        $participanteIds = $validated['participante_ids'] ?? [];
        $clienteIds = $validated['cliente_ids'] ?? [];

        if (empty($participanteIds) && empty($clienteIds)) {
            return response()->json([
                'success' => false,
                'error' => 'Selecione ao menos um participante ou cliente para consultar.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Verificar que os participantes pertencem ao usuário
        $participantes = Participante::where('user_id', $user->id)
            ->somenteCnpj()
            ->whereIn('id', $participanteIds)
            ->get(['id', 'documento', 'razao_social', 'uf', 'crt']);

        if ($participantes->count() !== count($participanteIds)) {
            return response()->json([
                'success' => false,
                'error' => 'Alguns participantes selecionados são inválidos para consulta.',
            ], Response::HTTP_FORBIDDEN);
        }

        // Clientes (CNPJ próprio): exige documento com 14 dígitos (CNPJ).
        $clientes = \App\Models\Cliente::where('user_id', $user->id)
            ->whereIn('id', $clienteIds)
            ->whereRaw("length(regexp_replace(coalesce(documento, ''), '[^0-9]', '', 'g')) = 14")
            ->get(['id', 'documento', 'razao_social']);

        if ($clientes->count() !== count($clienteIds)) {
            return response()->json([
                'success' => false,
                'error' => 'Alguns clientes selecionados são inválidos (CNPJ ausente) para consulta.',
            ], Response::HTTP_FORBIDDEN);
        }

        $plano = MonitoramentoPlano::find($validated['plano_id']);

        if (! $plano || ! $plano->is_active) {
            return response()->json([
                'success' => false,
                'error' => 'Produto de consulta não disponível.',
            ], Response::HTTP_BAD_REQUEST);
        }

        // Lista unificada de alvos {tipo, id, cnpj, uf, crt}
        $alvos = $participantes->map(fn ($p) => [
            'tipo' => 'participante', 'id' => $p->id,
            'cnpj' => preg_replace('/[^0-9]/', '', (string) $p->documento), 'uf' => $p->uf, 'crt' => $p->crt,
        ])->concat($clientes->map(fn ($c) => [
            'tipo' => 'cliente', 'id' => $c->id,
            'cnpj' => preg_replace('/[^0-9]/', '', (string) $c->documento), 'uf' => $c->uf ?? null, 'crt' => null,
        ]))->values();

        // Calcular custo
        $totalParticipantes = $alvos->count();

        $trialCap = $this->pricingCatalogService->trialCapStatus($user, $plano, $totalParticipantes);

        if ($trialCap['bloqueado']) {
            return response()->json([
                'success' => false,
                'error' => sprintf(
                    'Você usou %d de %d consultas liberadas no período de teste. Faça um depósito para liberar consultas ilimitadas.',
                    $trialCap['usados'],
                    $trialCap['limite']
                ),
                'trial_cap_atingido' => true,
                'trial_cap' => $trialCap,
                'produto_codigo' => $plano->codigo,
            ], Response::HTTP_FORBIDDEN);
        }

        $custoUnitario = $this->pricingCatalogService->getProductCreditsByPlan($plano, $user);
        $custoTotal = $totalParticipantes * $custoUnitario;

        // Verificar créditos (se não for gratuito)
        if (! $plano->is_gratuito && ! $this->creditService->hasEnough($user, $custoTotal)) {
            return response()->json([
                'success' => false,
                'error' => 'Créditos insuficientes.',
                'creditos_necessarios' => $custoTotal,
                'creditos_disponiveis' => $this->creditService->getBalance($user),
            ], Response::HTTP_PAYMENT_REQUIRED);
        }

        // Consulta de CNPJ roda 100% na camada de consultas do Laravel (cutover concluído
        // em 2026-06-07 — não há mais fallback n8n). Se a camada não cobre todas as fontes
        // do plano (hoje só 'enterprise', inativo, com fontes não implementadas), recusa.
        $consultasIncluidas = $plano->resolvedConsultasIncluidas();
        $registry = app(\App\Services\Consultas\FonteRegistry::class);

        if (! $registry->cobre($consultasIncluidas)) {
            Log::error('Consultas: plano sem cobertura na camada Laravel', [
                'plano' => $plano->codigo,
                'consultas_incluidas' => $consultasIncluidas,
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Este plano ainda não está disponível para consulta.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            // Debitar créditos (se não for gratuito)
            if (! $plano->is_gratuito) {
                $debitado = $this->creditService->deduct($user, $custoTotal);
                if (! $debitado) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Falha ao debitar créditos. Tente novamente.',
                    ], Response::HTTP_INTERNAL_SERVER_ERROR);
                }
            }

            // Criar lote
            $lote = ConsultaLote::create([
                'user_id' => $user->id,
                'cliente_id' => $validated['cliente_id'] ?? null,
                'plano_id' => $plano->id,
                'status' => ConsultaLote::STATUS_PROCESSANDO,
                'total_participantes' => $totalParticipantes,
                'creditos_cobrados' => $custoTotal,
                'tab_id' => $validated['tab_id'],
            ]);

            // Associar participantes (pivot é participante-específico; clientes ficam no
            // consulta_resultados.cliente_id, sem pivot).
            if (! empty($participanteIds)) {
                $lote->participantes()->attach($participanteIds);
            }

            Log::info('Consulta: lote criado', [
                'consulta_lote_id' => $lote->id,
                'user_id' => $user->id,
                'produto' => $plano->codigo,
                'total_alvos' => $totalParticipantes,
                'participantes' => count($participanteIds),
                'clientes' => count($clienteIds),
                'creditos_cobrados' => $custoTotal,
            ]);

            $etapas = $plano->resolvedEtapas();

            // values() reindexa 0..N-1 para o índice 1-based do alvo no lote (progresso global).
            $jobs = $alvos->values()->map(fn ($alvo, $i) => new \App\Jobs\ProcessarConsultaJob(
                loteId: $lote->id,
                alvoTipo: $alvo['tipo'],
                alvoId: $alvo['id'],
                userId: $user->id,
                tabId: $validated['tab_id'],
                consultasIncluidas: $consultasIncluidas,
                alvo: [
                    'cnpj' => $alvo['cnpj'],
                    'uf' => $alvo['uf'],
                    'crt' => $alvo['crt'],
                ],
                etapas: $etapas,
                alvoIndice: $i + 1,
                totalAlvos: $totalParticipantes,
            ))->all();

            \Illuminate\Support\Facades\Bus::batch($jobs)
                ->name("consulta-lote-{$lote->id}")
                ->then(function () use ($lote) {
                    app(\App\Services\Consultas\FecharLoteService::class)
                        ->fechar($lote->id, resumo: ['engine' => 'laravel']);
                })
                ->dispatch();

            return response()->json([
                'success' => true,
                'consulta_lote_id' => $lote->id,
                'redirect_url' => route('app.consulta.lote.show', ['id' => $lote->id]),
                'message' => 'Consulta iniciada com sucesso.',
                'creditos_cobrados' => $custoTotal,
                'novo_saldo' => $this->creditService->getBalance($user),
                'etapas' => $etapas,
            ]);

        } catch (\Exception $e) {
            Log::error('Consulta: exceção ao executar', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Se lote foi criado, marcar como erro
            if (isset($lote)) {
                $lote->update([
                    'status' => ConsultaLote::STATUS_ERRO,
                    'error_code' => 'INTERNAL_ERROR',
                    'error_message' => $e->getMessage(),
                ]);

                // Estornar créditos
                if (! $plano->is_gratuito && $custoTotal > 0) {
                    $this->creditService->add($user, $custoTotal);
                }
            }

            return response()->json([
                'success' => false,
                'error' => 'Erro interno ao processar consulta.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * SSE para acompanhar progresso do lote.
     */
    public function streamProgresso(Request $request)
    {
        if (! Auth::check()) {
            return response()->json([
                'success' => false,
                'error' => 'Usuário não autenticado.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $userId = auth()->id();
        $tabId = $request->query('tab_id');

        if (! $tabId) {
            return response()->json([
                'success' => false,
                'error' => 'tab_id obrigatório.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $cacheKey = "progresso:{$userId}:{$tabId}";

        Log::info('SSE Consulta streamProgresso iniciado', [
            'user_id' => $userId,
            'tab_id' => $tabId,
            'cache_key' => $cacheKey,
        ]);

        return response()->stream(function () use ($cacheKey, $userId, $tabId) {
            // Garantir que PHP não bufferize a saída SSE
            if (function_exists('ini_set')) {
                ini_set('output_buffering', 'Off');
                ini_set('zlib.output_compression', 'Off');
            }
            // Padding inicial para forçar flush em proxies que ainda bufferizam
            echo str_repeat(' ', 2048)."\n";

            $tentativas = 0;
            $maxTentativas = 600; // 5 minutos com 0.5s de intervalo
            $lastDataHash = null;

            echo ": SSE connection established for consulta progress stream (user:{$userId}, tab:{$tabId})\n";
            echo "retry: 3000\n\n";
            if (ob_get_level() > 0) {
                ob_flush();
            }
            flush();

            while ($tentativas < $maxTentativas) {
                try {
                    $data = Cache::get($cacheKey);

                    if ($data) {
                        $hashData = array_diff_key($data, ['updated_at' => true]);
                        $currentHash = md5(json_encode($hashData));

                        if ($currentHash !== $lastDataHash) {
                            $lastDataHash = $currentHash;

                            echo 'data: '.json_encode($data)."\n\n";

                            if (ob_get_level() > 0) {
                                ob_flush();
                            }
                            flush();

                            if (in_array($data['status'] ?? '', [ConsultaLote::STATUS_FINALIZADO, ConsultaLote::STATUS_ERRO], true)) {
                                Log::info('SSE Consulta streamProgresso: status terminal recebido', [
                                    'user_id' => $userId,
                                    'tab_id' => $tabId,
                                    'status' => $data['status'],
                                ]);
                                Cache::forget($cacheKey);
                                break;
                            }
                        }
                    }

                    if (connection_aborted()) {
                        Log::info('SSE Consulta streamProgresso: conexão abortada pelo cliente', [
                            'user_id' => $userId,
                            'tab_id' => $tabId,
                        ]);
                        break;
                    }

                    usleep(500000);
                    $tentativas++;

                    if ($tentativas % 15 === 0) {
                        echo ": ping\n\n";
                        if (ob_get_level() > 0) {
                            ob_flush();
                        }
                        flush();
                    }

                } catch (\Exception $e) {
                    Log::error('SSE Consulta streamProgresso: erro no loop', [
                        'user_id' => $userId,
                        'tab_id' => $tabId,
                        'error' => $e->getMessage(),
                    ]);
                    usleep(500000);
                    $tentativas++;
                    if (connection_aborted()) {
                        break;
                    }
                }
            }

            if ($tentativas >= $maxTentativas) {
                echo 'data: '.json_encode([
                    'status' => 'timeout',
                    'progresso' => 0,
                    'mensagem' => 'Tempo limite atingido. Verifique o histórico.',
                ])."\n\n";
                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
                Log::warning('SSE Consulta streamProgresso: timeout', [
                    'user_id' => $userId,
                    'tab_id' => $tabId,
                ]);
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
            'Connection' => 'keep-alive',
        ]);
    }

    /**
     * Download do relatório de um lote (CSV ou PDF).
     *
     * Gera o relatório on-demand a partir dos dados em consulta_resultados.
     * Fallback para report_csv_base64 se não houver resultados na nova tabela.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function baixarLote(Request $request, int $id)
    {
        if (! Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        $lote = ConsultaLote::where('id', $id)
            ->where('user_id', $user->id)
            ->with(['plano', 'resultados.participante'])
            ->first();

        if (! $lote) {
            abort(404, 'Lote não encontrado.');
        }

        if (! $lote->isFinalizado()) {
            abort(400, 'Lote ainda não foi processado.');
        }

        $formato = strtolower($request->query('formato', 'csv'));

        // Se tem resultados na nova tabela, gerar on-demand
        if ($lote->hasResultados()) {
            if ($formato === 'pdf') {
                $pdf = $this->reportService->gerarPdf($lote);
                $filename = "consulta_lote_{$lote->id}.pdf";

                return $pdf->download($filename);
            }

            // CSV
            $csvContent = $this->reportService->gerarCsv($lote);
            $filename = "consulta_lote_{$lote->id}.csv";

            return response($csvContent)
                ->header('Content-Type', 'text/csv; charset=utf-8')
                ->header('Content-Disposition', 'attachment; filename="'.$filename.'"');
        }

        abort(404, 'Relatório não disponível.');
    }

    /**
     * Adiciona um CNPJ como participante (cadastro rápido).
     * Opcionalmente associa a um Cliente existente.
     */
    public function adicionarCnpj(Request $request): JsonResponse
    {
        if (! Auth::check()) {
            return response()->json([
                'success' => false,
                'error' => 'Usuário não autenticado.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $user = Auth::user();

        $cnpjRaw = $request->input('cnpj', '');
        $cnpj = preg_replace('/[^0-9]/', '', trim($cnpjRaw));

        if (strlen($cnpj) !== 14) {
            return response()->json([
                'success' => false,
                'error' => 'CNPJ inválido. Informe 14 dígitos.',
            ], Response::HTTP_BAD_REQUEST);
        }

        // Associação opcional a cliente existente
        $clienteId = null;
        $clienteIdInput = $request->input('cliente_id');

        if ($clienteIdInput) {
            $clienteId = (int) $clienteIdInput;
            $clienteExists = Cliente::where('id', $clienteId)
                ->where('user_id', $user->id)
                ->exists();

            if (! $clienteExists) {
                return response()->json([
                    'success' => false,
                    'error' => 'Cliente não encontrado.',
                ], Response::HTTP_BAD_REQUEST);
            }
        }

        $participante = Participante::firstOrCreate(
            ['user_id' => $user->id, 'documento' => $cnpj],
            [
                'origem_tipo' => 'MANUAL',
                'tipo_documento' => 'PJ',
                'cliente_id' => $clienteId,
            ]
        );

        $isNew = $participante->wasRecentlyCreated;

        // Se já existia e cliente_id foi informado, atualizar vínculo
        if (! $isNew && $clienteId && ! $participante->cliente_id) {
            $participante->update(['cliente_id' => $clienteId]);
        }

        $message = $isNew
            ? 'Participante adicionado com sucesso.'
            : 'CNPJ já cadastrado. Selecionado para consulta.';

        if ($clienteId && ! $isNew && ! $participante->getOriginal('cliente_id')) {
            $message = 'CNPJ já cadastrado. Vinculado ao cliente.';
        }

        // Load cliente relationship for response
        $participante->load('cliente:id,razao_social');

        return response()->json([
            'success' => true,
            'is_new' => $isNew,
            'participante' => [
                'id' => $participante->id,
                'cnpj' => $participante->documento,
                'razao_social' => $participante->razao_social,
                'uf' => $participante->uf,
                'cliente' => $participante->cliente ? [
                    'id' => $participante->cliente->id,
                    'razao_social' => $participante->cliente->razao_social,
                ] : null,
            ],
            'message' => $message,
        ]);
    }

    /**
     * Retorna IDs de participantes vinculados a clientes específicos.
     */
    public function getParticipanteIdsByClientes(Request $request): JsonResponse
    {
        if (! Auth::check()) {
            return response()->json([
                'success' => false,
                'error' => 'Usuário não autenticado.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $validated = $request->validate([
            'cliente_ids' => 'required|array|min:1',
            'cliente_ids.*' => 'integer',
        ]);

        $ids = Participante::where('user_id', auth()->id())
            ->whereIn('cliente_id', $validated['cliente_ids'])
            ->somenteCnpj()
            ->pluck('id');

        return response()->json([
            'success' => true,
            'ids' => $ids,
        ]);
    }

    /**
     * Retorna lista de clientes do usuário (AJAX).
     */
    public function getClientes(Request $request): JsonResponse
    {
        if (! Auth::check()) {
            return response()->json([
                'success' => false,
                'error' => 'Usuário não autenticado.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $user = Auth::user();

        $query = Cliente::where('user_id', $user->id)
            ->where('ativo', true)
            ->withCount('participantes');

        $validated = $request->validate([
            'busca' => 'nullable|string|max:100',
            'tipo_pessoa' => 'nullable|string|in:PF,PJ',
            'situacao_cadastral' => 'nullable|string|max:50',
            'uf' => 'nullable|string|size:2',
            'faixa_participantes' => 'nullable|string|in:0,1-10,11-50,51+',
        ]);

        $busca = $validated['busca'] ?? null;
        if ($busca) {
            $buscaLimpa = preg_replace('/[^0-9]/', '', $busca);
            $query->where(function ($q) use ($busca, $buscaLimpa) {
                // unaccent + ILIKE = busca insensível a acento E maiúscula/minúscula.
                $q->whereRaw('unaccent(razao_social) ILIKE unaccent(?)', ["%{$busca}%"])
                    ->orWhereRaw('unaccent(nome) ILIKE unaccent(?)', ["%{$busca}%"]);
                if (strlen($buscaLimpa) >= 3) {
                    $q->orWhere('documento', 'LIKE', "%{$buscaLimpa}%");
                }
            });
        }

        if (! empty($validated['tipo_pessoa'])) {
            $query->where('tipo_pessoa', strtoupper($validated['tipo_pessoa']));
        }

        if (! empty($validated['situacao_cadastral'])) {
            $query->where('situacao_cadastral', 'ILIKE', $validated['situacao_cadastral']);
        }

        if (! empty($validated['uf'])) {
            $query->where('uf', strtoupper($validated['uf']));
        }

        if (! empty($validated['faixa_participantes'])) {
            match ($validated['faixa_participantes']) {
                '0' => $query->having('participantes_count', '=', 0),
                '1-10' => $query->havingBetween('participantes_count', [1, 10]),
                '11-50' => $query->havingBetween('participantes_count', [11, 50]),
                '51+' => $query->having('participantes_count', '>=', 51),
                default => null,
            };
        }

        $clientes = $query->orderBy('razao_social')
            ->get(['id', 'razao_social', 'nome', 'documento', 'tipo_pessoa', 'is_empresa_propria', 'situacao_cadastral', 'uf']);

        $agora = Carbon::now();
        $documentosClientes = $clientes
            ->pluck('documento')
            ->filter()
            ->unique()
            ->values();

        $participantesEquivalentes = Participante::query()
            ->where('user_id', $user->id)
            ->whereIn('documento', $documentosClientes)
            ->orderBy('id')
            ->get(['id', 'documento'])
            ->unique('documento')
            ->keyBy('documento');

        $participanteIds = $participantesEquivalentes->pluck('id')->all();
        $ultimosResultados = ConsultaResultado::query()
            ->whereIn('participante_id', $participanteIds)
            ->where('status', ConsultaResultado::STATUS_SUCESSO)
            ->orderBy('consultado_em', 'desc')
            ->get()
            ->unique('participante_id')
            ->keyBy('participante_id');

        return response()->json([
            'success' => true,
            'data' => $clientes->map(function ($c) use ($participantesEquivalentes, $ultimosResultados, $agora) {
                $participanteEquivalente = $participantesEquivalentes->get($c->documento);
                $ultimoResultado = $participanteEquivalente
                    ? $ultimosResultados->get($participanteEquivalente->id)
                    : null;
                $alerta = $this->buildParticipanteAlertData($ultimoResultado, $agora);

                return [
                    'id' => $c->id,
                    'razao_social' => $c->razao_social ?? $c->nome,
                    'nome' => $c->nome,
                    'documento' => $c->documento,
                    'tipo_pessoa' => $c->tipo_pessoa,
                    'is_empresa_propria' => $c->is_empresa_propria,
                    'situacao_cadastral' => $c->situacao_cadastral,
                    'uf' => $c->uf,
                    'participantes_count' => $c->participantes_count,
                    'alerta_nivel' => $alerta['nivel'],
                    'alerta_label' => $alerta['label'],
                    'alerta_hex' => $alerta['hex'],
                    'alerta_icone' => $alerta['icone'],
                    'alerta_detalhe' => $alerta['detalhe'],
                ];
            }),
        ]);
    }

    /**
     * Retorna lista de grupos do usuário (AJAX).
     */
    public function getGrupos(Request $request): JsonResponse
    {
        if (! Auth::check()) {
            return response()->json([
                'success' => false,
                'error' => 'Usuário não autenticado.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $user = Auth::user();

        // Conta só participantes CONSULTÁVEIS (CNPJ 14 dígitos) — bate com o que a seleção
        // do grupo realmente adiciona (CPF/sem CNPJ não são consultáveis).
        $grupos = ParticipanteGrupo::doUsuario($user->id)
            ->withCount(['participantes as participantes_count' => fn ($q) => $q->somenteCnpj()])
            ->orderBy('nome')
            ->get(['id', 'nome', 'cor']);

        $manuais = $grupos->map(fn ($g) => [
            'id' => $g->id,
            'tipo' => 'manual',
            'cliente_id' => null,
            'nome' => $g->nome,
            'cor' => $g->cor,
            'participantes_count' => $g->participantes_count,
        ]);

        // Grupos automáticos: "Participantes de <cliente>" para cada cliente que tem
        // participantes associados. Permite selecionar as contrapartes de um cliente sem
        // misturar com a consulta do CNPJ do próprio cliente (aba Clientes).
        $clientesComParticipantes = Cliente::where('user_id', $user->id)
            ->whereHas('participantes', fn ($q) => $q->somenteCnpj())
            ->withCount(['participantes as participantes_count' => fn ($q) => $q->somenteCnpj()])
            ->orderBy('razao_social')
            ->get(['id', 'nome', 'razao_social']);

        $automaticos = $clientesComParticipantes->map(fn ($c) => [
            'id' => 'cliente:'.$c->id,
            'tipo' => 'cliente',
            'cliente_id' => $c->id,
            'nome' => 'Participantes de '.($c->razao_social ?? $c->nome),
            'cor' => '#6b7280',
            'participantes_count' => $c->participantes_count,
        ]);

        return response()->json([
            'success' => true,
            'data' => $manuais->concat($automaticos)->values(),
        ]);
    }

    /**
     * Histórico de lotes do usuário.
     */
    public function historico(Request $request)
    {
        $historicoView = $this->getViewPrefix().'historico';

        if (! view()->exists($historicoView)) {
            abort(404);
        }

        if (! Auth::check()) {
            return $this->redirectToLogin($request);
        }

        $user = Auth::user();

        $validated = $request->validate([
            'busca' => 'nullable|string|max:100',
            'status' => 'nullable|in:pendente,processando,finalizado,concluido,erro',
            'plano_id' => 'nullable|integer|exists:monitoramento_planos,id',
            'data_inicio' => 'nullable|date',
            'data_fim' => 'nullable|date',
        ]);

        // Lotes de consulta de CNPJ (plano_id != null). Lotes de clearance (plano_id null) têm
        // histórico próprio (/app/clearance) e resultado em nfe_consultas — não entram aqui.
        $baseQuery = ConsultaLote::where('user_id', $user->id)
            ->whereNotNull('plano_id')
            ->whereDoesntHave('cliente', fn ($q) => $q->where('is_empresa_propria', true))
            ->with('plano');

        if (! empty($validated['busca'])) {
            $busca = trim($validated['busca']);

            $baseQuery->where(function ($q) use ($busca) {
                $q->where('consulta_lotes.id', 'like', "%{$busca}%")
                    ->orWhere('consulta_lotes.error_code', 'ilike', "%{$busca}%")
                    ->orWhere('consulta_lotes.error_message', 'ilike', "%{$busca}%")
                    ->orWhereHas('plano', fn ($plano) => $plano->where('nome', 'ilike', "%{$busca}%"));
            });
        }

        if (! empty($validated['status'])) {
            if (ConsultaLote::isSuccessfulStatus($validated['status'])) {
                $baseQuery->whereIn('status', ConsultaLote::successfulStatuses());
            } else {
                $baseQuery->where('status', $validated['status']);
            }
        }

        if (! empty($validated['plano_id'])) {
            $baseQuery->where('plano_id', $validated['plano_id']);
        }

        if (! empty($validated['data_inicio'])) {
            $baseQuery->whereDate('created_at', '>=', $validated['data_inicio']);
        }

        if (! empty($validated['data_fim'])) {
            $baseQuery->whereDate('created_at', '<=', $validated['data_fim']);
        }

        $kpis = [
            'total_lotes' => (clone $baseQuery)->count(),
            'total_participantes' => (int) ((clone $baseQuery)->sum('total_participantes') ?? 0),
            'total_creditos' => (int) ((clone $baseQuery)->sum('creditos_cobrados') ?? 0),
            'finalizados' => (clone $baseQuery)->whereIn('status', ConsultaLote::successfulStatuses())->count(),
            'processando' => (clone $baseQuery)->where('status', ConsultaLote::STATUS_PROCESSANDO)->count(),
            'erro' => (clone $baseQuery)->where('status', ConsultaLote::STATUS_ERRO)->count(),
        ];

        $lotes = (clone $baseQuery)
            ->withCount(['monitoramentoConsulta as eh_monitoramento'])
            ->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        $planosFiltro = MonitoramentoPlano::ativos()
            ->sortBy('nome')
            ->values();

        $filtros = [
            'busca' => $validated['busca'] ?? '',
            'status' => $validated['status'] ?? '',
            'plano_id' => $validated['plano_id'] ?? '',
            'data_inicio' => $validated['data_inicio'] ?? '',
            'data_fim' => $validated['data_fim'] ?? '',
        ];

        $data = [
            'lotes' => $lotes,
            'kpis' => $kpis,
            'filtros' => $filtros,
            'filtrosAtivos' => count(array_filter($filtros, fn ($value) => $value !== null && $value !== '')),
            'planosFiltro' => $planosFiltro,
            'relatoriosLegados' => collect([]), // Tabelas legadas removidas
            'credits' => $this->creditService->getBalance($user),
        ];

        if ($this->isAjaxRequest($request)) {
            $renderedView = view($historicoView, $data)->render();

            return response($renderedView)->header('Content-Type', 'text/html');
        }

        return view(self::AUTH_LAYOUT_VIEW, array_merge([
            'initialView' => $historicoView,
        ], $data));
    }

    /**
     * Exibe o detalhe de um lote de consulta.
     */
    public function showLote(Request $request, int $id)
    {
        $loteView = $this->getViewPrefix().'lote';

        if (! view()->exists($loteView)) {
            abort(404);
        }

        if (! Auth::check()) {
            return $this->redirectToLogin($request);
        }

        $user = Auth::user();

        $lote = ConsultaLote::with(['plano', 'cliente'])
            ->where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (! $lote) {
            abort(404);
        }

        // Lotes de clearance (plano_id null) não pertencem à consulta de CNPJ — o resultado vive
        // em nfe_consultas/cte_consultas, não em consulta_resultados. Sem isto, esta tela fica
        // "verificando" pra sempre. Redireciona pra tela de resultado do clearance.
        if ($lote->plano_id === null) {
            return redirect()->route('app.clearance.notas.resultado', ['consultaLoteId' => $lote->id]);
        }

        $statusLote = ConsultaLote::normalizeStatus($lote->status);
        $statusMeta = $this->getConsultaLoteStatusMeta($statusLote);
        $etapas = $lote->plano?->resolvedEtapas() ?? [];
        $contadores = $lote->getContadoresResultados();
        $perPageResultados = 20;
        $temResultadosNoLote = false;

        $resultados = new LengthAwarePaginator(
            collect(),
            0,
            $perPageResultados,
            LengthAwarePaginator::resolveCurrentPage('page_resultados'),
            [
                'path' => $request->url(),
                'pageName' => 'page_resultados',
            ]
        );
        $aguardaPersistencia = $statusLote === ConsultaLote::STATUS_FINALIZADO && $contadores['total'] === 0;
        $resumo = [
            'total_lote' => (int) $lote->total_participantes,
            'total_resultados' => (int) $contadores['total'],
            'sucesso' => (int) $contadores['sucesso'],
            'erro' => (int) $contadores['erro'],
            'com_parecer' => 0,
        ];
        $analise = null;

        if ($statusLote === ConsultaLote::STATUS_FINALIZADO && ! $aguardaPersistencia) {
            $resultadosDetalhe = $this->buildConsultaLoteResultadosDetalhe($lote);
            $temResultadosNoLote = $resultadosDetalhe->isNotEmpty();
            $resumo['com_parecer'] = $resultadosDetalhe
                ->filter(fn (array $resultado) => ($resultado['parecer_count'] ?? 0) > 0)
                ->count();

            if ($temResultadosNoLote) {
                $analise = app(\App\Services\Consultas\ResultadoDetalhePresenter::class)->analiseLote($resultadosDetalhe);
                $resultados = $this->paginateConsultaLoteResultados($resultadosDetalhe, $perPageResultados, $request);
            }
        }

        $data = [
            'lote' => $lote,
            'statusLote' => $statusLote,
            'statusMeta' => $statusMeta,
            'etapas' => $etapas,
            'resumo' => $resumo,
            'analise' => $analise,
            'resultados' => $resultados,
            'temResultadosNoLote' => $temResultadosNoLote,
            'aguardaPersistencia' => $aguardaPersistencia,
            'credits' => $this->creditService->getBalance($user),
        ];

        if ($this->isAjaxRequest($request)) {
            $renderedView = view($loteView, $data)->render();

            return response($renderedView)->header('Content-Type', 'text/html');
        }

        return view(self::AUTH_LAYOUT_VIEW, array_merge([
            'initialView' => $loteView,
        ], $data));
    }

    /**
     * Retorna status atual do lote (polling fallback para SSE).
     */
    public function statusLote(Request $request, int $id): JsonResponse
    {
        $lote = ConsultaLote::where('id', $id)->where('user_id', Auth::id())->first();

        if (! $lote) {
            return response()->json(['success' => false], 404);
        }

        $totalResultados = $lote->resultados()->count();
        $cacheData = null;

        if (! empty($lote->tab_id)) {
            $cacheKey = "progresso:{$lote->user_id}:{$lote->tab_id}";
            $cached = Cache::get($cacheKey);

            if (is_array($cached)) {
                $cacheStatus = $cached['status'] ?? null;
                $loteStatus = $lote->status;

                $loteAberto = in_array($loteStatus, [ConsultaLote::STATUS_PROCESSANDO, ConsultaLote::STATUS_PENDENTE], true);
                $cacheIntermediario = in_array($cacheStatus, [ConsultaLote::STATUS_PROCESSANDO, ConsultaLote::STATUS_PENDENTE, ConsultaLote::STATUS_CONCLUIDO], true);
                $cacheTerminalCompativel = $cacheStatus === ConsultaLote::STATUS_ERRO
                    ? $loteStatus === ConsultaLote::STATUS_ERRO
                    : $cacheStatus === ConsultaLote::STATUS_FINALIZADO && $lote->isFinalizado();

                $shouldUseCache = ($cacheIntermediario && $loteAberto) || $cacheTerminalCompativel;

                if ($shouldUseCache) {
                    $cacheData = $cached;
                }
            }
        }

        if ($cacheData) {
            $ultimaEtapaConcluida = $cacheData['ultima_etapa_concluida']
                ?? $this->inferUltimaEtapaConcluidaFromStatusSnapshot($cacheData);

            return response()->json([
                'success' => true,
                'status' => $cacheData['status'] ?? $lote->status,
                'progresso' => (int) ($cacheData['progresso'] ?? 0),
                'mensagem' => $cacheData['mensagem'] ?? null,
                'etapa' => $cacheData['etapa'] ?? null,
                'total_etapas' => $cacheData['total_etapas'] ?? null,
                'etapa_label' => $cacheData['etapa_label'] ?? null,
                'etapas_puladas' => $cacheData['etapas_puladas'] ?? [],
                'trilha_etapas' => $cacheData['trilha_etapas'] ?? null,
                'ultima_etapa_concluida' => $ultimaEtapaConcluida,
                'consulta_lote_id' => $cacheData['consulta_lote_id'] ?? $lote->id,
                'updated_at' => $cacheData['updated_at'] ?? null,
                'total_participantes' => $lote->total_participantes,
                'total_resultados' => $totalResultados,
                'ui_error' => $cacheData['ui_error'] ?? null,
            ]);
        }

        $status = ConsultaLote::normalizeStatus($lote->status);
        $mensagem = in_array($status, [ConsultaLote::STATUS_PENDENTE, ConsultaLote::STATUS_PROCESSANDO], true)
            ? 'Aguardando atualização do provedor...'
            : ($status === ConsultaLote::STATUS_ERRO ? $lote->publicErrorMessage() : null);
        $progresso = ConsultaLote::isSuccessfulStatus($lote->status) ? 100 : 0;

        return response()->json([
            'success' => true,
            'status' => $status,
            'progresso' => $progresso,
            'mensagem' => $mensagem,
            'etapa' => null,
            'total_etapas' => null,
            'etapa_label' => null,
            'etapas_puladas' => [],
            'trilha_etapas' => null,
            'ultima_etapa_concluida' => null,
            'consulta_lote_id' => $lote->id,
            'total_participantes' => $lote->total_participantes,
            'total_resultados' => $totalResultados,
            'ui_error' => $status === ConsultaLote::STATUS_ERRO ? $lote->publicErrorUi() : null,
        ]);
    }

    /**
     * Retorna resultados individuais de um lote (para exibição inline).
     */
    public function resultadosLote(Request $request, int $id): JsonResponse
    {
        $lote = ConsultaLote::where('id', $id)
            ->where('user_id', Auth::id())
            ->first();

        if (! $lote) {
            return response()->json(['success' => false], 404);
        }

        $resultados = $lote->resultados()
            ->with('participante:id,documento,razao_social,uf,crt,regime_tributario')
            ->get();

        $parecerService = app(ParecerFiscalService::class);

        return response()->json([
            'success' => true,
            'lote_id' => $lote->id,
            'total' => $resultados->count(),
            'resultados' => $resultados->map(function ($r) use ($parecerService) {
                $parecerResumo = $r->isSucesso()
                    ? $parecerService->gerarResumo($r->getParecerFiscalPayload())
                    : [];

                return [
                    'participante' => [
                        'id' => $r->participante?->id,
                        'cnpj' => $r->participante?->documento,
                        'documento_formatado' => $r->participante?->cnpj_formatado ?: $r->participante?->documento,
                        'razao_social' => $r->participante?->razao_social,
                        'uf' => $r->participante?->uf,
                    ],
                    'status' => $r->status,
                    'error_message' => $r->publicErrorMessage(),
                    'mensagem_exibivel' => $r->getMensagemExibivel(),
                    'situacao_cadastral' => $r->getDado('situacao_cadastral'),
                    'regime_tributario' => $r->getRegimeTributarioLabel(),
                    'simples_nacional' => $r->getDado('simples_nacional'),
                    'mei' => $r->getDado('mei'),
                    'cnd_federal' => $this->cndFederalComAnalise($r->getDado('cnd_federal')),
                    'crf_fgts' => $r->getDado('crf_fgts'),
                    'cndt' => $r->getDado('cndt'),
                    'cnd_estadual' => $r->getDado('cnd_estadual'),
                    'parecer' => $parecerResumo,
                ];
            }),
        ]);
    }

    /**
     * Anexa indeterminado/label/hex/motivo (CndFederal) ao payload de CND Federal.
     * Para status não-indeterminado retorna o valor original intacto.
     */
    private function cndFederalComAnalise(mixed $cnd): mixed
    {
        $analise = CndFederal::analisar($cnd);

        if (! $analise['indeterminado']) {
            return $cnd;
        }

        $base = is_array($cnd) ? $cnd : [];

        return array_merge($base, [
            'indeterminado' => true,
            'label' => $analise['label'],
            'hex' => $analise['hex'],
            'motivo' => $analise['motivo'],
        ]);
    }

    /**
     * Verifica se a requisição é AJAX (navegação SPA).
     */
    private function getConsultaLoteStatusMeta(?string $status): array
    {
        return match (ConsultaLote::normalizeStatus($status)) {
            ConsultaLote::STATUS_FINALIZADO => ['label' => 'Finalizado', 'hex' => '#047857'],
            ConsultaLote::STATUS_PROCESSANDO => ['label' => 'Processando', 'hex' => '#d97706'],
            ConsultaLote::STATUS_ERRO => ['label' => 'Erro', 'hex' => '#dc2626'],
            default => ['label' => 'Pendente', 'hex' => '#9ca3af'],
        };
    }

    private function buildConsultaLoteResultadosDetalhe(ConsultaLote $lote): Collection
    {
        $parecerService = app(ParecerFiscalService::class);
        $detalhePresenter = app(\App\Services\Consultas\ResultadoDetalhePresenter::class);

        return $lote->resultados()
            ->with([
                'participante:id,documento,razao_social,uf,crt,regime_tributario',
                'cliente:id,documento,razao_social,uf',
            ])
            ->orderByDesc('consultado_em')
            ->orderBy('id')
            ->get()
            ->map(function (ConsultaResultado $resultado) use ($parecerService, $detalhePresenter) {
                $parecerResumo = $resultado->isSucesso()
                    ? $parecerService->gerarResumo($resultado->getParecerFiscalPayload())
                    : [];

                $statusMeta = match ($resultado->status) {
                    ConsultaResultado::STATUS_SUCESSO => ['label' => 'Sucesso', 'hex' => '#047857'],
                    ConsultaResultado::STATUS_TIMEOUT => ['label' => 'Timeout', 'hex' => '#d97706'],
                    ConsultaResultado::STATUS_ERRO => ['label' => 'Erro', 'hex' => '#dc2626'],
                    default => ['label' => 'Pendente', 'hex' => '#9ca3af'],
                };
                $situacaoCadastral = trim((string) $resultado->getDado('situacao_cadastral'));
                $regimeTributario = $resultado->getRegimeTributarioLabel();
                $cndFederal = $this->normalizeConsultaLoteRegularidadeBadge($resultado->getDado('cnd_federal'), true);
                $fgts = $this->normalizeConsultaLoteRegularidadeBadge($resultado->getDado('crf_fgts'));
                $cndt = $this->normalizeConsultaLoteRegularidadeBadge($resultado->getDado('cndt'));

                // O alvo pode ser participante OU cliente (escopo "clientes"). O Laravel não
                // atualiza esses cadastros, então caímos no que a própria consulta trouxe
                // (resultado_dados da minhareceita) quando o cadastro está sem nome/CNPJ.
                $participante = $resultado->participante;
                $cliente = $resultado->cliente;
                $enderecoConsulta = is_array($resultado->getDado('endereco')) ? $resultado->getDado('endereco') : [];
                $documento = $participante?->documento ?: $cliente?->documento;
                $razaoSocial = $participante?->razao_social
                    ?: ($cliente?->razao_social
                    ?: ($resultado->getDado('razao_social') ?: $resultado->getDado('nome_fantasia')));
                $uf = $participante?->uf ?: ($cliente?->uf ?: ($enderecoConsulta['uf'] ?? null));

                return [
                    'participante_id' => $participante?->id,
                    'cliente_id' => $cliente?->id,
                    'cnpj' => $documento,
                    'documento_formatado' => $this->formatarDocumentoConsulta($documento),
                    'razao_social' => $razaoSocial,
                    'uf' => $uf,
                    'status' => $resultado->status,
                    'status_label' => $statusMeta['label'],
                    'status_hex' => $statusMeta['hex'],
                    'error_message' => $resultado->publicErrorMessage(),
                    'mensagem_exibivel' => $resultado->getMensagemExibivel(),
                    'consultado_em_label' => $resultado->consultado_em?->format('d/m/Y H:i') ?: '—',
                    'situacao_cadastral' => $situacaoCadastral !== '' ? $situacaoCadastral : '—',
                    'regime_tributario' => $regimeTributario ?: '—',
                    'cnd_federal_badge' => $cndFederal,
                    'fgts_badge' => $fgts,
                    'cndt_badge' => $cndt,
                    'parecer' => $parecerResumo,
                    'parecer_count' => count($parecerResumo),
                    'detalhe_blocos' => $detalhePresenter->blocos($resultado),
                    'resumo_texto' => $resultado->isSucesso() ? $detalhePresenter->resumoTextual($resultado) : null,
                ];
            });
    }

    /** Formata CNPJ (14) / CPF (11) p/ exibição; devolve o original se não bater. */
    private function formatarDocumentoConsulta(?string $documento): ?string
    {
        $doc = preg_replace('/\D/', '', (string) $documento);

        if (strlen($doc) === 14) {
            return preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $doc);
        }
        if (strlen($doc) === 11) {
            return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $doc);
        }

        return $documento ?: null;
    }

    private function normalizeConsultaLoteRegularidadeBadge(mixed $valor, bool $aplicarIndeterminado = false): array
    {
        // Classificação canônica de certidão (Negativa/Positiva-com-efeitos = regular).
        // Fonte única compartilhada com o detalhe expansível (ResultadoDetalhePresenter).
        return CertidaoBadge::classificar($valor, $aplicarIndeterminado);
    }

    private function paginateConsultaLoteResultados(Collection $resultados, int $perPage, Request $request): LengthAwarePaginator
    {
        $total = $resultados->count();
        $lastPage = max((int) ceil(max($total, 1) / $perPage), 1);
        $currentPage = min(max(LengthAwarePaginator::resolveCurrentPage('page_resultados'), 1), $lastPage);
        $paginator = new LengthAwarePaginator(
            $resultados->forPage($currentPage, $perPage)->values(),
            $total,
            $perPage,
            $currentPage,
            [
                'path' => $request->url(),
                'pageName' => 'page_resultados',
            ]
        );

        return $paginator->appends($request->except('page_resultados'));
    }

    private function inferUltimaEtapaConcluidaFromStatusSnapshot(array $snapshot): ?int
    {
        $status = $snapshot['status'] ?? null;
        $etapa = isset($snapshot['etapa']) ? (int) $snapshot['etapa'] : null;
        $totalEtapas = isset($snapshot['total_etapas']) ? (int) $snapshot['total_etapas'] : null;

        if ($status === ConsultaLote::STATUS_CONCLUIDO && $etapa !== null) {
            return $this->normalizeConsultaSnapshotEtapa($etapa, $totalEtapas);
        }

        if (in_array($status, [ConsultaLote::STATUS_PROCESSANDO, ConsultaLote::STATUS_ERRO], true)) {
            return $this->resolveConsultaSnapshotEtapaAnterior($etapa, $totalEtapas);
        }

        if ($status === ConsultaLote::STATUS_FINALIZADO) {
            return $this->normalizeConsultaSnapshotEtapa($etapa ?? $totalEtapas, $totalEtapas);
        }

        return null;
    }

    private function resolveConsultaSnapshotEtapaAnterior(?int $etapa, ?int $totalEtapas): ?int
    {
        if ($etapa === null) {
            return null;
        }

        if ($etapa === 0) {
            $etapaAnterior = $totalEtapas !== null && $totalEtapas > 1
                ? $totalEtapas - 1
                : $totalEtapas;

            return $this->normalizeConsultaSnapshotEtapa($etapaAnterior, $totalEtapas);
        }

        if ($etapa <= 1) {
            return null;
        }

        return $etapa - 1;
    }

    private function normalizeConsultaSnapshotEtapa(?int $etapa, ?int $totalEtapas): ?int
    {
        if ($etapa === null || $etapa < 0) {
            return null;
        }

        if ($etapa > 0 && $totalEtapas !== null) {
            return min($etapa, $totalEtapas);
        }

        return $etapa;
    }

    /**
     * Redireciona para login preservando URL.
     */
    private function redirectToLogin(Request $request)
    {
        session(['url.intended' => $request->fullUrl()]);

        return redirect()->route('login');
    }
}
