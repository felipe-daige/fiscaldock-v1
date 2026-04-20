<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\EfdNota;
use App\Models\Participante;
use App\Models\XmlNota;
use App\Services\NotaFiscalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotaFiscalController extends Controller
{
    private const AUTH_LAYOUT_VIEW = 'autenticado.layouts.app';

    public function __construct(private NotaFiscalService $service) {}

    public function index(Request $request)
    {
        $view = 'autenticado.notas-fiscais.index';

        if (! Auth::check()) {
            return $this->redirectToLogin($request);
        }

        $userId = (int) Auth::id();

        $filtros = $request->only([
            'origem', 'data_inicio', 'data_fim', 'tipo_operacao',
            'modelo', 'cliente_id', 'participante_id', 'busca',
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

        $data = [
            'notas' => $notas,
            'kpis' => $kpis,
            'clientes' => $clientes,
            'participantes' => $participantes,
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
                ->with(['participante', 'itens', 'cliente'])
                ->first();

            if (! $nota) {
                return response('Nota não encontrada', 404);
            }

            if ($this->isAjaxRequest($request)) {
                return view('autenticado.notas-fiscais.partials.efd-inline', compact('nota'));
            }

            return view(self::AUTH_LAYOUT_VIEW, [
                'initialView' => 'autenticado.importacao.efd-nota',
                'nota' => $nota,
            ]);
        }

        if ($origem === 'xml') {
            $nota = XmlNota::where('id', $id)
                ->where('user_id', $userId)
                ->with(['emitCliente', 'destCliente', 'cliente'])
                ->first();

            if (! $nota) {
                return response('Nota não encontrada', 404);
            }

            if ($this->isAjaxRequest($request)) {
                return view('autenticado.notas-fiscais.partials.xml-inline', compact('nota'));
            }

            return view(self::AUTH_LAYOUT_VIEW, [
                'initialView' => 'autenticado.notas-fiscais.xml-nota',
                'nota' => $nota,
            ]);
        }

        return response('Origem inválida', 400);
    }

    private function isAjaxRequest(Request $request): bool
    {
        if (method_exists($request, 'ajax')) {
            return $request->ajax();
        }

        return $request->wantsJson()
            || $request->expectsJson()
            || $request->header('X-Requested-With') === 'XMLHttpRequest';
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
