<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\ConsultaLote;
use App\Models\EfdNota;
use App\Models\XmlImportacao;
use App\Models\XmlNota;
use App\Services\Clearance\DivergenciaService;
use App\Services\CreditService;
use App\Services\NotaFiscalService;
use App\Services\ValidacaoContabilService;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class ClearanceController extends Controller
{
    public const CLEARANCE_NFE_AVULSA_CUSTO = 14;

    private const BUSCA_AVULSA_CACHE_TTL_MINUTES = 120;

    private const AUTH_VIEW_PREFIX = 'autenticado.clearance.';

    private const AUTH_LAYOUT_VIEW = 'autenticado.layouts.app';

    public function __construct(
        protected ValidacaoContabilService $validacaoService,
        protected CreditService $creditService,
        protected NotaFiscalService $notaFiscalService
    ) {}

    /**
     * Dashboard de Clearance DF-e — KPIs unificados XML+EFD por status Receita Federal.
     */
    public function index(Request $request)
    {
        if (! Auth::check()) {
            return $this->redirectToLogin($request);
        }

        $userId = Auth::id();
        $user = Auth::user();

        $data = [
            'kpis' => $this->validacaoService->getKpisStatusReceita($userId),
            'notasBloqueantes' => $this->validacaoService->getNotasComSituacaoBloqueante($userId, 5),
            'ultimasVerificacoes' => $this->validacaoService->getUltimasVerificacoes($userId, 10),
            'saldoCreditos' => $this->creditService->getBalance($user),
            'custoConsultaUnitaria' => 14,
        ];

        return $this->render($request, 'index', $data);
    }

    /**
     * Listagem paginada de notas com filtros e bulk-select.
     */
    public function notas(Request $request)
    {
        if (! Auth::check()) {
            return $this->redirectToLogin($request);
        }

        $userId = Auth::id();
        $filtros = $this->filtrosListagem($request);

        $sort = $request->input('sort', 'data_emissao');
        $dir = strtolower($request->input('dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        $sortMap = [
            'origem' => 'origem',
            'numero' => 'numero',
            'data_emissao' => 'data_emissao',
            'emit_razao_social' => 'emit_razao_social',
            'dest_razao_social' => 'dest_razao_social',
            'valor_total' => 'valor_total',
            'tipo_nota' => 'tipo_nota',
            'modelo' => 'modelo',
            'status' => null,
        ];

        if (! array_key_exists($sort, $sortMap)) {
            $sort = 'data_emissao';
        }

        $query = $this->queryListagem($userId, $filtros);

        if ($sort === 'status') {
            $query->orderByRaw(
                "CASE
                    WHEN validacao_json IS NULL THEN 0
                    WHEN EXISTS (
                        SELECT 1 FROM jsonb_array_elements((validacao_json::jsonb)->'alertas') a
                        WHERE a->>'nivel' = 'bloqueante'
                    ) THEN 3
                    WHEN EXISTS (
                        SELECT 1 FROM jsonb_array_elements((validacao_json::jsonb)->'alertas') a
                        WHERE a->>'nivel' = 'atencao'
                    ) THEN 2
                    ELSE 1
                 END $dir"
            )->orderByDesc('data_emissao')->orderByDesc('id');
        } else {
            $query->orderBy($sortMap[$sort], $dir)->orderByDesc('id');
        }

        $notas = $query->paginate(50)->withQueryString();

        $notas->getCollection()->transform(function ($row) {
            $row->validacao = $row->validacao_json ? json_decode($row->validacao_json, true) : null;
            unset($row->validacao_json);

            $badge = $this->modeloBadge($row->modelo ?? null);
            $row->modelo_label = $badge['label'];
            $row->modelo_hex = $badge['hex'];

            return $row;
        });

        $clientes = \App\Models\Cliente::where('user_id', $userId)
            ->orderBy('razao_social')
            ->get(['id', 'razao_social', 'documento']);

        $data = [
            'notas' => $notas,
            'clientes' => $clientes,
            'filtros' => $filtros,
            'escopoNotas' => $this->buildEscopoNotasResumo($userId),
            'saldoAtual' => $this->creditService->getBalance(Auth::user()),
            'custosTiers' => [
                'basico' => ValidacaoContabilService::custoUnitarioPorTier('basico'),
                'full' => ValidacaoContabilService::custoUnitarioPorTier('full'),
            ],
            'sort' => $sort,
            'dir' => $dir,
        ];

        return $this->render($request, 'notas', $data);
    }

    /**
     * Retorna todos os IDs que batem com os filtros atuais (cross-page select).
     */
    public function todosIds(Request $request)
    {
        if (! Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $userId = Auth::id();
        $filtros = $this->filtrosListagem($request);
        $status = $filtros['status_validacao'] ?? 'todos';

        $xml = $this->xmlSubquery($userId, $filtros);
        if ($status === 'validadas') {
            $xml->whereNotNull('xml_notas.validacao');
        } elseif ($status === 'com_alertas') {
            $xml->whereNotNull('xml_notas.validacao')
                ->whereRaw("EXISTS (SELECT 1 FROM jsonb_array_elements(xml_notas.validacao->'alertas') AS a WHERE a->>'nivel' = 'bloqueante')");
        } elseif ($status === 'nao_validadas') {
            $xml->whereNull('xml_notas.validacao');
        } elseif ($status === 'sem_situacao_receita') {
            $xml->whereRaw("(xml_notas.validacao IS NULL OR xml_notas.validacao->>'situacao' IS NULL)");
        }
        $xmlIds = $xml->pluck('id')->map(fn ($v) => (int) $v)->values();

        $efdIds = collect();
        if (! in_array($status, ['validadas', 'com_alertas'], true)) {
            $efd = $this->efdSubquery($userId, $filtros);
            if ($status === 'sem_situacao_receita') {
                $efd->whereRaw("(efd_notas.validacao IS NULL OR efd_notas.validacao->>'situacao' IS NULL)");
            }
            $efd->whereNotExists(function ($q) use ($userId) {
                $q->select(DB::raw(1))
                    ->from('xml_notas')
                    ->whereColumn('xml_notas.nfe_id', 'efd_notas.chave_acesso')
                    ->where('xml_notas.user_id', $userId);
            });
            $efdIds = $efd->pluck('id')->map(fn ($v) => (int) $v)->values();
        }

        $origens = [];
        foreach ($xmlIds as $id) {
            $origens[$id] = 'xml';
        }
        foreach ($efdIds as $id) {
            $origens[$id] = 'efd';
        }

        $ids = $xmlIds->concat($efdIds)->values();

        return response()->json([
            'success' => true,
            'ids' => $ids,
            'origens' => (object) $origens,
            'total' => $ids->count(),
        ]);
    }

    /**
     * Busca avulsa de NF-e por chave de acesso.
     */
    public function buscarNfe(Request $request)
    {
        if (! Auth::check()) {
            return $this->redirectToLogin($request);
        }

        $data = [
            'saldoAtual' => $this->creditService->getBalance(Auth::user()),
            'custoEstimadoCreditos' => self::CLEARANCE_NFE_AVULSA_CUSTO,
            'clientes' => Cliente::where('user_id', Auth::id())
                ->orderByDesc('is_empresa_propria')
                ->orderBy('razao_social')
                ->get(['id', 'razao_social', 'documento', 'is_empresa_propria']),
            'defaultClienteId' => Auth::user()->empresaPropria()?->id,
            'ultimasConsultasDfe' => $this->listarUltimasConsultasDfe(Auth::id(), 3),
        ];

        return $this->render($request, 'buscar', $data);
    }

    public function historico(Request $request)
    {
        if (! Auth::check()) {
            return $this->redirectToLogin($request);
        }

        $userId = Auth::id();
        $filtros = $this->filtrosHistoricoConsultasDfe($request);

        $query = $this->notaFiscalService->consultaDfeHistoricoQuery($userId);
        $this->aplicarFiltrosHistoricoConsultasDfe($query, $filtros);

        $consultas = $query
            ->orderByRaw('COALESCE(consultado_em, created_at) DESC')
            ->orderByDesc('consulta_id')
            ->paginate(25)
            ->withQueryString();

        $consultas->getCollection()->transform(
            fn ($consulta) => $this->notaFiscalService->formatarHistoricoConsultaDfe($consulta, $userId)
        );

        return $this->render($request, 'historico', [
            'consultas' => $consultas,
            'filtros' => $filtros,
            'filtrosAtivos' => collect($filtros)->filter(fn ($value) => $value !== null && $value !== '')->count(),
            'statusOptions' => $this->statusOptionsHistoricoDfe($userId),
        ]);
    }

    /**
     * Dispara consulta avulsa de DF-e via n8n + InfoSimples.
     *
     * Valida input, debita créditos, cria ConsultaLote (plano_id=null)
     * e envia payload para o webhook n8n. Em falha, estorna créditos.
     */
    public function consultarNfe(Request $request)
    {
        if (! Auth::check()) {
            return response()->json([
                'success' => false,
                'error' => 'Usuário não autenticado.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $user = Auth::user();

        $validated = $request->validate([
            'tipo_documento' => 'nullable|string|in:nfe,cte,nfse',
            'chave_acesso' => 'nullable|string',
            'cliente_id' => 'required|integer',
            'tab_id' => 'required|string|max:36',
            'blocos' => 'nullable|array|min:1',
            'blocos.*.tipo_documento' => 'required|string|in:nfe,cte,nfse',
            'blocos.*.chaves_acesso' => 'required|string',
        ], [
            'cliente_id.required' => 'Selecione o cliente associado antes de consultar.',
            'cliente_id.integer' => 'Cliente inválido.',
        ]);
        $blocos = $this->normalizarBlocosBusca($validated);

        if ($blocos->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Erro de validação.',
                'errors' => ['blocos' => ['Adicione ao menos um bloco com tipo e chaves de acesso.']],
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $duplicadas = [];
        $chavesVistas = [];
        $notasPreparadas = collect();

        foreach ($blocos as $indiceBloco => $bloco) {
            $tipoDocumentoBloco = strtolower((string) ($bloco['tipo_documento'] ?? ''));

            if ($tipoDocumentoBloco === 'nfse') {
                return response()->json([
                    'success' => false,
                    'message' => 'Erro de validação.',
                    'errors' => ['blocos' => ['NFS-e ainda não é suportada. Em breve.']],
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $chaves = $this->extrairChavesAcesso($bloco['chaves_acesso'] ?? null, deduplicar: false);

            if ($chaves === []) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erro de validação.',
                    'errors' => ['blocos' => ['Cada bloco precisa ter ao menos uma chave de acesso válida.']],
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $modelosPermitidos = match ($tipoDocumentoBloco) {
                'nfe' => ['55', '65'],
                'cte' => ['57'],
                default => [],
            };

            foreach ($chaves as $indiceChave => $chave) {
                if (strlen($chave) !== 44) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Erro de validação.',
                        'errors' => ['blocos' => ['A chave #'.($indiceChave + 1).' do bloco '.($indiceBloco + 1).' deve ter 44 dígitos numéricos.']],
                    ], Response::HTTP_UNPROCESSABLE_ENTITY);
                }

                if (! $this->validarDigitoVerificadorDfe($chave)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Erro de validação.',
                        'errors' => ['blocos' => ['A chave #'.($indiceChave + 1).' do bloco '.($indiceBloco + 1).' possui dígito verificador inválido.']],
                    ], Response::HTTP_UNPROCESSABLE_ENTITY);
                }

                $modeloChave = substr($chave, 20, 2);

                if (! in_array($modeloChave, $modelosPermitidos, true)) {
                    $labelTipo = strtoupper($tipoDocumentoBloco);
                    $labelModelos = implode(', ', $modelosPermitidos);

                    return response()->json([
                        'success' => false,
                        'message' => 'Erro de validação.',
                        'errors' => ['blocos' => ["A chave {$chave} usa modelo {$modeloChave} incompatível com {$labelTipo}. Modelos aceitos: {$labelModelos}."]],
                    ], Response::HTTP_UNPROCESSABLE_ENTITY);
                }

                if (isset($chavesVistas[$chave])) {
                    $duplicadas[] = $chave;

                    continue;
                }

                $chavesVistas[$chave] = true;

                $notasPreparadas->push([
                    'ordem_lote' => $notasPreparadas->count() + 1,
                    'chave_acesso' => $chave,
                    'tipo_documento' => match ($modeloChave) {
                        '55' => 'NFE',
                        '65' => 'NFCE',
                        '57' => 'CTE',
                        default => strtoupper($tipoDocumentoBloco),
                    },
                    'tipo_documento_bloco' => $tipoDocumentoBloco,
                ]);
            }
        }

        if ($duplicadas !== []) {
            return response()->json([
                'success' => false,
                'message' => 'Erro de validação.',
                'errors' => ['blocos' => ['Existem chaves repetidas no envio: '.implode(', ', array_values(array_unique($duplicadas))).'.']],
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $clienteId = (int) $validated['cliente_id'];
        $cliente = Cliente::where('id', $clienteId)
            ->where('user_id', $user->id)
            ->first();

        if (! $cliente) {
            return response()->json([
                'success' => false,
                'error' => 'Cliente não encontrado ou não pertence a este usuário.',
            ], Response::HTTP_FORBIDDEN);
        }

        $clienteCnpj = preg_replace('/\D/', '', (string) $cliente->documento);

        $acervoPorChave = $this->localizarNotasNoAcervo($user->id, $notasPreparadas->pluck('chave_acesso'));
        $notasExistentes = collect();
        $notasParaConsultar = collect();

        foreach ($notasPreparadas as $notaPreparada) {
            $chave = $notaPreparada['chave_acesso'];
            $ordem = (int) $notaPreparada['ordem_lote'];

            if (isset($acervoPorChave[$chave])) {
                $notasExistentes->push(
                    $this->formatarResultadoAcervoExistente(
                        $acervoPorChave[$chave]['nota'],
                        $acervoPorChave[$chave]['origem'],
                        $ordem
                    )
                );

                continue;
            }

            $notasParaConsultar->push(array_merge($notaPreparada, [
                'id' => null,
                'origem' => 'avulsa',
                'cliente_id' => $clienteId,
            ]));
        }

        $quantidadeNotas = $notasParaConsultar->count();
        $quantidadeItens = $notasPreparadas->count();
        $quantidadeExistentes = $notasExistentes->count();
        $custo = self::CLEARANCE_NFE_AVULSA_CUSTO * $quantidadeNotas;
        $tiposNoEnvio = $notasPreparadas->pluck('tipo_documento')->unique()->values();
        $labelTipoDoc = $tiposNoEnvio->contains('CTE') && $tiposNoEnvio->count() > 1
            ? 'DF-e'
            : ($tiposNoEnvio->contains('CTE') ? 'CT-e' : 'NF-e');
        $transactionType = 'clearance_busca_avulsa';
        $refundType = 'clearance_busca_avulsa_refund';

        if ($quantidadeItens === 0) {
            return response()->json([
                'success' => false,
                'message' => 'Erro de validação.',
                'errors' => ['blocos' => ['Nenhuma chave válida foi encontrada no envio.']],
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($quantidadeNotas === 0) {
            $token = $this->storeBuscaResultadoLocal($user->id, [
                'cliente_id' => $clienteId,
                'cliente_nome' => Cliente::query()->where('id', $clienteId)->value('razao_social'),
                'resultados' => $notasExistentes->sortBy('ordem_lote')->values()->all(),
                'resumo' => $this->resumirResultadosClearance($notasExistentes),
                'total_itens' => $quantidadeItens,
                'total_existentes' => $quantidadeExistentes,
            ]);

            return response()->json([
                'success' => true,
                'resultado_url' => route('app.clearance.buscar.resultado-local', ['token' => $token]),
                'mensagem' => 'Todas as chaves já estavam no acervo.',
                'novo_saldo' => $this->creditService->getBalance($user),
                'total_itens' => $quantidadeItens,
                'total_existentes' => $quantidadeExistentes,
                'total_consultadas' => 0,
            ]);
        }

        if (! $this->creditService->hasEnough($user, $custo)) {
            return response()->json([
                'success' => false,
                'error' => 'Créditos insuficientes.',
                'custo_necessario' => $custo,
                'saldo_atual' => $this->creditService->getBalance($user),
            ], Response::HTTP_PAYMENT_REQUIRED);
        }

        $webhookUrl = config('services.webhook.busca_nota_url');

        if (empty($webhookUrl)) {
            Log::error("Clearance {$labelTipoDoc}: webhook não configurado (WEBHOOK_BUSCA_NOTA_URL)");

            return response()->json([
                'success' => false,
                'error' => 'Configuração de webhook ausente. Contate o suporte.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $debitado = $this->creditService->deduct(
            $user,
            $custo,
            $transactionType,
            $quantidadeNotas === 1
                ? "Clearance {$labelTipoDoc} avulsa · chave …".substr($notasParaConsultar->first()['chave_acesso'], -4)
                : "Clearance {$labelTipoDoc} avulsa em lote · {$quantidadeNotas} chaves"
        );

        if (! $debitado) {
            return response()->json([
                'success' => false,
                'error' => 'Falha ao debitar créditos. Tente novamente.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $lote = null;

        try {
            $lote = ConsultaLote::create([
                'user_id' => $user->id,
                'cliente_id' => $clienteId,
                'plano_id' => null,
                'status' => ConsultaLote::STATUS_PROCESSANDO,
                'total_participantes' => $quantidadeNotas,
                'creditos_cobrados' => $custo,
                'tab_id' => $validated['tab_id'],
            ]);

            $ordemPorChave = $notasPreparadas
                ->mapWithKeys(fn (array $nota) => [$nota['chave_acesso'] => (int) $nota['ordem_lote']])
                ->all();

            $this->storeBuscaAcervoPrecheck($user->id, $lote->id, [
                'resultados' => $notasExistentes->values()->all(),
                'ordem_por_chave' => $ordemPorChave,
            ]);

            $notasPayload = $notasParaConsultar->values()->map(function (array $nota) {
                return [
                    'id' => null,
                    'origem' => 'avulsa',
                    'chave_acesso' => $nota['chave_acesso'],
                    'tipo_documento' => $nota['tipo_documento'],
                    'cliente_id' => $nota['cliente_id'],
                ];
            })->all();

            $totalNfe = collect($notasPayload)
                ->filter(fn (array $nota) => in_array(($nota['tipo_documento'] ?? null), ['NFE', 'NFCE'], true))
                ->count();
            $totalCte = collect($notasPayload)
                ->filter(fn (array $nota) => ($nota['tipo_documento'] ?? null) === 'CTE')
                ->count();

            $payload = [
                'user_id' => $user->id,
                'cliente_id' => $clienteId,
                'cliente_cnpj' => $clienteCnpj,
                'consulta_lote_id' => $lote->id,
                'tab_id' => $validated['tab_id'],
                'tipo_validacao' => 'basico',
                'total_notas' => $quantidadeNotas,
                'total_nfe' => $totalNfe,
                'total_cte' => $totalCte,
                'notas' => $notasPayload,
                'progress_url' => url('/api/consultas/progresso'),
            ];

            $response = Http::timeout(15)
                ->withHeaders([
                    'X-API-Token' => config('services.api.token'),
                    'Content-Type' => 'application/json',
                ])
                ->post($webhookUrl, $payload);

            if (! $response->successful()) {
                $this->creditService->add(
                    $user,
                    $custo,
                    $refundType,
                    'Estorno · webhook clearance indisponível'
                );

                $lote->update([
                    'status' => ConsultaLote::STATUS_ERRO,
                    'error_code' => 'WEBHOOK_ERROR',
                    'error_message' => 'Webhook n8n respondeu '.$response->status(),
                ]);

                Log::error("Clearance {$labelTipoDoc}: webhook retornou erro", [
                    'consulta_lote_id' => $lote->id,
                    'response_status' => $response->status(),
                    'response_body' => $response->body(),
                ]);

                return response()->json([
                    'success' => false,
                    'error' => 'Erro ao iniciar processamento. Créditos foram estornados.',
                ], Response::HTTP_BAD_GATEWAY);
            }

            Log::info("Clearance {$labelTipoDoc}: despachado para n8n", [
                'consulta_lote_id' => $lote->id,
                'user_id' => $user->id,
                'total_notas' => $quantidadeNotas,
                'total_existentes' => $quantidadeExistentes,
            ]);

            return response()->json([
                'success' => true,
                'consulta_lote_id' => $lote->id,
                'tab_id' => $validated['tab_id'],
                'progress_url' => url('/app/consulta/progresso/stream?tab_id='.$validated['tab_id']),
                'resultado_url' => $quantidadeNotas === 1 && $quantidadeExistentes === 0
                    ? route('app.clearance.buscar.resultado', [
                        'consultaLoteId' => $lote->id,
                        'tipo_documento' => strtolower($notasPayload[0]['tipo_documento']),
                        'chave_acesso' => $notasPayload[0]['chave_acesso'],
                    ])
                    : route('app.clearance.notas.resultado', ['consultaLoteId' => $lote->id]),
                'mensagem' => 'Consulta iniciada.',
                'novo_saldo' => $this->creditService->getBalance($user),
                'total_itens' => $quantidadeItens,
                'total_existentes' => $quantidadeExistentes,
                'total_consultadas' => $quantidadeNotas,
            ]);

        } catch (\Throwable $e) {
            if ($lote) {
                $lote->update([
                    'status' => ConsultaLote::STATUS_ERRO,
                    'error_code' => 'INTERNAL_ERROR',
                    'error_message' => $e->getMessage(),
                ]);
            }

            $this->creditService->add(
                $user,
                $custo,
                $refundType,
                'Estorno · exceção ao despachar clearance'
            );

            Log::error("Clearance {$labelTipoDoc}: exceção ao despachar", [
                'user_id' => $user->id,
                'consulta_lote_id' => $lote?->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erro interno ao processar consulta.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function normalizarBlocosBusca(array $validated): Collection
    {
        if (! empty($validated['blocos']) && is_array($validated['blocos'])) {
            return collect($validated['blocos'])
                ->map(fn ($bloco) => [
                    'tipo_documento' => strtolower((string) ($bloco['tipo_documento'] ?? '')),
                    'chaves_acesso' => (string) ($bloco['chaves_acesso'] ?? ''),
                ])
                ->filter(fn (array $bloco) => $bloco['tipo_documento'] !== '' || trim($bloco['chaves_acesso']) !== '')
                ->values();
        }

        if (! empty($validated['tipo_documento']) || ! empty($validated['chave_acesso'])) {
            return collect([[
                'tipo_documento' => strtolower((string) ($validated['tipo_documento'] ?? '')),
                'chaves_acesso' => (string) ($validated['chave_acesso'] ?? ''),
            ]]);
        }

        return collect();
    }

    private function extrairChavesAcesso(?string $conteudo, bool $deduplicar = true): array
    {
        $linhas = preg_split('/[\r\n,;]+/', (string) $conteudo) ?: [];

        $chaves = collect($linhas)
            ->map(fn ($linha) => preg_replace('/\D/', '', (string) $linha))
            ->filter()
            ->values();

        if ($deduplicar) {
            $chaves = $chaves->unique()->values();
        }

        return $chaves->all();
    }

    private function localizarNotasNoAcervo(int $userId, Collection $chaves): array
    {
        $chaves = $chaves->filter()->unique()->values();

        if ($chaves->isEmpty()) {
            return [];
        }

        $xml = XmlNota::query()
            ->with('cliente')
            ->where('user_id', $userId)
            ->whereIn('nfe_id', $chaves)
            ->get()
            ->keyBy('nfe_id');

        $chavesRestantes = $chaves->reject(fn (string $chave) => $xml->has($chave))->values();

        $efd = EfdNota::query()
            ->with(['cliente', 'participante'])
            ->where('user_id', $userId)
            ->whereIn('chave_acesso', $chavesRestantes)
            ->get()
            ->keyBy('chave_acesso');

        $encontradas = [];

        foreach ($chaves as $chave) {
            if ($xml->has($chave)) {
                $encontradas[$chave] = [
                    'origem' => 'xml',
                    'nota' => $xml->get($chave),
                ];

                continue;
            }

            if ($efd->has($chave)) {
                $encontradas[$chave] = [
                    'origem' => 'efd',
                    'nota' => $efd->get($chave),
                ];
            }
        }

        return $encontradas;
    }

    private function formatarResultadoAcervoExistente(object $nota, string $origem, int $ordem): object
    {
        if ($origem === 'xml' && $nota instanceof XmlNota) {
            return (object) [
                'id' => 'xml-'.$nota->id,
                'consulta_lote_id' => null,
                'chave_acesso' => $nota->nfe_id,
                'tipo_documento' => strtoupper((string) ($nota->tipo_documento ?: 'NFE')),
                'modelo' => $this->inferirModeloDocumento($nota->tipo_documento, $nota->nfe_id),
                'numero' => $nota->numero_nota,
                'serie' => $nota->serie,
                'status' => 'JA_NO_ACERVO',
                'status_label' => 'JA_NO_ACERVO',
                'status_hex' => $this->statusHexConsultaDfe('JA_NO_ACERVO'),
                'valor_total' => $nota->valor_total,
                'valor_total_label' => $nota->valor_total !== null ? 'R$ '.number_format((float) $nota->valor_total, 2, ',', '.') : '—',
                'data_emissao' => $nota->data_emissao,
                'data_emissao_label' => optional($nota->data_emissao)->format('d/m/Y H:i'),
                'emit_nome' => $nota->emit_razao_social,
                'emit_cnpj' => $nota->emit_cnpj,
                'dest_nome' => $nota->dest_razao_social,
                'dest_cnpj' => $nota->dest_cnpj,
                'tomador_nome' => null,
                'tomador_cnpj' => null,
                'participante_label' => $nota->dest_razao_social ?: $nota->dest_cnpj ?: 'Não informado',
                'consultado_em' => null,
                'consultado_em_label' => 'Já no acervo',
                'detalhe_url' => route('app.notas-fiscais.detalhes', ['origem' => 'xml', 'id' => $nota->id]),
                'origem_acervo_label' => 'XML',
                'origem_acervo_hex' => '#0f766e',
                'ordem_lote' => $ordem,
            ];
        }

        /** @var EfdNota $nota */
        $tipoDocumento = match ((string) $nota->modelo) {
            '57' => 'CTE',
            '65' => 'NFCE',
            default => 'NFE',
        };
        $clienteNome = $nota->cliente?->razao_social;
        $participanteNome = $nota->participante?->razao_social;
        $emitente = $nota->tipo_operacao === 'entrada' ? ($participanteNome ?: 'Participante EFD') : ($clienteNome ?: 'Empresa');
        $destinatario = $nota->tipo_operacao === 'entrada' ? ($clienteNome ?: 'Empresa') : ($participanteNome ?: 'Participante EFD');

        return (object) [
            'id' => 'efd-'.$nota->id,
            'consulta_lote_id' => null,
            'chave_acesso' => $nota->chave_acesso,
            'tipo_documento' => $tipoDocumento,
            'modelo' => (string) ($nota->modelo ?: $this->inferirModeloDocumento($tipoDocumento, $nota->chave_acesso)),
            'numero' => $nota->numero,
            'serie' => $nota->serie,
            'status' => 'JA_NO_ACERVO',
            'status_label' => 'JA_NO_ACERVO',
            'status_hex' => $this->statusHexConsultaDfe('JA_NO_ACERVO'),
            'valor_total' => $nota->valor_total,
            'valor_total_label' => $nota->valor_total !== null ? 'R$ '.number_format((float) $nota->valor_total, 2, ',', '.') : '—',
            'data_emissao' => $nota->data_emissao,
            'data_emissao_label' => optional($nota->data_emissao)->format('d/m/Y'),
            'emit_nome' => $emitente,
            'emit_cnpj' => null,
            'dest_nome' => $destinatario,
            'dest_cnpj' => null,
            'tomador_nome' => null,
            'tomador_cnpj' => null,
            'participante_label' => $destinatario ?: 'Não informado',
            'consultado_em' => null,
            'consultado_em_label' => 'Já no acervo',
            'detalhe_url' => route('app.notas-fiscais.detalhes', ['origem' => 'efd', 'id' => $nota->id]),
            'origem_acervo_label' => 'EFD',
            'origem_acervo_hex' => '#4338ca',
            'ordem_lote' => $ordem,
        ];
    }

    private function storeBuscaAcervoPrecheck(int $userId, int $consultaLoteId, array $payload): void
    {
        Cache::put(
            $this->buscarAcervoPrecheckCacheKey($userId, $consultaLoteId),
            $payload,
            now()->addMinutes(self::BUSCA_AVULSA_CACHE_TTL_MINUTES)
        );
    }

    private function getBuscaAcervoPrecheck(int $userId, int $consultaLoteId): array
    {
        return Cache::get($this->buscarAcervoPrecheckCacheKey($userId, $consultaLoteId), []);
    }

    private function buscarAcervoPrecheckCacheKey(int $userId, int $consultaLoteId): string
    {
        return "clearance:buscar:precheck:user:{$userId}:lote:{$consultaLoteId}";
    }

    private function storeBuscaResultadoLocal(int $userId, array $payload): string
    {
        $token = Str::random(40);

        Cache::put(
            $this->buscarResultadoLocalCacheKey($userId, $token),
            $payload,
            now()->addMinutes(self::BUSCA_AVULSA_CACHE_TTL_MINUTES)
        );

        return $token;
    }

    private function getBuscaResultadoLocal(int $userId, string $token): ?array
    {
        return Cache::get($this->buscarResultadoLocalCacheKey($userId, $token));
    }

    private function buscarResultadoLocalCacheKey(int $userId, string $token): string
    {
        return "clearance:buscar:local:user:{$userId}:token:{$token}";
    }

    private function inferirModeloDocumento(?string $tipoDocumento, ?string $chaveAcesso): string
    {
        $chave = preg_replace('/\D/', '', (string) $chaveAcesso);

        if (strlen($chave) === 44) {
            return substr($chave, 20, 2);
        }

        return match (strtoupper((string) $tipoDocumento)) {
            'CTE' => '57',
            'NFCE' => '65',
            default => '55',
        };
    }

    /**
     * Retorna a consulta canônica persistida pelo fluxo de clearance avulso.
     *
     * Chamado pelo frontend depois do SSE sinalizar status=finalizado.
     */
    public function resultadoUltimaConsulta(Request $request, int $consultaLoteId)
    {
        if (! Auth::check()) {
            if ($this->isAjaxRequest($request)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Usuário não autenticado.',
                ], Response::HTTP_UNAUTHORIZED);
            }

            return $this->redirectToLogin($request);
        }

        $userId = Auth::id();
        $chaveConsultada = preg_replace('/\D/', '', (string) $request->query('chave_acesso', ''));
        $tipoDocumento = strtoupper((string) $request->query('tipo_documento', 'NFE'));

        $lote = ConsultaLote::where('id', $consultaLoteId)
            ->where('user_id', $userId)
            ->first();

        if (! $lote) {
            if (! $this->isAjaxRequest($request)) {
                abort(404);
            }

            return response()->json([
                'success' => false,
                'error' => 'Lote não encontrado.',
            ], Response::HTTP_NOT_FOUND);
        }

        $nota = $this->buscarConsultaDfePorLote($userId, $lote->id);
        $notaAcervo = null;

        if (! $nota && strlen($chaveConsultada) === 44) {
            $notaAcervo = $this->buscarNotaAcervoPorChave($userId, $chaveConsultada);
        }

        $notaResultado = $nota
            ? $this->formatarResultadoConsultaDfe($nota, $userId)
            : ($notaAcervo ? $this->formatarResultadoXmlAcervo($notaAcervo) : null);

        if (! $this->isAjaxRequest($request)) {
            return $this->render($request, 'buscar-resultado', [
                'lote' => $lote,
                'statusMeta' => $this->statusMetaLote($lote->status),
                'notaResultado' => $notaResultado,
                'tipoDocumento' => $tipoDocumento,
                'chaveConsultada' => strlen($chaveConsultada) === 44
                    ? $chaveConsultada
                    : ($notaResultado['nfe_id'] ?? null),
                'aguardaPersistencia' => $lote->isFinalizado() && ! $notaResultado,
                'progressSnapshot' => $this->getClearanceProgressSnapshot($lote),
            ]);
        }

        if (! $notaResultado) {
            return response()->json([
                'success' => false,
                'error' => 'Consulta ainda não persistida nas tabelas canônicas do clearance.',
                'status_lote' => ConsultaLote::normalizeStatus($lote->status),
                'resultado_pronto' => false,
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'success' => true,
            'status_lote' => ConsultaLote::normalizeStatus($lote->status),
            'resultado_pronto' => true,
            'nota' => $notaResultado,
        ]);
    }

    public function resultadoBuscaLocal(Request $request, string $token)
    {
        if (! Auth::check()) {
            if ($this->isAjaxRequest($request)) {
                return response()->json(['success' => false, 'error' => 'Usuário não autenticado.'], Response::HTTP_UNAUTHORIZED);
            }

            return $this->redirectToLogin($request);
        }

        $userId = Auth::id();
        $payload = $this->getBuscaResultadoLocal($userId, $token);

        if (! $payload) {
            if ($this->isAjaxRequest($request)) {
                return response()->json(['success' => false, 'error' => 'Resultado local não encontrado.'], Response::HTTP_NOT_FOUND);
            }

            abort(404);
        }

        $resultados = collect($payload['resultados'] ?? [])->map(fn ($resultado) => (object) $resultado);
        $resumo = $payload['resumo'] ?? $this->resumirResultadosClearance($resultados);

        return $this->render($request, 'buscar-resultado-local', [
            'resultados' => $resultados,
            'resumo' => $resumo,
            'clienteNome' => $payload['cliente_nome'] ?? 'Cliente não informado',
            'totalItens' => (int) ($payload['total_itens'] ?? $resultados->count()),
            'totalExistentes' => (int) ($payload['total_existentes'] ?? $resultados->count()),
        ]);
    }

    public function resultadoNotas(Request $request, int $consultaLoteId)
    {
        if (! Auth::check()) {
            if ($this->isAjaxRequest($request)) {
                return response()->json(['success' => false, 'error' => 'Usuário não autenticado.'], Response::HTTP_UNAUTHORIZED);
            }

            return $this->redirectToLogin($request);
        }

        $userId = Auth::id();

        $lote = ConsultaLote::where('id', $consultaLoteId)
            ->where('user_id', $userId)
            ->first();

        if (! $lote) {
            if ($this->isAjaxRequest($request)) {
                return response()->json(['success' => false, 'error' => 'Lote não encontrado.'], Response::HTTP_NOT_FOUND);
            }

            abort(404);
        }

        $resultados = $this->listarConsultasDfePorLote($userId, $lote->id);
        $resumo = $this->resumirResultadosClearance($resultados);

        $analiseDivergencia = (new DivergenciaService)->analisar(
            $resultados,
            $userId,
            (int) ($lote->creditos_cobrados ?? 0)
        );

        if ($this->isAjaxRequest($request)) {
            $resultadoPronto = $lote->isFinalizado() && $resultados->isNotEmpty();

            return response()->json([
                'success' => true,
                'status_lote' => ConsultaLote::normalizeStatus($lote->status),
                'total_resultados' => $resultados->count(),
                'resultado_pronto' => $resultadoPronto,
                'resumo' => $resumo,
                'veredito' => $analiseDivergencia['veredito'],
                'kpis' => $analiseDivergencia['kpis'],
            ]);
        }

        return $this->render($request, 'notas-resultado', [
            'lote' => $lote,
            'statusMeta' => $this->statusMetaLote($lote->status),
            'resultados' => $resultados,
            'resumo' => $resumo,
            'divergencia' => $analiseDivergencia,
            'tipoValidacao' => strtolower((string) $request->query('tipo_validacao', '')),
            'aguardaPersistencia' => $lote->isFinalizado() && $resultados->isEmpty(),
            'progressSnapshot' => $this->getClearanceProgressSnapshot($lote),
        ]);
    }

    /**
     * Valida dígito verificador (módulo 11) de uma chave de acesso NF-e de 44 dígitos.
     */
    private function validarDigitoVerificadorDfe(string $chave): bool
    {
        if (strlen($chave) !== 44 || ! ctype_digit($chave)) {
            return false;
        }

        $base = substr($chave, 0, 43);
        $dvInformado = (int) substr($chave, -1);

        $peso = 2;
        $soma = 0;
        for ($i = strlen($base) - 1; $i >= 0; $i--) {
            $soma += ((int) $base[$i]) * $peso;
            $peso = $peso === 9 ? 2 : $peso + 1;
        }

        $resto = $soma % 11;
        $dvCalculado = ($resto === 0 || $resto === 1) ? 0 : 11 - $resto;

        return $dvCalculado === $dvInformado;
    }

    /**
     * Normaliza tipo legado (completa/deep/local) para os tiers atuais (basico/full).
     * Free tier removido — local vira basico.
     */
    private function normalizarTier(?string $tipo): string
    {
        return match ($tipo) {
            'full', 'deep' => 'full',
            default => 'basico',
        };
    }

    private function filtrosListagem(Request $request): array
    {
        return [
            'periodo_de' => $request->input('periodo_de'),
            'periodo_ate' => $request->input('periodo_ate'),
            'cliente_id' => $request->input('cliente_id'),
            'participante_cnpj' => $request->input('participante_cnpj'),
            'tipo_nota' => $request->input('tipo_nota'),
            'status_validacao' => $request->input('status_validacao', 'todos'),
            'situacao_receita' => $request->input('situacao_receita'),
        ];
    }

    private function queryListagem(int $userId, array $f): Builder
    {
        $status = $f['status_validacao'] ?? 'todos';

        $xml = $this->xmlSubquery($userId, $f);

        if ($status === 'validadas') {
            $xml->whereNotNull('xml_notas.validacao');

            return DB::query()->fromSub($xml, 'u');
        }

        if ($status === 'com_alertas') {
            $xml->whereNotNull('xml_notas.validacao')
                ->whereRaw("EXISTS (SELECT 1 FROM jsonb_array_elements(xml_notas.validacao->'alertas') AS a WHERE a->>'nivel' = 'bloqueante')");

            return DB::query()->fromSub($xml, 'u');
        }

        if ($status === 'nao_validadas') {
            $xml->whereNull('xml_notas.validacao');
        }

        if ($status === 'sem_situacao_receita') {
            $xml->whereRaw("(xml_notas.validacao IS NULL OR xml_notas.validacao->>'situacao' IS NULL)");
        }

        $efd = $this->efdSubquery($userId, $f);

        if ($status === 'sem_situacao_receita') {
            $efd->whereRaw("(efd_notas.validacao IS NULL OR efd_notas.validacao->>'situacao' IS NULL)");
        }

        $efd->whereNotExists(function ($q) use ($userId) {
            $q->select(DB::raw(1))
                ->from('xml_notas')
                ->whereColumn('xml_notas.nfe_id', 'efd_notas.chave_acesso')
                ->where('xml_notas.user_id', $userId);
        });

        return DB::query()->fromSub($xml->unionAll($efd), 'u');
    }

    private function xmlSubquery(int $userId, array $f): \Illuminate\Database\Query\Builder
    {
        $q = DB::table('xml_notas')
            ->selectRaw("
                'xml'::text                                   as origem,
                xml_notas.id                                   as id,
                xml_notas.nfe_id                               as chave,
                xml_notas.numero_nota                          as numero,
                xml_notas.serie::text                          as serie,
                xml_notas.tipo_documento                       as modelo,
                xml_notas.data_emissao                         as data_emissao,
                xml_notas.valor_total                          as valor_total,
                CASE xml_notas.tipo_nota WHEN 0 THEN 'entrada' ELSE 'saida' END as tipo_nota,
                xml_notas.emit_razao_social                    as emit_razao_social,
                xml_notas.dest_razao_social                    as dest_razao_social,
                COALESCE(xml_notas.emit_cnpj, xml_notas.dest_cnpj) as participante_cnpj,
                COALESCE(xml_notas.emit_cliente_id, xml_notas.dest_cliente_id) as cliente_id,
                xml_notas.icms_valor                           as icms_valor,
                xml_notas.pis_valor                            as pis_valor,
                xml_notas.cofins_valor                         as cofins_valor,
                xml_notas.ipi_valor                            as ipi_valor,
                xml_notas.tributos_total                       as tributos_total,
                NULL::text                                     as situacao_cadastral,
                xml_notas.validacao::text                      as validacao_json
            ")
            ->where('xml_notas.user_id', $userId)
            ->whereRaw("UPPER(COALESCE(xml_notas.tipo_documento, '')) NOT IN ('NFSE', 'NFS-E')");

        $this->applyCommonFiltersXml($q, $f);

        return $q;
    }

    private function efdSubquery(int $userId, array $f): \Illuminate\Database\Query\Builder
    {
        $q = DB::table('efd_notas')
            ->leftJoin('participantes', 'participantes.id', '=', 'efd_notas.participante_id')
            ->leftJoin('clientes', 'clientes.id', '=', 'efd_notas.cliente_id')
            ->selectRaw("
                'efd'::text                                   as origem,
                efd_notas.id                                   as id,
                efd_notas.chave_acesso                         as chave,
                efd_notas.numero                               as numero,
                efd_notas.serie                                as serie,
                efd_notas.modelo                               as modelo,
                efd_notas.data_emissao::timestamp              as data_emissao,
                efd_notas.valor_total                          as valor_total,
                efd_notas.tipo_operacao                        as tipo_nota,
                CASE WHEN efd_notas.tipo_operacao = 'entrada'
                     THEN participantes.razao_social
                     ELSE clientes.razao_social END            as emit_razao_social,
                CASE WHEN efd_notas.tipo_operacao = 'saida'
                     THEN participantes.razao_social
                     ELSE clientes.razao_social END            as dest_razao_social,
                participantes.documento                        as participante_cnpj,
                efd_notas.cliente_id                           as cliente_id,
                NULL::numeric                                  as icms_valor,
                NULL::numeric                                  as pis_valor,
                NULL::numeric                                  as cofins_valor,
                NULL::numeric                                  as ipi_valor,
                NULL::numeric                                  as tributos_total,
                participantes.situacao_cadastral               as situacao_cadastral,
                NULL::text                                     as validacao_json
            ")
            ->where('efd_notas.user_id', $userId)
            ->whereRaw("UPPER(COALESCE(efd_notas.modelo, '')) NOT IN ('00', 'NFSE', 'NFS-E')");

        $this->applyCommonFiltersEfd($q, $f);

        return $q;
    }

    private function applyCommonFiltersXml(\Illuminate\Database\Query\Builder $q, array $f): void
    {
        if (! empty($f['periodo_de']) && ! empty($f['periodo_ate'])) {
            $q->whereBetween('xml_notas.data_emissao', [$f['periodo_de'].' 00:00:00', $f['periodo_ate'].' 23:59:59']);
        } elseif (! empty($f['periodo_de'])) {
            $q->where('xml_notas.data_emissao', '>=', $f['periodo_de'].' 00:00:00');
        } elseif (! empty($f['periodo_ate'])) {
            $q->where('xml_notas.data_emissao', '<=', $f['periodo_ate'].' 23:59:59');
        }

        if (! empty($f['cliente_id'])) {
            $q->where(function ($sub) use ($f) {
                $sub->where('xml_notas.emit_cliente_id', $f['cliente_id'])
                    ->orWhere('xml_notas.dest_cliente_id', $f['cliente_id']);
            });
        }

        if (! empty($f['participante_cnpj'])) {
            $cnpj = preg_replace('/\D/', '', $f['participante_cnpj']);
            $q->where(function ($sub) use ($cnpj) {
                $sub->where('xml_notas.emit_cnpj', $cnpj)->orWhere('xml_notas.dest_cnpj', $cnpj);
            });
        }

        if (($f['tipo_nota'] ?? null) === 'entrada') {
            $q->where('xml_notas.tipo_nota', XmlNota::TIPO_ENTRADA);
        } elseif (($f['tipo_nota'] ?? null) === 'saida') {
            $q->where('xml_notas.tipo_nota', XmlNota::TIPO_SAIDA);
        }

        if (! empty($f['situacao_receita'])) {
            $q->whereRaw("xml_notas.validacao->>'situacao' = ?", [$f['situacao_receita']]);
        }
    }

    private function applyCommonFiltersEfd(\Illuminate\Database\Query\Builder $q, array $f): void
    {
        if (! empty($f['periodo_de']) && ! empty($f['periodo_ate'])) {
            $q->whereBetween('efd_notas.data_emissao', [$f['periodo_de'], $f['periodo_ate']]);
        } elseif (! empty($f['periodo_de'])) {
            $q->where('efd_notas.data_emissao', '>=', $f['periodo_de']);
        } elseif (! empty($f['periodo_ate'])) {
            $q->where('efd_notas.data_emissao', '<=', $f['periodo_ate']);
        }

        if (! empty($f['cliente_id'])) {
            $q->where('efd_notas.cliente_id', $f['cliente_id']);
        }

        if (! empty($f['participante_cnpj'])) {
            $cnpj = preg_replace('/\D/', '', $f['participante_cnpj']);
            $q->where('participantes.documento', $cnpj);
        }

        if (($f['tipo_nota'] ?? null) === 'entrada') {
            $q->where('efd_notas.tipo_operacao', 'entrada');
        } elseif (($f['tipo_nota'] ?? null) === 'saida') {
            $q->where('efd_notas.tipo_operacao', 'saida');
        }

        if (! empty($f['situacao_receita'])) {
            $q->whereRaw("efd_notas.validacao->>'situacao' = ?", [$f['situacao_receita']]);
        }
    }

    private function modeloBadge(?string $codigo): array
    {
        $codigo = $codigo !== null ? strtoupper(trim($codigo)) : null;

        return match ($codigo) {
            '55' => ['label' => 'NF-e', 'hex' => '#2563eb'],
            '65' => ['label' => 'NFC-e', 'hex' => '#0891b2'],
            '57' => ['label' => 'CT-e', 'hex' => '#7c3aed'],
            '67' => ['label' => 'CT-e OS', 'hex' => '#7c3aed'],
            '00', 'NFSE', 'NFS-E' => ['label' => 'NFS-e', 'hex' => '#047857'],
            '01' => ['label' => 'Modelo 1', 'hex' => '#6b7280'],
            '1B' => ['label' => 'NF Avulsa', 'hex' => '#6b7280'],
            '04' => ['label' => 'NF Produtor', 'hex' => '#6b7280'],
            null, '' => ['label' => 'N/D', 'hex' => '#9ca3af'],
            default => ['label' => $codigo, 'hex' => '#6b7280'],
        };
    }

    private function buildEscopoNotasResumo(int $userId): array
    {
        $totalXml = XmlNota::where('user_id', $userId)->count();
        $totalEfd = EfdNota::where('user_id', $userId)->count();

        return [
            'total_xml' => $totalXml,
            'total_efd' => $totalEfd,
            'total_unificado' => $totalXml + $totalEfd,
            'possui_apenas_efd' => $totalXml === 0 && $totalEfd > 0,
        ];
    }

    /**
     * Calcula o custo de validacao.
     * Aceita nota_ids OU importacao_id.
     */
    public function calcularCusto(Request $request)
    {
        if (! Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $request->validate([
            'nota_ids' => 'array',
            'nota_ids.*' => 'integer',
            'origens' => 'array',
            'importacao_id' => 'integer',
            'tipo' => 'in:basico,full,completa,deep,local',
        ]);

        $userId = Auth::id();
        $tipo = $this->normalizarTier($request->input('tipo'));
        $origens = $request->input('origens', []);

        if ($request->has('nota_ids') && ! empty($request->input('nota_ids'))) {
            $notaIds = $request->input('nota_ids');
        } elseif ($request->has('importacao_id')) {
            $notaIds = XmlNota::where('importacao_xml_id', $request->input('importacao_id'))
                ->where('user_id', $userId)
                ->pluck('id')
                ->toArray();

            if (empty($notaIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nenhuma nota encontrada nesta importacao',
                ]);
            }
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Informe nota_ids ou importacao_id',
            ], 422);
        }

        $custo = $this->validacaoService->calcularCusto($notaIds, $origens, $userId, $tipo);
        $saldoAtual = $this->creditService->getBalance(Auth::user());

        return response()->json([
            'success' => true,
            'custo' => $custo,
            'saldo_atual' => $saldoAtual,
            'saldo_suficiente' => $saldoAtual >= $custo['custo_total'],
        ]);
    }

    /**
     * Executa validacao de notas especificas.
     */
    public function validarNotas(Request $request)
    {
        if (! Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $request->validate([
            'nota_ids' => 'required|array',
            'nota_ids.*' => 'integer',
            'origens' => 'array',
            'tipo' => 'in:basico,full,completa,deep,local',
            'tab_id' => 'nullable|string|max:36',
        ]);

        $userId = Auth::id();
        $notaIds = $request->input('nota_ids');
        $origens = $request->input('origens', []);
        $tipo = $this->normalizarTier($request->input('tipo'));
        $tabId = $request->input('tab_id');
        $user = Auth::user();

        $custo = $this->validacaoService->calcularCusto($notaIds, $origens, $userId, $tipo);

        if ($custo['custo_total'] > 0) {
            if (! $this->creditService->hasEnough($user, $custo['custo_total'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Creditos insuficientes',
                    'custo_necessario' => $custo['custo_total'],
                    'saldo_atual' => $this->creditService->getBalance($user),
                ], 402);
            }

            $this->creditService->deduct($user, $custo['custo_total']);
        }

        $resultado = $this->validacaoService->validarNotas($notaIds, $origens, $userId, $tipo);

        $responseBase = array_merge($resultado, [
            'creditos_utilizados' => $custo['custo_total'],
        ]);

        $webhookUrl = config('services.webhook.consultas_notas_url');

        if (empty($webhookUrl)) {
            Log::warning('Clearance bulk: webhook nao configurado (WEBHOOK_CONSULTAS_NOTAS_URL)');

            if ($custo['custo_total'] > 0) {
                $this->creditService->add(
                    $user,
                    $custo['custo_total'],
                    'clearance_bulk_refund',
                    'Estorno - webhook de clearance nao configurado'
                );
            }

            return response()->json([
                'success' => false,
                'error' => 'Webhook de clearance nao configurado. Creditos estornados.',
                'refund_aplicado' => $custo['custo_total'] > 0,
                'novo_saldo' => $this->creditService->getBalance($user),
            ], 503);
        }

        $notasPayload = $this->montarPayloadNotasClearance($notaIds, $origens, $userId);

        $lote = null;

        try {
            $lote = ConsultaLote::create([
                'user_id' => $userId,
                'cliente_id' => null,
                'plano_id' => null,
                'status' => ConsultaLote::STATUS_PROCESSANDO,
                'total_participantes' => count($notasPayload),
                'creditos_cobrados' => $custo['custo_total'],
                'tab_id' => $tabId,
            ]);

            $totalNfe = collect($notasPayload)
                ->filter(fn (array $nota) => ($nota['tipo_documento'] ?? null) === 'NFE')
                ->count();
            $totalCte = collect($notasPayload)
                ->filter(fn (array $nota) => ($nota['tipo_documento'] ?? null) === 'CTE')
                ->count();

            $payload = [
                'user_id' => $userId,
                'consulta_lote_id' => $lote->id,
                'tab_id' => $tabId,
                'tipo_validacao' => $tipo,
                'total_notas' => count($notasPayload),
                'total_nfe' => $totalNfe,
                'total_cte' => $totalCte,
                'notas' => $notasPayload,
                'progress_url' => url('/api/consultas/progresso'),
            ];

            if (! empty($tabId)) {
                Cache::put(
                    "progresso:{$userId}:{$tabId}",
                    $this->buildClearanceNotasInitialProgressCache(
                        $userId,
                        $tabId,
                        $lote->id,
                        $tipo,
                        $totalNfe,
                        $totalCte
                    ),
                    600
                );
            }

            $response = Http::timeout(15)
                ->withHeaders([
                    'X-API-Token' => config('services.api.token'),
                    'Content-Type' => 'application/json',
                ])
                ->post($webhookUrl, $payload);

            if (! $response->successful()) {
                if ($custo['custo_total'] > 0) {
                    $this->creditService->add(
                        $user,
                        $custo['custo_total'],
                        'clearance_bulk_refund',
                        'Estorno - webhook clearance bulk indisponivel'
                    );
                }

                $lote->update([
                    'status' => ConsultaLote::STATUS_ERRO,
                    'error_code' => 'WEBHOOK_ERROR',
                    'error_message' => 'Webhook n8n respondeu '.$response->status(),
                ]);

                Log::error('Clearance bulk: webhook retornou erro', [
                    'consulta_lote_id' => $lote->id,
                    'response_status' => $response->status(),
                    'response_body' => $response->body(),
                ]);

                return response()->json([
                    'success' => false,
                    'error' => 'Erro ao iniciar clearance externo. Creditos foram estornados.',
                    'refund_aplicado' => true,
                    'novo_saldo' => $this->creditService->getBalance($user),
                ], Response::HTTP_BAD_GATEWAY);
            }

            Log::info('Clearance bulk: despachado para n8n', [
                'consulta_lote_id' => $lote->id,
                'user_id' => $userId,
                'total_notas' => count($notasPayload),
            ]);

            return response()->json(array_merge($responseBase, [
                'webhook_disparado' => true,
                'consulta_lote_id' => $lote->id,
                'tab_id' => $tabId,
                'progress_url' => $tabId ? url('/app/consulta/progresso/stream?tab_id='.$tabId) : null,
                'resultado_url' => route('app.clearance.notas.resultado', [
                    'consultaLoteId' => $lote->id,
                    'tipo_validacao' => $tipo,
                ]),
                'novo_saldo' => $this->creditService->getBalance($user),
            ]));
        } catch (\Throwable $e) {
            if ($lote) {
                $lote->update([
                    'status' => ConsultaLote::STATUS_ERRO,
                    'error_code' => 'INTERNAL_ERROR',
                    'error_message' => $e->getMessage(),
                ]);
            }

            if ($custo['custo_total'] > 0) {
                $this->creditService->add(
                    $user,
                    $custo['custo_total'],
                    'clearance_bulk_refund',
                    'Estorno - excecao ao despachar clearance bulk'
                );
            }

            Log::error('Clearance bulk: excecao ao despachar', [
                'user_id' => $userId,
                'consulta_lote_id' => $lote?->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erro interno ao despachar clearance externo.',
                'refund_aplicado' => true,
                'novo_saldo' => $this->creditService->getBalance($user),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Monta o array de notas do payload enviado ao n8n.
     * Agrupa XmlNota + EfdNota por chave de acesso de 44 digitos.
     */
    private function montarPayloadNotasClearance(array $notaIds, array $origens, int $userId): array
    {
        $xmlIds = [];
        $efdIds = [];
        foreach ($notaIds as $id) {
            $origem = $origens[$id] ?? $origens[(string) $id] ?? 'xml';
            if ($origem === 'efd') {
                $efdIds[] = (int) $id;
            } else {
                $xmlIds[] = (int) $id;
            }
        }

        $payload = [];

        if (! empty($xmlIds)) {
            $xml = XmlNota::whereIn('id', $xmlIds)
                ->where('user_id', $userId)
                ->get(['id', 'nfe_id', 'tipo_documento', 'emit_cliente_id', 'dest_cliente_id']);

            foreach ($xml as $nota) {
                $chave = trim((string) ($nota->nfe_id ?? ''));
                $payload[] = [
                    'id' => $nota->id,
                    'origem' => 'xml',
                    'chave_acesso' => $chave,
                    'tipo_documento' => strtoupper((string) ($nota->tipo_documento ?: 'NFE')),
                    'cliente_id' => $nota->emit_cliente_id ?: $nota->dest_cliente_id,
                ];
            }
        }

        if (! empty($efdIds)) {
            $efd = EfdNota::whereIn('id', $efdIds)
                ->where('user_id', $userId)
                ->get(['id', 'chave_acesso', 'modelo', 'cliente_id']);

            foreach ($efd as $nota) {
                $chave = trim((string) ($nota->chave_acesso ?? ''));
                $modelo = strtoupper((string) $nota->modelo);
                $payload[] = [
                    'id' => $nota->id,
                    'origem' => 'efd',
                    'chave_acesso' => $chave,
                    'tipo_documento' => match ($modelo) {
                        '55' => 'NFE',
                        '57' => 'CTE',
                        '65' => 'NFCE',
                        '00', 'NFSE', 'NFS-E' => 'NFSE',
                        default => 'NFE',
                    },
                    'cliente_id' => $nota->cliente_id,
                ];
            }
        }

        return $payload;
    }

    private function buildClearanceNotasInitialProgressCache(
        int $userId,
        string $tabId,
        int $consultaLoteId,
        string $tipoValidacao,
        int $totalNfe,
        int $totalCte
    ): array {
        $etapasPuladas = [];

        if ($totalNfe <= 0) {
            $etapasPuladas[] = 2;
        }

        if ($totalCte <= 0) {
            $etapasPuladas[] = 3;
        }

        return [
            'user_id' => $userId,
            'tab_id' => $tabId,
            'consulta_lote_id' => $consultaLoteId,
            'fluxo' => 'clearance_notas',
            'tipo_validacao' => $tipoValidacao,
            'total_nfe' => $totalNfe,
            'total_cte' => $totalCte,
            'progresso' => 0,
            'mensagem' => 'Preparando consulta',
            'etapa' => 1,
            'total_etapas' => 4,
            'etapa_label' => 'Preparando consulta',
            'etapas_concluidas' => [],
            'etapas_puladas' => $etapasPuladas,
            'ultima_etapa_concluida' => null,
            'trilha_etapas' => $this->buildClearanceNotasProgressTrail(
                1,
                'Preparando consulta',
                ConsultaLote::STATUS_PROCESSANDO,
                [],
                $etapasPuladas
            ),
            'status' => ConsultaLote::STATUS_PROCESSANDO,
            'updated_at' => now()->toIso8601String(),
        ];
    }

    private function buildClearanceNotasProgressTrail(
        ?int $etapaAtual,
        ?string $etapaLabel,
        string $status,
        array $etapasConcluidas,
        array $etapasPuladas
    ): array {
        $sequence = [
            1 => 'Preparando consulta',
            2 => 'Consultando NF-e na Receita Federal',
            3 => 'Consultando CT-e na Receita Federal',
            0 => 'Preparando resultados',
        ];

        $skipped = array_fill_keys(array_map('intval', $etapasPuladas), true);
        $done = array_fill_keys(array_map('intval', $etapasConcluidas), true);
        $current = $etapaAtual !== null ? (int) $etapaAtual : null;

        $trail = [];

        foreach ([1, 2, 3, 0] as $step) {
            $label = $sequence[$step];
            $label = match (true) {
                $status === ConsultaLote::STATUS_FINALIZADO && $step === 0 => 'Resultados prontos',
                $current === $step && filled($etapaLabel) => (string) $etapaLabel,
                default => $label,
            };

            $stepStatus = match (true) {
                isset($skipped[$step]) => 'skipped',
                $status === ConsultaLote::STATUS_FINALIZADO => 'done',
                $status === ConsultaLote::STATUS_ERRO && $current === $step => 'error',
                $current === $step && $status === ConsultaLote::STATUS_PROCESSANDO => 'current',
                $current === $step && $status === ConsultaLote::STATUS_CONCLUIDO => 'done',
                ($step > 0 && isset($done[$step])) || ($step === 0 && $current === 0 && $status === ConsultaLote::STATUS_CONCLUIDO) => 'done',
                default => 'pending',
            };

            $trail[] = [
                'etapa' => $step,
                'etapa_label' => $label,
                'status' => $stepStatus,
            ];
        }

        return $trail;
    }

    /**
     * Executa validacao de todas as notas de uma importacao.
     */
    public function validarImportacao(Request $request, int $id)
    {
        if (! Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $userId = Auth::id();
        $user = Auth::user();

        // Verificar se a importacao pertence ao usuario
        $importacao = XmlImportacao::where('id', $id)
            ->where('user_id', $userId)
            ->firstOrFail();

        // Obter IDs das notas
        $notaIds = XmlNota::where('importacao_xml_id', $id)
            ->where('user_id', $userId)
            ->pluck('id')
            ->toArray();

        if (empty($notaIds)) {
            return response()->json([
                'success' => false,
                'message' => 'Nenhuma nota encontrada nesta importacao',
            ], 404);
        }

        $request->validate([
            'tipo' => 'in:basico,full,completa,deep,local',
        ]);
        $tipo = $this->normalizarTier($request->input('tipo'));

        // Calcular e cobrar creditos
        $origens = array_fill_keys($notaIds, 'xml');
        $custo = $this->validacaoService->calcularCusto($notaIds, $origens, $userId, $tipo);

        if ($custo['custo_total'] > 0) {
            if (! $this->creditService->hasEnough($user, $custo['custo_total'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Creditos insuficientes',
                    'custo_necessario' => $custo['custo_total'],
                    'saldo_atual' => $this->creditService->getBalance($user),
                ], 402);
            }

            $this->creditService->deduct($user, $custo['custo_total']);
        }

        // Executar validacao
        $resultado = $this->validacaoService->validarImportacao($id, $userId, $tipo);

        return response()->json(array_merge($resultado, [
            'creditos_utilizados' => $custo['custo_total'],
        ]));
    }

    /**
     * Detalhes de validacao de uma nota especifica.
     */
    public function notaDetalhes(Request $request, int $id)
    {
        if (! Auth::check()) {
            return $this->redirectToLogin($request);
        }

        $userId = Auth::id();

        if ($request->input('origem') === 'efd') {
            $efdNota = EfdNota::where('id', $id)
                ->where('user_id', $userId)
                ->firstOrFail();

            return redirect('/app/notas?chave='.$efdNota->chave_acesso);
        }

        $nota = XmlNota::where('id', $id)
            ->where('user_id', $userId)
            ->with(['emitente', 'destinatario', 'importacaoXml'])
            ->firstOrFail();

        $validacao = $nota->validacao;
        if (! $validacao) {
            $validacao = $this->validacaoService->validarNota($nota);
            $validacao['preview'] = true;
        }

        $categorias = $this->validacaoService->getCategorias();

        $data = [
            'nota' => $nota,
            'validacao' => $validacao,
            'categorias' => $categorias,
        ];

        return $this->render($request, 'nota', $data);
    }

    /**
     * Lista de alertas do usuario.
     */
    public function alertas(Request $request)
    {
        if (! Auth::check()) {
            return $this->redirectToLogin($request);
        }

        $userId = Auth::id();

        // Filtros
        $nivel = $request->input('nivel'); // bloqueante, atencao, info
        $categoria = $request->input('categoria');

        $query = XmlNota::where('user_id', $userId)
            ->whereNotNull('validacao')
            ->with(['emitente', 'destinatario']);

        // Filtrar por nivel
        if ($nivel) {
            $query->whereRaw("EXISTS (SELECT 1 FROM jsonb_array_elements(validacao->'alertas') AS a WHERE a->>'nivel' = ?)", [$nivel]);
        }

        // Filtrar por categoria
        if ($categoria) {
            $query->whereRaw("EXISTS (SELECT 1 FROM jsonb_array_elements(validacao->'alertas') AS a WHERE a->>'categoria' = ?)", [$categoria]);
        }

        $notas = $query->orderByDesc('updated_at')->paginate(20);

        // Contar alertas por nivel
        $contadores = XmlNota::where('user_id', $userId)
            ->whereNotNull('validacao')
            ->select(
                \DB::raw("(SELECT COUNT(*) FROM jsonb_array_elements(validacao->'alertas') AS a WHERE a->>'nivel' = 'bloqueante') as bloqueantes"),
                \DB::raw("(SELECT COUNT(*) FROM jsonb_array_elements(validacao->'alertas') AS a WHERE a->>'nivel' = 'atencao') as atencao"),
                \DB::raw("(SELECT COUNT(*) FROM jsonb_array_elements(validacao->'alertas') AS a WHERE a->>'nivel' = 'info') as info")
            )
            ->first();

        $data = [
            'notas' => $notas,
            'contadores' => [
                'bloqueante' => (int) ($contadores->bloqueantes ?? 0),
                'atencao' => (int) ($contadores->atencao ?? 0),
                'info' => (int) ($contadores->info ?? 0),
            ],
            'filtroNivel' => $nivel,
            'filtroCategoria' => $categoria,
            'categorias' => $this->validacaoService->getCategorias(),
        ];

        return $this->render($request, 'alertas', $data);
    }

    /**
     * Dashboard resumido (AJAX).
     */
    public function dashboard(Request $request)
    {
        if (! Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $userId = Auth::id();

        $estatisticas = $this->validacaoService->getEstatisticas($userId);

        // Distribuicao por classificacao
        $distribuicao = [
            ['classificacao' => 'Conforme', 'quantidade' => $estatisticas['conforme'], 'cor' => '#22c55e'],
            ['classificacao' => 'Atencao', 'quantidade' => $estatisticas['atencao'], 'cor' => '#eab308'],
            ['classificacao' => 'Irregular', 'quantidade' => $estatisticas['irregular'], 'cor' => '#f97316'],
            ['classificacao' => 'Critico', 'quantidade' => $estatisticas['critico'], 'cor' => '#ef4444'],
        ];

        // Ultimas notas validadas
        $ultimasValidadas = XmlNota::where('user_id', $userId)
            ->whereNotNull('validacao')
            ->with(['emitente:id,cnpj,razao_social'])
            ->orderByDesc('updated_at')
            ->limit(5)
            ->get()
            ->map(fn ($nota) => [
                'id' => $nota->id,
                'numero' => $nota->numero_nota,
                'emitente' => $nota->emitente->razao_social ?? $nota->emit_cnpj,
                'valor' => $nota->valor_formatado,
                'score' => $nota->validacao_score,
                'classificacao' => $nota->validacao_classificacao,
            ]);

        return response()->json([
            'estatisticas' => $estatisticas,
            'distribuicao' => $distribuicao,
            'ultimas_validadas' => $ultimasValidadas,
        ]);
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

    private function getClearanceProgressSnapshot(ConsultaLote $lote): ?array
    {
        if (empty($lote->tab_id)) {
            return null;
        }

        $cached = Cache::get("progresso:{$lote->user_id}:{$lote->tab_id}");

        if (! is_array($cached)) {
            return null;
        }

        $cacheStatus = $cached['status'] ?? null;
        $loteStatus = ConsultaLote::normalizeStatus($lote->status);
        $loteAberto = in_array($loteStatus, [ConsultaLote::STATUS_PROCESSANDO, ConsultaLote::STATUS_PENDENTE], true);
        $cacheIntermediario = in_array($cacheStatus, [
            ConsultaLote::STATUS_PROCESSANDO,
            ConsultaLote::STATUS_PENDENTE,
            ConsultaLote::STATUS_CONCLUIDO,
        ], true);
        $cacheTerminalCompativel = $cacheStatus === ConsultaLote::STATUS_ERRO
            ? $loteStatus === ConsultaLote::STATUS_ERRO
            : $cacheStatus === ConsultaLote::STATUS_FINALIZADO && $lote->isFinalizado();

        if (! (($cacheIntermediario && $loteAberto) || $cacheTerminalCompativel)) {
            return null;
        }

        return [
            'status' => $cached['status'] ?? $loteStatus,
            'progresso' => (int) ($cached['progresso'] ?? 0),
            'mensagem' => $cached['mensagem'] ?? null,
            'etapa' => $cached['etapa'] ?? null,
            'total_etapas' => $cached['total_etapas'] ?? null,
            'etapa_label' => $cached['etapa_label'] ?? null,
            'etapas_puladas' => $cached['etapas_puladas'] ?? [],
            'trilha_etapas' => $cached['trilha_etapas'] ?? null,
            'ultima_etapa_concluida' => $cached['ultima_etapa_concluida'] ?? null,
            'consulta_lote_id' => $cached['consulta_lote_id'] ?? $lote->id,
            'updated_at' => $cached['updated_at'] ?? null,
        ];
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

    private function listarUltimasConsultasDfe(int $userId, int $limite = 10)
    {
        return $this->consultaDfeHistoricoQuery($userId)
            ->where('fluxo_origem', 'avulsa')
            ->orderByRaw('COALESCE(consultado_em, created_at) DESC')
            ->orderByDesc('consulta_id')
            ->limit($limite)
            ->get()
            ->map(function ($consulta) {
                $consulta->momento_consulta = $this->formatarDataConsulta($consulta->consultado_em ?: $consulta->created_at);

                return $consulta;
            });
    }

    private function consultaDfeHistoricoQuery(int $userId): Builder
    {
        return $this->notaFiscalService->consultaDfeHistoricoQuery($userId);
    }

    private function buscarConsultaDfePorLote(int $userId, int $consultaLoteId): ?object
    {
        return $this->consultaDfeHistoricoQuery($userId)
            ->where('consulta_lote_id', $consultaLoteId)
            ->orderByRaw('COALESCE(consultado_em, created_at) DESC')
            ->orderByDesc('id')
            ->first();
    }

    private function filtrosHistoricoConsultasDfe(Request $request): array
    {
        $tipoDocumento = strtolower((string) $request->input('tipo_documento', ''));
        $status = strtoupper(trim((string) $request->input('status', '')));
        $origemFluxo = strtolower((string) $request->input('origem_fluxo', ''));

        return [
            'busca' => trim((string) $request->input('busca', '')),
            'tipo_documento' => in_array($tipoDocumento, ['nfe', 'nfce', 'cte'], true) ? $tipoDocumento : '',
            'status' => $status,
            'origem_fluxo' => in_array($origemFluxo, ['avulsa', 'lote'], true) ? $origemFluxo : '',
        ];
    }

    private function aplicarFiltrosHistoricoConsultasDfe(Builder $query, array $filtros): void
    {
        if (($filtros['busca'] ?? '') !== '') {
            $busca = '%'.$filtros['busca'].'%';
            $query->where(function ($sub) use ($busca) {
                $sub->where('chave_acesso', 'ILIKE', $busca)
                    ->orWhere('numero', 'ILIKE', $busca)
                    ->orWhere('cliente_nome', 'ILIKE', $busca)
                    ->orWhere('emit_nome', 'ILIKE', $busca)
                    ->orWhere('emit_cnpj', 'ILIKE', $busca)
                    ->orWhere('dest_nome', 'ILIKE', $busca)
                    ->orWhere('dest_cnpj', 'ILIKE', $busca)
                    ->orWhere('tomador_nome', 'ILIKE', $busca)
                    ->orWhere('tomador_cnpj', 'ILIKE', $busca);
            });
        }

        if (($filtros['tipo_documento'] ?? '') !== '') {
            $query->where('tipo_documento', strtoupper($filtros['tipo_documento']));
        }

        if (($filtros['status'] ?? '') !== '') {
            $query->whereRaw('UPPER(status) = ?', [$filtros['status']]);
        }

        if (($filtros['origem_fluxo'] ?? '') !== '') {
            $query->where('fluxo_origem', $filtros['origem_fluxo']);
        }
    }

    private function statusOptionsHistoricoDfe(int $userId): Collection
    {
        return $this->consultaDfeHistoricoQuery($userId)
            ->selectRaw('UPPER(status) as status')
            ->whereNotNull('status')
            ->distinct()
            ->orderBy('status')
            ->pluck('status');
    }

    private function listarConsultasDfePorLote(int $userId, int $consultaLoteId): Collection
    {
        $nfe = DB::table('nfe_consultas as consulta')
            ->leftJoin('clientes as cliente', 'cliente.id', '=', 'consulta.cliente_id')
            ->selectRaw("
                consulta.id,
                consulta.consulta_lote_id,
                consulta.chave_acesso,
                UPPER(COALESCE(consulta.tipo_documento, 'NFE')) as tipo_documento,
                COALESCE(consulta.modelo, '55') as modelo,
                consulta.numero,
                consulta.serie,
                consulta.status,
                consulta.valor_total,
                consulta.data_emissao,
                consulta.emit_nome,
                consulta.emit_cnpj,
                consulta.dest_nome,
                consulta.dest_cnpj,
                NULL::varchar as tomador_nome,
                NULL::varchar as tomador_cnpj,
                cliente.razao_social as cliente_nome,
                consulta.consultado_em,
                consulta.created_at
            ")
            ->where('consulta.user_id', $userId)
            ->where('consulta.consulta_lote_id', $consultaLoteId);

        $cte = DB::table('cte_consultas as consulta')
            ->leftJoin('clientes as cliente', 'cliente.id', '=', 'consulta.cliente_id')
            ->selectRaw("
                consulta.id,
                consulta.consulta_lote_id,
                consulta.chave_acesso,
                UPPER(COALESCE(consulta.tipo_documento, 'CTE')) as tipo_documento,
                COALESCE(consulta.modelo, '57') as modelo,
                consulta.numero,
                consulta.serie,
                consulta.status,
                consulta.valor_prestacao as valor_total,
                consulta.data_emissao,
                consulta.emit_nome,
                consulta.emit_cnpj,
                consulta.dest_nome,
                consulta.dest_cnpj,
                consulta.tomador_nome,
                consulta.tomador_cnpj,
                cliente.razao_social as cliente_nome,
                consulta.consultado_em,
                consulta.created_at
            ")
            ->where('consulta.user_id', $userId)
            ->where('consulta.consulta_lote_id', $consultaLoteId);

        $resultados = DB::query()
            ->fromSub($nfe->unionAll($cte), 'consultas')
            ->orderByRaw('COALESCE(consultado_em, created_at) DESC')
            ->orderByDesc('id')
            ->get();

        $chaves = $resultados->pluck('chave_acesso')->filter()->unique()->values();
        $xmlByChave = XmlNota::query()
            ->where('user_id', $userId)
            ->whereIn('nfe_id', $chaves)
            ->pluck('id', 'nfe_id')
            ->all();
        $efdByChave = EfdNota::query()
            ->where('user_id', $userId)
            ->whereIn('chave_acesso', $chaves)
            ->pluck('id', 'chave_acesso')
            ->all();

        $resultadosPersistidos = $resultados->map(function ($resultado) use ($xmlByChave, $efdByChave) {
            $status = strtoupper((string) ($resultado->status ?? 'INDETERMINADO'));
            $resultado->status_label = $status;
            $resultado->status_hex = $this->statusHexConsultaDfe($status);
            $resultado->valor_total_label = $resultado->valor_total !== null
                ? 'R$ '.number_format((float) $resultado->valor_total, 2, ',', '.')
                : '—';
            $resultado->data_emissao_label = $this->formatarDataCurta($resultado->data_emissao);
            $resultado->consultado_em_label = $this->formatarDataConsulta($resultado->consultado_em ?: $resultado->created_at);
            $resultado->participante_label = $resultado->dest_nome
                ?: $resultado->tomador_nome
                ?: $resultado->dest_cnpj
                ?: $resultado->tomador_cnpj
                ?: 'Não informado';
            $chave = trim((string) $resultado->chave_acesso);
            $resultado->detalhe_url = match (true) {
                $chave !== '' && isset($xmlByChave[$chave]) => route('app.notas-fiscais.detalhes', ['origem' => 'xml', 'id' => $xmlByChave[$chave]]),
                $chave !== '' && isset($efdByChave[$chave]) => route('app.notas-fiscais.detalhes', ['origem' => 'efd', 'id' => $efdByChave[$chave]]),
                default => null,
            };
            $resultado->origem_acervo_label = null;
            $resultado->origem_acervo_hex = null;
            $resultado->ordem_lote = null;

            return $resultado;
        });

        $precheck = $this->getBuscaAcervoPrecheck($userId, $consultaLoteId);
        $resultadosAcervo = collect($precheck['resultados'] ?? [])
            ->map(fn ($resultado) => (object) $resultado);
        $ordemPorChave = collect($precheck['ordem_por_chave'] ?? []);

        return $resultadosPersistidos
            ->concat($resultadosAcervo)
            ->sortBy(function ($resultado) use ($ordemPorChave) {
                $chave = trim((string) ($resultado->chave_acesso ?? ''));

                return $ordemPorChave->get($chave, PHP_INT_MAX);
            })
            ->values();
    }

    private function buscarNotaAcervoPorChave(int $userId, string $chaveAcesso): ?XmlNota
    {
        return XmlNota::query()
            ->where('user_id', $userId)
            ->where('nfe_id', $chaveAcesso)
            ->first();
    }

    private function formatarResultadoConsultaDfe(object $nota, int $userId): array
    {
        return [
            'id' => $nota->id,
            'consulta_lote_id' => $nota->consulta_lote_id,
            'tipo_documento' => strtoupper((string) ($nota->tipo_documento ?? 'NFE')),
            'modelo' => $nota->modelo ?? null,
            'nfe_id' => $nota->chave_acesso,
            'numero_nota' => $nota->numero,
            'numero' => $nota->numero,
            'serie' => $nota->serie,
            'valor_total' => $nota->valor_total,
            'valor_total_label' => $nota->valor_total !== null
                ? 'R$ '.number_format((float) $nota->valor_total, 2, ',', '.')
                : '—',
            'data_emissao' => $this->formatarDataCurta($nota->data_emissao),
            'emit' => $nota->emit_nome ?: $nota->emit_cnpj,
            'emit_cnpj' => $nota->emit_cnpj ?? null,
            'dest' => $nota->dest_nome ?: $nota->tomador_nome ?: $nota->dest_cnpj ?: $nota->tomador_cnpj,
            'dest_cnpj' => $nota->dest_cnpj ?? null,
            'tomador_nome' => $nota->tomador_nome ?? null,
            'tomador_cnpj' => $nota->tomador_cnpj ?? null,
            'cliente_nome' => $nota->cliente_nome,
            'situacao' => strtoupper((string) ($nota->status ?? 'INDETERMINADO')),
            'situacao_hex' => $this->statusHexConsultaDfe($nota->status ?? null),
            'consultado_em' => $this->formatarDataConsulta($nota->consultado_em),
            'detalhe_url' => $this->resolverDetalheNotaUrl($userId, $nota->chave_acesso),
        ];
    }

    private function formatarResultadoXmlAcervo(XmlNota $nota): array
    {
        $situacao = strtoupper((string) data_get($nota->validacao, 'situacao', 'SALVA_NO_ACERVO'));
        $chave = (string) $nota->nfe_id;
        $modeloDerivado = strlen($chave) === 44 ? substr($chave, 20, 2) : null;

        return [
            'id' => $nota->id,
            'consulta_lote_id' => null,
            'tipo_documento' => strtoupper((string) ($nota->tipo_documento ?: 'NFE')),
            'modelo' => $modeloDerivado,
            'nfe_id' => $nota->nfe_id,
            'numero_nota' => $nota->numero_nota,
            'numero' => $nota->numero_nota,
            'serie' => $nota->serie,
            'valor_total' => $nota->valor_total,
            'valor_total_label' => $nota->valor_total !== null
                ? 'R$ '.number_format((float) $nota->valor_total, 2, ',', '.')
                : '—',
            'data_emissao' => optional($nota->data_emissao)->format('d/m/Y H:i'),
            'emit' => $nota->emit_razao_social ?: $nota->emit_cnpj,
            'emit_cnpj' => $nota->emit_cnpj,
            'dest' => $nota->dest_razao_social ?: $nota->dest_cnpj,
            'dest_cnpj' => $nota->dest_cnpj,
            'tomador_nome' => null,
            'tomador_cnpj' => null,
            'cliente_nome' => $nota->cliente?->razao_social,
            'situacao' => $situacao,
            'situacao_hex' => $this->statusHexConsultaDfe($situacao),
            'consultado_em' => $this->formatarDataConsulta($nota->updated_at ?: $nota->created_at),
            'detalhe_url' => route('app.notas-fiscais.detalhes', ['origem' => 'xml', 'id' => $nota->id]),
        ];
    }

    private function resolverDetalheNotaUrl(int $userId, ?string $chaveAcesso): ?string
    {
        $chave = trim((string) $chaveAcesso);

        if ($chave === '') {
            return null;
        }

        $xmlNotaId = XmlNota::query()
            ->where('user_id', $userId)
            ->where('nfe_id', $chave)
            ->value('id');

        if ($xmlNotaId) {
            return route('app.notas-fiscais.detalhes', ['origem' => 'xml', 'id' => $xmlNotaId]);
        }

        $efdNotaId = EfdNota::query()
            ->where('user_id', $userId)
            ->where('chave_acesso', $chave)
            ->value('id');

        if ($efdNotaId) {
            return route('app.notas-fiscais.detalhes', ['origem' => 'efd', 'id' => $efdNotaId]);
        }

        return null;
    }

    private function resumirResultadosClearance(Collection $resultados): array
    {
        return [
            'total' => $resultados->count(),
            'ja_no_acervo' => $resultados->filter(fn ($item) => ($item->status_label ?? '') === 'JA_NO_ACERVO')->count(),
            'autorizadas' => $resultados->filter(fn ($item) => in_array($item->status_label ?? '', ['AUTORIZADA', 'NEGATIVA'], true))->count(),
            'alertas' => $resultados->filter(fn ($item) => in_array($item->status_label ?? '', ['CANCELADA', 'DENEGADA', 'INUTILIZADA'], true))->count(),
            'indeterminadas' => $resultados->filter(fn ($item) => in_array($item->status_label ?? '', ['INDETERMINADO', 'NAO_ENCONTRADA'], true))->count(),
            'erros' => $resultados->filter(function ($item) {
                $status = strtoupper((string) ($item->status_label ?? ''));

                return str_starts_with($status, 'ERRO');
            })->count(),
        ];
    }

    private function statusMetaLote(?string $status): array
    {
        return match (ConsultaLote::normalizeStatus($status)) {
            ConsultaLote::STATUS_PROCESSANDO => ['label' => 'Processando', 'hex' => '#d97706'],
            ConsultaLote::STATUS_FINALIZADO => ['label' => 'Finalizado', 'hex' => '#047857'],
            ConsultaLote::STATUS_ERRO => ['label' => 'Erro', 'hex' => '#dc2626'],
            default => ['label' => 'Pendente', 'hex' => '#9ca3af'],
        };
    }

    private function statusHexConsultaDfe(?string $status): string
    {
        return match (strtoupper((string) $status)) {
            'JA_NO_ACERVO' => '#4338ca',
            'AUTORIZADA', 'NEGATIVA' => '#047857',
            'CANCELADA', 'DENEGADA', 'INUTILIZADA' => '#dc2626',
            'INDETERMINADO', 'NAO_ENCONTRADA' => '#d97706',
            'ERRO_PARAMETRO', 'ERRO_PROVEDOR' => '#6b7280',
            default => '#374151',
        };
    }

    private function formatarDataCurta($valor): ?string
    {
        if (empty($valor)) {
            return null;
        }

        if ($this->isInvalidDatePlaceholder($valor)) {
            return null;
        }

        try {
            return Carbon::parse($valor)->format('d/m/Y H:i');
        } catch (\Throwable) {
            return is_string($valor) && ! $this->isInvalidDatePlaceholder($valor) ? $valor : null;
        }
    }

    private function formatarDataConsulta($valor): ?string
    {
        if (empty($valor)) {
            return null;
        }

        if ($this->isInvalidDatePlaceholder($valor)) {
            return null;
        }

        try {
            return Carbon::parse($valor)->format('d/m/Y H:i');
        } catch (\Throwable) {
            return is_string($valor) && ! $this->isInvalidDatePlaceholder($valor) ? $valor : null;
        }
    }

    private function isInvalidDatePlaceholder($valor): bool
    {
        if (! is_string($valor)) {
            return false;
        }

        return in_array(strtolower(trim($valor)), [
            'invalid datetime',
            'invalid date',
            'invalid date time',
            'nan',
            'null',
            'undefined',
        ], true);
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
