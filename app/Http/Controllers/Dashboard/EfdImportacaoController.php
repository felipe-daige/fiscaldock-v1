<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Concerns\RespondeAjax;
use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\EfdImportacao;
use App\Models\EfdNota;
use App\Models\Participante;
use App\Models\XmlImportacao;
use App\Services\CreditService;
use App\Services\EfdProgressoBuilder;
use App\Services\Entitlements\EntitlementService;
use App\Services\SpedDetectorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class EfdImportacaoController extends Controller
{
    use RespondeAjax;

    private const AUTH_VIEW_PREFIX = 'autenticado.monitoramento.';

    private const AUTH_LAYOUT_VIEW = 'autenticado.layouts.app';

    public function __construct(
        protected CreditService $creditService,
        protected SpedDetectorService $spedDetector,
        protected \App\Services\Efd\EfdImportacaoDuplicidadeService $duplicidade,
        protected \App\Services\Efd\ExcluirImportacaoService $excluir,
        protected EntitlementService $entitlements = new EntitlementService,
        protected \App\Services\Efd\EfdPlanilhaExportService $planilhaExport = new \App\Services\Efd\EfdPlanilhaExportService,
    ) {}

    /**
     * Lista relatórios RAF para importação de participantes.
     */
    public function index(Request $request)
    {
        $efdView = self::AUTH_VIEW_PREFIX.'efd';

        if (! view()->exists($efdView)) {
            abort(404);
        }

        if (! Auth::check()) {
            return $this->redirectToLogin($request);
        }

        $user = Auth::user();
        $userId = (int) $user->id;

        $importacoes = EfdImportacao::where('user_id', $userId)
            ->with('cliente')
            ->orderBy('created_at', 'desc')
            ->paginate(5)
            ->withQueryString();

        $data = [
            'credits' => $this->creditService->getBalance($user),
            'importacoes' => $importacoes,
            'totalParticipantes' => Participante::where('user_id', $userId)->count(),
            'totalNotas' => EfdNota::where('user_id', $userId)->count(),
        ];

        if ($this->isAjaxRequest($request)) {
            $renderedView = view($efdView, $data)->render();

            return response($renderedView)->header('Content-Type', 'text/html');
        }

        return view(self::AUTH_LAYOUT_VIEW, array_merge([
            'initialView' => $efdView,
        ], $data));
    }

    /**
     * Detalhes de uma importação EFD específica.
     */
    public function show(Request $request, $id)
    {
        $view = 'autenticado.importacao.efd-detalhes';

        if (! Auth::check()) {
            return $this->redirectToLogin($request);
        }

        $user = Auth::user();
        $userId = (int) $user->id;

        $importacao = EfdImportacao::where('id', $id)
            ->where('user_id', $userId)
            ->with(['cliente', 'apuracaoContribuicao', 'apuracaoIcms', 'retencoesFonte'])
            ->firstOrFail();

        // Pagination limits
        $allowedPerPages = [10, 25, 50, 100];
        $perPageParticipantes = in_array((int) $request->input('per_page_participantes'), $allowedPerPages) ? (int) $request->input('per_page_participantes') : 10;
        $perPageCatalogo = in_array((int) $request->input('per_page_catalogo'), $allowedPerPages) ? (int) $request->input('per_page_catalogo') : 10;

        // Dual-path: participante_ids (n8n v2) ou importacao_efd_id (legado)
        $notaCountSubquery = EfdNota::selectRaw('count(*)')
            ->whereColumn('participante_id', 'participantes.id')
            ->where('importacao_id', $importacao->id);

        if (! empty($importacao->participante_ids)) {
            $participantes = Participante::whereIn('id', $importacao->participante_ids)
                ->where('user_id', $userId)
                ->orderByDesc($notaCountSubquery)
                ->orderBy('razao_social')
                ->paginate($perPageParticipantes, ['*'], 'page')
                ->withQueryString();
        } else {
            $participantes = Participante::where('importacao_efd_id', $id)
                ->where('user_id', $userId)
                ->orderByDesc($notaCountSubquery)
                ->orderBy('razao_social')
                ->paginate($perPageParticipantes, ['*'], 'page')
                ->withQueryString();
        }

        // Catálogo de itens (0200) — paginação separada para não conflitar com participantes
        $catalogoItens = $importacao->extrair_catalogo
            ? \App\Models\EfdCatalogoItem::where('importacao_id', $importacao->id)
                ->where('user_id', $userId)
                ->orderBy('cod_item')
                ->paginate($perPageCatalogo, ['*'], 'catalogo_page')
                ->withQueryString()
            : collect();

        $data = compact('importacao', 'participantes', 'catalogoItens');
        $data['resumoFinal'] = $importacao->resumo_final;
        $data['apuracaoContribuicao'] = $importacao->apuracaoContribuicao;
        $data['apuracaoIcms'] = $importacao->apuracaoIcms;
        $data['retencoesFonte'] = $importacao->retencoesFonte ?? collect();

        if ($this->isAjaxRequest($request)) {
            return response(view($view, $data)->render())->header('Content-Type', 'text/html');
        }

        return view(self::AUTH_LAYOUT_VIEW, array_merge(['initialView' => $view], $data));
    }

    /**
     * Exporta tudo que foi extraído da importação como ZIP de CSVs (planilha).
     */
    public function exportar(Request $request, $id)
    {
        if (! Auth::check()) {
            return $this->redirectToLogin($request);
        }

        $userId = (int) Auth::id();

        $importacao = EfdImportacao::where('id', $id)
            ->where('user_id', $userId)
            ->firstOrFail();

        $dataset = (string) $request->query('dataset', '');

        // CSV único de um dataset específico (whitelist server-side — nunca confiar no frontend).
        if ($dataset !== '' && $dataset !== 'tudo') {
            abort_unless(array_key_exists($dataset, \App\Services\Efd\EfdPlanilhaExportService::DATASETS), 404);

            $csv = $this->planilhaExport->csvDataset($importacao, $userId, $dataset);
            $nome = $this->planilhaExport->nomeCsv($importacao, $dataset);

            return \App\Support\CsvExport::stream($nome, $csv);
        }

        // Tudo: ZIP de CSVs.
        $zipPath = $this->planilhaExport->zipPath($importacao, $userId);
        $nome = $this->planilhaExport->nomeZip($importacao);

        return response()->streamDownload(function () use ($zipPath) {
            readfile($zipPath);
            @unlink($zipPath);
        }, $nome, ['Content-Type' => 'application/zip']);
    }

    /**
     * Retorna notas EFD por array de IDs (usado para expansão inline na tabela de participantes).
     *
     * GET /app/efd/notas?ids[]=1&ids[]=2&...
     */
    public function notasPorIds(Request $request): JsonResponse
    {
        if (! Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'ids' => 'required|array|max:500',
            'ids.*' => 'integer',
        ]);

        $userImportacaoIds = \App\Models\EfdImportacao::where('user_id', Auth::id())
            ->pluck('id');

        $notas = \App\Models\EfdNota::whereIn('id', $validated['ids'])
            ->whereIn('importacao_id', $userImportacaoIds)
            ->select(['id', 'numero', 'serie', 'modelo', 'data_emissao', 'tipo_operacao', 'valor_total', 'chave_acesso', 'participante_id'])
            ->get();

        return response()->json($notas);
    }

    /**
     * Retorna todas as notas EFD de um participante em uma importação específica.
     *
     * GET /app/importacao/efd/notas-participante?participante_id=X&importacao_id=Y
     */
    public function notasPorParticipante(Request $request): JsonResponse
    {
        if (! Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'participante_id' => 'required|integer',
            'importacao_id' => 'required|integer',
        ]);

        $importacao = \App\Models\EfdImportacao::where('id', $validated['importacao_id'])
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $notas = \App\Models\EfdNota::where('participante_id', $validated['participante_id'])
            ->where('importacao_id', $importacao->id)
            ->select(['id', 'numero', 'serie', 'modelo', 'data_emissao', 'tipo_operacao', 'valor_total', 'chave_acesso', 'participante_id'])
            ->orderBy('data_emissao')
            ->get();

        return response()->json($notas);
    }

    /**
     * Histórico unificado de importações (EFD + XML).
     */
    public function historico(Request $request)
    {
        $view = 'autenticado.importacao.historico';

        if (! Auth::check()) {
            return $this->redirectToLogin($request);
        }

        $user = Auth::user();
        $userId = (int) $user->id;

        $efdImportacoes = EfdImportacao::where('user_id', $userId)
            ->with('cliente')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function (EfdImportacao $importacao) {
                return array_merge($importacao->toArray(), [
                    '_tipo' => 'efd',
                    'volume_label' => $importacao->total_processados.' participante(s)',
                ]);
            });

        $xmlRows = XmlImportacao::where('user_id', $userId)
            ->with('cliente')
            ->orderBy('created_at', 'desc')
            ->get();

        $nullClienteIds = $xmlRows->filter(fn ($i) => $i->cliente_id === null)->pluck('id');
        $countMap = \App\Models\XmlNota::whereIn('importacao_xml_id', $nullClienteIds)
            ->whereNotNull('cliente_id')
            ->groupBy('importacao_xml_id')
            ->selectRaw('importacao_xml_id, COUNT(DISTINCT cliente_id) as cnt')
            ->pluck('cnt', 'importacao_xml_id');

        $xmlImportacoes = $xmlRows->map(function (XmlImportacao $importacao) use ($countMap) {
            $totalXmls = (int) ($importacao->total_xmls ?? 0);

            return array_merge($importacao->toArray(), [
                '_tipo' => 'xml',
                'volume_label' => $totalXmls.' XML'.($totalXmls !== 1 ? 's' : ''),
                'clientes_resolvidos' => $importacao->cliente_id ? 1 : (int) ($countMap[$importacao->id] ?? 0),
            ]);
        });

        $importacoes = $efdImportacoes->concat($xmlImportacoes)
            ->sortByDesc('created_at')
            ->values();

        $data = ['importacoes' => $importacoes];

        if ($this->isAjaxRequest($request)) {
            return response(view($view, $data)->render())->header('Content-Type', 'text/html');
        }

        return view(self::AUTH_LAYOUT_VIEW, array_merge(['initialView' => $view], $data));
    }

    /**
     * Recebe arquivo .txt e envia para n8n processar.
     * Laravel não valida/extrai CNPJs - apenas repassa o arquivo em base64.
     */
    public function upload(Request $request)
    {
        if (! Auth::check()) {
            return response()->json([
                'success' => false,
                'error' => 'Usuário não autenticado.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $request->validate([
            'arquivo' => 'required|file|max:10240', // Máximo 10MB
            'tipo_efd' => 'required|in:EFD ICMS/IPI,EFD PIS/COFINS',
            'cliente_id' => 'nullable|integer',
            'tab_id' => 'nullable|string|max:36',
            'substituir' => 'nullable|boolean',
        ]);

        $user = Auth::user();
        $arquivo = $request->file('arquivo');
        $tipoEfd = $request->tipo_efd;

        $conteudoArquivo = file_get_contents($arquivo->path());
        $arquivoHash = hash('sha256', $conteudoArquivo);
        $cabecalho = $this->spedDetector->extrairCabecalho($conteudoArquivo);

        if (! $cabecalho['valido']) {
            return response()->json([
                'success' => false,
                'error' => 'Arquivo invalido: nao parece ser um SPED. '.implode(' ', $cabecalho['erros']),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($cabecalho['tipo'] !== null && $cabecalho['tipo'] !== $tipoEfd) {
            Log::info('tipo_efd corrigido pelo detector', [
                'user_id' => $user->id,
                'escolhido' => $tipoEfd,
                'detectado' => $cabecalho['tipo'],
                'arquivo' => $arquivo->getClientOriginalName(),
            ]);
            $tipoEfd = $cabecalho['tipo'];
        }

        // Selecionar webhook baseado no tipo de EFD (extração completa sempre)
        $webhookUrl = $tipoEfd === 'EFD ICMS/IPI'
            ? config('services.webhook.importacao_efd_fiscal_url')
            : config('services.webhook.importacao_efd_contribuicoes_url');

        // Validar que o cliente pertence ao usuário (se fornecido)
        $clienteId = $request->input('cliente_id');
        if ($clienteId) {
            $cliente = Cliente::where('id', $clienteId)
                ->where('user_id', $user->id)
                ->first();
            if (! $cliente) {
                return response()->json([
                    'success' => false,
                    'error' => 'Cliente não encontrado ou não pertence ao usuário.',
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
        }

        // CNPJ do arquivo × documento do cliente (só bloqueia com contradição real).
        if (isset($cliente) && $cliente && $cabecalho['cnpj']) {
            $cnpjCliente = preg_replace('/\D/', '', (string) $cliente->documento);
            if ($cnpjCliente !== '' && $cnpjCliente !== $cabecalho['cnpj']) {
                return response()->json([
                    'success' => false,
                    'error' => "Este arquivo é da empresa {$cabecalho['cnpj']}, mas o cliente selecionado tem documento {$cnpjCliente}.",
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
        }

        // Guard de duplicidade: impede reimportar o mesmo documento por engano.
        $conflito = $this->duplicidade->verificar(
            $user->id,
            $clienteId,
            $tipoEfd,
            $cabecalho['periodo_inicio'],
            $cabecalho['periodo_fim'],
            $arquivoHash,
        );

        $substituir = $request->boolean('substituir');

        if ($conflito['caso'] !== null && ! $substituir) {
            $imp = $conflito['importacao'];

            return response()->json([
                'success' => false,
                'caso' => $conflito['caso'],
                'retificadora' => (bool) $cabecalho['retificadora'],
                'importacao' => [
                    'id' => $imp->id,
                    'tipo_efd' => $imp->tipo_efd,
                    'periodo_inicio' => optional($imp->periodo_inicio)->format('Y-m-d'),
                    'periodo_fim' => optional($imp->periodo_fim)->format('Y-m-d'),
                    'criada_em' => optional($imp->created_at)->format('d/m/Y H:i'),
                ],
                'error' => $conflito['caso'] === 'identico'
                    ? 'Este arquivo já foi importado.'
                    : 'Já existe uma importação deste período.',
            ], Response::HTTP_CONFLICT);
        }

        // Substituir: apaga a importação anterior (resolvida server-side, nunca por ID do front).
        // O FK cascadeOnDelete limpa notas/itens/apuração/retenções da importação removida.
        if ($substituir && $conflito['caso'] !== null) {
            $conflito['importacao']->delete();
        }

        if (empty($webhookUrl)) {
            $configKey = $tipoEfd === 'EFD ICMS/IPI'
                ? 'WEBHOOK_IMPORTACAO_EFD_FISCAL_URL'
                : 'WEBHOOK_IMPORTACAO_EFD_CONTRIBUICOES_URL';
            Log::error("Webhook URL para importação .txt não configurada ({$configKey})", [
                'tipo_efd' => $tipoEfd,
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Serviço de importação não configurado. Verifique as variáveis de ambiente.',
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        // EFD sem cliente vinculado: o CNPJ do arquivo É a empresa-sujeito. Resolvido
        // APÓS o dedup (que escopa pelo cliente_id do request) pra não mudar o contrato
        // de duplicidade. Vincula ao cliente existente ou registra um novo (cap-checked)
        // — modelo "acesso = CNPJs distintos": CNPJ novo = +1 cliente. Nunca confia no front.
        if (! $clienteId && $cabecalho['cnpj']) {
            $existente = Cliente::where('user_id', $user->id)
                ->where('documento', $cabecalho['cnpj'])
                ->first();

            if ($existente) {
                $clienteId = $existente->id;
            } elseif (! $this->entitlements->podeAdicionarCliente($user)) {
                $limite = $this->entitlements->limiteClientes($user);

                return response()->json([
                    'success' => false,
                    'error' => "Seu plano permite até {$limite} cliente(s) além da sua empresa. Este SPED é de um CNPJ ainda não cadastrado — faça upgrade para importá-lo.",
                ], Response::HTTP_FORBIDDEN);
            } else {
                $clienteId = Cliente::create([
                    'user_id' => $user->id,
                    'tipo_pessoa' => 'PJ',
                    'documento' => $cabecalho['cnpj'],
                    'is_empresa_propria' => false,
                    'ativo' => true,
                ])->id;
            }
        }

        // Criar registro de importação antes de enviar ao n8n
        $importacao = EfdImportacao::create([
            'user_id' => $user->id,
            'cliente_id' => $clienteId,
            'tipo_efd' => $tipoEfd,
            'cnpj' => $cabecalho['cnpj'],
            'periodo_inicio' => $cabecalho['periodo_inicio'],
            'periodo_fim' => $cabecalho['periodo_fim'],
            'arquivo_hash' => $arquivoHash,
            'filename' => $arquivo->getClientOriginalName(),
            'status' => 'processando',
            'iniciado_em' => now(),
        ]);

        try {
            // Enviar arquivo para n8n via multipart
            $response = Http::timeout(30)
                ->attach('file', $conteudoArquivo, $arquivo->getClientOriginalName())
                ->post($webhookUrl, [
                    'user_id' => $user->id,
                    'importacao_id' => $importacao->id,
                    'cliente_id' => $clienteId,
                    'tipo_efd' => $tipoEfd,
                    'filename' => $arquivo->getClientOriginalName(),
                    'progress_url' => url('/api/importacao/efd/progresso'),
                    'tab_id' => $request->input('tab_id'),
                ]);

            if (! $response->successful()) {
                Log::error('Erro ao enviar arquivo para n8n', [
                    'user_id' => $user->id,
                    'importacao_id' => $importacao->id,
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);

                $importacao->update(['status' => 'erro']);

                return response()->json([
                    'success' => false,
                    'error' => $response->json('error') ?? 'Erro ao processar arquivo.',
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            Log::info('Arquivo .txt enviado para n8n', [
                'user_id' => $user->id,
                'filename' => $arquivo->getClientOriginalName(),
                'importacao_id' => $importacao->id,
                'tab_id' => $request->input('tab_id'),
            ]);

            return response()->json([
                'success' => true,
                'importacao_id' => $importacao->id,
            ]);
        } catch (\Exception $e) {
            $importacao->update(['status' => 'erro']);

            Log::error('Exceção ao enviar arquivo para n8n', [
                'user_id' => $user->id,
                'importacao_id' => $importacao->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erro de conexão com o serviço de importação.',
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        }
    }

    /**
     * SSE para acompanhar progresso da importação.
     * Lê dados do cache (enviados pelo n8n via API) - não consulta banco.
     */
    public function streamImportacao($id)
    {
        if (! Auth::check()) {
            return response()->json([
                'success' => false,
                'error' => 'Usuário não autenticado.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        return response()->stream(function () use ($id) {
            $cacheKey = "importacao_progresso_{$id}";
            $tentativas = 0;
            $maxTentativas = 300; // 5 minutos (300 segundos)

            while ($tentativas < $maxTentativas) {
                // Lê dados do cache (n8n envia via API)
                $dados = Cache::get($cacheKey);

                if (! $dados) {
                    // Ainda não começou ou não existe
                    echo 'data: '.json_encode(['status' => 'aguardando'])."\n\n";
                } else {
                    echo 'data: '.json_encode($dados)."\n\n";

                    if (in_array($dados['status'] ?? '', ['concluido', 'erro'])) {
                        Cache::forget($cacheKey); // Limpa cache
                        break;
                    }
                }

                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();

                sleep(1);
                $tentativas++;
            }

            // Se chegou no limite, encerra
            if ($tentativas >= $maxTentativas) {
                echo 'data: '.json_encode(['status' => 'timeout', 'error' => 'Tempo limite atingido'])."\n\n";
                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
            'Connection' => 'keep-alive',
        ]);
    }

    /**
     * SSE para acompanhar progresso de processamento SPED em tempo real.
     * Lê dados do cache (enviados pelo n8n via API) isolados por user_id + tab_id.
     *
     * GET /app/importacao/efd/progresso/stream?tab_id=xxx
     *
     * Este endpoint é usado pelo frontend para acompanhar o progresso da
     * identificação de participantes em arquivos SPED. O n8n envia atualizações
     * de progresso para /api/importacao/efd/importacao-txt/progresso com
     * user_id, tab_id, progresso (0-100), mensagem e status.
     */
    public function streamProgresso(Request $request)
    {
        if (! Auth::check()) {
            return response()->json([
                'success' => false,
                'error' => 'Usuário não autenticado.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $userId = (int) auth()->id();
        $importacaoId = (int) $request->query('importacao_id');

        if ($importacaoId <= 0) {
            return response()->json([
                'success' => false,
                'error' => 'importacao_id obrigatório.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $importacao = EfdImportacao::where('id', $importacaoId)
            ->where('user_id', $userId)
            ->first();

        if (! $importacao) {
            return response()->json([
                'success' => false,
                'error' => 'Importação não encontrada.',
            ], Response::HTTP_NOT_FOUND);
        }

        Log::info('SSE streamProgresso iniciado', [
            'user_id' => $userId,
            'importacao_id' => $importacaoId,
        ]);

        return response()->stream(function () use ($importacaoId, $userId) {
            $builder = app(EfdProgressoBuilder::class);
            $tentativas = 0;
            $maxTentativas = 900; // 30 min (900 × 2s)
            $lastDataHash = null;

            echo ": SSE connection established (user:{$userId}, importacao:{$importacaoId})\n";
            echo "retry: 3000\n\n";
            if (ob_get_level() > 0) {
                ob_flush();
            }
            flush();

            while ($tentativas < $maxTentativas) {
                try {
                    $imp = EfdImportacao::find($importacaoId);
                    if (! $imp) {
                        echo 'data: '.json_encode([
                            'status' => 'erro',
                            'progresso' => 0,
                            'mensagem' => 'Importação removida.',
                        ])."\n\n";
                        if (ob_get_level() > 0) {
                            ob_flush();
                        }
                        flush();
                        break;
                    }

                    $payload = $builder->build($imp);
                    $hash = md5(json_encode(array_diff_key($payload, ['mensagem' => true])));

                    if ($hash !== $lastDataHash) {
                        $lastDataHash = $hash;
                        echo 'data: '.json_encode($payload)."\n\n";
                        if (ob_get_level() > 0) {
                            ob_flush();
                        }
                        flush();
                    }

                    if (in_array($payload['status'], ['concluido', 'erro'], true)) {
                        Log::info('SSE streamProgresso: status final', [
                            'user_id' => $userId,
                            'importacao_id' => $importacaoId,
                            'status' => $payload['status'],
                        ]);
                        break;
                    }

                    if (connection_aborted()) {
                        Log::info('SSE streamProgresso: conexão abortada pelo cliente', [
                            'user_id' => $userId,
                            'importacao_id' => $importacaoId,
                        ]);
                        break;
                    }

                    sleep(2);
                    $tentativas++;

                    if ($tentativas % 4 === 0) {
                        echo ": ping\n\n";
                        if (ob_get_level() > 0) {
                            ob_flush();
                        }
                        flush();
                    }
                } catch (\Exception $e) {
                    Log::error('SSE streamProgresso: erro no loop', [
                        'user_id' => $userId,
                        'importacao_id' => $importacaoId,
                        'error' => $e->getMessage(),
                    ]);
                    sleep(2);
                    $tentativas++;
                    if (connection_aborted()) {
                        break;
                    }
                }
            }

            // Se chegou no limite, encerrar
            if ($tentativas >= $maxTentativas) {
                echo 'data: '.json_encode([
                    'status' => 'timeout',
                    'progresso' => 0,
                    'mensagem' => 'Tempo limite atingido. Tente novamente.',
                ])."\n\n";
                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
                Log::warning('SSE streamProgresso: timeout', [
                    'user_id' => $userId,
                    'importacao_id' => $importacaoId,
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
     * Exibe o detalhe de uma nota EFD.
     *
     * GET /app/importacao/efd/nota/{id}
     */
    public function notaDetalhes(Request $request, int $id)
    {
        if (! Auth::check()) {
            return $this->redirectToLogin($request);
        }

        $nota = EfdNota::with(['participante', 'itens'])
            ->whereHas('importacao', fn ($q) => $q->where('user_id', Auth::id()))
            ->findOrFail($id);

        $view = 'autenticado.importacao.efd-nota';

        if ($this->isAjaxRequest($request)) {
            return response(view($view, compact('nota'))->render())->header('Content-Type', 'text/html');
        }

        return view(self::AUTH_LAYOUT_VIEW, array_merge(['initialView' => $view], compact('nota')));
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
    public function previewExclusao(Request $request, $id): JsonResponse
    {
        $imp = $this->importacaoDoDono($id);

        return response()->json($this->excluir->preview($imp));
    }

    public function destroy(Request $request, $id): JsonResponse
    {
        $imp = $this->importacaoDoDono($id);

        if (in_array($imp->status, ['processando', 'pendente'], true)) {
            return response()->json([
                'success' => false,
                'error' => 'Importação em processamento não pode ser excluída. Aguarde a conclusão.',
            ], 409);
        }

        $excluirParticipantes = $request->boolean('excluir_participantes');
        $resultado = $this->excluir->execute($imp, $excluirParticipantes);

        Log::info('Importação EFD excluída', [
            'user_id' => (int) Auth::id(),
            'importacao_id' => (int) $id,
            'excluir_participantes' => $excluirParticipantes,
            'resultado' => $resultado,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Importação excluída com sucesso.',
            'resultado' => $resultado,
        ]);
    }

    /**
     * Carrega a importação garantindo posse pelo usuário autenticado (404 caso contrário).
     */
    private function importacaoDoDono($id): EfdImportacao
    {
        return EfdImportacao::where('id', $id)
            ->where('user_id', (int) Auth::id())
            ->firstOrFail();
    }
}
