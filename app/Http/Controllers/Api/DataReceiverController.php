<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\ConsultaLote;
use App\Models\ConsultaResultado;
use App\Models\EfdImportacao;
use App\Models\XmlImportacao;
use App\Models\MonitoramentoConsulta;
use App\Models\Participante;
use App\Models\User;
use App\Services\CreditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class DataReceiverController extends Controller
{
    public function __construct(
        protected CreditService $creditService
    ) {}

    /**
     * Health check endpoint - verifica estado do token sem autenticação.
     *
     * GET /api/health
     */
    public function health()
    {
        $token = config('services.api.token');
        $sanitized = $token ? trim(trim($token), '"\'') : '';

        return response()->json([
            'status' => 'ok',
            'api_token_configured' => ! empty($sanitized),
            'token_prefix' => $sanitized ? substr($sanitized, 0, 8) . '...' : '(vazio)',
            'token_length' => strlen($sanitized),
            'raw_length' => strlen($token ?? ''),
            'had_quotes_or_whitespace' => strlen($token ?? '') !== strlen($sanitized),
            'php_version' => PHP_VERSION,
            'laravel_env' => config('app.env'),
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Verifica se o token API é válido.
     * Retorna array com 'valid' e 'debug' para diagnostico.
     */
    private function validateToken(Request $request): array
    {
        $apiToken = $request->header('X-API-Token') ?? $request->input('api_token');
        $expectedToken = config('services.api.token');

        // Sanitizar: remover whitespace e aspas literais
        $apiToken = $apiToken ? trim(trim($apiToken), '"\'') : '';
        $expectedToken = $expectedToken ? trim(trim($expectedToken), '"\'') : '';

        $isValid = ! empty($apiToken) && ! empty($expectedToken) && hash_equals($expectedToken, $apiToken);

        $debug = [
            'received_prefix' => $apiToken ? substr($apiToken, 0, 8) . '...' : '(vazio)',
            'expected_prefix' => $expectedToken ? substr($expectedToken, 0, 8) . '...' : '(vazio)',
            'received_length' => strlen($apiToken),
            'expected_length' => strlen($expectedToken),
        ];

        if (! $isValid) {
            if (empty($expectedToken)) {
                $debug['hint'] = "config('services.api.token') esta vazio — verifique API_TOKEN no .env e re-execute config:cache";
            } elseif (empty($apiToken)) {
                $debug['hint'] = 'Header X-API-Token nao enviado ou vazio';
            } else {
                $debug['hint'] = 'Token recebido difere do esperado — compare os prefixos acima';
            }

            Log::warning('Token validation failed', $debug);
        }

        return ['valid' => $isValid, 'debug' => $debug];
    }

    /**
     * Verifica se o token API é válido (wrapper booleano para compatibilidade).
     */
    private function isTokenValid(Request $request): bool
    {
        return $this->validateToken($request)['valid'];
    }

    /**
     * Retorna resposta 401 com diagnostico de token.
     */
    private function unauthorizedResponse(Request $request): \Illuminate\Http\JsonResponse
    {
        $validation = $this->validateToken($request);

        return response()->json([
            'success' => false,
            'message' => 'Token de API inválido.',
            'debug' => $validation['debug'],
        ], Response::HTTP_UNAUTHORIZED);
    }

    /**
     * Recebe progresso da importação/processamento de arquivo SPED (enviado pelo n8n).
     * Armazena em cache para o SSE ler e enviar ao frontend.
     * NÃO edita banco de dados - apenas cache.
     *
     * POST /api/importacao/efd/importacao-txt/progresso
     *
     * Payload esperado (novo formato - n8n controla 100% do progresso):
     * {
     *   "user_id": 1,
     *   "tab_id": "550e8400-e29b-41d4-a716-446655440000",
     *   "progresso": 45,
     *   "mensagem": "Identificando participantes...",
     *   "status": "processando"
     * }
     *
     * Payload legado (ainda suportado para compatibilidade):
     * {
     *   "importacao_id": 123,
     *   "status": "processando",
     *   "total_cnpjs": 150,
     *   "processados": 75,
     *   "importados": 70,
     *   "duplicados": 5
     * }
     */
    public function receiveImportacaoTxtProgress(Request $request)
    {
        try {

            // Verifica autenticação via token
            if (! $this->isTokenValid($request)) {
                Log::warning('Token inválido em receiveImportacaoTxtProgress');

                return $this->unauthorizedResponse($request);
            }

            // Detectar formato do payload (novo vs legado)
            // Novo formato: user_id + tab_id (progresso pode estar ausente em erros, default 0)
            $hasNewFormat = $request->has('user_id') && $request->has('tab_id');
            $hasLegacyFormat = $request->has('importacao_id') && ! $hasNewFormat;

            if ($hasNewFormat) {
                // Novo formato: n8n controla 100% do progresso
                return $this->handleNewProgressFormat($request);
            } elseif ($hasLegacyFormat) {
                // Formato legado: compatibilidade com implementação anterior
                return $this->handleLegacyProgressFormat($request);
            } else {
                Log::warning('Formato de payload não reconhecido em receiveImportacaoTxtProgress', [
                    'request_data' => $request->all(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Formato de payload inválido. Use user_id+tab_id+progresso ou importacao_id.',
                ], Response::HTTP_BAD_REQUEST);
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Erro de validação em receiveImportacaoTxtProgress', [
                'errors' => $e->errors(),
                'request_data' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro de validação.',
                'errors' => $e->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);

        } catch (\Exception $e) {
            Log::error('Erro inesperado em receiveImportacaoTxtProgress', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro interno do servidor. Tente novamente mais tarde.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Recebe progresso de extração de notas EFD por bloco (A, C, D).
     * n8n envia fase/status e contadores por bloco; Laravel armazena em cache para SSE.
     *
     * POST /api/importacao/efd/notas/progresso
     * Headers: X-API-Token
     * Body: { user_id, tab_id, status, bloco, progresso, mensagem? }
     */
    public function receiveNotasEfdProgress(Request $request): JsonResponse
    {
        if (! $this->isTokenValid($request)) {
            return response()->json(['error' => 'Unauthorized', 'message' => 'Token inválido'], 401);
        }

        $data = $request->validate([
            'user_id'              => 'required|integer',
            'tab_id'               => 'required|string|max:36',
            'status'               => 'required|in:inicio,processando,concluido,skip,erro',
            'bloco'                => 'nullable|in:participantes,notas_servicos,notas_mercadorias,notas_transportes,catalogo,apuracao_icms,retencoes_fonte,apuracao_pis_cofins',
            'progresso'            => 'nullable|integer|min:0|max:100',
            'mensagem'             => 'nullable|string|max:255',
            'importacao_id'        => 'nullable|integer',
            'error_code'           => 'nullable|string|max:50',
            'error_message'        => 'nullable|string|max:500',
            'resumo_final'         => 'nullable|array',
            'notas_blocos'         => 'nullable|array',
            'blocos'               => 'nullable|array',
            'estatisticas'         => 'nullable|array',
            'totais'               => 'nullable|array',
            'participantes_resumo' => 'nullable|array',
            'dados'                => 'nullable',
        ]);

        // Default progresso=0 quando não enviado (ex: payloads de erro)
        $data['progresso'] = $data['progresso'] ?? 0;

        // Tratar status de erro: cachear para SSE e persistir no banco
        if ($data['status'] === 'erro') {
            $mainKey = "progresso:{$data['user_id']}:{$data['tab_id']}";
            $existing = Cache::get($mainKey, []);
            $importacaoId = $data['importacao_id'] ?? ($existing['importacao_id'] ?? null);

            $cachePayload = array_merge($existing, [
                'user_id'       => $data['user_id'],
                'tab_id'        => $data['tab_id'],
                'status'        => 'erro',
                'progresso'     => $data['progresso'],
                'mensagem'      => $data['mensagem'] ?? $data['error_message'] ?? 'Erro no processamento',
                'error_code'    => $data['error_code'] ?? null,
                'error_message' => $data['error_message'] ?? null,
                'updated_at'    => now()->toIso8601String(),
            ]);
            if (!empty($importacaoId)) {
                $cachePayload['importacao_id'] = $importacaoId;

                EfdImportacao::where('id', $importacaoId)
                    ->where('user_id', $data['user_id'])
                    ->update([
                        'status' => 'erro',
                    ]);
            }
            Cache::put($mainKey, $cachePayload, 600);

            return response()->json(['status' => 'ok', 'received' => 'erro']);
        }

        // Se os 4 campos chegaram separados, montar resumo_final internamente.
        // Mantém compatibilidade: se resumo_final já vier pronto, usa direto.
        if (empty($data['resumo_final'])
            && (!empty($data['blocos']) || !empty($data['estatisticas']) || !empty($data['totais']))) {
            $data['resumo_final'] = [
                'blocos'               => $data['blocos']               ?? [],
                'estatisticas'         => $data['estatisticas']         ?? [],
                'totais'               => $data['totais']               ?? [],
                'participantes_resumo' => $data['participantes_resumo'] ?? [],
            ];
        }

        // Ler cache existente antes de qualquer operação (para fallback de importacao_id)
        $mainKey = "progresso:{$data['user_id']}:{$data['tab_id']}";
        $existing = Cache::get($mainKey, []);

        // Usar importacao_id do payload OU do cache existente
        $importacaoId = $data['importacao_id'] ?? ($existing['importacao_id'] ?? null);

        // Persiste resumo_final no banco se presente, junto com colunas de stats
        if (!empty($data['resumo_final']) && !empty($importacaoId)) {
            $rfUpdate = ['resumo_final' => $data['resumo_final']];

            if ($data['status'] === 'concluido') {
                $rfUpdate['status']       = 'concluido';
                $rfUpdate['concluido_em'] = now();

                $imp = EfdImportacao::find($importacaoId);
                if ($imp && $imp->iniciado_em) {
                    $rfUpdate['tempo_processamento_segundos'] = (int) $imp->iniciado_em->diffInSeconds(now());
                }
            }

            $est = $data['resumo_final']['estatisticas'] ?? [];
            if (!empty($est['total_participantes_processados'])) {
                $rfUpdate['total_participantes'] = (int) $est['total_participantes_processados'];
            }
            if (isset($est['participantes_novos'])) {
                $rfUpdate['novos'] = (int) $est['participantes_novos'];
            }
            if (isset($est['participantes_repetidos'])) {
                $rfUpdate['duplicados'] = (int) $est['participantes_repetidos'];
            }
            if (!empty($est['total_cnpjs_unicos'])) {
                $rfUpdate['total_cnpjs_unicos'] = (int) $est['total_cnpjs_unicos'];
            }
            if (!empty($est['total_cpfs_unicos'])) {
                $rfUpdate['total_cpfs_unicos'] = (int) $est['total_cpfs_unicos'];
            }

            EfdImportacao::where('id', $importacaoId)
                ->where('user_id', $data['user_id'])
                ->update($rfUpdate);
        }

        // Recalcular alertas após importação concluída
        if ($data['status'] === 'concluido' && !empty($importacaoId)) {
            dispatch(function () use ($data) {
                app(\App\Services\AlertaCentralService::class)->recalcular((int) $data['user_id']);
            })->afterResponse();
        }

        // Atualiza cache principal (lido pelo SSE) com o progresso atual.
        // Não rebaixar status de 'concluido' para 'processando' — blocos extras
        // (catálogo, apuração) podem chegar após o payload final do n8n.
        $existingStatus = $existing['status'] ?? null;
        $incomingStatus = $data['status'];
        $statusFinal = ($existingStatus === 'concluido' && $incomingStatus === 'processando')
            ? 'concluido'
            : $incomingStatus;

        $cachePayload = array_merge($existing, [
            'user_id'    => $data['user_id'],
            'tab_id'     => $data['tab_id'],
            'status'     => $statusFinal,
            'progresso'  => $existingStatus === 'concluido' ? ($existing['progresso'] ?? 100) : $data['progresso'],
            'mensagem'   => $existingStatus === 'concluido' ? ($existing['mensagem'] ?? $data['mensagem']) : ($data['mensagem'] ?? null),
            'bloco'      => $data['bloco'] ?? null,
            'updated_at' => now()->toIso8601String(),
        ]);
        // Preservar importacao_id no cache (do payload ou do existente)
        if (!empty($importacaoId)) {
            $cachePayload['importacao_id'] = $importacaoId;
        }
        if (!empty($data['resumo_final'])) {
            $cachePayload['resumo_final'] = $data['resumo_final'];
        }
        if (!empty($data['notas_blocos'])) {
            $cachePayload['notas_blocos'] = $data['notas_blocos'];
        }
        $dadosRaw = $data['dados'] ?? null;
        if (is_string($dadosRaw) && !empty($dadosRaw)) {
            $dadosParsed = json_decode($dadosRaw, true) ?? [];
        } elseif (is_array($dadosRaw)) {
            $dadosParsed = $dadosRaw;
        } else {
            $dadosParsed = [];
        }
        if (!empty($dadosParsed)) {
            $cachePayload['dados'] = $dadosParsed;
        }
        Cache::put($mainKey, $cachePayload, 600);

        // Cache por bloco (lido pelo SSE para montar notas_blocos)
        if (! empty($data['bloco'])) {
            $blocoKey = "efd_notas_progress:{$data['user_id']}:{$data['tab_id']}:{$data['bloco']}";
            Cache::put($blocoKey, [
                'bloco'      => $data['bloco'],
                'status'     => $data['status'],
                'progresso'  => $data['progresso'],
                'mensagem'   => $data['mensagem'] ?? null,
                'updated_at' => now()->toIso8601String(),
            ], 600);

            // Marcar blocos anteriores como concluídos se ainda estiverem processando
            if (in_array($data['status'], ['inicio', 'processando'])) {
                $ordemBlocos = ['participantes', 'notas_servicos', 'notas_mercadorias', 'notas_transportes', 'catalogo', 'apuracao_icms', 'retencoes_fonte', 'apuracao_pis_cofins'];
                $currentIdx = array_search($data['bloco'], $ordemBlocos);

                for ($i = 0; $i < $currentIdx; $i++) {
                    $priorKey = "efd_notas_progress:{$data['user_id']}:{$data['tab_id']}:{$ordemBlocos[$i]}";
                    $priorData = Cache::get($priorKey);
                    if ($priorData && !in_array($priorData['status'], ['concluido', 'skip'])) {
                        $priorData['status'] = 'concluido';
                        $priorData['progresso'] = 100;
                        Cache::put($priorKey, $priorData, 600);
                    }
                }
            }
        }

        return response()->json(['success' => true]);
    }

    /**
     * Processa novo formato de progresso (user_id + tab_id).
     * n8n controla 100% do progresso (percentual + mensagem).
     *
     * Quando status="erro", pode incluir:
     * - error_code: Código do erro (ex: "API_TIMEOUT", "INFOSIMPLES_ERROR")
     * - error_message: Mensagem descritiva do erro
     */
    private function handleNewProgressFormat(Request $request)
    {
        $validated = $request->validate([
            'user_id'       => 'required|integer',
            'tab_id'        => 'required|string|max:36',
            'progresso'     => 'nullable|integer|min:0|max:100',
            'mensagem'      => 'nullable|string|max:255',
            'status'        => 'required|in:iniciando,processando,concluido,erro',
            'error_code'    => 'nullable|string|max:50',
            'error_message' => 'nullable|string|max:500',
            'dados'         => 'nullable',
            'cliente_id'    => 'nullable|integer',
            'importacao_id' => 'nullable|integer',
            'resumo_final'     => 'nullable|array',
            'notas_blocos'     => 'nullable|array',
            'participante_ids' => 'nullable|array',
        ]);

        $cacheKey = "progresso:{$validated['user_id']}:{$validated['tab_id']}";

        // Ler cache existente antes de qualquer operação (para merge e fallback de importacao_id)
        $existing = Cache::get($cacheKey, []);

        // Idempotência: não grava no cache se progresso/mensagem/status são idênticos.
        // Bypassar se resumo_final presente — payloads finais devem sempre ser processados.
        if ($existing &&
            ($existing['progresso'] ?? null) === ($validated['progresso'] ?? 0) &&
            ($existing['mensagem'] ?? null) === ($validated['mensagem'] ?? null) &&
            ($existing['status'] ?? null) === $validated['status'] &&
            empty($validated['error_code']) &&
            empty($validated['dados']) &&
            empty($validated['resumo_final'])
        ) {
            return response()->json([
                'success' => true,
                'message' => 'Progresso sem alteração (idempotente).',
                'progresso' => $validated['progresso'],
            ], Response::HTTP_OK);
        }

        // Merge com cache existente para preservar importacao_id e outros campos anteriores
        $cacheData = array_merge($existing, [
            'user_id'    => $validated['user_id'],
            'tab_id'     => $validated['tab_id'],
            'progresso'  => $validated['progresso'] ?? 0,
            'mensagem'   => $validated['mensagem'] ?? null,
            'status'     => $validated['status'],
            'updated_at' => now()->toIso8601String(),
        ]);

        // Se a fase de notas está ativa (bloco presente no cache), não sobrescrever
        // o progresso com o valor do endpoint principal — o endpoint de notas controla.
        if (isset($existing['bloco']) && $existing['bloco'] !== '' && $validated['status'] !== 'concluido' && $validated['status'] !== 'erro') {
            $cacheData['progresso'] = $existing['progresso'] ?? $cacheData['progresso'];
            $cacheData['mensagem']  = $existing['mensagem']  ?? $cacheData['mensagem'];
        }

        // Preservar importacao_id do cache inicial se n8n não reenviar
        $importacaoIdTop = $validated['importacao_id'] ?? ($existing['importacao_id'] ?? null);
        if (!empty($importacaoIdTop)) {
            $cacheData['importacao_id'] = $importacaoIdTop;
        }

        // Propagar resumo_final e notas_blocos ao cache
        if (!empty($validated['resumo_final'])) {
            $resumoFinal = $validated['resumo_final'];
            
            // Enriquecer resumo_final com apurações do DB para os Resumos Inteligentes
            $importacaoId = $validated['importacao_id'] ?? ($existing['importacao_id'] ?? null);
            if ($importacaoId) {
                // Buscamos a importacao e as obrigações que foram extraídas na fase anterior do workflow
                $importacao = \App\Models\EfdImportacao::with(['apuracaoIcms', 'apuracaoContribuicao', 'retencoesFonte'])
                    ->find($importacaoId);
                    
                if ($importacao) {
                    if (!isset($resumoFinal['blocos'])) {
                        $resumoFinal['blocos'] = [];
                    }
                    
                    if ($importacao->apuracaoIcms) {
                        $resumoFinal['blocos']['apuracao_icms'] = [
                            'total_notas' => 1,
                            'valor_total' => $importacao->apuracaoIcms->icms_a_recolher + ($importacao->apuracaoIcms->tem_st ? $importacao->apuracaoIcms->st_icms_recolher : 0),
                            'label_count' => 'apuração',
                        ];
                    }
                    if ($importacao->apuracaoContribuicao) {
                        $resumoFinal['blocos']['apuracao_pis_cofins'] = [
                            'total_notas' => 1,
                            'valor_total' => $importacao->apuracaoContribuicao->pis_total_recolher + $importacao->apuracaoContribuicao->cofins_total_recolher,
                            'label_count' => 'apuração',
                        ];
                    }
                    if ($importacao->retencoesFonte && $importacao->retencoesFonte->isNotEmpty()) {
                        $totalRet = $importacao->retencoesFonte->count();
                        $valorRet = $importacao->retencoesFonte->sum('valor_pis') + $importacao->retencoesFonte->sum('valor_cofins');
                        $resumoFinal['blocos']['retencoes_fonte'] = [
                            'total_notas' => $totalRet,
                            'valor_total' => $valorRet,
                            'label_count' => $totalRet > 1 ? 'retenções' : 'retenção',
                        ];
                    }
                    
                    // Atualiza o registro no BD caso ele não propague de outra forma
                    $importacao->update(['resumo_final' => $resumoFinal]);
                }
            }
            
            $cacheData['resumo_final'] = $resumoFinal;
        }
        if (!empty($validated['notas_blocos'])) {
            $cacheData['notas_blocos'] = $validated['notas_blocos'];
        }

        // Adicionar campos de erro se fornecidos
        if (! empty($validated['error_code'])) {
            $cacheData['error_code'] = $validated['error_code'];
        }
        if (! empty($validated['error_message'])) {
            $cacheData['error_message'] = $validated['error_message'];
        }

        // n8n pode enviar dados como string JSON (via JSON.stringify) — fazer parse aqui
        $dadosRaw = $validated['dados'] ?? null;
        if (is_string($dadosRaw) && !empty($dadosRaw)) {
            $dadosParsed = json_decode($dadosRaw, true) ?? [];
        } elseif (is_array($dadosRaw)) {
            $dadosParsed = $dadosRaw;
        } else {
            $dadosParsed = [];
        }
        $cacheData['dados'] = $dadosParsed;

        // Enriquecer dados com informações do cliente se fornecido
        if (! empty($validated['cliente_id'])) {
            $cliente = \App\Models\Cliente::find($validated['cliente_id']);
            if ($cliente) {
                $dados = $cacheData['dados'];
                if (is_array($dados)) {
                    $dados['cliente_id']          = $validated['cliente_id'];
                    $dados['cliente_nome']        = $cliente->razao_social ?: $cliente->nome;
                    $dados['cliente_documento']   = $cliente->documento_formatado ?? $cliente->documento;
                    $dados['cliente_tipo_pessoa'] = $cliente->tipo_pessoa;
                    $cacheData['dados']           = $dados;
                }
            }
        }

        // Atualizar DB antes do cache para evitar race condition com SSE
        if ($validated['status'] === 'concluido') {
            $this->updateEfdImportacaoFromProgress($validated, $importacaoIdTop);
        }

        // Armazena em cache (TTL 10 minutos)
        Cache::put($cacheKey, $cacheData, 600);

        Log::info('Progresso armazenado em cache (novo formato)', [
            'cache_key'         => $cacheKey,
            'user_id'           => $validated['user_id'],
            'tab_id'            => $validated['tab_id'],
            'progresso'         => $validated['progresso'],
            'status'            => $validated['status'],
            'has_error'         => ! empty($validated['error_code']),
            'has_dados'         => ! empty($validated['dados']),
            'has_resumo_final'  => ! empty($validated['resumo_final']),
            'importacao_id'     => $importacaoIdTop,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Progresso atualizado.',
            'progresso' => $validated['progresso'],
        ], Response::HTTP_OK);
    }

    /**
     * Processa formato legado de progresso (importacao_id).
     * Mantido para compatibilidade com implementações anteriores.
     */
    private function handleLegacyProgressFormat(Request $request)
    {
        $validated = $request->validate([
            'importacao_id' => 'required|integer',
            'status' => 'required|in:processando,concluido,erro',
            'total_cnpjs' => 'sometimes|integer|min:0',
            'processados' => 'sometimes|integer|min:0',
            'importados' => 'sometimes|integer|min:0',
            'duplicados' => 'sometimes|integer|min:0',
            'error_message' => 'sometimes|string|max:500',
        ]);

        $importacaoId = $validated['importacao_id'];
        $cacheKey = "importacao_progresso_{$importacaoId}";

        // Extrair valores com defaults
        $total = $validated['total_cnpjs'] ?? 0;
        $processados = $validated['processados'] ?? 0;
        $importados = $validated['importados'] ?? 0;
        $duplicados = $validated['duplicados'] ?? 0;

        // Calcular porcentagem
        $porcentagem = $total > 0 ? (int) round(($processados / $total) * 100) : 0;

        // Dados para cache
        $cacheData = [
            'status' => $validated['status'],
            'total_cnpjs' => $total,
            'processados' => $processados,
            'importados' => $importados,
            'duplicados' => $duplicados,
            'porcentagem' => $porcentagem,
            'updated_at' => now()->toIso8601String(),
        ];

        // Se houver mensagem de erro, incluir
        if (! empty($validated['error_message'])) {
            $cacheData['error_message'] = $validated['error_message'];
        }

        // Armazena em cache (expira em 10 minutos)
        Cache::put($cacheKey, $cacheData, 600);

        Log::info('Progresso de importação armazenado em cache (formato legado)', [
            'importacao_id' => $importacaoId,
            'cache_key' => $cacheKey,
            'status' => $validated['status'],
            'porcentagem' => $porcentagem,
            'processados' => $processados,
            'total' => $total,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Progresso atualizado.',
            'porcentagem' => $porcentagem,
        ], Response::HTTP_OK);
    }

    /**
     * Recebe resultado de consulta do Monitoramento (enviado pelo n8n).
     * n8n pode escrever diretamente no PostgreSQL, mas também pode usar este
     * endpoint para notificar Laravel e permitir lógica adicional.
     *
     * POST /api/monitoramento/consulta/resultado
     */
    public function receiveMonitoramentoConsulta(Request $request)
    {
        try {
            Log::info('Requisição recebida em receiveMonitoramentoConsulta', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'ip' => $request->ip(),
                'headers' => [
                    'x-api-token' => $request->hasHeader('X-API-Token') ? 'presente' : 'ausente',
                    'content-type' => $request->header('Content-Type'),
                ],
                'body' => $request->all(),
            ]);

            // Verifica autenticação via token
            if (! $this->isTokenValid($request)) {
                Log::warning('Token inválido em receiveMonitoramentoConsulta');

                return $this->unauthorizedResponse($request);
            }

            // Validar payload
            $validated = $request->validate([
                'consulta_id' => 'required|integer',
                'status' => 'required|in:sucesso,erro,processando',
                'resultado' => 'sometimes|array',
                'situacao_geral' => 'sometimes|in:regular,atencao,irregular',
                'tem_pendencias' => 'sometimes|boolean',
                'proxima_validade' => 'sometimes|nullable|date',
                'error_code' => 'sometimes|string|max:50',
                'error_message' => 'sometimes|string|max:500',
            ]);

            $consultaId = $validated['consulta_id'];

            // Buscar consulta
            $consulta = MonitoramentoConsulta::find($consultaId);

            if (! $consulta) {
                Log::warning('Consulta não encontrada em receiveMonitoramentoConsulta', [
                    'consulta_id' => $consultaId,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Consulta não encontrada.',
                ], Response::HTTP_NOT_FOUND);
            }

            // Atualizar consulta
            $updateData = [
                'status' => $validated['status'],
                'executado_em' => now(),
            ];

            if ($validated['status'] === 'sucesso') {
                if (isset($validated['resultado'])) {
                    $updateData['resultado'] = $validated['resultado'];
                }
                if (isset($validated['situacao_geral'])) {
                    $updateData['situacao_geral'] = $validated['situacao_geral'];
                }
                if (isset($validated['tem_pendencias'])) {
                    $updateData['tem_pendencias'] = $validated['tem_pendencias'];
                }
                if (isset($validated['proxima_validade'])) {
                    $updateData['proxima_validade'] = $validated['proxima_validade'];
                }
            } elseif ($validated['status'] === 'erro') {
                if (isset($validated['error_code'])) {
                    $updateData['error_code'] = $validated['error_code'];
                }
                if (isset($validated['error_message'])) {
                    $updateData['error_message'] = $validated['error_message'];
                }
            }

            $consulta->update($updateData);

            Log::info('Consulta atualizada com resultado', [
                'consulta_id' => $consultaId,
                'status' => $validated['status'],
            ]);

            // Armazenar em cache para SSE (notificação em tempo real)
            $cacheKey = "monitoramento_consulta_resultado_{$consultaId}";
            Cache::put($cacheKey, [
                'consulta_id' => $consultaId,
                'user_id' => $consulta->user_id,
                'status' => $validated['status'],
                'situacao_geral' => $validated['situacao_geral'] ?? null,
                'updated_at' => now()->toIso8601String(),
            ], 300); // Cache por 5 minutos

            return response()->json([
                'success' => true,
                'message' => 'Resultado da consulta processado com sucesso.',
                'consulta_id' => $consultaId,
            ], Response::HTTP_OK);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Erro de validação em receiveMonitoramentoConsulta', [
                'errors' => $e->errors(),
                'request_data' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro de validação.',
                'errors' => $e->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);

        } catch (\Exception $e) {
            Log::error('Erro inesperado em receiveMonitoramentoConsulta', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro interno do servidor. Tente novamente mais tarde.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Recebe progresso de importação de XMLs (enviado pelo n8n).
     *
     * POST /api/importacao/xml/progress
     */
    public function receiveXmlImportacaoProgress(Request $request)
    {
        try {
            Log::info('Requisição recebida em receiveXmlImportacaoProgress', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'ip' => $request->ip(),
                'headers' => [
                    'x-api-token' => $request->hasHeader('X-API-Token') ? 'presente' : 'ausente',
                    'content-type' => $request->header('Content-Type'),
                ],
                'body' => $request->all(),
            ]);

            // Verifica autenticação via token
            if (! $this->isTokenValid($request)) {
                Log::warning('Token inválido em receiveXmlImportacaoProgress');

                return $this->unauthorizedResponse($request);
            }

            // Validar payload
            $validated = $request->validate([
                'user_id' => 'required|integer|exists:users,id',
                'tab_id' => 'required|string|max:36',
                'progresso' => 'required|integer|min:0|max:100',
                'mensagem' => 'nullable|string|max:255',
                'status' => 'required|in:iniciando,processando,concluido,erro',
                'importacao_id' => 'nullable|integer',
                'error_code' => 'nullable|string|max:50',
                'error_message' => 'nullable|string|max:500',
                'dados' => 'nullable',
            ]);

            // Chave do cache: progresso:{user_id}:{tab_id}
            $cacheKey = "progresso:{$validated['user_id']}:{$validated['tab_id']}";

            // Dados para cache (repassa exatamente o que n8n enviou)
            $cacheData = [
                'user_id' => $validated['user_id'],
                'tab_id' => $validated['tab_id'],
                'progresso' => $validated['progresso'],
                'mensagem' => $validated['mensagem'] ?? null,
                'status' => $validated['status'],
                'updated_at' => now()->toIso8601String(),
            ];

            // Adicionar importacao_id se fornecido
            if (! empty($validated['importacao_id'])) {
                $cacheData['importacao_id'] = $validated['importacao_id'];
            }

            // Adicionar campos de erro se fornecidos
            if (! empty($validated['error_code'])) {
                $cacheData['error_code'] = $validated['error_code'];
            }
            if (! empty($validated['error_message'])) {
                $cacheData['error_message'] = $validated['error_message'];
            }

            // Sempre incluir campo dados no cache
            $cacheData['dados'] = $validated['dados'] ?? [];

            // Armazena em cache (TTL 10 minutos)
            Cache::put($cacheKey, $cacheData, 600);

            Log::info('Progresso XML armazenado em cache', [
                'cache_key' => $cacheKey,
                'user_id' => $validated['user_id'],
                'tab_id' => $validated['tab_id'],
                'progresso' => $validated['progresso'],
                'status' => $validated['status'],
                'has_error' => ! empty($validated['error_code']),
                'has_dados' => ! empty($validated['dados']),
            ]);

            // Quando status é final, atualizar registro XmlImportacao no banco
            if (in_array($validated['status'], ['concluido', 'erro'])) {
                $this->updateXmlImportacaoFromProgress($validated);
            }

            return response()->json([
                'success' => true,
                'message' => 'Progresso atualizado.',
                'progresso' => $validated['progresso'],
            ], Response::HTTP_OK);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Erro de validação em receiveXmlImportacaoProgress', [
                'errors' => $e->errors(),
                'request_data' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro de validação.',
                'errors' => $e->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);

        } catch (\Exception $e) {
            Log::error('Erro inesperado em receiveXmlImportacaoProgress', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro interno do servidor. Tente novamente mais tarde.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Atualiza EfdImportacao e vincula participantes ao cliente quando n8n envia status final.
     */
    private function updateEfdImportacaoFromProgress(array $validated, ?int $importacaoIdTop = null): void
    {
        $dadosRaw = $validated['dados'] ?? [];
        if (is_string($dadosRaw)) {
            $dados = json_decode($dadosRaw, true) ?? [];
        } else {
            $dados = is_array($dadosRaw) ? $dadosRaw : [];
        }
        // Aceita importacao_id top-level (preferido) ou dentro de dados
        $importacaoId = $importacaoIdTop
            ?? $dados['importacao_id']
            ?? $dados['importacoes_efd_id']
            ?? null;

        if (!$importacaoId) return;

        try {
            $importacao = EfdImportacao::where('id', $importacaoId)
                ->where('user_id', $validated['user_id'])
                ->first();

            if (!$importacao) return;

            $updateData = [
                'status'       => 'concluido',
                'concluido_em' => now(),
            ];

            if ($importacao->iniciado_em) {
                $updateData['tempo_processamento_segundos'] = (int) $importacao->iniciado_em->diffInSeconds(now());
            }

            if (!empty($dados['total_processados'])) {
                $updateData['total_participantes'] = (int) $dados['total_processados'];
            }
            if (isset($dados['novos_salvos'])) {
                $updateData['novos'] = (int) $dados['novos_salvos'];
            }
            if (isset($dados['duplicados_identificados'])) {
                $updateData['duplicados'] = (int) $dados['duplicados_identificados'];
            }

            // cliente_id vem no topo do payload (não dentro de dados)
            $clienteId = $validated['cliente_id'] ?? null;
            if ($clienteId) {
                $updateData['cliente_id'] = $clienteId;
            }

            // participante_ids: preferir array top-level (n8n via Execute Query), fallback para string CSV
            $ids = [];
            if (!empty($validated['participante_ids']) && is_array($validated['participante_ids'])) {
                $ids = array_values(array_filter(array_map('intval', $validated['participante_ids'])));
                $updateData['participante_ids'] = $ids;
            } else {
                // Fallback: aceitar "lita" (typo n8n) ou "lista" como string CSV
                $idsStr = $dados['participante_lita_geral_ids']
                    ?? $dados['participante_lista_geral_ids']
                    ?? '';
                if (!empty($idsStr)) {
                    $ids = array_values(array_filter(array_map('intval', explode(',', $idsStr))));
                    $updateData['participante_ids'] = $ids;
                }
            }

            // Persistir resumo_final: prioridade para o campo top-level, fallback para dados
            if (!empty($validated['resumo_final'])) {
                $updateData['resumo_final'] = $validated['resumo_final'];
            } elseif (!empty($dados['estatisticas']) || !empty($dados['blocos'])) {
                $updateData['resumo_final'] = $dados;
            }
            // Enriquecer contadores a partir de estatisticas (se não vieram nos campos legados)
            if (!empty($dados['estatisticas'])) {
                $est = $dados['estatisticas'];
                if (empty($updateData['total_participantes']) && !empty($est['total_participantes_processados'])) {
                    $updateData['total_participantes'] = (int) $est['total_participantes_processados'];
                }
                if (!isset($updateData['novos']) && isset($est['participantes_novos'])) {
                    $updateData['novos'] = (int) $est['participantes_novos'];
                }
                if (!isset($updateData['duplicados']) && isset($est['participantes_repetidos'])) {
                    $updateData['duplicados'] = (int) $est['participantes_repetidos'];
                }
            }

            $importacao->update($updateData);

            // Vincular participantes ao cliente
            if ($clienteId && !empty($ids)) {
                Participante::whereIn('id', $ids)
                    ->where('user_id', $validated['user_id'])
                    ->whereNull('cliente_id')
                    ->update(['cliente_id' => $clienteId]);
            }

        } catch (\Exception $e) {
            Log::error('Erro ao atualizar EfdImportacao do progresso', [
                'importacao_id' => $importacaoId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Atualiza o registro XmlImportacao quando o n8n envia status final.
     */
    private function updateXmlImportacaoFromProgress(array $validated): void
    {
        if (empty($validated['importacao_id'])) {
            Log::warning('updateXmlImportacaoFromProgress: importacao_id não fornecido', [
                'user_id' => $validated['user_id'],
                'tab_id' => $validated['tab_id'],
                'status' => $validated['status'],
            ]);

            return;
        }

        try {
            $importacao = XmlImportacao::where('id', $validated['importacao_id'])
                ->where('user_id', $validated['user_id'])
                ->first();

            if (! $importacao) {
                Log::warning('updateXmlImportacaoFromProgress: importacao não encontrada', [
                    'importacao_id' => $validated['importacao_id'],
                    'user_id' => $validated['user_id'],
                ]);

                return;
            }

            $dados = $validated['dados'] ?? [];

            $updateData = [
                'status' => $validated['status'],
                'concluido_em' => now(),
            ];

            if ($importacao->iniciado_em) {
                $updateData['tempo_processamento_segundos'] = (int) $importacao->iniciado_em->diffInSeconds(now());
            }

            // Estatísticas de XMLs
            if (isset($dados['xmls_processados'])) {
                $updateData['xmls_processados'] = (int) $dados['xmls_processados'];
            }
            if (isset($dados['total_xmls'])) {
                $updateData['total_xmls'] = (int) $dados['total_xmls'];
            }
            if (isset($dados['xmls_novos'])) {
                $updateData['xmls_novos'] = (int) $dados['xmls_novos'];
            }
            if (isset($dados['xmls_duplicados_processados'])) {
                $updateData['xmls_duplicados_processados'] = (int) $dados['xmls_duplicados_processados'];
            }
            if (isset($dados['xmls_com_erro'])) {
                $updateData['xmls_com_erro'] = (int) $dados['xmls_com_erro'];
            }

            // Estatísticas de participantes
            if (isset($dados['participantes_novos'])) {
                $updateData['participantes_novos'] = (int) $dados['participantes_novos'];
            }
            if (isset($dados['participantes_atualizados'])) {
                $updateData['participantes_atualizados'] = (int) $dados['participantes_atualizados'];
            }
            if (isset($dados['participantes_ignorados'])) {
                $updateData['participantes_ignorados'] = (int) $dados['participantes_ignorados'];
            }

            // IDs dos participantes processados (crítico para getParticipantes)
            if (! empty($dados['participante_ids'])) {
                $updateData['participante_ids'] = array_values(array_unique(array_map('intval', $dados['participante_ids'])));
            }

            // Valor total das notas
            if (isset($dados['valor_total'])) {
                $updateData['valor_total'] = (float) $dados['valor_total'];
            }

            // Erros detalhados
            if (! empty($dados['erros'])) {
                $updateData['erros_detalhados'] = $dados['erros'];
            }

            // Mensagem de erro
            if ($validated['status'] === 'erro' && ! empty($validated['error_message'])) {
                $updateData['erro_mensagem'] = $validated['error_message'];
            }

            $importacao->update($updateData);

            Log::info('XmlImportacao atualizada com dados do progresso', [
                'importacao_id' => $importacao->id,
                'status' => $validated['status'],
                'participante_ids_count' => count($updateData['participante_ids'] ?? []),
                'xmls_processados' => $updateData['xmls_processados'] ?? null,
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao atualizar XmlImportacao do progresso', [
                'importacao_id' => $validated['importacao_id'],
                'error' => $e->getMessage(),
            ]);
        }
    }


    /**
     * Endpoint unificado de progresso, conclusão e erro de consulta em lote.
     *
     * POST /api/consultas/progresso
     *
     * Status possíveis:
     * - progresso: atualiza cache SSE com progresso parcial
     * - concluido: persiste resultado no DB + atualiza cache SSE
     * - erro: persiste erro no DB + refund (parcial ou total) + atualiza cache SSE
     */
    public function receiveConsultasProgresso(Request $request)
    {
        try {
            Log::info('Requisição recebida em receiveConsultasProgresso', [
                'url' => $request->fullUrl(),
                'ip' => $request->ip(),
                'headers' => [
                    'x-api-token' => $request->hasHeader('X-API-Token') ? 'presente' : 'ausente',
                    'content-type' => $request->header('Content-Type'),
                ],
                'body' => $request->except(['resultado_resumo']),
            ]);

            if (! $this->isTokenValid($request)) {
                Log::warning('Token inválido em receiveConsultasProgresso');

                return $this->unauthorizedResponse($request);
            }

            $validated = $request->validate([
                'user_id'          => 'required|integer|exists:users,id',
                'tab_id'           => 'required|string|max:36',
                'status'           => 'required|in:processando,concluido,erro',
                'progresso'        => 'required_if:status,processando|integer|min:0|max:100',
                'mensagem'         => 'nullable|string|max:255',
                'etapa'            => 'nullable|integer|min:1',
                'total_etapas'     => 'nullable|integer|min:1',
                'etapa_label'      => 'nullable|string|max:50',
                'consulta_lote_id' => 'required_if:status,concluido|required_if:status,erro|nullable|integer|exists:consulta_lotes,id',
                'resultado_resumo' => 'nullable|array',
                'error_code'       => 'required_if:status,erro|nullable|string|max:50',
                'error_message'    => 'required_if:status,erro|nullable|string|max:500',
                'refund_credits'   => 'nullable|boolean',
                'refund_amount'    => 'nullable|integer|min:1',
            ]);

            if (
                ! empty($validated['etapa']) && ! empty($validated['total_etapas'])
                && $validated['etapa'] > $validated['total_etapas']
            ) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erro de validação.',
                    'errors'  => ['etapa' => ['O número da etapa não pode ser maior que total_etapas.']],
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $cacheKey = "progresso:{$validated['user_id']}:{$validated['tab_id']}";
            $status = $validated['status'];

            // ── Cenário A: processando (só cache) ──
            if ($status === 'processando') {
                $cacheData = [
                    'user_id'      => $validated['user_id'],
                    'tab_id'       => $validated['tab_id'],
                    'progresso'    => $validated['progresso'],
                    'mensagem'     => $validated['mensagem'] ?? null,
                    'etapa'        => $validated['etapa'] ?? null,
                    'total_etapas' => $validated['total_etapas'] ?? null,
                    'etapa_label'  => $validated['etapa_label'] ?? null,
                    'status'       => 'processando',
                    'updated_at'   => now()->toIso8601String(),
                ];

                Cache::put($cacheKey, $cacheData, 600);

                Log::info('Progresso consulta armazenado em cache', [
                    'cache_key' => $cacheKey,
                    'user_id'   => $validated['user_id'],
                    'tab_id'    => $validated['tab_id'],
                    'progresso' => $validated['progresso'],
                ]);

                return response()->json([
                    'success'   => true,
                    'message'   => 'Progresso atualizado.',
                    'progresso' => $validated['progresso'],
                ], Response::HTTP_OK);
            }

            // ── Cenários B e C: concluido / erro (DB + cache) ──
            $lote = ConsultaLote::where('id', $validated['consulta_lote_id'])
                ->where('user_id', $validated['user_id'])
                ->first();

            if (! $lote) {
                Log::warning('receiveConsultasProgresso: lote não encontrado', [
                    'consulta_lote_id' => $validated['consulta_lote_id'],
                    'user_id'          => $validated['user_id'],
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Lote não encontrado para este usuário.',
                ], Response::HTTP_NOT_FOUND);
            }

            if ($status === 'concluido') {
                $updateData = [
                    'status'        => 'concluido',
                    'processado_em' => now(),
                ];

                if (! empty($validated['resultado_resumo'])) {
                    $updateData['resultado_resumo'] = $validated['resultado_resumo'];
                }

                $lote->update($updateData);

                $totalEtapas = $validated['total_etapas'] ?? $validated['etapa'] ?? null;

                Cache::put($cacheKey, [
                    'user_id'          => $validated['user_id'],
                    'tab_id'           => $validated['tab_id'],
                    'consulta_lote_id' => $lote->id,
                    'progresso'        => 100,
                    'mensagem'         => $validated['mensagem'] ?? 'Consulta concluída.',
                    'etapa'            => $totalEtapas,
                    'total_etapas'     => $totalEtapas,
                    'etapa_label'      => $validated['etapa_label'] ?? null,
                    'status'           => 'concluido',
                    'updated_at'       => now()->toIso8601String(),
                ], 600);

                Log::info('ConsultaLote concluído', [
                    'consulta_lote_id' => $lote->id,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Consulta concluída.',
                ], Response::HTTP_OK);
            }

            // ── status === 'erro' ──
            $lote->update([
                'status'        => 'erro',
                'error_code'    => $validated['error_code'] ?? 'UNKNOWN_ERROR',
                'error_message' => $validated['error_message'] ?? 'Erro desconhecido',
                'processado_em' => now(),
            ]);

            if (! empty($validated['refund_credits'])) {
                $refundAmount = $validated['refund_amount'] ?? null;
                $this->refundConsultaLoteCredits($lote, $refundAmount);
            }

            Cache::put($cacheKey, [
                'user_id'          => $validated['user_id'],
                'tab_id'           => $validated['tab_id'],
                'consulta_lote_id' => $lote->id,
                'progresso'        => $validated['progresso'] ?? 0,
                'mensagem'         => $validated['error_message'] ?? 'Erro no processamento.',
                'etapa'            => $validated['etapa'] ?? null,
                'total_etapas'     => $validated['total_etapas'] ?? null,
                'etapa_label'      => $validated['etapa_label'] ?? null,
                'status'           => 'erro',
                'error_code'       => $validated['error_code'] ?? 'UNKNOWN_ERROR',
                'error_message'    => $validated['error_message'] ?? 'Erro desconhecido',
                'updated_at'       => now()->toIso8601String(),
            ], 600);

            Log::info('ConsultaLote marcado como erro', [
                'consulta_lote_id' => $lote->id,
                'error_code'       => $validated['error_code'] ?? 'UNKNOWN_ERROR',
                'refund_credits'   => $validated['refund_credits'] ?? false,
                'refund_amount'    => $validated['refund_amount'] ?? null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Erro registrado.',
            ], Response::HTTP_OK);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Erro de validação em receiveConsultasProgresso', [
                'errors'       => $e->errors(),
                'request_data' => $request->except(['resultado_resumo']),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro de validação.',
                'errors'  => $e->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);

        } catch (\Exception $e) {
            Log::error('Erro inesperado em receiveConsultasProgresso', [
                'message'      => $e->getMessage(),
                'trace'        => $e->getTraceAsString(),
                'request_data' => $request->except(['resultado_resumo']),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro interno do servidor.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Estorna créditos de um lote de consulta em caso de erro.
     *
     * Se $refundAmount for informado, estorna esse valor (refund parcial).
     * Caso contrário, estorna o total cobrado do lote (refund total).
     */
    private function refundConsultaLoteCredits(ConsultaLote $lote, ?int $refundAmount = null): void
    {
        $amount = $refundAmount ?? $lote->creditos_cobrados;

        if ($amount <= 0) {
            return;
        }

        try {
            $user = User::find($lote->user_id);
            if ($user) {
                $this->creditService->add($user, $amount);
                Log::info('Créditos estornados para consulta lote com erro', [
                    'consulta_lote_id'   => $lote->id,
                    'user_id'            => $lote->user_id,
                    'creditos_estornados' => $amount,
                    'tipo_refund'        => $refundAmount ? 'parcial' : 'total',
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Erro ao estornar créditos do consulta lote', [
                'consulta_lote_id' => $lote->id,
                'error'            => $e->getMessage(),
            ]);
        }
    }

}

