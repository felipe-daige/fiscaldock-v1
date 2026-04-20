<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\ConsultaLote;
use App\Models\ConsultaResultado;
use App\Models\EfdImportacao;
use App\Models\EfdNota;
use App\Models\MonitoramentoAssinatura;
use App\Models\MonitoramentoConsulta;
use App\Models\MonitoramentoPlano;
use App\Models\Participante;
use App\Models\XmlNota;
use App\Services\CreditService;
use App\Services\NotaFiscalService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ParticipanteController extends Controller
{
    private const AUTH_VIEW_PREFIX = 'autenticado.monitoramento.';

    private const AUTH_LAYOUT_VIEW = 'autenticado.layouts.app';

    public function __construct(
        protected CreditService $creditService,
        protected NotaFiscalService $notaFiscalService,
    ) {}

    /**
     * Formulário de cadastro manual de participante.
     */
    public function create(Request $request)
    {
        $viewName = self::AUTH_VIEW_PREFIX.'novo-participante';

        if (! Auth::check()) {
            return $this->redirectToLogin($request);
        }

        $user = Auth::user();
        $userId = (int) $user->id;

        $clientes = Cliente::where('user_id', $userId)
            ->where('ativo', true)
            ->orderBy('razao_social')
            ->get();

        $data = [
            'clientes' => $clientes,
            'credits' => $this->creditService->getBalance($user),
        ];

        if ($this->isAjaxRequest($request)) {
            $renderedView = view($viewName, $data)->render();

            return response($renderedView)->header('Content-Type', 'text/html');
        }

        return view(self::AUTH_LAYOUT_VIEW, array_merge([
            'initialView' => $viewName,
        ], $data));
    }

    /**
     * Salva um novo participante cadastrado manualmente.
     */
    public function store(Request $request)
    {
        if (! Auth::check()) {
            return response()->json([
                'success' => false,
                'error' => 'Usuário não autenticado.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $user = Auth::user();

        $tipDoc = $request->input('tipo_documento', 'PJ');
        $isPF = $tipDoc === 'PF';
        $docLabel = $isPF ? 'CPF' : 'CNPJ';

        $validated = $request->validate([
            'tipo_documento' => 'required|in:PF,PJ',
            'cnpj' => 'required|string|max:18',
            'razao_social' => $isPF ? 'nullable|string|max:255' : 'required|string|max:255',
            'nome_fantasia' => $isPF ? 'required|string|max:255' : 'nullable|string|max:255',
            'inscricao_estadual' => 'nullable|string|max:20',
            'crt' => 'nullable|in:1,2,3',
            'telefone' => 'nullable|string|max:20',
            'cliente_id' => 'nullable|integer',
            'cep' => 'nullable|string|max:9',
            'endereco' => 'nullable|string|max:255',
            'numero' => 'nullable|string|max:20',
            'complemento' => 'nullable|string|max:100',
            'bairro' => 'nullable|string|max:100',
            'municipio' => 'nullable|string|max:100',
            'uf' => 'nullable|string|size:2',
        ], [
            'razao_social.required' => 'Razão social é obrigatória para Pessoa Jurídica.',
            'nome_fantasia.required' => 'Nome completo é obrigatório para Pessoa Física.',
        ]);

        // Limpar documento (CNPJ ou CPF)
        $doc = preg_replace('/[^0-9]/', '', $validated['cnpj']);
        $expectedLen = $isPF ? 11 : 14;

        if (strlen($doc) !== $expectedLen) {
            return response()->json([
                'success' => false,
                'errors' => ['cnpj' => ["{$docLabel} deve conter {$expectedLen} dígitos."]],
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Verificar unicidade (user_id, cnpj)
        $existente = Participante::where('user_id', $user->id)
            ->where('documento', $doc)
            ->first();

        if ($existente) {
            return response()->json([
                'success' => false,
                'errors' => ['cnpj' => ["Este {$docLabel} já está cadastrado na sua base."]],
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Validar que cliente_id pertence ao usuário
        $clienteId = $validated['cliente_id'] ?? null;
        if ($clienteId) {
            $cliente = Cliente::where('id', $clienteId)
                ->where('user_id', $user->id)
                ->first();
            if (! $cliente) {
                return response()->json([
                    'success' => false,
                    'errors' => ['cliente_id' => ['Cliente não encontrado.']],
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
        }

        // Limpar CEP
        $cep = isset($validated['cep']) ? preg_replace('/[^0-9]/', '', $validated['cep']) : null;

        // Para PF: copiar nome_fantasia para razao_social se vazio (garante listagens)
        $razaoSocial = $validated['razao_social'] ?? null;
        if ($isPF && empty($razaoSocial)) {
            $razaoSocial = $validated['nome_fantasia'];
        }

        try {
            $participante = Participante::create([
                'user_id' => $user->id,
                'documento' => $doc,
                'tipo_documento' => $tipDoc,
                'razao_social' => $razaoSocial,
                'nome_fantasia' => $validated['nome_fantasia'] ?? null,
                'inscricao_estadual' => $isPF ? null : ($validated['inscricao_estadual'] ?? null),
                'crt' => $isPF ? null : ($validated['crt'] ?? null),
                'telefone' => $validated['telefone'] ?? null,
                'cliente_id' => $clienteId,
                'cep' => $cep,
                'endereco' => $validated['endereco'] ?? null,
                'numero' => $validated['numero'] ?? null,
                'complemento' => $validated['complemento'] ?? null,
                'bairro' => $validated['bairro'] ?? null,
                'municipio' => $validated['municipio'] ?? null,
                'uf' => $validated['uf'] ?? null,
                'origem_tipo' => 'MANUAL',
            ]);

            Log::info('Participante criado manualmente', [
                'user_id' => $user->id,
                'participante_id' => $participante->id,
                'tipo_documento' => $tipDoc,
                'documento' => $doc,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Participante cadastrado com sucesso!',
                'participante_id' => $participante->id,
                'redirect' => '/app/participantes',
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao criar participante manualmente', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erro ao cadastrar participante. Tente novamente.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Formulário de edição de participante (reutiliza view novo-participante).
     */
    public function edit(Request $request, $id)
    {
        $viewName = self::AUTH_VIEW_PREFIX.'novo-participante';

        if (! Auth::check()) {
            return $this->redirectToLogin($request);
        }

        $user = Auth::user();
        $userId = (int) $user->id;

        $participante = Participante::where('user_id', $userId)->findOrFail($id);

        $clientes = Cliente::where('user_id', $userId)
            ->where('ativo', true)
            ->orderBy('razao_social')
            ->get();

        $data = [
            'participante' => $participante,
            'clientes' => $clientes,
            'credits' => $this->creditService->getBalance($user),
        ];

        if ($this->isAjaxRequest($request)) {
            $renderedView = view($viewName, $data)->render();

            return response($renderedView)->header('Content-Type', 'text/html');
        }

        return view(self::AUTH_LAYOUT_VIEW, array_merge([
            'initialView' => $viewName,
        ], $data));
    }

    /**
     * Atualiza um participante existente.
     */
    public function update(Request $request, $id)
    {
        if (! Auth::check()) {
            return response()->json([
                'success' => false,
                'error' => 'Usuário não autenticado.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $user = Auth::user();

        $participante = Participante::where('user_id', $user->id)->findOrFail($id);

        $isPF = $participante->tipo_documento === 'PF';

        $validated = $request->validate([
            'razao_social' => $isPF ? 'nullable|string|max:255' : 'required|string|max:255',
            'nome_fantasia' => $isPF ? 'required|string|max:255' : 'nullable|string|max:255',
            'inscricao_estadual' => 'nullable|string|max:20',
            'crt' => 'nullable|in:1,2,3',
            'telefone' => 'nullable|string|max:20',
            'cliente_id' => 'nullable|integer',
            'cep' => 'nullable|string|max:9',
            'endereco' => 'nullable|string|max:255',
            'numero' => 'nullable|string|max:20',
            'complemento' => 'nullable|string|max:100',
            'bairro' => 'nullable|string|max:100',
            'municipio' => 'nullable|string|max:100',
            'uf' => 'nullable|string|size:2',
        ], [
            'razao_social.required' => 'Razão social é obrigatória para Pessoa Jurídica.',
            'nome_fantasia.required' => 'Nome completo é obrigatório para Pessoa Física.',
        ]);

        // Validar que cliente_id pertence ao usuário
        $clienteId = $validated['cliente_id'] ?? null;
        if ($clienteId) {
            $cliente = Cliente::where('id', $clienteId)
                ->where('user_id', $user->id)
                ->first();
            if (! $cliente) {
                return response()->json([
                    'success' => false,
                    'errors' => ['cliente_id' => ['Cliente não encontrado.']],
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
        }

        // Limpar CEP
        $cep = isset($validated['cep']) ? preg_replace('/[^0-9]/', '', $validated['cep']) : null;

        // Para PF: copiar nome_fantasia para razao_social se vazio
        $razaoSocial = $validated['razao_social'] ?? null;
        if ($isPF && empty($razaoSocial)) {
            $razaoSocial = $validated['nome_fantasia'];
        }

        try {
            $participante->update([
                'razao_social' => $razaoSocial,
                'nome_fantasia' => $validated['nome_fantasia'] ?? null,
                'inscricao_estadual' => $isPF ? null : ($validated['inscricao_estadual'] ?? null),
                'crt' => $isPF ? null : ($validated['crt'] ?? null),
                'telefone' => $validated['telefone'] ?? null,
                'cliente_id' => $clienteId,
                'cep' => $cep,
                'endereco' => $validated['endereco'] ?? null,
                'numero' => $validated['numero'] ?? null,
                'complemento' => $validated['complemento'] ?? null,
                'bairro' => $validated['bairro'] ?? null,
                'municipio' => $validated['municipio'] ?? null,
                'uf' => $validated['uf'] ?? null,
            ]);

            Log::info('Participante atualizado', [
                'user_id' => $user->id,
                'participante_id' => $participante->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Participante atualizado com sucesso!',
                'participante_id' => $participante->id,
                'redirect' => '/app/participante/'.$participante->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao atualizar participante', [
                'user_id' => $user->id,
                'participante_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erro ao atualizar participante. Tente novamente.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Lista participantes importados com filtros.
     */
    public function index(Request $request)
    {
        $participantesView = self::AUTH_VIEW_PREFIX.'participantes-importados';

        if (! view()->exists($participantesView)) {
            abort(404);
        }

        if (! Auth::check()) {
            return $this->redirectToLogin($request);
        }

        $user = Auth::user();
        $userId = (int) $user->id;

        // Filtros
        $importacaoId = $request->get('importacao');
        $clienteId = $request->get('cliente');
        $origemTipo = $request->get('origem');
        $busca = $request->get('busca');
        $regimeTributario = $request->get('regime');
        $situacaoCadastral = $request->get('situacao');
        $uf = $request->get('uf');
        $tipoDocumento = strtoupper((string) $request->get('tipo_documento', ''));

        // Query de participantes com filtros
        $participantesQuery = Participante::where('user_id', $userId)
            ->excludingEmpresaPropria()
            ->with([
                'cliente',
                'importacaoEfd',
                'assinaturas' => fn ($q) => $q
                    ->whereIn('status', ['ativo', 'pausado'])
                    ->orderByRaw("CASE WHEN status = 'ativo' THEN 0 ELSE 1 END")
                    ->orderBy('updated_at', 'desc'),
            ])
            ->when($importacaoId, fn ($q) => $q->where('importacao_efd_id', $importacaoId))
            ->when($clienteId, fn ($q) => $q->where('cliente_id', $clienteId))
            ->when($origemTipo, fn ($q) => $q->where('origem_tipo', $origemTipo))
            ->when($busca, function ($q) use ($busca) {
                $q->where(function ($sub) use ($busca) {
                    $sub->where('documento', 'like', "%{$busca}%")
                        ->orWhere('razao_social', 'ilike', "%{$busca}%");
                });
            })
            ->when($regimeTributario, fn ($q) => $q->where('regime_tributario', 'ilike', $regimeTributario))
            ->when($situacaoCadastral, fn ($q) => $q->where('situacao_cadastral', $situacaoCadastral))
            ->when($uf, fn ($q) => $q->where('uf', $uf))
            ->when($tipoDocumento === 'CPF', fn ($q) => $q->somenteCpf())
            ->when($tipoDocumento === 'CNPJ', fn ($q) => $q->somenteCnpj())
            ->orderBy('created_at', 'desc');

        $participantes = $participantesQuery->paginate(20)->withQueryString();
        $agora = Carbon::now();
        $participanteIds = $participantes->getCollection()->pluck('id')->all();
        $ultimosResultados = ConsultaResultado::query()
            ->whereIn('participante_id', $participanteIds)
            ->where('status', ConsultaResultado::STATUS_SUCESSO)
            ->orderBy('consultado_em', 'desc')
            ->get()
            ->unique('participante_id')
            ->keyBy('participante_id');

        $participantes->getCollection()->transform(function (Participante $participante) use ($agora, $ultimosResultados) {
            $assinatura = $participante->assinaturas->first();
            $ultimaConsulta = $participante->ultima_consulta_em;
            $ultimoResultado = $ultimosResultados->get($participante->id);
            $cndFederal = $ultimoResultado?->getCndFederal() ?? [];

            $consultaStatus = 'nunca_consultado';
            $consultaStatusLabel = 'Nunca consultado';
            $consultaStatusHex = '#9ca3af';
            $consultaStatusMeta = 'Sem consulta realizada';

            if ($ultimaConsulta) {
                $diasSemConsulta = $ultimaConsulta->diffInDays($agora);

                if ($diasSemConsulta > 30) {
                    $consultaStatus = 'desatualizada';
                    $consultaStatusLabel = 'Consulta desatualizada';
                    $consultaStatusHex = '#d97706';
                    $consultaStatusMeta = 'Última atualização em '.$ultimaConsulta->format('d/m/Y H:i');
                } else {
                    $consultaStatus = 'consultado_recente';
                    $consultaStatusLabel = 'Consultado recentemente';
                    $consultaStatusHex = '#047857';
                    $consultaStatusMeta = 'Última atualização em '.$ultimaConsulta->format('d/m/Y H:i');
                }
            }

            $cndStatusLabel = 'Não consultada';
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

            $assinaturaLabel = null;
            $assinaturaHex = null;

            if ($assinatura) {
                $assinaturaLabel = $assinatura->status === 'ativo' ? 'Monitoramento ativo' : 'Monitoramento pausado';
                $assinaturaHex = $assinatura->status === 'ativo' ? '#1f2937' : '#6b7280';
            }

            $participante->setAttribute('consulta_status', $consultaStatus);
            $participante->setAttribute('consulta_status_label', $consultaStatusLabel);
            $participante->setAttribute('consulta_status_hex', $consultaStatusHex);
            $participante->setAttribute('consulta_status_meta', $consultaStatusMeta);
            $participante->setAttribute('cnd_federal_status_label', $cndStatusLabel);
            $participante->setAttribute('cnd_federal_status_hex', $cndStatusHex);
            $participante->setAttribute('cnd_federal_meta', $cndMeta);
            $participante->setAttribute('assinatura_label', $assinaturaLabel);
            $participante->setAttribute('assinatura_hex', $assinaturaHex);

            return $participante;
        });

        // Contagens para KPI cards
        $baseQuery = Participante::where('user_id', $userId)->excludingEmpresaPropria();
        $totalParticipantes = (clone $baseQuery)->count();
        $totalAtiva = (clone $baseQuery)->where('situacao_cadastral', 'ATIVA')->count();
        $totalIrregular = (clone $baseQuery)->whereIn('situacao_cadastral', ['BAIXADA', 'SUSPENSA', 'INAPTA'])->count();
        $totalSemConsulta = (clone $baseQuery)->whereNull('ultima_consulta_em')->count();

        // Buscar importações SPED para o filtro
        $importacoes = EfdImportacao::where('user_id', $userId)
            ->where('status', 'concluido')
            ->orderBy('created_at', 'desc')
            ->get();

        // Buscar clientes para o filtro (excluindo empresa própria)
        $clientes = Cliente::where('user_id', $userId)
            ->where('ativo', true)
            ->where('is_empresa_propria', false)
            ->orderBy('razao_social')
            ->get();

        // Tipos de origem disponíveis
        $origens = ['SPED_EFD_FISCAL', 'SPED_EFD_CONTRIB', 'NFE', 'NFSE', 'MANUAL'];

        // UFs distintas para o filtro
        $ufs = Participante::where('user_id', $userId)
            ->excludingEmpresaPropria()
            ->whereNotNull('uf')
            ->where('uf', '!=', '')
            ->distinct()
            ->orderBy('uf')
            ->pluck('uf');

        $data = [
            'participantes' => $participantes,
            'importacoes' => $importacoes,
            'clientes' => $clientes,
            'origens' => $origens,
            'ufs' => $ufs,
            'totalParticipantes' => $totalParticipantes,
            'totalAtiva' => $totalAtiva,
            'totalIrregular' => $totalIrregular,
            'totalSemConsulta' => $totalSemConsulta,
            'filtros' => [
                'importacao' => $importacaoId,
                'cliente' => $clienteId,
                'origem' => $origemTipo,
                'busca' => $busca,
                'regime' => $regimeTributario,
                'situacao' => $situacaoCadastral,
                'uf' => $uf,
                'tipo_documento' => $tipoDocumento,
            ],
            'credits' => $this->creditService->getBalance($user),
        ];

        if ($this->isAjaxRequest($request)) {
            $renderedView = view($participantesView, $data)->render();

            return response($renderedView)->header('Content-Type', 'text/html');
        }

        return view(self::AUTH_LAYOUT_VIEW, array_merge([
            'initialView' => $participantesView,
        ], $data));
    }

    /**
     * Retorna todos os IDs de participantes matching os filtros atuais (para "Selecionar todos").
     */
    public function todosIds(Request $request): JsonResponse
    {
        $user = Auth::user();
        $userId = (int) $user->id;

        $ids = Participante::where('user_id', $userId)
            ->excludingEmpresaPropria()
            ->somenteCnpj()
            ->when($request->importacao, fn ($q, $v) => $q->where('importacao_efd_id', $v))
            ->when($request->cliente, fn ($q, $v) => $q->where('cliente_id', $v))
            ->when($request->origem, fn ($q, $v) => $q->where('origem_tipo', $v))
            ->when($request->busca, fn ($q, $v) => $q->where(function ($sub) use ($v) {
                $sub->where('documento', 'like', "%{$v}%")
                    ->orWhere('razao_social', 'ilike', "%{$v}%");
            }))
            ->when($request->regime, fn ($q, $v) => $q->where('regime_tributario', 'ilike', $v))
            ->when($request->situacao, fn ($q, $v) => $q->where('situacao_cadastral', $v))
            ->when($request->uf, fn ($q, $v) => $q->where('uf', $v))
            ->when(strtoupper((string) $request->tipo_documento) === 'CPF', fn ($q) => $q->somenteCpf())
            ->when(strtoupper((string) $request->tipo_documento) === 'CNPJ', fn ($q) => $q->somenteCnpj())
            ->pluck('id');

        return response()->json(['success' => true, 'ids' => $ids, 'total' => $ids->count()]);
    }

    /**
     * Detalhes de um participante específico.
     */
    public function show(Request $request, $id)
    {
        $participanteView = self::AUTH_VIEW_PREFIX.'participante';

        if (! Auth::check()) {
            return $this->redirectToLogin($request);
        }

        $user = Auth::user();
        $userId = (int) $user->id;

        $participante = Participante::where('id', $id)
            ->where('user_id', $userId)
            ->firstOrFail();

        // Carregar consultas do participante
        $consultas = MonitoramentoConsulta::where('participante_id', $participante->id)
            ->where('user_id', $userId)
            ->with('plano')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        // Buscar assinatura ativa ou pausada
        $assinaturaAtiva = MonitoramentoAssinatura::where('participante_id', $participante->id)
            ->where('user_id', $userId)
            ->whereIn('status', ['ativo', 'pausado'])
            ->with('plano')
            ->first();

        // Notas fiscais unificadas (EFD + XML) do participante
        $notasFiscais = $this->notaFiscalService->listarUnificadas(
            $userId,
            ['participante_id' => $participante->id],
            5,
            1,
            "/app/participante/{$id}/notas"
        );
        $totalNotasFiscais = $notasFiscais->total();

        // Carregar planos disponíveis
        $planos = MonitoramentoPlano::ativos();

        // Estatísticas do participante - combinar ambos sistemas
        $monitoramentoTotal = MonitoramentoConsulta::where('participante_id', $participante->id)
            ->where('user_id', $userId)->count();
        $monitoramentoSucesso = MonitoramentoConsulta::where('participante_id', $participante->id)
            ->where('user_id', $userId)->where('status', 'sucesso')->count();
        $monitoramentoErro = MonitoramentoConsulta::where('participante_id', $participante->id)
            ->where('user_id', $userId)->where('status', 'erro')->count();
        $monitoramentoCreditos = MonitoramentoConsulta::where('participante_id', $participante->id)
            ->where('user_id', $userId)->sum('creditos_cobrados');

        // Consultas em lote (sistema novo)
        $consultaLoteTotal = ConsultaResultado::where('participante_id', $participante->id)
            ->whereHas('lote', fn ($q) => $q->where('user_id', $userId))->count();
        $consultaLoteSucesso = ConsultaResultado::where('participante_id', $participante->id)
            ->whereHas('lote', fn ($q) => $q->where('user_id', $userId))
            ->where('status', 'sucesso')->count();
        $consultaLoteErro = ConsultaResultado::where('participante_id', $participante->id)
            ->whereHas('lote', fn ($q) => $q->where('user_id', $userId))
            ->whereIn('status', ['erro', 'timeout'])->count();

        $estatisticas = [
            'total_consultas' => $monitoramentoTotal + $consultaLoteTotal,
            'consultas_sucesso' => $monitoramentoSucesso + $consultaLoteSucesso,
            'consultas_erro' => $monitoramentoErro + $consultaLoteErro,
            'creditos_utilizados' => $monitoramentoCreditos,
        ];

        // Buscar última consulta com sucesso para o participante (sistema de consultas em lote)
        $ultimaConsulta = ConsultaResultado::where('participante_id', $participante->id)
            ->where('status', ConsultaResultado::STATUS_SUCESSO)
            ->with(['lote:id,plano_id,created_at', 'lote.plano:id,nome,codigo'])
            ->orderBy('consultado_em', 'desc')
            ->first();

        // Buscar lotes que incluem este participante (para histórico de consultas em lote)
        $lotesDoParticipante = ConsultaLote::whereHas('participantes', function ($q) use ($participante) {
            $q->where('participantes.id', $participante->id);
        })
            ->where('user_id', $userId)
            ->with([
                'plano:id,nome,codigo',
                'resultados' => function ($q) use ($participante) {
                    $q->where('participante_id', $participante->id)
                        ->select(['id', 'consulta_lote_id', 'participante_id', 'status', 'resultado_dados', 'error_message', 'consultado_em']);
                },
            ])
            ->orderBy('created_at', 'desc')
            ->get();

        // Se participante nao tem CEP salvo, tentar pegar da ultima consulta
        if (empty($participante->cep) && $ultimaConsulta) {
            $cepDados = $ultimaConsulta->resultado_dados['endereco']['cep'] ?? null;
            if ($cepDados) {
                $participante->update(['cep' => preg_replace('/\D/', '', $cepDados)]);
            }
        }

        // Geocoding (salva no DB para evitar chamadas repetidas)
        if (is_null($participante->latitude)) {
            $lat = null;
            $lng = null;

            // Tentativa 1: Brasil API via CEP
            if (! empty($participante->cep)) {
                $cep = preg_replace('/\D/', '', $participante->cep);
                $response = Http::timeout(5)
                    ->get("https://brasilapi.com.br/api/cep/v2/{$cep}");
                if ($response->successful()) {
                    $data = $response->json();
                    $lat = $data['location']['coordinates']['latitude'] ?? null;
                    $lng = $data['location']['coordinates']['longitude'] ?? null;
                }
            }

            // Tentativa 2: Nominatim via municipio/UF (quando Brasil API nao tem coordenadas)
            if (! $lat || ! $lng) {
                $municipio = $ultimaConsulta->resultado_dados['endereco']['municipio'] ?? ($participante->municipio ?? null);
                $uf = $ultimaConsulta->resultado_dados['endereco']['uf'] ?? ($participante->uf ?? null);
                if ($municipio && $uf) {
                    $query = urlencode("{$municipio},{$uf},Brasil");
                    $response = Http::timeout(5)
                        ->withHeaders(['User-Agent' => 'FiscalDock/1.0'])
                        ->get("https://nominatim.openstreetmap.org/search?q={$query}&format=json&limit=1");
                    if ($response->successful()) {
                        $results = $response->json();
                        $lat = $results[0]['lat'] ?? null;
                        $lng = $results[0]['lon'] ?? null;
                    }
                }
            }

            if ($lat && $lng) {
                $participante->update(['latitude' => $lat, 'longitude' => $lng]);
            }
        }

        // Saldo de créditos do usuário
        $credits = $this->creditService->getBalance($user);

        $data = [
            'participante' => $participante,
            'consultas' => $consultas,
            'assinaturaAtiva' => $assinaturaAtiva,
            'planos' => $planos,
            'estatisticas' => $estatisticas,
            'credits' => $credits,
            'notasFiscais' => $notasFiscais,
            'totalNotasFiscais' => $totalNotasFiscais,
            'notasAjaxUrl' => "/app/participante/{$id}/notas",
            'notasContexto' => 'participante',
            'notasEntityId' => $participante->id,
            'ultimaConsulta' => $ultimaConsulta,
            'lotesDoParticipante' => $lotesDoParticipante,
        ];

        if ($this->isAjaxRequest($request)) {
            $renderedView = view($participanteView, $data)->render();

            return response($renderedView)->header('Content-Type', 'text/html');
        }

        return view(self::AUTH_LAYOUT_VIEW, array_merge([
            'initialView' => $participanteView,
        ], $data));
    }

    /**
     * Notas fiscais unificadas do participante (AJAX pagination).
     */
    public function notas(Request $request, int $id)
    {
        if (! Auth::check()) {
            return response('Nao autenticado', 401);
        }

        $userId = (int) Auth::id();
        $participante = Participante::where('id', $id)
            ->where('user_id', $userId)
            ->firstOrFail();

        $page = max(1, (int) $request->get('page', 1));
        $notas = $this->notaFiscalService->listarUnificadas(
            $userId,
            ['participante_id' => $participante->id],
            5,
            $page,
            "/app/participante/{$id}/notas"
        );

        return view('autenticado.partials.notas-fiscais-card', [
            'notas' => $notas,
            'totalNotas' => $notas->total(),
            'ajaxUrl' => "/app/participante/{$id}/notas",
            'contexto' => 'participante',
            'entityId' => $participante->id,
        ]);
    }

    /**
     * Detalhes de uma nota fiscal (retorna JSON).
     */
    public function notaFiscalDetalhes(Request $request, $id)
    {
        if (! Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Usuário não autenticado.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $userId = (int) Auth::id();

        $nota = XmlNota::where('id', $id)
            ->where('user_id', $userId)
            ->first();

        if (! $nota) {
            return response()->json([
                'success' => false,
                'message' => 'Nota fiscal não encontrada.',
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'nfe_id' => $nota->nfe_id,
                'tipo_documento' => $nota->tipo_documento,
                'numero_nota' => $nota->numero_nota,
                'serie' => $nota->serie,
                'data_emissao' => $nota->data_emissao?->format('d/m/Y'),
                'natureza_operacao' => $nota->natureza_operacao,
                'valor_total' => number_format((float) $nota->valor_total, 2, ',', '.'),
                'tipo_nota' => $nota->tipo_nota_descricao,
                'finalidade' => $nota->finalidade_descricao,
                'emit_cnpj' => $nota->emit_cnpj_formatado,
                'emit_razao_social' => $nota->emit_razao_social,
                'emit_uf' => $nota->emit_uf,
                'dest_cnpj' => $nota->dest_cnpj_formatado,
                'dest_razao_social' => $nota->dest_razao_social,
                'dest_uf' => $nota->dest_uf,
                'icms_valor' => number_format((float) ($nota->icms_valor ?? 0), 2, ',', '.'),
                'icms_st_valor' => number_format((float) ($nota->icms_st_valor ?? 0), 2, ',', '.'),
                'pis_valor' => number_format((float) ($nota->pis_valor ?? 0), 2, ',', '.'),
                'cofins_valor' => number_format((float) ($nota->cofins_valor ?? 0), 2, ',', '.'),
                'ipi_valor' => number_format((float) ($nota->ipi_valor ?? 0), 2, ',', '.'),
                'tributos_total' => number_format((float) ($nota->tributos_total ?? 0), 2, ',', '.'),
            ],
        ]);
    }

    /**
     * Exclui um participante e seus registros associados (cascades do DB).
     * Notas fiscais (xml_notas, efd_notas) ficam com participante_id = NULL.
     */
    public function destroy(Request $request, $id)
    {
        if (! Auth::check()) {
            return response()->json([
                'success' => false,
                'error' => 'Usuário não autenticado.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $user = Auth::user();
        $userId = (int) $user->id;

        $participante = Participante::where('id', $id)
            ->where('user_id', $userId)
            ->first();

        if (! $participante) {
            return response()->json([
                'success' => false,
                'error' => 'Participante não encontrado.',
            ], Response::HTTP_NOT_FOUND);
        }

        // Contar registros associados antes de deletar
        $assinaturas = MonitoramentoAssinatura::where('participante_id', $participante->id)->count();
        $consultas = MonitoramentoConsulta::where('participante_id', $participante->id)->count();
        $scores = $participante->score()->exists() ? 1 : 0;
        $notasXml = XmlNota::where('user_id', $userId)
            ->where(fn ($q) => $q->where('emit_participante_id', $participante->id)
                ->orWhere('dest_participante_id', $participante->id))
            ->count();
        $consultaLoteResultados = ConsultaResultado::where('participante_id', $participante->id)->count();

        try {
            $razaoSocial = $participante->razao_social;
            $cnpj = $participante->documento;

            // DB cascades handle: assinaturas, consultas, scores, pivot grupos, consulta_lote_resultados
            // xml_notas/efd_notas: SET NULL on participante_id
            $participante->delete();

            Log::info('Participante excluído', [
                'user_id' => $userId,
                'participante_id' => $id,
                'cnpj' => $cnpj,
                'deletados' => compact('assinaturas', 'consultas', 'scores', 'consultaLoteResultados'),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Participante excluído com sucesso.',
                'deletados' => [
                    'assinaturas' => $assinaturas,
                    'consultas' => $consultas,
                    'scores' => $scores,
                    'consulta_lote_resultados' => $consultaLoteResultados,
                    'notas_desvinculadas' => $notasXml,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao excluir participante', [
                'user_id' => $userId,
                'participante_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erro ao excluir participante. Tente novamente.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Bulk delete participantes.
     * DB cascades handle: assinaturas, consultas, scores, pivot grupos, consulta_lote_resultados.
     * xml_notas/efd_notas: SET NULL on participante_id.
     */
    public function bulkExcluir(Request $request)
    {
        if (! Auth::check()) {
            return response()->json([
                'success' => false,
                'error' => 'Usuario nao autenticado.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $user = Auth::user();
        $userId = (int) $user->id;

        $validated = $request->validate([
            'ids' => 'required|array|min:1|max:500',
            'ids.*' => 'integer',
        ]);

        $cpfSelecionados = Participante::where('user_id', $userId)
            ->whereIn('id', $validated['ids'])
            ->somenteCpf()
            ->count();

        if ($cpfSelecionados > 0) {
            return response()->json([
                'success' => false,
                'error' => 'CPFs não podem ser selecionados para ações em lote.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $count = Participante::where('user_id', $userId)
                ->whereIn('id', $validated['ids'])
                ->delete();

            Log::info('Participantes excluidos em lote', [
                'user_id' => $userId,
                'count' => $count,
                'ids' => $validated['ids'],
            ]);

            return response()->json([
                'success' => true,
                'message' => $count.' participante(s) excluido(s) com sucesso.',
                'count' => $count,
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao excluir participantes em lote', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erro ao excluir participantes. Tente novamente.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Retorna participantes por array de IDs (JSON para AJAX).
     * Usado quando n8n envia participante_ids no payload de conclusão.
     */
    public function porIds(Request $request)
    {
        if (! Auth::check()) {
            return response()->json([
                'success' => false,
                'error' => 'Usuário não autenticado.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $user = Auth::user();
        $ids = $request->input('ids', []);

        if (empty($ids) || ! is_array($ids)) {
            return response()->json([
                'success' => false,
                'error' => 'Nenhum ID de participante fornecido.',
            ], Response::HTTP_BAD_REQUEST);
        }

        // Converter strings para inteiros (n8n pode enviar ["295", "325"] como strings)
        $ids = array_map('intval', $ids);

        // Buscar participantes pelos IDs, garantindo que pertencem ao usuário
        $perPage = $request->input('per_page', 10);
        $query = Participante::whereIn('id', $ids)
            ->where('user_id', $user->id);

        $importacaoId = $request->input('importacao_id');
        if ($importacaoId) {
            $query->orderByDesc(
                EfdNota::selectRaw('COALESCE(SUM(valor_total), 0)')
                    ->whereColumn('participante_id', 'participantes.id')
                    ->where('importacao_id', $importacaoId)
            );
        } else {
            $query->orderByDesc(
                EfdNota::selectRaw('COALESCE(SUM(valor_total), 0)')
                    ->whereColumn('participante_id', 'participantes.id')
            )->orderBy('created_at', 'desc');
        }

        $participantes = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'participantes' => $participantes->map(function ($p) {
                return [
                    'id' => $p->id,
                    'cnpj' => $p->documento,
                    'razao_social' => $p->razao_social,
                    'situacao_cadastral' => $p->situacao_cadastral,
                    'regime_tributario' => $p->regime_tributario,
                    'uf' => $p->uf,
                ];
            }),
            'total' => $participantes->total(),
            'per_page' => $participantes->perPage(),
            'current_page' => $participantes->currentPage(),
            'last_page' => $participantes->lastPage(),
            'prev_page_url' => $participantes->previousPageUrl(),
            'next_page_url' => $participantes->nextPageUrl(),
        ]);
    }

    /**
     * Retorna participantes de uma importação específica (JSON para AJAX).
     */
    public function porImportacao(Request $request, $importacaoId)
    {
        if (! Auth::check()) {
            return response()->json([
                'success' => false,
                'error' => 'Usuário não autenticado.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $user = Auth::user();

        // Verificar se a importação pertence ao usuário
        $importacao = EfdImportacao::where('id', $importacaoId)
            ->where('user_id', $user->id)
            ->first();

        if (! $importacao) {
            return response()->json([
                'success' => false,
                'error' => 'Importação não encontrada.',
            ], Response::HTTP_NOT_FOUND);
        }

        // Buscar participantes dessa importação
        $perPage = $request->input('per_page', 10);
        $query = Participante::where('user_id', $user->id);

        // Prioriza participante_ids salvo na EfdImportacao (caminho atual do n8n)
        // Fallback para importacao_efd_id (campo legado)
        if (! empty($importacao->participante_ids)) {
            $query->whereIn('id', $importacao->participante_ids);
        } else {
            $query->where('importacao_efd_id', $importacaoId);
        }

        $participantes = $query->orderByDesc(
            EfdNota::selectRaw('COALESCE(SUM(valor_total), 0)')
                ->whereColumn('participante_id', 'participantes.id')
                ->where('importacao_id', $importacaoId)
        )->paginate($perPage);

        return response()->json([
            'success' => true,
            'importacao' => [
                'id' => $importacao->id,
                'filename' => $importacao->filename,
                'tipo_efd' => $importacao->tipo_efd,
                'total_participantes' => $importacao->total_participantes,
                'novos' => $importacao->novos,
                'duplicados' => $importacao->duplicados,
                'created_at' => $importacao->created_at->format('d/m/Y H:i'),
            ],
            'participantes' => $participantes->map(function ($p) {
                return [
                    'id' => $p->id,
                    'cnpj' => $p->documento,
                    'razao_social' => $p->razao_social,
                    'situacao_cadastral' => $p->situacao_cadastral,
                    'regime_tributario' => $p->regime_tributario,
                    'uf' => $p->uf,
                ];
            }),
            'total' => $participantes->total(),
            'per_page' => $participantes->perPage(),
            'current_page' => $participantes->currentPage(),
            'last_page' => $participantes->lastPage(),
            'prev_page_url' => $participantes->previousPageUrl(),
            'next_page_url' => $participantes->nextPageUrl(),
        ]);
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

    /**
     * Verifica se a requisição é AJAX.
     */
    private function isAjaxRequest(Request $request): bool
    {
        if (method_exists($request, 'ajax')) {
            return $request->ajax();
        }

        $xRequestedWith = $request->header('X-Requested-With');
        $wantsJson = $request->wantsJson();
        $expectsJson = $request->expectsJson();

        return $wantsJson
            || $expectsJson
            || $xRequestedWith === 'XMLHttpRequest';
    }
}
