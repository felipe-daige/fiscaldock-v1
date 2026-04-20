<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\CreditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class RelatorioCompletoController extends Controller
{
    public function __construct(
        protected CreditService $creditService
    ) {}

    /**
     * Recebe o payload completo do relatório e retorna dados formatados para exibição.
     * Aceita autenticação via token (header X-API-Token) ou sessão (para frontend).
     */
    public function confirmarRelatorioCompleto(Request $request)
    {
        try {
            Log::info('Requisição recebida em confirmarRelatorioCompleto', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'ip' => $request->ip(),
                'headers' => [
                    'x-api-token' => $request->hasHeader('X-API-Token') ? 'presente' : 'ausente',
                    'content-type' => $request->header('Content-Type'),
                ],
                'body' => $request->all(),
            ]);
            
            // Sanitizar resume_url se presente (remover caracteres inválidos no início/fim)
            if ($request->has('resume_url')) {
                $resumeUrl = trim($request->input('resume_url'));
                // Remove caracteres inválidos no início (como {, [, espaços, etc)
                $resumeUrl = preg_replace('/^[{\[\s]+/', '', $resumeUrl);
                // Remove caracteres inválidos no fim
                $resumeUrl = preg_replace('/[}\]\s]+$/', '', $resumeUrl);
                $request->merge(['resume_url' => $resumeUrl]);
            }
            
            // Verifica autenticação via token ou sessão
            $user = $this->authenticate($request);
            
            if (!$user) {
                Log::warning('Autenticação falhou', [
                    'has_token' => $request->hasHeader('X-API-Token'),
                    'has_session' => Auth::check(),
                    'token_valido' => $this->isTokenValid($request),
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Não autenticado. Forneça um token válido (X-API-Token) ou faça login.',
                ], Response::HTTP_UNAUTHORIZED);
            }

            // Validação do payload
            $validationRules = [
                'resume_url' => 'required|url',
                'valor_total_consulta' => 'required|numeric|min:0',
                'qtd_participantes_unicos' => 'nullable|integer|min:0',
                'custo_unitario' => 'nullable|numeric|min:0',
                'user_id' => 'nullable|integer|exists:users,id', // user_id agora é opcional
            ];
            
            $validated = $request->validate($validationRules, [
                'resume_url.required' => 'O campo resume_url é obrigatório.',
                'resume_url.url' => 'O campo resume_url deve ser uma URL válida.',
                'valor_total_consulta.required' => 'O campo valor_total_consulta é obrigatório.',
                'valor_total_consulta.numeric' => 'O campo valor_total_consulta deve ser um número.',
                'valor_total_consulta.min' => 'O campo valor_total_consulta deve ser maior ou igual a zero.',
                'qtd_participantes_unicos.integer' => 'O campo qtd_participantes_unicos deve ser um número inteiro.',
                'qtd_participantes_unicos.min' => 'O campo qtd_participantes_unicos deve ser maior ou igual a zero.',
                'custo_unitario.numeric' => 'O campo custo_unitario deve ser um número.',
                'custo_unitario.min' => 'O campo custo_unitario deve ser maior ou igual a zero.',
                'user_id.integer' => 'O campo user_id deve ser um número inteiro.',
                'user_id.exists' => 'O usuário especificado não existe.',
            ]);
            
            // Se user_id foi fornecido e é diferente do usuário autenticado, usar o fornecido
            if (isset($validated['user_id']) && $validated['user_id'] != $user->id) {
                $originalUserId = $user->id;
                $requestedUser = User::find($validated['user_id']);
                if ($requestedUser) {
                    $user = $requestedUser;
                    Log::info('Usuário alterado conforme user_id fornecido', [
                        'user_id_original' => $originalUserId,
                        'user_id_fornecido' => $validated['user_id'],
                    ]);
                }
            }

            // Buscar saldo de créditos do usuário
            $saldoAtual = $this->creditService->getBalance($user);

            // Calcular valor de créditos necessários
            $valorTotalConsulta = (float) $validated['valor_total_consulta'];
            $valorCreditosNecessarios = $valorTotalConsulta;

            // Verificar se tem créditos suficientes
            $temCreditosSuficientes = $this->creditService->hasEnough($user, $valorCreditosNecessarios);

            Log::info('API confirmar-relatorio-completo processada com sucesso', [
                'user_id' => $user->id,
                'saldo_atual' => $saldoAtual,
                'valor_total_consulta' => $valorTotalConsulta,
                'valor_creditos_necessarios' => $valorCreditosNecessarios,
                'tem_creditos_suficientes' => $temCreditosSuficientes,
            ]);

            // Preparar dados de resposta
            $data = [
                'resume_url' => $validated['resume_url'],
                'valor_total_consulta' => $valorTotalConsulta,
                'valor_creditos_necessarios' => $valorCreditosNecessarios,
                'qtd_participantes_unicos' => isset($validated['qtd_participantes_unicos']) 
                    ? (int) $validated['qtd_participantes_unicos'] 
                    : null,
                'custo_unitario' => isset($validated['custo_unitario']) 
                    ? (float) $validated['custo_unitario'] 
                    : null,
                'credits' => [
                    'saldo_atual' => $saldoAtual,
                    'necessario' => $valorCreditosNecessarios,
                    'suficiente' => $temCreditosSuficientes,
                ],
                'actions' => [
                    'confirm_url' => '/app/credits/confirm',
                    'cancel_url' => '/app/credits/cancel',
                ],
            ];

            // Se não tem créditos suficientes, adicionar flag e mensagem
            if (!$temCreditosSuficientes) {
                return response()->json([
                    'success' => true,
                    'data' => $data,
                    'insufficient_credits' => true,
                    'message' => 'Créditos insuficientes. Entre em contato pelo telefone (67) 99984-4366 para adquirir mais créditos.',
                ], Response::HTTP_OK);
            }

            return response()->json([
                'success' => true,
                'data' => $data,
            ], Response::HTTP_OK);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Erro de validação na API', [
                'errors' => $e->errors(),
                'request_data' => $request->all(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Erro de validação.',
                'errors' => $e->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
            
        } catch (\Exception $e) {
            Log::error('Erro inesperado na API confirmar-relatorio-completo', [
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
     * Autentica via token API ou sessão.
     * Retorna o usuário autenticado ou null.
     */
    private function authenticate(Request $request): ?User
    {
        // Tenta autenticação via token API (para n8n)
        $apiToken = $request->header('X-API-Token') ?? $request->input('api_token');
        $expectedToken = config('services.api.token');
        
        if (!empty($apiToken) && !empty($expectedToken) && $apiToken === $expectedToken) {
            // Token válido - busca user_id no payload (opcional)
            $userId = $request->input('user_id');
            if ($userId) {
                $user = User::find($userId);
                if ($user) {
                    Log::info('Usuário autenticado via token API com user_id', ['user_id' => $userId]);
                    return $user;
                }
                Log::warning('User ID não encontrado', ['user_id' => $userId]);
            }
            
            // Se não tiver user_id, tenta pegar da sessão
            $user = Auth::user();
            if ($user) {
                Log::info('Usuário autenticado via token API com sessão', ['user_id' => $user->id]);
                return $user;
            }
            
            // Se não tiver user_id nem sessão, busca o primeiro usuário admin ou o primeiro usuário
            $user = User::orderBy('id')->first();
            if ($user) {
                Log::info('Usuário autenticado via token API (fallback para primeiro usuário)', ['user_id' => $user->id]);
                return $user;
            }
            
            Log::warning('Token válido mas nenhum usuário encontrado no sistema');
            return null;
        }
        
        // Fallback: autenticação via sessão (para frontend)
        return Auth::user();
    }
    
    /**
     * Verifica se o token API é válido.
     */
    private function isTokenValid(Request $request): bool
    {
        $apiToken = $request->header('X-API-Token') ?? $request->input('api_token');
        $expectedToken = config('services.api.token');
        return !empty($apiToken) && !empty($expectedToken) && $apiToken === $expectedToken;
    }
}

