<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Concerns\RespondeAjax;
use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Services\BiService;
use App\Support\CsvExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BiController extends Controller
{
    use RespondeAjax;

    private const AUTH_VIEW_PREFIX = 'autenticado.bi.';

    private const AUTH_LAYOUT_VIEW = 'autenticado.layouts.app';

    public function __construct(
        protected BiService $biService,
        protected \App\Services\BiExportService $biExport,
    ) {}

    /**
     * Dashboard principal de Analytics.
     */
    public function index(Request $request)
    {
        if (! Auth::check()) {
            return $this->redirectToLogin($request);
        }

        $userId = Auth::id();

        // Buscar clientes para filtro
        $clientes = Cliente::where('user_id', $userId)
            ->where('ativo', true)
            ->select('id', 'nome', 'documento', 'is_empresa_propria')
            ->orderByDesc('is_empresa_propria')
            ->orderBy('nome')
            ->get();

        // Resumo geral
        $resumo = $this->biService->getResumoGeral($userId);
        $resumoEfd = $this->biService->getKpisEfd($userId, null, null);

        $efdTotal = ($resumoEfd['total_entradas_notas'] ?? 0) + ($resumoEfd['total_saidas_notas'] ?? 0);
        $xmlTotal = $resumo['total_notas'] ?? 0;
        $defaultTab = ($efdTotal > 0 && $xmlTotal === 0) ? 'efd' : 'faturamento';

        $data = [
            'clientes' => $clientes,
            'resumo' => $resumo,
            'resumoEfd' => $resumoEfd,
            'cobertura' => $this->biService->getCoberturaResumo($userId, null, null, null),
            'defaultTab' => $defaultTab,
        ];

        return $this->render($request, 'index', $data);
    }

    /**
     * Dados de faturamento (para AJAX).
     */
    public function faturamento(Request $request)
    {
        if (! Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $userId = Auth::id();
        $dataInicio = $request->get('data_inicio');
        $dataFim = $request->get('data_fim');
        $clienteId = $request->get('cliente_id');

        $faturamento = $this->biService->getFaturamentoPorPeriodo($userId, $dataInicio, $dataFim, $clienteId);
        $topClientes = $this->biService->getTopClientes($userId, 10, $dataInicio, $dataFim, $clienteId);
        $faturamentoPorUf = $this->biService->getFaturamentoPorUf($userId, $dataInicio, $dataFim, $clienteId);

        return response()->json([
            'faturamento_mensal' => $faturamento,
            'top_clientes' => $topClientes,
            'faturamento_por_uf' => $faturamentoPorUf,
        ]);
    }

    /**
     * Dados de compras (para AJAX).
     */
    public function compras(Request $request)
    {
        if (! Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $userId = Auth::id();
        $dataInicio = $request->get('data_inicio');
        $dataFim = $request->get('data_fim');
        $clienteId = $request->get('cliente_id');

        $topFornecedores = $this->biService->getTopFornecedores($userId, 10, $dataInicio, $dataFim, $clienteId);
        $entradasVsSaidas = $this->biService->getEntradasVsSaidas($userId, $dataInicio, $dataFim, $clienteId);
        $devolucoes = $this->biService->getDevolucoes($userId, $dataInicio, $dataFim, $clienteId);

        return response()->json([
            'top_fornecedores' => $topFornecedores,
            'entradas_vs_saidas' => $entradasVsSaidas,
            'devolucoes' => $devolucoes,
        ]);
    }

    /**
     * Dados de tributos (para AJAX).
     */
    public function tributos(Request $request)
    {
        if (! Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $userId = Auth::id();
        $dataInicio = $request->get('data_inicio');
        $dataFim = $request->get('data_fim');
        $clienteId = $request->get('cliente_id');

        $cargaTributaria = $this->biService->getCargaTributaria($userId, $dataInicio, $dataFim, $clienteId);
        $tributosPorTipo = $this->biService->getTributosPorTipo($userId, $dataInicio, $dataFim, $clienteId);

        return response()->json([
            'carga_tributaria' => $cargaTributaria,
            'tributos_por_tipo' => $tributosPorTipo,
        ]);
    }

    /**
     * Dados EFD (para AJAX).
     */
    public function efd(Request $request)
    {
        if (! Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $userId = Auth::id();
        $dataInicio = $request->get('data_inicio');
        $dataFim = $request->get('data_fim');

        return response()->json([
            'kpis' => $this->biService->getKpisEfd($userId, $dataInicio, $dataFim),
            'fluxo_mensal' => $this->biService->getFluxoMensalEfd($userId, $dataInicio, $dataFim),
            'volume_blocos' => $this->biService->getVolumePorBlocoEfd($userId, $dataInicio, $dataFim),
            'top_fornecedores' => $this->biService->getTopParticipantesEfd($userId, 10, $dataInicio, $dataFim, 'E'),
            'top_clientes' => $this->biService->getTopParticipantesEfd($userId, 10, $dataInicio, $dataFim, 'S'),
            'tributos_por_tipo' => $this->biService->getTributosPorTipoEfd($userId, $dataInicio, $dataFim),
        ]);
    }

    /**
     * Ranking de participantes EFD (para AJAX).
     */
    public function participantes(Request $request)
    {
        if (! Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $userId = Auth::id();
        $dataInicio = $request->get('data_inicio');
        $dataFim = $request->get('data_fim');

        $fornecedores = $this->biService->getRankingParticipantes($userId, 'E', $dataInicio, $dataFim);
        $clientes = $this->biService->getRankingParticipantes($userId, 'S', $dataInicio, $dataFim);

        $calcConcentracao = function (array $lista) {
            $totalValor = array_sum(array_column($lista, 'total_valor'));
            $top5Valor = array_sum(array_column(array_slice($lista, 0, 5), 'total_valor'));
            $top5Pct = $totalValor > 0 ? round(($top5Valor / $totalValor) * 100, 1) : 0.0;

            return ['top5_percentual' => $top5Pct, 'top5_valor' => $top5Valor, 'total_valor' => $totalValor];
        };

        return response()->json([
            'fornecedores' => $fornecedores,
            'clientes' => $clientes,
            'concentracao' => [
                'fornecedores' => $calcConcentracao($fornecedores),
                'clientes' => $calcConcentracao($clientes),
            ],
        ]);
    }

    /**
     * Ficha detalhada de um participante EFD (para AJAX).
     */
    public function fichaParticipante(Request $request, int $id)
    {
        if (! Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $userId = Auth::id();
        $dataInicio = $request->get('data_inicio');
        $dataFim = $request->get('data_fim');

        $ficha = $this->biService->getFichaParticipante($userId, $id, $dataInicio, $dataFim);

        if (empty($ficha)) {
            return response()->json(['error' => 'Não encontrado'], 404);
        }

        return response()->json($ficha);
    }

    /**
     * Dados de riscos (para AJAX).
     */
    public function riscos(Request $request)
    {
        if (! Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $userId = Auth::id();
        $dataInicio = $request->get('data_inicio');
        $dataFim = $request->get('data_fim');

        return response()->json([
            'score_carteira' => $this->biService->getScoreCarteira($userId),
            'fornecedores_irregulares' => $this->biService->getFornecedoresIrregulares($userId, $dataInicio, $dataFim),
            'notas_em_risco' => $this->biService->getNotasEmRisco($userId, $dataInicio, $dataFim),
            'mudancas_regime' => $this->biService->getMudancasRegime($userId),
            'gap_importacoes' => $this->biService->getGapImportacoes($userId),
        ]);
    }

    /**
     * Dados tributários EFD (para AJAX).
     */
    public function tributarioEfd(Request $request)
    {
        if (! Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $userId = Auth::id();
        $dataInicio = $request->get('data_inicio');
        $dataFim = $request->get('data_fim');

        return response()->json([
            'consolidado' => $this->biService->getTributarioEfd($userId, $dataInicio, $dataFim),
            'mensal' => $this->biService->getTributarioMensalEfd($userId, $dataInicio, $dataFim),
            'aliquota' => $this->biService->getAliquotaEfetivaEfd($userId, $dataInicio, $dataFim),
            'por_regime' => $this->biService->getTributarioPorRegime($userId, $dataInicio, $dataFim),
        ]);
    }

    /**
     * Análise por CFOP — para AJAX.
     */
    public function cfop(Request $request)
    {
        if (! Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $userId = Auth::id();
        $ini = $request->get('data_inicio');
        $fim = $request->get('data_fim');
        $cli = $request->get('cliente_id');

        return response()->json([
            'ranking' => $this->biService->getCfopAnalitico($userId, $ini, $fim, $cli)['ranking'],
            'tendencia' => $this->biService->getCfopTendencia($userId, $ini, $fim, $cli, 5),
        ]);
    }

    /**
     * Apuração × Notas (gold) — para AJAX.
     */
    public function apuracaoNotas(Request $request)
    {
        if (! Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $userId = Auth::id();

        return response()->json($this->biService->getApuracaoVsNotas(
            $userId,
            $request->get('data_inicio'),
            $request->get('data_fim'),
            $request->get('cliente_id'),
        ));
    }

    /**
     * Export CSV por aba (UTF-8 BOM + separador ;).
     */
    public function exportar(Request $request)
    {
        if (! Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $aba = (string) $request->get('aba', '');
        $ds = $this->biExport->dataset($aba, Auth::id(), $request->get('data_inicio'), $request->get('data_fim'), $request->get('cliente_id'));

        if (empty($ds['colunas'])) {
            abort(404, 'Aba sem exportação disponível');
        }

        $filename = 'bi-'.$aba.'-'.now()->format('Ymd').'.csv';

        return CsvExport::download($filename, $ds['colunas'], $ds['linhas']);
    }

    public function exportarXlsx(Request $request)
    {
        if (! Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        if (! \App\Support\Reports\XlsxReport::disponivel()) {
            abort(503, 'Exportação XLSX indisponível');
        }

        [$ini, $fim] = $this->resolverPeriodo($request);
        $rel = $this->biExport->relatorioCompleto(
            Auth::id(), $ini, $fim, $request->get('cliente_id')
        );
        $filename = 'bi-fiscal-'.now()->format('Ymd').'.xlsx';

        return $this->comTokenDownload(app(\App\Services\Bi\Export\BiXlsxBuilder::class)->download($rel, $filename), $request);
    }

    public function exportarPdf(Request $request)
    {
        if (! Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        [$ini, $fim] = $this->resolverPeriodo($request);
        $rel = $this->biExport->relatorioCompleto(
            Auth::id(), $ini, $fim, $request->get('cliente_id')
        );
        $filename = 'bi-fiscal-'.now()->format('Ymd').'.pdf';

        return $this->comTokenDownload(
            \App\Support\PdfReport::render('reports.bi-executivo', ['relatorio' => $rel])->download($filename),
            $request
        );
    }

    /**
     * CSV do relatório completo em ZIP — 1 arquivo CSV por seção (CSV é 1 tabela;
     * o relatório tem 4 seções, então empacota em ZIP).
     */
    public function exportarCsvZip(Request $request)
    {
        if (! Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        [$ini, $fim] = $this->resolverPeriodo($request);
        $rel = $this->biExport->relatorioCompleto(Auth::id(), $ini, $fim, $request->get('cliente_id'));

        $arquivos = [
            'faturamento' => 'faturamento.csv',
            'tributos' => 'tributos.csv',
            'apuracao-notas' => 'declarado-x-computado.csv',
            'cfop' => 'cfop.csv',
        ];

        $tmp = tempnam(sys_get_temp_dir(), 'bicsvzip');
        if ($tmp === false) {
            throw new \RuntimeException('Falha ao criar arquivo temporário para o ZIP.');
        }

        $zip = new \ZipArchive;
        $zip->open($tmp, \ZipArchive::OVERWRITE);
        foreach ($arquivos as $aba => $nomeArquivo) {
            $sec = $rel['secoes'][$aba] ?? null;
            if (! $sec) {
                continue;
            }
            $zip->addFromString($nomeArquivo, CsvExport::build($sec['colunas'], $sec['linhas']));
        }
        $zip->close();

        $filename = 'bi-fiscal-'.now()->format('Ymd').'.csv.zip';

        return $this->comTokenDownload(
            response()->download($tmp, $filename, ['Content-Type' => 'application/zip'])->deleteFileAfterSend(true),
            $request
        );
    }

    /**
     * Anexa cookie `bi_download=<token>` à resposta de download quando o request traz
     * `download_token`. O frontend (iframe nativo + poll do cookie) usa isso para saber
     * que o arquivo chegou e esconder o spinner. httpOnly=false: o JS precisa ler.
     */
    private function comTokenDownload($response, Request $request)
    {
        $token = $request->get('download_token');
        if ($token) {
            $response->headers->setCookie(cookie('bi_download', (string) $token, 1, '/', null, null, false));
        }

        return $response;
    }

    /**
     * Resolve a janela [data_inicio, data_fim] do request. Aceita data_inicio/data_fim
     * explícitos OU o param `meses` (0/1/3/6/12, igual ao #filtro-periodo) computando
     * server-side — para os botões de export funcionarem por onclick inline sem
     * depender do bi.js cacheado.
     */
    private function resolverPeriodo(Request $request): array
    {
        $ini = $request->get('data_inicio');
        $fim = $request->get('data_fim');
        if ($ini || $fim) {
            return [$ini, $fim];
        }

        $meses = (int) $request->get('meses', 0);
        if ($meses > 0) {
            return [now()->subMonths($meses)->toDateString(), now()->toDateString()];
        }

        return [null, null];
    }

    /**
     * Resumo geral (para AJAX).
     */
    public function resumo(Request $request)
    {
        if (! Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $userId = Auth::id();
        $clienteId = $request->get('cliente_id');
        $dataInicio = $request->get('data_inicio');
        $dataFim = $request->get('data_fim');

        $resumo = $this->biService->getResumoGeral($userId, $clienteId, $dataInicio, $dataFim);
        // KPIs EFD (Saldo Líquido + barra secundária) também respeitam o período.
        $resumo['kpis_efd'] = $this->biService->getKpisEfd($userId, $dataInicio, $dataFim);
        $resumo['cobertura'] = $this->biService->getCoberturaResumo($userId, $dataInicio, $dataFim, $clienteId);

        return response()->json($resumo);
    }

    /**
     * Verifica se é requisição AJAX.
     */
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
}
