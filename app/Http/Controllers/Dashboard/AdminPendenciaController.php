<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Concerns\RespondeAjax;
use App\Http\Controllers\Controller;
use App\Models\AdminPendencia;
use Illuminate\Http\Request;

/**
 * Console admin — pendências/notas operacionais do operador FiscalDock.
 * Gate: middleware EnsureAdmin na rota. Mesmo padrão SPA-partial dos demais painéis admin
 * (initialView + $data no layout; AJAX renderiza só o partial).
 */
class AdminPendenciaController extends Controller
{
    use RespondeAjax;

    private const AUTH_LAYOUT_VIEW = 'autenticado.layouts.app';

    public function index(Request $request)
    {
        $view = 'autenticado.admin.pendencias.index';
        $data = [
            'abertas' => AdminPendencia::abertas()->with('criadoPor')
                ->orderByRaw('CASE WHEN lembrar_em IS NOT NULL AND lembrar_em <= CURRENT_DATE THEN 0 ELSE 1 END')
                ->orderByRaw('CASE WHEN lembrar_em IS NOT NULL AND lembrar_em <= CURRENT_DATE THEN lembrar_em END ASC')
                ->orderByDesc('created_at')
                ->get(),
            'resolvidas' => AdminPendencia::resolvidas()->with(['criadoPor', 'resolvidoPor'])
                ->orderByDesc('resolvido_em')->limit(20)->get(),
        ];

        if ($this->isAjaxRequest($request)) {
            return response(view($view, $data)->render())->header('Content-Type', 'text/html');
        }

        return view(self::AUTH_LAYOUT_VIEW, array_merge(['initialView' => $view], $data));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'titulo' => 'required|string|max:255',
            'nota' => 'nullable|string',
            'lembrar_em' => 'nullable|date',
        ]);
        $data['status'] = AdminPendencia::STATUS_ABERTA;
        $data['criado_por'] = $request->user()->id;
        AdminPendencia::create($data);

        return redirect()->route('app.admin.pendencias.index')->with('ok', 'Pendência criada.');
    }

    public function resolver(AdminPendencia $pendencia, Request $request)
    {
        $pendencia->update([
            'status' => AdminPendencia::STATUS_RESOLVIDA,
            'resolvido_por' => $request->user()->id,
            'resolvido_em' => now(),
        ]);

        return back()->with('ok', 'Pendência resolvida.');
    }

    public function reabrir(AdminPendencia $pendencia)
    {
        $pendencia->update([
            'status' => AdminPendencia::STATUS_ABERTA,
            'resolvido_por' => null,
            'resolvido_em' => null,
        ]);

        return back()->with('ok', 'Pendência reaberta.');
    }

    public function destroy(AdminPendencia $pendencia)
    {
        $pendencia->delete();

        return back()->with('ok', 'Pendência excluída.');
    }
}
