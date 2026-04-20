<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\MonitoramentoAssinatura;
use App\Models\MonitoramentoConsulta;
use App\Models\MonitoramentoPlano;
use App\Models\Participante;
use App\Services\CreditService;
use App\Services\PricingCatalogService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class MonitoramentoController extends Controller
{
    private const AUTH_VIEW_PREFIX = 'autenticado.monitoramento.';

    private const AUTH_LAYOUT_VIEW = 'autenticado.layouts.app';

    public function __construct(
        protected CreditService $creditService,
        protected PricingCatalogService $pricingCatalogService,
    ) {}

    /**
     * Página instrucional com detalhes dos planos de consulta.
     */
    public function planos(Request $request)
    {
        $planosView = self::AUTH_VIEW_PREFIX.'planos';

        if (! view()->exists($planosView)) {
            abort(404);
        }

        if (! Auth::check()) {
            return $this->redirectToLogin($request);
        }

        $user = Auth::user();

        $data = [
            'credits' => $this->creditService->getBalance($user),
            'planos' => MonitoramentoPlano::ativos(),
            'hasMadeFirstPurchase' => $this->pricingCatalogService->userHasFirstPurchase($user),
            'firstPurchaseLockedProducts' => $this->pricingCatalogService->getFirstPurchaseLockedProducts(),
        ];

        if ($this->isAjaxRequest($request)) {
            $renderedView = view($planosView, $data)->render();

            return response($renderedView)->header('Content-Type', 'text/html');
        }

        return view(self::AUTH_LAYOUT_VIEW, array_merge([
            'initialView' => $planosView,
        ], $data));
    }

    /**
     * Histórico de consultas realizadas.
     */
    public function historico(Request $request)
    {
        $historicoView = self::AUTH_VIEW_PREFIX.'historico';

        if (! view()->exists($historicoView)) {
            abort(404);
        }

        if (! Auth::check()) {
            return $this->redirectToLogin($request);
        }

        $user = Auth::user();
        $userId = (int) $user->id;

        // Buscar consultas com relacionamentos
        $consultas = MonitoramentoConsulta::where('user_id', $userId)
            ->with(['participante', 'plano', 'assinatura'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $data = [
            'consultas' => $consultas,
            'planos' => MonitoramentoPlano::ativos(),
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
     * Monitoramento de clientes - visualiza status dos clientes monitorados.
     */
    public function clientes(Request $request)
    {
        $clientesView = self::AUTH_VIEW_PREFIX.'clientes';

        if (! view()->exists($clientesView)) {
            abort(404);
        }

        if (! Auth::check()) {
            return $this->redirectToLogin($request);
        }

        $user = Auth::user();

        $data = [
            'credits' => $this->creditService->getBalance($user),
        ];

        if ($this->isAjaxRequest($request)) {
            $renderedView = view($clientesView, $data)->render();

            return response($renderedView)->header('Content-Type', 'text/html');
        }

        return view(self::AUTH_LAYOUT_VIEW, array_merge([
            'initialView' => $clientesView,
        ], $data));
    }

    /**
     * Detalhes de uma consulta específica (retorna JSON).
     */
    public function consultaDetalhes(Request $request, $id)
    {
        if (! Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Usuário não autenticado.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $user = Auth::user();

        $consulta = MonitoramentoConsulta::where('id', $id)
            ->where('user_id', $user->id)
            ->with(['participante', 'plano'])
            ->first();

        if (! $consulta) {
            return response()->json([
                'success' => false,
                'message' => 'Consulta não encontrada.',
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'success' => true,
            'id' => $consulta->id,
            'tipo' => $consulta->tipo,
            'status' => $consulta->status,
            'creditos_cobrados' => $consulta->creditos_cobrados,
            'executado_em' => $consulta->executado_em?->format('d/m/Y H:i'),
            'created_at' => $consulta->created_at->format('d/m/Y H:i'),
            'plano' => $consulta->plano ? [
                'nome' => $consulta->plano->nome,
                'codigo' => $consulta->plano->codigo,
            ] : null,
            'resultado' => $consulta->resultado ?? [
                'cnpj' => $consulta->participante?->documento,
                'razao_social' => $consulta->participante?->razao_social,
                'situacao_cadastral' => $consulta->participante?->situacao_cadastral,
                'regime_tributario' => $consulta->participante?->regime_tributario,
                'detalhes' => [],
            ],
            'error_message' => $consulta->error_message,
        ]);
    }

    /**
     * Adiciona CNPJs avulsos para monitoramento.
     */
    public function adicionarCnpj(Request $request)
    {
        if (! Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Usuário não autenticado.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $user = Auth::user();
        $cnpjsInput = $request->input('cnpjs', '');

        // Aceita string separada por vírgula, quebra de linha ou array
        if (is_string($cnpjsInput)) {
            $cnpjs = preg_split('/[,;\n\r]+/', $cnpjsInput);
        } else {
            $cnpjs = $cnpjsInput;
        }

        $cnpjs = array_filter(array_map(function ($cnpj) {
            return preg_replace('/[^0-9]/', '', trim($cnpj));
        }, $cnpjs), function ($cnpj) {
            return strlen($cnpj) === 14;
        });

        if (empty($cnpjs)) {
            return response()->json([
                'success' => false,
                'message' => 'Nenhum CNPJ válido informado.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $adicionados = 0;
        $duplicados = 0;

        try {
            DB::beginTransaction();

            foreach ($cnpjs as $cnpj) {
                // Verificar se já existe
                $existente = Participante::where('user_id', $user->id)
                    ->where('documento', $cnpj)
                    ->first();

                if ($existente) {
                    $duplicados++;

                    continue;
                }

                Participante::create([
                    'user_id' => $user->id,
                    'documento' => $cnpj,
                    'origem_tipo' => 'MANUAL',
                ]);

                $adicionados++;
            }

            DB::commit();

            Log::info('CNPJs avulsos adicionados', [
                'user_id' => $user->id,
                'adicionados' => $adicionados,
                'duplicados' => $duplicados,
            ]);

            $message = $adicionados.' CNPJ(s) adicionado(s) com sucesso.';
            if ($duplicados > 0) {
                $message .= ' '.$duplicados.' já existiam e foram ignorados.';
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'adicionados' => $adicionados,
                'duplicados' => $duplicados,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao adicionar CNPJs', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao adicionar CNPJs. Tente novamente.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Cria assinatura de monitoramento para um ou mais participantes.
     */
    public function criarAssinatura(Request $request)
    {
        if (! Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Usuário não autenticado.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $user = Auth::user();

        // Aceita participante_id (único) ou participantes (array)
        $participanteId = $request->input('participante_id');
        $participanteIds = $request->input('participantes', []);
        $planoId = $request->input('plano_id');
        $frequencia = $request->input('frequencia', 'quinzenal');

        // Se participante_id foi passado, converter para array
        if ($participanteId && empty($participanteIds)) {
            $participanteIds = [$participanteId];
        }

        if (empty($participanteIds) || empty($planoId)) {
            return response()->json([
                'success' => false,
                'error' => 'Dados incompletos. Selecione participantes e um plano.',
            ], Response::HTTP_BAD_REQUEST);
        }

        // Validar frequência
        $frequenciasValidas = ['diario', 'semanal', 'quinzenal', 'mensal'];
        if (! in_array($frequencia, $frequenciasValidas)) {
            return response()->json([
                'success' => false,
                'error' => 'Frequência inválida.',
            ], Response::HTTP_BAD_REQUEST);
        }

        // Buscar plano
        $plano = MonitoramentoPlano::find($planoId);
        if (! $plano || ! $plano->is_active) {
            return response()->json([
                'success' => false,
                'error' => 'Plano não encontrado ou inativo.',
            ], Response::HTTP_NOT_FOUND);
        }

        try {
            DB::beginTransaction();

            $assinaturasCriadas = 0;
            $jaExistentes = 0;

            foreach ($participanteIds as $pId) {
                // Verificar se participante pertence ao usuário
                $participante = Participante::where('id', $pId)
                    ->where('user_id', $user->id)
                    ->first();

                if (! $participante) {
                    continue;
                }

                // Verificar se já existe assinatura ativa/pausada
                $assinaturaExistente = MonitoramentoAssinatura::where('participante_id', $participante->id)
                    ->where('user_id', $user->id)
                    ->whereIn('status', ['ativo', 'pausado'])
                    ->first();

                if ($assinaturaExistente) {
                    $jaExistentes++;

                    continue;
                }

                // Converter frequência para dias
                $frequenciaDias = $this->frequenciaParaDias($frequencia);
                $proximaExecucao = Carbon::now()->addDays($frequenciaDias)->setTime(8, 0, 0);

                // Criar assinatura
                MonitoramentoAssinatura::create([
                    'user_id' => $user->id,
                    'participante_id' => $participante->id,
                    'plano_id' => $plano->id,
                    'frequencia_dias' => $frequenciaDias,
                    'status' => 'ativo',
                    'proxima_execucao_em' => $proximaExecucao,
                ]);

                $assinaturasCriadas++;
            }

            DB::commit();

            Log::info('Assinatura(s) criada(s)', [
                'user_id' => $user->id,
                'criadas' => $assinaturasCriadas,
                'ja_existentes' => $jaExistentes,
                'plano_id' => $planoId,
                'frequencia' => $frequencia,
            ]);

            if ($assinaturasCriadas === 0 && $jaExistentes > 0) {
                return response()->json([
                    'success' => false,
                    'error' => 'Já existe assinatura ativa para este(s) participante(s).',
                ], Response::HTTP_CONFLICT);
            }

            return response()->json([
                'success' => true,
                'message' => $assinaturasCriadas.' assinatura(s) criada(s) com sucesso.',
                'criadas' => $assinaturasCriadas,
                'ja_existentes' => $jaExistentes,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao criar assinatura', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erro ao criar assinatura. Tente novamente.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Pausa uma assinatura de monitoramento.
     */
    public function pausarAssinatura(Request $request, $id)
    {
        if (! Auth::check()) {
            return response()->json([
                'success' => false,
                'error' => 'Usuário não autenticado.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $user = Auth::user();

        $assinatura = MonitoramentoAssinatura::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (! $assinatura) {
            return response()->json([
                'success' => false,
                'error' => 'Assinatura não encontrada.',
            ], Response::HTTP_NOT_FOUND);
        }

        if ($assinatura->status !== 'ativo') {
            return response()->json([
                'success' => false,
                'error' => 'Apenas assinaturas ativas podem ser pausadas.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $assinatura->update(['status' => 'pausado']);

        Log::info('Assinatura pausada', [
            'user_id' => $user->id,
            'assinatura_id' => $id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Assinatura pausada com sucesso.',
        ]);
    }

    /**
     * Reativa uma assinatura pausada.
     */
    public function reativarAssinatura(Request $request, $id)
    {
        if (! Auth::check()) {
            return response()->json([
                'success' => false,
                'error' => 'Usuário não autenticado.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $user = Auth::user();

        $assinatura = MonitoramentoAssinatura::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (! $assinatura) {
            return response()->json([
                'success' => false,
                'error' => 'Assinatura não encontrada.',
            ], Response::HTTP_NOT_FOUND);
        }

        if ($assinatura->status !== 'pausado') {
            return response()->json([
                'success' => false,
                'error' => 'Apenas assinaturas pausadas podem ser reativadas.',
            ], Response::HTTP_BAD_REQUEST);
        }

        // Recalcular próxima execução baseado em frequencia_dias
        $proximaExecucao = Carbon::now()->addDays($assinatura->frequencia_dias)->setTime(8, 0, 0);

        $assinatura->update([
            'status' => 'ativo',
            'proxima_execucao_em' => $proximaExecucao,
        ]);

        Log::info('Assinatura reativada', [
            'user_id' => $user->id,
            'assinatura_id' => $id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Assinatura reativada com sucesso.',
        ]);
    }

    /**
     * Cancela uma assinatura de monitoramento.
     */
    public function cancelarAssinatura(Request $request, $id)
    {
        if (! Auth::check()) {
            return response()->json([
                'success' => false,
                'error' => 'Usuário não autenticado.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $user = Auth::user();

        $assinatura = MonitoramentoAssinatura::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (! $assinatura) {
            return response()->json([
                'success' => false,
                'error' => 'Assinatura não encontrada.',
            ], Response::HTTP_NOT_FOUND);
        }

        if ($assinatura->status === 'cancelado') {
            return response()->json([
                'success' => false,
                'error' => 'Assinatura já foi cancelada.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $assinatura->update([
            'status' => 'cancelado',
        ]);

        Log::info('Assinatura cancelada', [
            'user_id' => $user->id,
            'assinatura_id' => $id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Assinatura cancelada com sucesso.',
        ]);
    }

    /**
     * Converte frequência textual para número de dias.
     */
    private function frequenciaParaDias(string $frequencia): int
    {
        return match ($frequencia) {
            'diario' => 1,
            'semanal' => 7,
            'quinzenal' => 15,
            'mensal' => 30,
            default => 15,
        };
    }

    /**
     * SSE para acompanhar resultado de consultas em tempo real.
     * Verifica o banco de dados para consultas que foram concluídas.
     *
     * GET /app/monitoramento/consulta/stream
     *
     * Query params:
     * - consultas: IDs das consultas separados por vírgula (ex: "1,2,3")
     */
    public function streamConsultas(Request $request)
    {
        if (! Auth::check()) {
            return response()->json([
                'success' => false,
                'error' => 'Usuário não autenticado.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $user = Auth::user();
        $consultasIds = $request->query('consultas', '');

        // Parse IDs das consultas
        $ids = array_filter(array_map('intval', explode(',', $consultasIds)));

        if (empty($ids)) {
            return response()->json([
                'success' => false,
                'error' => 'Nenhuma consulta especificada.',
            ], Response::HTTP_BAD_REQUEST);
        }

        return response()->stream(function () use ($user, $ids) {
            $tentativas = 0;
            $maxTentativas = 300; // 5 minutos
            $consultasConcluidas = [];

            // Enviar comentário inicial
            echo ": SSE connection established for monitoramento consultas\n\n";
            if (ob_get_level() > 0) {
                ob_flush();
            }
            flush();

            while ($tentativas < $maxTentativas) {
                try {
                    // Buscar consultas que ainda estão pendentes ou processando
                    $consultas = MonitoramentoConsulta::where('user_id', $user->id)
                        ->whereIn('id', $ids)
                        ->whereNotIn('id', $consultasConcluidas)
                        ->get();

                    foreach ($consultas as $consulta) {
                        // Se a consulta foi concluída (sucesso ou erro)
                        if (in_array($consulta->status, ['sucesso', 'erro'])) {
                            $data = [
                                'type' => $consulta->status === 'sucesso' ? 'consulta_sucesso' : 'consulta_erro',
                                'consulta_id' => $consulta->id,
                                'participante_id' => $consulta->participante_id,
                                'status' => $consulta->status,
                                'situacao_geral' => $consulta->situacao_geral,
                                'tem_pendencias' => $consulta->tem_pendencias,
                                'executado_em' => $consulta->executado_em?->toIso8601String(),
                            ];

                            if ($consulta->status === 'erro') {
                                $data['error_code'] = $consulta->error_code;
                                $data['error_message'] = $consulta->error_message;
                            }

                            echo 'data: '.json_encode($data)."\n\n";
                            $consultasConcluidas[] = $consulta->id;

                            Log::info('SSE: Consulta concluída notificada', [
                                'user_id' => $user->id,
                                'consulta_id' => $consulta->id,
                                'status' => $consulta->status,
                            ]);
                        }
                    }

                    // Se todas as consultas foram concluídas, encerrar
                    if (count($consultasConcluidas) >= count($ids)) {
                        echo 'data: '.json_encode(['type' => 'complete', 'message' => 'Todas as consultas concluídas'])."\n\n";
                        if (ob_get_level() > 0) {
                            ob_flush();
                        }
                        flush();
                        break;
                    }

                    if (ob_get_level() > 0) {
                        ob_flush();
                    }
                    flush();

                    sleep(2); // Verifica a cada 2 segundos
                    $tentativas++;

                    // Verificar se a conexão ainda está ativa
                    if (connection_aborted()) {
                        break;
                    }
                } catch (\Exception $e) {
                    Log::error('SSE: Erro no stream de consultas', [
                        'user_id' => $user->id,
                        'error' => $e->getMessage(),
                    ]);
                    sleep(2);
                    $tentativas++;
                }
            }

            // Se chegou no limite, encerra
            if ($tentativas >= $maxTentativas) {
                echo 'data: '.json_encode(['type' => 'timeout', 'error' => 'Tempo limite atingido'])."\n\n";
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
