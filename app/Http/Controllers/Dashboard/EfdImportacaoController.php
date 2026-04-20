<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\EfdImportacao;
use App\Models\EfdNota;

use App\Models\Participante;
use App\Services\CreditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class EfdImportacaoController extends Controller
{
    private const AUTH_VIEW_PREFIX = 'autenticado.monitoramento.';

    private const AUTH_LAYOUT_VIEW = 'autenticado.layouts.app';

    public function __construct(
        protected CreditService $creditService,
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
            ->map(fn ($i) => array_merge($i->toArray(), ['_tipo' => 'efd']));

        $xmlImportacoes = \App\Models\XmlImportacao::where('user_id', $userId)
            ->with('cliente')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($i) => array_merge($i->toArray(), ['_tipo' => 'xml']));

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
        ]);

        $user = Auth::user();
        $arquivo = $request->file('arquivo');
        $tipoEfd = $request->tipo_efd;

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

        // Criar registro de importação antes de enviar ao n8n
        $importacao = EfdImportacao::create([
            'user_id' => $user->id,
            'cliente_id' => $clienteId,
            'tipo_efd' => $tipoEfd,
            'arquivo' => $arquivo->getClientOriginalName(),
            'status' => 'processando',
            'iniciado_em' => now(),
        ]);

        try {
            // Enviar arquivo para n8n via multipart
            $response = Http::timeout(30)
                ->attach('file', file_get_contents($arquivo->path()), $arquivo->getClientOriginalName())
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

        $userId = auth()->id();
        $tabId = $request->query('tab_id');

        if (! $tabId) {
            return response()->json([
                'success' => false,
                'error' => 'tab_id obrigatório.',
            ], Response::HTTP_BAD_REQUEST);
        }

        // Chave do cache: progresso:{user_id}:{tab_id}
        $cacheKey = "progresso:{$userId}:{$tabId}";

        Log::info('SSE streamProgresso iniciado', [
            'user_id' => $userId,
            'tab_id' => $tabId,
            'cache_key' => $cacheKey,
        ]);

        return response()->stream(function () use ($cacheKey, $userId, $tabId) {
            $tentativas = 0;
            $maxTentativas = 3600; // 30 minutos (3600 × 0.5s)
            $lastDataHash = null; // Para evitar enviar dados repetidos
            $ciclosAguardandoResumo = 0; // Grace period aguardando resumo_final da fase 2

            // Enviar comentário inicial + retry hint para o browser
            echo ": SSE connection established for progress stream (user:{$userId}, tab:{$tabId})\n";
            echo "retry: 3000\n\n";
            if (ob_get_level() > 0) {
                ob_flush();
            }
            flush();

            while ($tentativas < $maxTentativas) {
                try {
                    // Lê dados do cache (n8n envia via API)
                    $data = Cache::get($cacheKey);

                    if ($data) {
                        // Lê progresso dos blocos de notas e adiciona ao payload.
                        // Começa com notas_blocos do cache principal (inclui 'participantes'
                        // e status enviados pelo payload final do n8n), depois sobrescreve
                        // com entradas individuais por bloco (atualizações intermediárias).
                        $notasBlocos = $data['notas_blocos'] ?? [];
                        foreach (['participantes', 'notas_servicos', 'notas_mercadorias', 'notas_transportes', 'catalogo', 'apuracao_icms', 'retencoes_fonte', 'apuracao_pis_cofins'] as $bloco) {
                            $blocoKey = "efd_notas_progress:{$userId}:{$tabId}:{$bloco}";
                            $blocoData = Cache::get($blocoKey);
                            if ($blocoData !== null) {
                                $notasBlocos[$bloco] = $blocoData;
                            }
                        }
                        if (! empty($notasBlocos)) {
                            $data['notas_blocos'] = $notasBlocos;
                        }

                        // Calcular hash para detectar mudanças (exclui updated_at para não
                        // reenviar dados idênticos quando n8n escreve o mesmo progresso várias vezes)
                        $hashData = array_diff_key($data, ['updated_at' => true]);
                        $currentHash = md5(json_encode($hashData));

                        // Só enviar se os dados mudaram
                        if ($currentHash !== $lastDataHash) {
                            $lastDataHash = $currentHash;

                            // Enviar dados de progresso
                            echo 'data: '.json_encode($data)."\n\n";

                            if (ob_get_level() > 0) {
                                ob_flush();
                            }
                            flush();

                            // Se status é final, encerrar a conexão
                            if (($data['status'] ?? '') === 'erro') {
                                Log::info('SSE streamProgresso: status final recebido', [
                                    'user_id' => $userId,
                                    'tab_id' => $tabId,
                                    'status' => $data['status'],
                                ]);
                                Cache::forget($cacheKey);
                                break;
                            }
                            if (($data['status'] ?? '') === 'concluido') {
                                // Sempre aguardar resumo_final antes de encerrar (n8n sempre envia
                                // o aggregation step, mesmo quando feature de notas está desabilitada).
                                $aguardandoResumo = empty($data['resumo_final']);
                                if (! $aguardandoResumo) {
                                    Log::info('SSE streamProgresso: status final recebido', [
                                        'user_id' => $userId,
                                        'tab_id' => $tabId,
                                        'status' => $data['status'],
                                    ]);
                                    Cache::forget($cacheKey);
                                    break;
                                }
                                // Fase 1 concluída, aguardando resumo_final da fase 2
                                $ciclosAguardandoResumo++;
                                if ($ciclosAguardandoResumo > 60) { // 30s de grace (60 × 0.5s)
                                    Log::warning('SSE streamProgresso: timeout aguardando resumo_final', [
                                        'user_id' => $userId,
                                        'tab_id' => $tabId,
                                    ]);
                                    Cache::forget($cacheKey);
                                    break;
                                }
                            }
                        }
                    }

                    // Verificar se a conexão ainda está ativa
                    if (connection_aborted()) {
                        Log::info('SSE streamProgresso: conexão abortada pelo cliente', [
                            'user_id' => $userId,
                            'tab_id' => $tabId,
                        ]);
                        break;
                    }

                    usleep(500000); // 0.5s por ciclo
                    $tentativas++;

                    // Heartbeat a cada 15 ciclos (~7.5s) para manter proxy/LB vivos
                    if ($tentativas % 15 === 0) {
                        echo ": ping\n\n";
                        if (ob_get_level() > 0) {
                            ob_flush();
                        }
                        flush();
                    }

                } catch (\Exception $e) {
                    Log::error('SSE streamProgresso: erro no loop', [
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
