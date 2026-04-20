<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Participante;
use App\Models\XmlImportacao;
use App\Models\MonitoramentoPlano;
use App\Models\XmlNota;
use App\Services\CreditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\JsonResponse;
use ZipArchive;

class XmlImportacaoController extends Controller
{
    private const AUTH_VIEW_PREFIX = 'autenticado.importacao.';
    private const AUTH_LAYOUT_VIEW = 'autenticado.layouts.app';

    public function __construct(
        protected CreditService $creditService
    ) {}

    /**
     * Página de importação de XMLs (placeholder - em desenvolvimento).
     */
    public function index(Request $request)
    {
        $placeholderView = self::AUTH_VIEW_PREFIX . 'xml-placeholder';

        if (!Auth::check()) {
            return $this->redirectToLogin($request);
        }

        if ($this->isAjaxRequest($request)) {
            $renderedView = view($placeholderView)->render();
            return response($renderedView)->header('Content-Type', 'text/html');
        }

        return view(self::AUTH_LAYOUT_VIEW, [
            'initialView' => $placeholderView,
        ]);
    }

    /**
     * Detalhes de uma importação XML específica.
     */
    public function show(Request $request, $id)
    {
        $view = self::AUTH_VIEW_PREFIX . 'xml-detalhes';

        if (!Auth::check()) {
            return $this->redirectToLogin($request);
        }

        $user = Auth::user();
        $userId = (int) $user->id;

        $importacao = XmlImportacao::where('id', $id)
            ->where('user_id', $userId)
            ->with('cliente')
            ->firstOrFail();

        // Pagination limits
        $allowedPerPages = [10, 25, 50, 100];
        $perPageParticipantes = in_array((int) $request->input('per_page_participantes'), $allowedPerPages) ? (int) $request->input('per_page_participantes') : 10;

        // Dual-path: participante_ids (n8n v2) ou importacao_xml_id (legado)
        if (!empty($importacao->participante_ids)) {
            $participantes = Participante::whereIn('id', $importacao->participante_ids)
                ->where('user_id', $userId)
                ->orderBy('razao_social')
                ->paginate($perPageParticipantes, ['*'], 'page')
                ->withQueryString();
        } else {
            $participantes = Participante::where('importacao_xml_id', $id)
                ->where('user_id', $userId)
                ->orderBy('razao_social')
                ->paginate($perPageParticipantes, ['*'], 'page')
                ->withQueryString();
        }

        $data = compact('importacao', 'participantes');

        if ($this->isAjaxRequest($request)) {
            return response(view($view, $data)->render())->header('Content-Type', 'text/html');
        }

        return view(self::AUTH_LAYOUT_VIEW, array_merge(['initialView' => $view], $data));
    }

    /**
     * Página de importação de XMLs (versão funcional - dev only).
     */
    public function indexDev(Request $request)
    {
        $xmlView = self::AUTH_VIEW_PREFIX . 'xml';

        if (!view()->exists($xmlView)) {
            abort(404);
        }

        if (!Auth::check()) {
            return $this->redirectToLogin($request);
        }

        $user = Auth::user();

        // Buscar clientes do usuário para o select
        $clientes = Cliente::where('user_id', $user->id)
            ->orderBy('razao_social')
            ->get();

        // Últimas importações do usuário
        $importacoes = XmlImportacao::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $data = [
            'clientes' => $clientes,
            'importacoes' => $importacoes,
            'credits' => $this->creditService->getBalance($user),
            'planos' => MonitoramentoPlano::ativos(),
        ];

        if ($this->isAjaxRequest($request)) {
            $renderedView = view($xmlView, $data)->render();
            return response($renderedView)->header('Content-Type', 'text/html');
        }

        return view(self::AUTH_LAYOUT_VIEW, array_merge([
            'initialView' => $xmlView
        ], $data));
    }

