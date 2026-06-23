<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Admin\AdminAcaoService;
use Illuminate\Http\Request;

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
}
