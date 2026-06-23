<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Concerns\RespondeAjax;
use App\Http\Controllers\Controller;
use App\Models\CatalogoAlertaDescarte;
use App\Models\Cliente;
use App\Services\Catalogo\AlertaCatalogoDescarteService;
use App\Services\Catalogo\NotaItemUnificadoService;
use App\Services\Catalogo\ReconciliacaoXmlEfdService;
use App\Support\Cfop;
use App\Support\CsvExport;
use App\Support\PdfReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BiCatalogoItensController extends Controller
{
    use RespondeAjax;

    private const AUTH_LAYOUT_VIEW = 'autenticado.layouts.app';

    public function __construct(
        private NotaItemUnificadoService $service,
        private ReconciliacaoXmlEfdService $reconciliacao,
        private AlertaCatalogoDescarteService $descartes,
    ) {}

    public function index(Request $request)
    {
        $view = 'autenticado.bi.catalogo-itens';

        if (! Auth::check()) {
            if ($this->isAjaxRequest($request)) {
                return response('Não autenticado', 401);
            }

            return redirect()->route('login');
        }

        $userId = (int) Auth::id();

        $filtros = $this->montarFiltros($request);

        // Opções dos filtros (universo do usuário); ignora a própria seleção cfop/cst.
        $facetas = $this->service->facetas($userId, $filtros);
        $cfopOpcoes = array_map(fn (string $codigo) => $this->rotularCfop($codigo), $facetas['cfops']);

        $itens = $this->service->itensAgregados($userId, $filtros);

        $mostrarDispensados = $request->boolean('dispensados');
        $descNcm = $this->descartes->descartados($userId, 'ncm_divergente');
        $descSem = $this->descartes->descartados($userId, 'sem_catalogo');

        // Divergência é sempre XML×catálogo (não-deduplicado); o filtro `fonte` não se aplica a ela.
        $divergencias = collect($this->service->divergenciasNcmPorItem($userId, $filtros))
            ->filter(fn ($d) => $d['ncm_divergente'])
            ->map(fn ($d, $cod) => array_merge([
                'codigo_item' => (string) $cod,
                'dispensado' => in_array((string) $cod, $descNcm, true),
            ], $d))
            ->values();

        $semCatalogo = $this->service->itensSemCatalogo($userId, $filtros)
            ->map(fn ($i) => array_merge($i, ['dispensado' => in_array($i['codigo_item'], $descSem, true)]))
            ->values();

        $kpis = [
            'total_itens' => $itens->count(),
            'com_catalogo' => $itens->where('tem_catalogo', true)->count(),
            // contagens dos alertas refletem só os ATIVOS (não dispensados)
            'sem_catalogo' => $semCatalogo->where('dispensado', false)->count(),
            'ncm_revisar' => $divergencias->where('dispensado', false)->count(),
            'valor_movimentado' => (float) $itens->sum('valor_total'),
            'sem_ncm' => $itens->filter(fn ($i) => empty($i['ncm']))->count(),
        ];

        $totalDispensados = $divergencias->where('dispensado', true)->count()
            + $semCatalogo->where('dispensado', true)->count();

        // sem o toggle, os painéis escondem os dispensados
        $divergenciasView = $mostrarDispensados ? $divergencias : $divergencias->where('dispensado', false)->values();
        $semCatalogoView = $mostrarDispensados ? $semCatalogo : $semCatalogo->where('dispensado', false)->values();

        $reconciliacao = $this->reconciliacao->resumo($userId, $filtros);

        $clientes = Cliente::where('user_id', $userId)->orderByDesc('is_empresa_propria')->orderBy('razao_social')->get(['id', 'razao_social']);

        $data = ['itens' => $itens, 'kpis' => $kpis, 'clientes' => $clientes, 'filtros' => $filtros,
            'facetas' => $facetas, 'cfopOpcoes' => $cfopOpcoes,
            'divergencias' => $divergenciasView, 'semCatalogo' => $semCatalogoView, 'reconciliacao' => $reconciliacao,
            'mostrarDispensados' => $mostrarDispensados, 'totalDispensados' => $totalDispensados];

        if ($this->isAjaxRequest($request)) {
            return response(view($view, $data)->render())->header('Content-Type', 'text/html');
        }

        return view(self::AUTH_LAYOUT_VIEW, array_merge(['initialView' => $view], $data));
    }

    /**
     * Exporta a tabela de itens (resultado do filtro atual, ou todos se sem filtro) em CSV.
     * Gate: entitlement `export` (rota). Mesmos filtros do index → relatório == tela.
     */
    public function exportarCsv(Request $request)
    {
        if (! Auth::check()) {
            return redirect()->route('login');
        }

        $itens = $this->service->itensAgregados((int) Auth::id(), $this->montarFiltros($request));
        $filename = 'catalogo-itens-'.now()->format('Ymd-His').'.csv';

        return CsvExport::download($filename, $this->colunasExport(), $this->linhasExport($itens));
    }

    /**
     * Exporta a tabela de itens (resultado do filtro atual, ou todos se sem filtro) em PDF.
     * Gate: entitlement `export` (rota).
     */
    public function exportarPdf(Request $request)
    {
        if (! Auth::check()) {
            return redirect()->route('login');
        }

        $userId = (int) Auth::id();
        $filtros = $this->montarFiltros($request);
        $itens = $this->service->itensAgregados($userId, $filtros);

        $dados = [
            'itens' => $itens,
            'resumoFiltros' => $this->resumoFiltros($userId, $filtros),
            'totalValor' => (float) $itens->sum('valor_total'),
            'geradoEm' => now(),
        ];

        $pdf = PdfReport::render('autenticado.bi.catalogo-itens-pdf', $dados, 'landscape');

        return $pdf->download('catalogo-itens-'.now()->format('Ymd-His').'.pdf');
    }

    /**
     * Constrói o mapa de filtros canônico (compartilhado entre tela e exportações).
     *
     * @return array<string,mixed>
     */
    private function montarFiltros(Request $request): array
    {
        $filtros = array_filter([
            'cliente_id' => $request->integer('cliente_id') ?: null,
            'periodo_de' => $request->input('periodo_de') ?: null,
            'periodo_ate' => $request->input('periodo_ate') ?: null,
            'fonte' => in_array($request->input('fonte'), ['efd', 'xml', 'ambas'], true) ? $request->input('fonte') : null,
        ]);

        // CFOP/CST: multi-select (1+). Saneados (só dígitos / alfanumérico) antes de ir pro IN(...).
        if ($cfops = $this->parseLista($request->input('cfops'), '/\D/')) {
            $filtros['cfops'] = $cfops;
        }
        if ($csts = $this->parseLista($request->input('csts'), '/[^0-9A-Za-z]/')) {
            $filtros['csts'] = $csts;
        }

        return $filtros;
    }

    /** @return list<string> */
    private function colunasExport(): array
    {
        return ['Código', 'Descrição', 'Origem', 'NCM', 'CFOPs', 'CSTs', 'Quantidade', 'Ocorrências', 'Alíq. média %', 'Valor movimentado', 'Catálogo'];
    }

    /**
     * @param  \Illuminate\Support\Collection<int,array<string,mixed>>  $itens
     * @return list<list<string>>
     */
    private function linhasExport($itens): array
    {
        return $itens->map(fn ($i) => [
            (string) $i['codigo_item'],
            (string) ($i['descricao'] ?? ''),
            (string) $i['fontes'],
            (string) ($i['ncm'] ?? ''),
            (string) ($i['cfops'] ?? ''),
            (string) ($i['csts'] ?? ''),
            number_format((float) $i['quantidade'], 0, ',', '.'),
            (string) $i['ocorrencias'],
            $i['aliquota_media'] !== null ? number_format((float) $i['aliquota_media'], 2, ',', '.') : '',
            number_format((float) $i['valor_total'], 2, ',', '.'),
            $i['tem_catalogo'] ? ($i['catalogo']['descr_item'] ?? 'Sim') : 'Sem catálogo',
        ])->values()->all();
    }

    /**
     * Resumo legível dos filtros aplicados (cabeçalho do PDF). Resolve cliente_id → razão social.
     *
     * @param  array<string,mixed>  $filtros
     * @return list<array{rotulo:string,valor:string}>
     */
    private function resumoFiltros(int $userId, array $filtros): array
    {
        $linhas = [];
        if (! empty($filtros['cliente_id'])) {
            $cli = Cliente::where('user_id', $userId)->where('id', $filtros['cliente_id'])->value('razao_social');
            $linhas[] = ['rotulo' => 'Cliente', 'valor' => (string) ($cli ?: $filtros['cliente_id'])];
        }
        if (! empty($filtros['fonte'])) {
            $linhas[] = ['rotulo' => 'Fonte', 'valor' => strtoupper((string) $filtros['fonte'])];
        }
        if (! empty($filtros['periodo_de']) || ! empty($filtros['periodo_ate'])) {
            $linhas[] = ['rotulo' => 'Período', 'valor' => ($filtros['periodo_de'] ?? '…').' a '.($filtros['periodo_ate'] ?? '…')];
        }
        if (! empty($filtros['cfops'])) {
            $linhas[] = ['rotulo' => 'CFOP', 'valor' => implode(', ', $filtros['cfops'])];
        }
        if (! empty($filtros['csts'])) {
            $linhas[] = ['rotulo' => 'CST', 'valor' => implode(', ', $filtros['csts'])];
        }

        return $linhas;
    }

    /**
     * Normaliza um input multi-select numa lista de strings saneada (remove o que casar com $stripPattern),
     * sem vazios e sem repetição. Aceita só array (ignora qualquer outra forma do input).
     *
     * @return list<string>
     */
    private function parseLista(mixed $raw, string $stripPattern): array
    {
        return collect(is_array($raw) ? $raw : [])
            ->map(fn ($v) => preg_replace($stripPattern, '', (string) $v))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Rotula um CFOP para a opção do filtro: código + descrição CONFAZ + tipo (entrada/saída).
     * Cfop::descricao devolve "código — descrição" (ou só o código quando não há mapa).
     *
     * @return array{codigo:string,descricao:string,tipo:string}
     */
    private function rotularCfop(string $codigo): array
    {
        $full = Cfop::descricao($codigo);
        $descricao = str_contains($full, ' — ') ? trim(explode(' — ', $full, 2)[1]) : '';

        return ['codigo' => $codigo, 'descricao' => $descricao, 'tipo' => Cfop::tipoOperacao($codigo)];
    }

    /** Dispensa um alerta de catálogo (NCM a revisar / sem catálogo) para o usuário. */
    public function descartarAlerta(Request $request)
    {
        return $this->mutarDescarte($request, descartar: true);
    }

    /** Restaura (reativa) um alerta dispensado. */
    public function restaurarAlerta(Request $request)
    {
        return $this->mutarDescarte($request, descartar: false);
    }

    private function mutarDescarte(Request $request, bool $descartar)
    {
        if (! Auth::check()) {
            return response()->json(['error' => 'Não autenticado'], 401);
        }

        $tipo = (string) $request->input('tipo');
        $codigo = trim((string) $request->input('codigo_item'));

        if (! in_array($tipo, CatalogoAlertaDescarte::TIPOS, true) || $codigo === '') {
            return response()->json(['error' => 'Parâmetros inválidos'], 422);
        }

        $userId = (int) Auth::id();
        $descartar
            ? $this->descartes->descartar($userId, $tipo, $codigo)
            : $this->descartes->restaurar($userId, $tipo, $codigo);

        return response()->json(['ok' => true]);
    }
}
