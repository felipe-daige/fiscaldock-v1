<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Concerns\RespondeAjax;
use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\EfdImportacao;
use App\Models\EfdNota;
use App\Models\Participante;
use App\Models\XmlNota;
use App\Services\NotaFiscalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotaFiscalController extends Controller
{
    use RespondeAjax;

    private const AUTH_LAYOUT_VIEW = 'autenticado.layouts.app';

    public function __construct(private NotaFiscalService $service) {}

    public function index(Request $request)
    {
        $view = 'autenticado.notas.index';

        if (! Auth::check()) {
            return $this->redirectToLogin($request);
        }

        $userId = (int) Auth::id();

        $filtros = $request->only([
            'origem', 'data_inicio', 'data_fim', 'tipo_operacao',
            'modelo', 'cliente_id', 'participante_id', 'importacao_id', 'busca',
        ]);

        $perPage = 25;
        $page = max(1, (int) $request->get('page', 1));

        $notas = $this->service->listarUnificadas($userId, $filtros, $perPage, $page);
        $kpis = $this->service->calcularKpis($userId, $filtros);

        $clientes = Cliente::where('user_id', $userId)
            ->orderBy('razao_social')
            ->get(['id', 'razao_social']);

        $participantes = Participante::where('user_id', $userId)
            ->orderBy('razao_social')
            ->get(['id', 'razao_social', 'documento']);

        $importacoes = EfdImportacao::where('user_id', $userId)
            ->where('status', 'concluido')
            ->orderByDesc('created_at')
            ->get(['id', 'filename', 'tipo_efd', 'created_at']);

        $data = [
            'notas' => $notas,
            'kpis' => $kpis,
            'clientes' => $clientes,
            'participantes' => $participantes,
            'importacoes' => $importacoes,
            'filtros' => $filtros,
        ];

        if ($this->isAjaxRequest($request)) {
            return response(view($view, $data)->render())->header('Content-Type', 'text/html');
        }

        return view(self::AUTH_LAYOUT_VIEW, array_merge(['initialView' => $view], $data));
    }

    public function detalhes(Request $request, string $origem, int $id)
    {
        if (! Auth::check()) {
            return response('Não autenticado', 401);
        }

        $userId = (int) Auth::id();

        if ($origem === 'efd') {
            $nota = EfdNota::where('id', $id)
                ->where('user_id', $userId)
                ->with(['participante', 'itens', 'cliente', 'consolidados'])
                ->first();

            if (! $nota) {
                return response('Nota não encontrada', 404);
            }

            if ($this->isAjaxRequest($request)) {
                // Drill-down da listagem pede o card compacto (header X-Nota-Detalhe: inline).
                // Navegação SPA (data-link) serve a página cheia, idêntica ao reload direto.
                $view = $this->querDetalheInline($request)
                    ? 'autenticado.notas.partials.efd-inline'
                    : 'autenticado.importacao.efd-nota';

                return view($view, compact('nota'));
            }

            return view(self::AUTH_LAYOUT_VIEW, [
                'initialView' => 'autenticado.importacao.efd-nota',
                'nota' => $nota,
            ]);
        }

        if ($origem === 'xml') {
            $nota = XmlNota::where('id', $id)
                ->where('user_id', $userId)
                ->with(['emitCliente', 'destCliente', 'cliente', 'itens'])
                ->first();

            if (! $nota) {
                return response('Nota não encontrada', 404);
            }

            if ($this->isAjaxRequest($request)) {
                $view = $this->querDetalheInline($request)
                    ? 'autenticado.notas.partials.xml-inline'
                    : 'autenticado.notas.xml-nota';

                return view($view, compact('nota'));
            }

            return view(self::AUTH_LAYOUT_VIEW, [
                'initialView' => 'autenticado.notas.xml-nota',
                'nota' => $nota,
            ]);
        }

        return response('Origem inválida', 400);
    }

    /**
     * O card compacto (efd-inline/xml-inline) só é servido quando o drill-down da
     * listagem o pede explicitamente via header. Navegação SPA recebe a página cheia.
     */
    private function querDetalheInline(Request $request): bool
    {
        return $request->header('X-Nota-Detalhe') === 'inline';
    }

    private function redirectToLogin(Request $request)
    {
        if ($this->isAjaxRequest($request)) {
            return response()->json([
                'success' => false,
                'message' => 'Você não está logado',
            ], 401);
        }

        return redirect()->route('login');
    }
}