    /**
     * Inicia importação de XMLs enviando para n8n (sempre como ZIP).
     *
     * Se o modo de envio for 'xml' (arquivos avulsos), comprime em ZIP antes de enviar.
     * Isso simplifica o workflow do n8n que sempre recebe um único ZIP.
     */
    public function importar(Request $request)
    {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'error' => 'Usuário não autenticado.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $user = Auth::user();

        $validated = $request->validate([
            'tipo_documento' => 'required|in:NFE,NFSE,CTE',
            'modo_envio' => 'required|in:zip,xml',
            'cliente_id' => 'nullable|integer|exists:clientes,id',
            'salvar_movimentacoes' => 'nullable|boolean',
            'tab_id' => 'required|string|max:36',
            'arquivos' => 'required|array|min:1|max:100',
            'arquivos.*.nome' => 'required|string|max:255',
            'arquivos.*.tipo' => 'required|string|max:100',
            'arquivos.*.conteudo_base64' => 'required|string',
        ]);

        // Calcular tamanho total
        $tamanhoTotal = 0;
        $totalArquivos = count($validated['arquivos']);

        foreach ($validated['arquivos'] as $arquivo) {
            $tamanhoTotal += strlen(base64_decode($arquivo['conteudo_base64']));
        }

        // Limite de 200MB total
        if ($tamanhoTotal > 200 * 1024 * 1024) {
            return response()->json([
                'success' => false,
                'error' => 'Tamanho total dos arquivos excede o limite de 200MB.',
            ], Response::HTTP_BAD_REQUEST);
        }

        // Verificar webhook configurado
        $webhookUrl = config('services.webhook.importacao_xml_url');

        if (empty($webhookUrl)) {
            Log::error('XmlImportacao: webhook não configurado');
            return response()->json([
                'success' => false,
                'error' => 'Configuração de webhook ausente. Contate o suporte.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        try {
            // Criar registro de importação
            $importacao = XmlImportacao::create([
                'user_id' => $user->id,
                'cliente_id' => $validated['cliente_id'] ?? null,
                'tipo_documento' => $validated['tipo_documento'],
                'modo_envio' => $validated['modo_envio'],
                'total_arquivos' => $totalArquivos,
                'tamanho_total_bytes' => $tamanhoTotal,
                'status' => 'pendente',
                'iniciado_em' => now(),
            ]);

            Log::info('XmlImportacao: registro criado', [
                'importacao_id' => $importacao->id,
                'user_id' => $user->id,
                'tipo_documento' => $validated['tipo_documento'],
                'modo_envio' => $validated['modo_envio'],
                'total_arquivos' => $totalArquivos,
                'tamanho_bytes' => $tamanhoTotal,
            ]);

            // Preparar ZIP para n8n (sempre enviamos ZIP como arquivo)
            $tempZipPath = null;

            try {
                if ($validated['modo_envio'] === 'zip') {
                    // Já é ZIP - salvar em arquivo temporário e contar XMLs
                    $arquivo = $validated['arquivos'][0];
                    $tempZipPath = tempnam(sys_get_temp_dir(), 'xml_import_') . '.zip';
                    file_put_contents($tempZipPath, base64_decode($arquivo['conteudo_base64']));
                    $totalXmls = $this->contarXmlsNoZip($tempZipPath);
                } else {
                    // XMLs avulsos - comprimir em ZIP
                    $resultado = $this->comprimirXmlsEmZip($validated['arquivos']);
                    $tempZipPath = $resultado['path'];
                    $totalXmls = $resultado['total'];
                }

                $zipFileName = "importacao_{$importacao->id}.zip";
                $zipFileSize = filesize($tempZipPath);

                Log::info('XmlImportacao: enviando para n8n', [
                    'importacao_id' => $importacao->id,
                    'modo_envio_original' => $validated['modo_envio'],
                    'total_xmls' => $totalXmls,
                    'zip_size_bytes' => $zipFileSize,
                ]);

                // Enviar para n8n como multipart/form-data
                $response = Http::timeout(120)
                    ->withHeaders([
                        'X-API-Token' => config('services.api.token'),
                    ])
                    ->attach('file', file_get_contents($tempZipPath), $zipFileName)
                    ->post($webhookUrl, [
                        'user_id' => $user->id,
                        'importacao_id' => $importacao->id,
                        'tab_id' => $validated['tab_id'],
                        'tipo_documento' => $validated['tipo_documento'],
                        'modo_envio' => $validated['modo_envio'],
                        'cliente_id' => $validated['cliente_id'] ?? null,
                        'cliente_cnpj' => $this->getClienteCnpj($validated['cliente_id'] ?? null),
                        'salvar_movimentacoes' => $validated['salvar_movimentacoes'] ?? false,
                        'total_xmls' => $totalXmls,
                        'progress_url' => url('/api/importacao/xml/progress'),
                    ]);

                if ($response->successful()) {
                    $importacao->update(['status' => 'processando']);

                    Log::info('XmlImportacao: enviado para n8n com sucesso', [
                        'importacao_id' => $importacao->id,
                        'response_status' => $response->status(),
                    ]);

                    return response()->json([
                        'success' => true,
                        'importacao_id' => $importacao->id,
                        'message' => 'Importação iniciada com sucesso.',
                    ]);
                } else {
                    $importacao->update([
                        'status' => 'erro',
                        'erro_mensagem' => 'Erro ao enviar para processamento: ' . $response->status(),
                    ]);

                    Log::error('XmlImportacao: erro na resposta do n8n', [
                        'importacao_id' => $importacao->id,
                        'response_status' => $response->status(),
                        'response_body' => $response->body(),
                    ]);

                    return response()->json([
                        'success' => false,
                        'error' => 'Erro ao iniciar processamento. Tente novamente.',
                    ], Response::HTTP_BAD_GATEWAY);
                }
            } finally {
                // Limpar arquivo temporário
                if ($tempZipPath && file_exists($tempZipPath)) {
                    @unlink($tempZipPath);
                }
            }

        } catch (\Exception $e) {
            Log::error('XmlImportacao: exceção ao enviar', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            if (isset($importacao)) {
                $importacao->update([
                    'status' => 'erro',
                    'erro_mensagem' => 'Erro interno: ' . $e->getMessage(),
                ]);
            }

            return response()->json([
                'success' => false,
                'error' => 'Erro interno ao processar importação.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Comprime XMLs avulsos em um único arquivo ZIP temporário.
     *
     * IMPORTANTE: O chamador é responsável por deletar o arquivo temporário.
     *
     * @param array $arquivos Array de arquivos com nome e conteudo_base64
     * @return array ['path' => string, 'total' => int]
     */
    private function comprimirXmlsEmZip(array $arquivos): array
    {
        $tempZip = tempnam(sys_get_temp_dir(), 'xml_import_') . '.zip';

        $zip = new ZipArchive();
        if ($zip->open($tempZip, ZipArchive::CREATE) !== true) {
            throw new \RuntimeException('Não foi possível criar arquivo ZIP temporário');
        }

        foreach ($arquivos as $arquivo) {
            $nome = $this->sanitizarNomeArquivo($arquivo['nome']);
            $conteudo = base64_decode($arquivo['conteudo_base64']);
            $zip->addFromString($nome, $conteudo);
        }

        $zip->close();

        return [
            'path' => $tempZip,
            'total' => count($arquivos),
        ];
    }

    /**
     * Conta XMLs dentro de um arquivo ZIP.
     *
     * Exclui arquivos na pasta __MACOSX (resource forks do Mac).
     * Usa fallback com comando unzip se ZipArchive falhar.
     * Retorna -1 se não conseguir contar (n8n fará a contagem).
     *
     * @param string $zipPath Caminho para o arquivo ZIP
     * @return int Quantidade de XMLs encontrados, ou -1 se indisponível
     */
    private function contarXmlsNoZip(string $zipPath): int
    {
        $zip = new ZipArchive();
        $result = $zip->open($zipPath);

        if ($result !== true) {
            // Tentar fallback com unzip
            $fallback = $this->validarZipComUnzip($zipPath);
            if ($fallback['success']) {
                return $fallback['total_xmls'] ?? 0;
            }

            // Verificar magic bytes como último recurso
            $content = @file_get_contents($zipPath, false, null, 0, 4);
            if ($content && $this->isValidZipMagicBytes($content)) {
                Log::info('contarXmlsNoZip: ZIP aceito via magic bytes, contagem será feita pelo n8n', [
                    'zipPath' => $zipPath,
                ]);
                return -1; // -1 indica que n8n fará a contagem
            }

            return 0;
        }

        $count = 0;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if ($name &&
                str_ends_with(strtolower($name), '.xml') &&
                !str_starts_with($name, '__MACOSX/')) {
                $count++;
            }
        }

        $zip->close();
        return $count;
    }

    /**
     * Sanitiza nome do arquivo para segurança.
     */
    private function sanitizarNomeArquivo(string $nome): string
    {
        $extensao = pathinfo($nome, PATHINFO_EXTENSION);
        $nomeBase = pathinfo($nome, PATHINFO_FILENAME);
        $nomeBaseSanitizado = preg_replace('/[^a-zA-Z0-9_-]/', '_', $nomeBase);

        if (!in_array(strtolower($extensao), ['xml', 'zip'])) {
            $extensao = 'xml';
        }

        return $nomeBaseSanitizado . '.' . strtolower($extensao);
    }

    /**
     * SSE para acompanhar progresso de importação XML.
     */
    public function streamProgresso(Request $request)
    {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'error' => 'Usuário não autenticado.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $userId = auth()->id();
        $tabId = $request->query('tab_id');

        if (!$tabId) {
            return response()->json([
                'success' => false,
                'error' => 'tab_id obrigatório.',
            ], Response::HTTP_BAD_REQUEST);
        }

        // Usa a mesma chave de cache do progresso SPED
        $cacheKey = "progresso:{$userId}:{$tabId}";

        Log::info('SSE XML streamProgresso iniciado', [
            'user_id' => $userId,
            'tab_id' => $tabId,
            'cache_key' => $cacheKey,
        ]);

        return response()->stream(function () use ($cacheKey, $userId, $tabId) {
            $tentativas = 0;
            $maxTentativas = 600; // 10 minutos (XMLs podem demorar mais)
            $lastDataHash = null;

            // Enviar comentário inicial
            echo ": SSE connection established for XML progress stream (user:{$userId}, tab:{$tabId})\n\n";
            if (ob_get_level() > 0) {
                ob_flush();
            }
            flush();

            while ($tentativas < $maxTentativas) {
                try {
                    // Lê dados do cache (n8n envia via API)
                    $data = Cache::get($cacheKey);

                    if ($data) {
                        // Calcular hash para detectar mudanças
                        $currentHash = md5(json_encode($data));

                        // Só enviar se os dados mudaram
                        if ($currentHash !== $lastDataHash) {
                            $lastDataHash = $currentHash;

                            // Enviar dados de progresso
                            echo "data: " . json_encode($data) . "\n\n";

                            if (ob_get_level() > 0) {
                                ob_flush();
                            }
                            flush();

                            // Se status é final, encerrar a conexão
                            if (in_array($data['status'] ?? '', ['concluido', 'erro'])) {
                                Log::info('SSE XML streamProgresso: status final recebido', [
                                    'user_id' => $userId,
                                    'tab_id' => $tabId,
                                    'status' => $data['status'],
                                ]);
                                // Limpar cache após status final
                                Cache::forget($cacheKey);
                                break;
                            }
                        }
                    }

                    // Verificar se a conexão ainda está ativa
                    if (connection_aborted()) {
                        Log::info('SSE XML streamProgresso: conexão abortada pelo cliente', [
                            'user_id' => $userId,
                            'tab_id' => $tabId,
                        ]);
                        break;
                    }

                    sleep(1);
                    $tentativas++;

                } catch (\Exception $e) {
                    Log::error('SSE XML streamProgresso: erro no loop', [
                        'user_id' => $userId,
                        'tab_id' => $tabId,
                        'error' => $e->getMessage(),
                    ]);
                    sleep(1);
                    $tentativas++;
                    if (connection_aborted()) {
                        break;
                    }
                }
            }

            // Se chegou no limite, encerrar
            if ($tentativas >= $maxTentativas) {
                echo "data: " . json_encode([
                    'status' => 'timeout',
                    'progresso' => 0,
                    'mensagem' => 'Tempo limite atingido. Tente novamente.',
                ]) . "\n\n";
                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
                Log::warning('SSE XML streamProgresso: timeout', [
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
     * Valida arquivo antes de importar (conta XMLs em ZIPs, detecta tipo).
     */
    public function validar(Request $request): JsonResponse
    {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'error' => 'Usuário não autenticado.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $validated = $request->validate([
            'arquivo' => 'required|array',
            'arquivo.nome' => 'required|string|max:255',
            'arquivo.conteudo_base64' => 'required|string',
        ]);

        $fileName = $validated['arquivo']['nome'];
        $base64Content = $validated['arquivo']['conteudo_base64'];

        // Check base64 size before decoding (avoid memory issues)
        $estimatedSize = (int) (strlen($base64Content) * 0.75);
        if ($estimatedSize > 50 * 1024 * 1024) {
            return response()->json([
                'success' => false,
                'error' => 'Arquivo excede 50MB.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $content = base64_decode($base64Content, true);
        if ($content === false) {
            return response()->json([
                'success' => false,
                'error' => 'Conteúdo base64 inválido.',
            ], Response::HTTP_BAD_REQUEST);
        }

        if (str_ends_with(strtolower($fileName), '.zip')) {
            return $this->validarZip($content, $fileName);
        } else {
            return $this->validarXml($content, $fileName);
        }
    }

    /**
     * Valida arquivo ZIP e conta XMLs dentro.
     *
     * Usa ZipArchive do PHP como método primário. Se falhar (comum com ZIPs
     * criados no Mac via Finder), tenta fallback com comando `unzip` do sistema.
     */
    private function validarZip(string $content, string $fileName): JsonResponse
    {
        // Detectar Apple Finder Bookmark (arquivo arrastado da Lixeira ou alias)
        if ($this->isAppleBookmark($content)) {
            Log::warning('Arquivo é Apple Finder Bookmark, não ZIP real', [
                'arquivo' => $fileName,
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Este arquivo é uma referência, não o ZIP real.',
                'hint' => 'Tire o arquivo da Lixeira antes de enviar, ou use "Comprimir" novamente.',
            ]);
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'xml_validate_');
        if (!$tempFile) {
            return response()->json([
                'success' => false,
                'error' => 'Erro ao criar arquivo temporário.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        try {
            file_put_contents($tempFile, $content);

            $zip = new ZipArchive();
            $result = $zip->open($tempFile);

            if ($result !== true) {
                // Logar erro real para diagnóstico
                Log::warning('ZipArchive falhou ao abrir arquivo', [
                    'arquivo' => $fileName,
                    'erro_codigo' => $result,
                    'erro_mensagem' => $this->getZipArchiveErrorMessage($result),
                ]);

                // Tentar fallback com unzip do sistema (suporta mais formatos)
                $fallback = $this->validarZipComUnzip($tempFile);

                if ($fallback['success']) {
                    Log::info('Fallback unzip funcionou', [
                        'arquivo' => $fileName,
                        'total_xmls' => $fallback['total_xmls'],
                    ]);

                    return response()->json([
                        'success' => true,
                        'tipo' => 'zip',
                        'total_xmls' => $fallback['total_xmls'],
                        'mensagem' => $fallback['total_xmls'] === 0 ? 'Nenhum XML encontrado no ZIP' : null,
                    ]);
                }

                // Terceiro fallback: verificar magic bytes
                // ZIPs do Mac (Archive Utility) às vezes usam formatos que as ferramentas
                // não conseguem abrir para listar, mas o n8n (via Node.js) consegue extrair
                if ($this->isValidZipMagicBytes($content)) {
                    Log::info('Fallback magic bytes: ZIP aceito para processamento', [
                        'arquivo' => $fileName,
                        'ziparchive_erro' => $this->getZipArchiveErrorMessage($result),
                        'unzip_erro' => $fallback['error'] ?? 'desconhecido',
                    ]);

                    return response()->json([
                        'success' => true,
                        'tipo' => 'zip',
                        'total_xmls' => -1, // -1 indica que a contagem será feita pelo n8n
                        'validacao_relaxada' => true,
                        'mensagem' => 'ZIP aceito. A contagem será feita durante o processamento.',
                    ]);
                }

                // Todos os métodos falharam - retornar erro detalhado com dica
                Log::error('ZIP inválido: ZipArchive, unzip e magic bytes falharam', [
                    'arquivo' => $fileName,
                    'ziparchive_erro' => $this->getZipArchiveErrorMessage($result),
                    'unzip_erro' => $fallback['error'] ?? 'desconhecido',
                ]);

                return response()->json([
                    'success' => false,
                    'error' => $this->getZipArchiveErrorMessage($result),
                    'hint' => 'Se criado no Mac, tente: zip -r arquivo.zip pasta/',
                ]);
            }

            // Contar XMLs (excluindo __MACOSX que contém resource forks do Mac)
            $totalXmls = 0;
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $entryName = $zip->getNameIndex($i);
                if ($entryName &&
                    str_ends_with(strtolower($entryName), '.xml') &&
                    !str_starts_with($entryName, '__MACOSX/')) {
                    $totalXmls++;
                }
            }

            $zip->close();

            return response()->json([
                'success' => true,
                'tipo' => 'zip',
                'total_xmls' => $totalXmls,
                'mensagem' => $totalXmls === 0 ? 'Nenhum XML encontrado no ZIP' : null,
            ]);

        } finally {
            @unlink($tempFile);
        }
    }

    /**
     * Traduz códigos de erro do ZipArchive para mensagens amigáveis.
     */
    private function getZipArchiveErrorMessage(int $errorCode): string
    {
        return match ($errorCode) {
            ZipArchive::ER_NOZIP => 'Arquivo não é um ZIP válido ou usa formato não suportado',
            ZipArchive::ER_COMPNOTSUPP => 'Método de compressão não suportado pelo servidor',
            ZipArchive::ER_INCONS => 'ZIP inconsistente (possível corrupção)',
            ZipArchive::ER_CRC => 'Erro de CRC (arquivo corrompido)',
            ZipArchive::ER_EOF => 'Arquivo truncado ou incompleto',
            ZipArchive::ER_NOENT => 'Arquivo não encontrado',
            ZipArchive::ER_OPEN => 'Não foi possível abrir o arquivo',
            ZipArchive::ER_READ => 'Erro de leitura',
            ZipArchive::ER_MEMORY => 'Erro de memória',
            default => "Erro ao processar ZIP (código: {$errorCode})",
        };
    }

    /**
     * Valida ZIP usando comando `unzip` do sistema como fallback.
     *
     * O comando unzip suporta mais métodos de compressão que o ZipArchive do PHP,
     * incluindo alguns formatos criados pelo Archive Utility do Mac.
     *
     * @return array{success: bool, total_xmls?: int, error?: string}
     */
    private function validarZipComUnzip(string $tempFile): array
    {
        // Verificar se unzip está disponível
        $whichResult = Process::run(['which', 'unzip']);
        if (!$whichResult->successful()) {
            return ['success' => false, 'error' => 'unzip não disponível no sistema'];
        }

        // Testar integridade do ZIP
        $testResult = Process::run(['unzip', '-t', $tempFile]);

        if (!$testResult->successful()) {
            return [
                'success' => false,
                'error' => trim($testResult->errorOutput()) ?: 'ZIP inválido',
            ];
        }

        // Listar conteúdo e contar XMLs (excluindo __MACOSX)
        $listResult = Process::run(['unzip', '-l', $tempFile]);

        if (!$listResult->successful()) {
            // ZIP é válido mas não conseguimos listar - retornar sucesso com 0 XMLs
            return ['success' => true, 'total_xmls' => 0];
        }

        $output = $listResult->output();

        // Contar arquivos .xml que NÃO estão em __MACOSX
        $lines = explode("\n", $output);
        $totalXmls = 0;

        foreach ($lines as $line) {
            // Formato típico: "  12345  2024-01-01 10:00   path/to/file.xml"
            if (preg_match('/\.xml$/i', $line) && !preg_match('/__MACOSX/i', $line)) {
                $totalXmls++;
            }
        }

        return ['success' => true, 'total_xmls' => $totalXmls];
    }

    /**
     * Verifica se o conteúdo tem magic bytes de um arquivo ZIP válido.
     *
     * ZIP files começam com "PK" (Phil Katz, criador do formato).
     * Esta verificação é usada como último fallback quando ZipArchive
     * e unzip falham em abrir o arquivo (comum com ZIPs do Mac).
     *
     * @param string $content Conteúdo binário do arquivo
     * @return bool True se os magic bytes indicam um ZIP
     */
    private function isValidZipMagicBytes(string $content): bool
    {
        if (strlen($content) < 4) {
            return false;
        }

        $magic = substr($content, 0, 4);

        // ZIP magic numbers
        return $magic === "PK\x03\x04"  // Normal ZIP (local file header)
            || $magic === "PK\x05\x06"  // Empty ZIP (end of central directory)
            || $magic === "PK\x07\x08"; // Spanned ZIP (data descriptor)
    }

    /**
     * Verifica se o conteúdo é um Apple Finder Bookmark.
     *
     * Bookmarks são enviados pelo Finder quando o arquivo está na Lixeira
     * ou é um alias. O conteúdo começa com "book" seguido de bytes nulos
     * e contém "mark" nos primeiros 16 bytes.
     *
     * @param string $content Conteúdo binário do arquivo
     * @return bool True se é um Apple Finder Bookmark
     */
    private function isAppleBookmark(string $content): bool
    {
        if (strlen($content) < 8) {
            return false;
        }

        // Apple Bookmark magic: "book" seguido de bytes nulos, ou "mark" nos primeiros bytes
        return substr($content, 0, 4) === 'book'
            && substr($content, 4, 4) === "\x00\x00\x00\x00"
            || str_contains(substr($content, 0, 16), 'mark');
    }

    /**
     * Valida arquivo XML e tenta detectar o tipo de documento.
     */
    private function validarXml(string $content, string $fileName): JsonResponse
    {
        // Suppress libxml errors to handle them gracefully
        $previousErrors = libxml_use_internal_errors(true);

        try {
            $dom = new \DOMDocument();
            $loaded = $dom->loadXML($content);

            if (!$loaded) {
                $errors = libxml_get_errors();
                libxml_clear_errors();
                return response()->json([
                    'success' => false,
                    'error' => 'XML mal formado.',
                ]);
            }

            libxml_clear_errors();

            // Try to detect document type from root element and content
            $tipoDocumento = $this->detectarTipoDocumento($dom);

            return response()->json([
                'success' => true,
                'tipo' => 'xml',
                'total_xmls' => 1,
                'tipo_documento' => $tipoDocumento,
            ]);

        } finally {
            libxml_use_internal_errors($previousErrors);
        }
    }

    /**
     * Detecta o tipo de documento fiscal a partir do DOM.
     */
    private function detectarTipoDocumento(\DOMDocument $dom): ?string
    {
        $rootElement = $dom->documentElement;
        if (!$rootElement) {
            return null;
        }

        $rootName = strtolower($rootElement->localName);

        // NF-e detection
        if (in_array($rootName, ['nfeproc', 'nfe', 'enviarnfe'])) {
            return 'NFE';
        }

        // CT-e detection
        if (in_array($rootName, ['cteproc', 'cte', 'enviarcte'])) {
            return 'CTE';
        }

        // NFS-e detection (various formats)
        if (str_contains($rootName, 'nfse') || str_contains($rootName, 'infnfse')) {
            return 'NFSE';
        }

        // Check for NFS-e tags inside the document
        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('x', $rootElement->namespaceURI ?: '');

        // Look for common NFS-e elements
        $nfseElements = $dom->getElementsByTagName('InfNfse');
        if ($nfseElements->length > 0) {
            return 'NFSE';
        }

        $nfseElements = $dom->getElementsByTagName('Nfse');
        if ($nfseElements->length > 0) {
            return 'NFSE';
        }

        // Look for NF-e elements inside
        $nfeElements = $dom->getElementsByTagName('infNFe');
        if ($nfeElements->length > 0) {
            return 'NFE';
        }

        // Look for CT-e elements inside
        $cteElements = $dom->getElementsByTagName('infCte');
        if ($cteElements->length > 0) {
            return 'CTE';
        }

        return null;
    }

    /**
     * Verifica se a requisição é AJAX (navegação SPA).
     */
    private function isAjaxRequest(Request $request): bool
    {
        return $request->ajax() || $request->header('X-Requested-With') === 'XMLHttpRequest';
    }

    /**
     * Redireciona para login preservando URL.
     */
    private function redirectToLogin(Request $request)
    {
        session(['url.intended' => $request->fullUrl()]);
        return redirect()->route('login');
    }

    /**
     * Busca o CNPJ do cliente pelo ID.
     *
     * @param int|null $clienteId
     * @return string|null CNPJ limpo (apenas numeros) ou null
     */
    private function getClienteCnpj(?int $clienteId): ?string
    {
        if (!$clienteId) {
            return null;
        }

        $cliente = Cliente::find($clienteId);
        if (!$cliente || empty($cliente->documento)) {
            return null;
        }

        // Retorna apenas numeros (remove formatacao)
        return preg_replace('/[^0-9]/', '', $cliente->documento);
    }

    /**
     * Retorna os detalhes dos participantes e notas de uma importacao.
     */
    public function getParticipantes(Request $request, int $importacaoId): JsonResponse
    {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'error' => 'Usuario nao autenticado.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $userId = Auth::id();

        // Buscar importacao
        $importacao = XmlImportacao::where('id', $importacaoId)
            ->where('user_id', $userId)
            ->first();

        if (!$importacao) {
            return response()->json([
                'success' => false,
                'error' => 'Importacao nao encontrada.',
            ], Response::HTTP_NOT_FOUND);
        }

        // Buscar participantes pelos IDs armazenados
        $participantes = [];
        $participantesNovos = 0;
        $participantesAtualizados = 0;

        // Se participante_ids estiver vazio, tentar extrair das notas fiscais (fallback)
        $participanteIds = $importacao->participante_ids;
        if (empty($participanteIds)) {
            $participanteIds = $this->extrairParticipanteIdsDasNotas($importacaoId, $userId);
        }

        if (!empty($participanteIds)) {
            $participantesQuery = \App\Models\Participante::whereIn('id', $participanteIds)
                ->where('user_id', $userId)
                ->orderBy('razao_social')
                ->get();

            foreach ($participantesQuery as $p) {
                // Determinar se e novo baseado no created_at vs iniciado_em da importacao
                $isNovo = $p->created_at >= $importacao->iniciado_em;

                if ($isNovo) {
                    $participantesNovos++;
                } else {
                    $participantesAtualizados++;
                }

                $participantes[] = [
                    'id' => $p->id,
                    'cnpj' => $p->documento,
                    'cnpj_formatado' => $p->cnpj_formatado,
                    'razao_social' => $p->razao_social,
                    'nome_fantasia' => $p->nome_fantasia,
                    'endereco' => $p->endereco,
                    'inscricao_estadual' => $p->inscricao_estadual,
                    'is_novo' => $isNovo,
                ];
            }
        }

        // Buscar notas fiscais da importacao
        $notasFiscais = [];
        $resumoFinanceiro = [
            'valor_total' => 0,
            'icms_total' => 0,
            'icms_st_total' => 0,
            'pis_cofins_total' => 0,
            'ipi_total' => 0,
            'tributos_total' => 0,
            'qtd_entradas' => 0,
            'qtd_saidas' => 0,
            'qtd_devolucoes' => 0,
        ];

        $notasQuery = \App\Models\XmlNota::where('importacao_xml_id', $importacaoId)
            ->where('user_id', $userId)
            ->orderBy('data_emissao', 'desc')
            ->get();

        // Nota: xml_chaves_processadas foi eliminada - deduplicação agora usa xml_notas diretamente

        foreach ($notasQuery as $nota) {
            // Acumular resumo financeiro
            $resumoFinanceiro['valor_total'] += (float) $nota->valor_total;
            $resumoFinanceiro['icms_total'] += (float) $nota->icms_valor;
            $resumoFinanceiro['icms_st_total'] += (float) $nota->icms_st_valor;
            $resumoFinanceiro['pis_cofins_total'] += (float) $nota->pis_valor + (float) $nota->cofins_valor;
            $resumoFinanceiro['ipi_total'] += (float) $nota->ipi_valor;
            $resumoFinanceiro['tributos_total'] += (float) $nota->tributos_total;

            if ($nota->tipo_nota === \App\Models\XmlNota::TIPO_ENTRADA) {
                $resumoFinanceiro['qtd_entradas']++;
            } else {
                $resumoFinanceiro['qtd_saidas']++;
            }

            if ($nota->finalidade === \App\Models\XmlNota::FINALIDADE_DEVOLUCAO) {
                $resumoFinanceiro['qtd_devolucoes']++;
            }

            $notasFiscais[] = [
                'id' => $nota->id,
                'numero_nota' => $nota->numero_nota,
                'serie' => $nota->serie,
                'data_emissao' => $nota->data_emissao?->format('d/m/Y'),
                'emit_cnpj' => $nota->emit_cnpj,
                'emit_cnpj_formatado' => $nota->emit_cnpj_formatado,
                'emit_razao_social' => $nota->emit_razao_social,
                'emit_uf' => $nota->emit_uf,
                'dest_cnpj' => $nota->dest_cnpj,
                'dest_cnpj_formatado' => $nota->dest_cnpj_formatado,
                'dest_razao_social' => $nota->dest_razao_social,
                'dest_uf' => $nota->dest_uf,
                'valor_total' => (float) $nota->valor_total,
                'valor_formatado' => $nota->valor_formatado,
                'icms_valor' => (float) $nota->icms_valor,
                'pis_valor' => (float) $nota->pis_valor,
                'cofins_valor' => (float) $nota->cofins_valor,
                'ipi_valor' => (float) $nota->ipi_valor,
                'tipo_nota' => $nota->tipo_nota,
                'tipo_nota_desc' => $nota->tipo_nota_descricao,
                'finalidade' => $nota->finalidade,
                'finalidade_desc' => $nota->finalidade_descricao,
                'natureza_operacao' => $nota->natureza_operacao,
            ];
        }

        return response()->json([
            'success' => true,
            'importacao' => [
                'id' => $importacao->id,
                'tipo_documento' => $importacao->tipo_documento,
                'total_xmls' => $importacao->total_xmls ?? $importacao->xmls_processados,
                'xmls_processados' => $importacao->xmls_processados,
                'status' => $importacao->status,
                'concluido_em' => $importacao->concluido_em?->format('d/m/Y H:i'),
            ],
            'resumo_financeiro' => $resumoFinanceiro,
            'notas_fiscais' => $notasFiscais,
            'participantes' => $participantes,
            'totais' => [
                'participantes_novos' => $participantesNovos ?: $importacao->participantes_novos,
                'participantes_atualizados' => $participantesAtualizados ?: $importacao->participantes_atualizados,
                'notas_total' => count($notasFiscais),
            ],
        ]);
    }

    /**
     * Salva CNPJs novos descobertos durante importacao como participantes e/ou clientes.
     *
     * Chamado pelo frontend apos o usuario revisar a lista de CNPJs novos
     * e decidir quais salvar. Cria os registros e atualiza as FKs em xml_notas.
     */
    public function salvarCnpjsNovos(Request $request, int $importacaoId): JsonResponse
    {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'error' => 'Usuario nao autenticado.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $userId = Auth::id();

        // Validar que a importacao pertence ao usuario
        $importacao = XmlImportacao::where('id', $importacaoId)
            ->where('user_id', $userId)
            ->first();

        if (!$importacao) {
            return response()->json([
                'success' => false,
                'error' => 'Importacao nao encontrada.',
            ], Response::HTTP_NOT_FOUND);
        }

        $validated = $request->validate([
            'cnpjs' => 'required|array|min:1|max:500',
            'cnpjs.*.cnpj' => 'required|string|size:14',
            'cnpjs.*.salvar_como' => 'required|in:participante,cliente',
            'cnpjs.*.razao_social' => 'nullable|string|max:255',
            'cnpjs.*.nome_fantasia' => 'nullable|string|max:255',
            'cnpjs.*.uf' => 'nullable|string|max:2',
            'cnpjs.*.cep' => 'nullable|string|max:10',
            'cnpjs.*.municipio' => 'nullable|string|max:255',
            'cnpjs.*.telefone' => 'nullable|string|max:20',
            'cnpjs.*.crt' => 'nullable|integer|in:1,2,3',
        ]);

        $criados = [];
        $erros = [];

        try {
            DB::beginTransaction();

            foreach ($validated['cnpjs'] as $cnpjData) {
                $cnpj = $cnpjData['cnpj'];

                try {
                    $clienteId = null;

                    // Se salvar como cliente, criar o registro em clientes primeiro
                    if ($cnpjData['salvar_como'] === 'cliente') {
                        $cliente = Cliente::firstOrCreate(
                            [
                                'user_id' => $userId,
                                'documento' => $cnpj,
                            ],
                            [
                                'tipo_pessoa' => 'PJ',
                                'razao_social' => $cnpjData['razao_social'] ?? null,
                                'nome' => $cnpjData['nome_fantasia'] ?? $cnpjData['razao_social'] ?? null,
                                'ativo' => true,
                                'is_empresa_propria' => false,
                            ]
                        );
                        $clienteId = $cliente->id;
                    }

                    // Criar participante (upsert para evitar conflitos)
                    $participante = Participante::updateOrCreate(
                        [
                            'user_id' => $userId,
                            'documento' => $cnpj,
                        ],
                        [
                            'razao_social' => $cnpjData['razao_social'] ?? null,
                            'nome_fantasia' => $cnpjData['nome_fantasia'] ?? null,
                            'uf' => $cnpjData['uf'] ?? null,
                            'cep' => $cnpjData['cep'] ?? null,
                            'municipio' => $cnpjData['municipio'] ?? null,
                            'telefone' => $cnpjData['telefone'] ?? null,
                            'crt' => $cnpjData['crt'] ?? null,
                            'cliente_id' => $clienteId,
                            'importacao_xml_id' => $importacaoId,
                            'origem_tipo' => $importacao->tipo_documento ?? 'NFE',
                        ]
                    );

                    // Atualizar xml_notas: preencher FKs onde CNPJ coincide e participante_id e NULL
                    XmlNota::where('importacao_xml_id', $importacaoId)
                        ->where('user_id', $userId)
                        ->where('emit_cnpj', $cnpj)
                        ->whereNull('emit_participante_id')
                        ->update([
                            'emit_participante_id' => $participante->id,
                            'emit_cliente_id' => $clienteId,
                        ]);

                    XmlNota::where('importacao_xml_id', $importacaoId)
                        ->where('user_id', $userId)
                        ->where('dest_cnpj', $cnpj)
                        ->whereNull('dest_participante_id')
                        ->update([
                            'dest_participante_id' => $participante->id,
                            'dest_cliente_id' => $clienteId,
                        ]);

                    $criados[] = [
                        'cnpj' => $cnpj,
                        'participante_id' => $participante->id,
                        'cliente_id' => $clienteId,
                        'salvo_como' => $cnpjData['salvar_como'],
                    ];
                } catch (\Exception $e) {
                    Log::warning('salvarCnpjsNovos: erro ao salvar CNPJ', [
                        'cnpj' => $cnpj,
                        'error' => $e->getMessage(),
                    ]);
                    $erros[] = [
                        'cnpj' => $cnpj,
                        'erro' => $e->getMessage(),
                    ];
                }
            }

            // Atualizar participante_ids na importacao
            $novosIds = array_column($criados, 'participante_id');
            $idsAtuais = $importacao->participante_ids ?? [];
            $idsMerged = array_values(array_unique(array_merge($idsAtuais, $novosIds)));
            $importacao->update(['participante_ids' => $idsMerged]);

            DB::commit();

            Log::info('salvarCnpjsNovos: concluido', [
                'importacao_id' => $importacaoId,
                'user_id' => $userId,
                'criados' => count($criados),
                'erros' => count($erros),
            ]);

            return response()->json([
                'success' => true,
                'criados' => $criados,
                'erros' => $erros,
                'total_criados' => count($criados),
                'total_erros' => count($erros),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('salvarCnpjsNovos: erro geral', [
                'importacao_id' => $importacaoId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erro ao salvar CNPJs: ' . $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Extrai IDs de participantes das notas fiscais da importação (fallback).
     *
     * Usado quando participante_ids não foi preenchido no registro XmlImportacao.
     * Busca emit_participante_id e dest_participante_id únicos das notas.
     * Salva os IDs no registro para próximas consultas.
     *
     * @param int $importacaoId ID da importação
     * @param int $userId ID do usuário (para validação)
     * @return array IDs únicos dos participantes
     */
    private function extrairParticipanteIdsDasNotas(int $importacaoId, int $userId): array
    {
        // Buscar IDs de emitentes
        $emitIds = \App\Models\XmlNota::where('importacao_xml_id', $importacaoId)
            ->where('user_id', $userId)
            ->whereNotNull('emit_participante_id')
            ->distinct()
            ->pluck('emit_participante_id')
            ->toArray();

        // Buscar IDs de destinatários
        $destIds = \App\Models\XmlNota::where('importacao_xml_id', $importacaoId)
            ->where('user_id', $userId)
            ->whereNotNull('dest_participante_id')
            ->distinct()
            ->pluck('dest_participante_id')
            ->toArray();

        // Combinar e remover duplicados
        $participanteIds = array_values(array_unique(array_merge($emitIds, $destIds)));

        // Se encontrou IDs, salvar no registro para próximas consultas
        if (!empty($participanteIds)) {
            try {
                XmlImportacao::where('id', $importacaoId)
                    ->where('user_id', $userId)
                    ->update(['participante_ids' => $participanteIds]);

                Log::info('Fallback: participante_ids extraídos das notas fiscais', [
                    'importacao_id' => $importacaoId,
                    'user_id' => $userId,
                    'total_ids' => count($participanteIds),
                ]);
            } catch (\Exception $e) {
                Log::warning('Fallback: erro ao salvar participante_ids', [
                    'importacao_id' => $importacaoId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $participanteIds;
    }
}
