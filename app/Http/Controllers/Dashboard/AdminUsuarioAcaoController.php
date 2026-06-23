<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Admin\AdminAcaoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminUsuarioAcaoController extends Controller
{
    public function __construct(private AdminAcaoService $acoes) {}

    public function creditar(Request $request, int $id)
    {
        $dados = $request->validate([
            'valor' => ['required', 'numeric', 'not_in:0'],
            'motivo' => ['required', 'string', 'min:3', 'max:500'],
        ]);
        $alvo = User::findOrFail($id);

        try {
            $this->acoes->creditar($request->user(), $alvo, (float) $dados['valor'], $dados['motivo']);
        } catch (\RuntimeException|\InvalidArgumentException $e) {
            return back()->withErrors(['valor' => $e->getMessage()]);
        }

        return redirect("/app/admin/usuarios/{$id}")->with('status', 'Saldo ajustado.');
    }

    public function bloquear(Request $request, int $id)
    {
        $dados = $request->validate(['motivo' => ['required', 'string', 'min:3', 'max:500']]);
        $alvo = User::findOrFail($id);

        try {
            $alvo->bloqueado_em
                ? $this->acoes->desbloquear($request->user(), $alvo, $dados['motivo'])
                : $this->acoes->bloquear($request->user(), $alvo, $dados['motivo']);
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['motivo' => $e->getMessage()]);
        }

        return redirect("/app/admin/usuarios/{$id}")->with('status', 'Status de acesso atualizado.');
    }

    public function admin(Request $request, int $id)
    {
        $dados = $request->validate(['motivo' => ['required', 'string', 'min:3', 'max:500']]);
        $alvo = User::findOrFail($id);

        try {
            $this->acoes->definirAdmin($request->user(), $alvo, ! $alvo->is_admin, $dados['motivo']);
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['motivo' => $e->getMessage()]);
        }

        return redirect("/app/admin/usuarios/{$id}")->with('status', 'Permissão de admin atualizada.');
    }

    public function impersonar(Request $request, int $id)
    {
        $dados = $request->validate(['motivo' => ['required', 'string', 'min:3', 'max:500']]);
        $alvo = User::findOrFail($id);
        $admin = $request->user();

        if ($alvo->id === $admin->id || $alvo->is_admin) {
            return back()->withErrors(['motivo' => 'Alvo inválido para impersonação.']);
        }

        $this->acoes->registrar($admin, $alvo, 'impersonar', $dados['motivo']);
        $request->session()->put('impersonator_id', $admin->id);
        Auth::login($alvo);
        $request->session()->regenerate();

        return redirect('/app');
    }

    public function auditoria(Request $request)
    {
        $logs = \App\Models\AdminActionLog::with(['admin', 'alvo'])
            ->orderByDesc('created_at')->paginate(30);

        $view = 'autenticado.admin.auditoria';
        $data = ['logs' => $logs, 'tab' => 'auditoria'];

        if ($request->ajax() || $request->wantsJson()) {
            return response(view($view, $data)->render())->header('Content-Type', 'text/html');
        }

        return view('autenticado.layouts.app', array_merge(['initialView' => $view], $data));
    }

    public function impersonarSair(Request $request)
    {
        $adminId = $request->session()->pull('impersonator_id');
        if (! $adminId) {
            return redirect('/app');
        }

        $alvoId = Auth::id();
        $admin = User::find($adminId);

        if ($admin) {
            Auth::login($admin);
            $request->session()->regenerate();
            $this->acoes->registrar($admin, User::find($alvoId), 'impersonar_sair', 'fim da impersonação');

            return redirect("/app/admin/usuarios/{$alvoId}");
        }

        return redirect('/app');
    }
}
