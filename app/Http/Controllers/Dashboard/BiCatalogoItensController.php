<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Concerns\RespondeAjax;
use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Services\Catalogo\NotaItemUnificadoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BiCatalogoItensController extends Controller
{
    use RespondeAjax;

    private const AUTH_LAYOUT_VIEW = 'autenticado.layouts.app';

    public function __construct(private NotaItemUnificadoService $service) {}

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

        $filtros = array_filter([
            'cliente_id' => $request->integer('cliente_id') ?: null,
            'periodo_de' => $request->input('periodo_de') ?: null,
            'periodo_ate' => $request->input('periodo_ate') ?: null,
            'fonte' => in_array($request->input('fonte'), ['efd', 'xml', 'ambas'], true) ? $request->input('fonte') : null,
        ]);

        $itens = $this->service->itensAgregados($userId, $filtros);

        $kpis = [
            'total_itens' => $itens->count(),
            'com_catalogo' => $itens->where('tem_catalogo', true)->count(),
            'sem_catalogo' => $itens->where('tem_catalogo', false)->count(),
            'valor_movimentado' => (float) $itens->sum('valor_total'),
            'sem_ncm' => $itens->filter(fn ($i) => empty($i['ncm']))->count(),
        ];

        $clientes = Cliente::where('user_id', $userId)->orderByDesc('is_empresa_propria')->orderBy('razao_social')->get(['id', 'razao_social']);

        $data = ['itens' => $itens, 'kpis' => $kpis, 'clientes' => $clientes, 'filtros' => $filtros];

        if ($this->isAjaxRequest($request)) {
            return response(view($view, $data)->render())->header('Content-Type', 'text/html');
        }

        return view(self::AUTH_LAYOUT_VIEW, array_merge(['initialView' => $view], $data));
    }
}
