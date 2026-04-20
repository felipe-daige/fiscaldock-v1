<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Participante;
use App\Models\ParticipanteGrupo;
use App\Services\CreditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ParticipanteGrupoController extends Controller
{
    private const AUTH_VIEW_PREFIX = 'autenticado.monitoramento.';

    private const AUTH_LAYOUT_VIEW = 'autenticado.layouts.app';

    public function __construct(
        private readonly CreditService $creditService,
    ) {}

    /**
     * Lista grupos de participantes.
     */
    public function index(Request $request)
    {
        $gruposView = self::AUTH_VIEW_PREFIX.'grupos';

        if (! Auth::check()) {
            return $this->redirectToLogin($request);
        }

        $user = Auth::user();
        $userId = (int) $user->id;

        $busca = trim($request->string('busca')->toString());
        $tipo = trim($request->string('tipo')->toString());

        // Buscar grupos do usuário com contagem de participantes
        $grupos = ParticipanteGrupo::where('user_id', $userId)
            ->when($busca !== '', fn ($query) => $query->where('nome', 'ilike', "%{$busca}%"))
            ->when($tipo !== '', function ($query) use ($tipo) {
                if ($tipo === 'manual') {
                    $query->where('is_auto', false);
                } elseif ($tipo === 'auto') {
                    $query->where('is_auto', true);
                }
            })
            ->withCount('participantes')
            ->orderBy('nome')
            ->paginate(20)
            ->withQueryString();

        $data = [
            'grupos' => $grupos,
            'coresPredefinidas' => ParticipanteGrupo::CORES_PREDEFINIDAS,
            'credits' => $this->creditService->getBalance($user),
            'filtros' => [
                'busca' => $busca,
                'tipo' => $tipo,
            ],
        ];

        if ($this->isAjaxRequest($request)) {
            $renderedView = view($gruposView, $data)->render();

            return response($renderedView)->header('Content-Type', 'text/html');
        }

        return view(self::AUTH_LAYOUT_VIEW, array_merge([
            'initialView' => $gruposView,
        ], $data));
    }

    public function participantes(Request $request, $id)
    {
        if (! Auth::check()) {
            return response('Nao autenticado', 401);
        }

        $userId = (int) Auth::id();
        $grupo = ParticipanteGrupo::where('id', $id)
            ->where('user_id', $userId)
            ->firstOrFail();

        $participantes = $grupo->participantes()
            ->where('participantes.user_id', $userId)
            ->withCount('efdNotas')
            ->orderByRaw("COALESCE(participantes.razao_social, participantes.nome_fantasia, participantes.documento, '') asc")
            ->paginate(5)
            ->withQueryString();

        return view('autenticado.partials.relacionados-participantes', [
            'participantes' => $participantes,
            'titulo' => 'Participantes do grupo',
            'emptyMessage' => 'Nenhum participante associado a este grupo.',
            'scope' => 'grupo',
            'entityId' => $grupo->id,
            'ajaxBaseUrl' => "/app/monitoramento/grupos/{$grupo->id}/participantes",
        ]);
    }

    /**
     * Cria um novo grupo de participantes.
     */
    public function store(Request $request)
    {
        if (! Auth::check()) {
            return response()->json([
                'success' => false,
                'error' => 'Usuário não autenticado.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $user = Auth::user();
        $nome = trim($request->input('nome', ''));
        $cor = $request->input('cor', ParticipanteGrupo::CORES_PREDEFINIDAS[0]);
        $descricao = $request->input('descricao');

        if (empty($nome)) {
            return response()->json([
                'success' => false,
                'error' => 'Nome do grupo é obrigatório.',
            ], Response::HTTP_BAD_REQUEST);
        }

        // Verificar se já existe grupo com mesmo nome
        $existente = ParticipanteGrupo::where('user_id', $user->id)
            ->where('nome', $nome)
            ->exists();

        if ($existente) {
            return response()->json([
                'success' => false,
                'error' => 'Já existe um grupo com este nome.',
            ], Response::HTTP_CONFLICT);
        }

        try {
            $grupo = ParticipanteGrupo::create([
                'user_id' => $user->id,
                'nome' => $nome,
                'cor' => $cor,
                'descricao' => $descricao,
                'is_auto' => false,
            ]);

            Log::info('Grupo de participantes criado', [
                'user_id' => $user->id,
                'grupo_id' => $grupo->id,
                'nome' => $nome,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Grupo criado com sucesso.',
                'grupo' => [
                    'id' => $grupo->id,
                    'nome' => $grupo->nome,
                    'cor' => $grupo->cor,
                    'descricao' => $grupo->descricao,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao criar grupo', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erro ao criar grupo. Tente novamente.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Edita um grupo de participantes.
     */
    public function update(Request $request, $id)
    {
        if (! Auth::check()) {
            return response()->json([
                'success' => false,
                'error' => 'Usuário não autenticado.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $user = Auth::user();

        $grupo = ParticipanteGrupo::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (! $grupo) {
            return response()->json([
                'success' => false,
                'error' => 'Grupo não encontrado.',
            ], Response::HTTP_NOT_FOUND);
        }

        $nome = trim($request->input('nome', $grupo->nome));
        $cor = $request->input('cor', $grupo->cor);
        $descricao = $request->input('descricao', $grupo->descricao);

        if (empty($nome)) {
            return response()->json([
                'success' => false,
                'error' => 'Nome do grupo é obrigatório.',
            ], Response::HTTP_BAD_REQUEST);
        }

        // Verificar se já existe outro grupo com mesmo nome
        $existente = ParticipanteGrupo::where('user_id', $user->id)
            ->where('nome', $nome)
            ->where('id', '!=', $id)
            ->exists();

        if ($existente) {
            return response()->json([
                'success' => false,
                'error' => 'Já existe outro grupo com este nome.',
            ], Response::HTTP_CONFLICT);
        }

        try {
            $grupo->update([
                'nome' => $nome,
                'cor' => $cor,
                'descricao' => $descricao,
            ]);

            Log::info('Grupo de participantes editado', [
                'user_id' => $user->id,
                'grupo_id' => $grupo->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Grupo atualizado com sucesso.',
                'grupo' => [
                    'id' => $grupo->id,
                    'nome' => $grupo->nome,
                    'cor' => $grupo->cor,
                    'descricao' => $grupo->descricao,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao editar grupo', [
                'user_id' => $user->id,
                'grupo_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erro ao atualizar grupo. Tente novamente.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Exclui um grupo de participantes.
     */
    public function destroy(Request $request, $id)
    {
        if (! Auth::check()) {
            return response()->json([
                'success' => false,
                'error' => 'Usuário não autenticado.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $user = Auth::user();

        $grupo = ParticipanteGrupo::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (! $grupo) {
            return response()->json([
                'success' => false,
                'error' => 'Grupo não encontrado.',
            ], Response::HTTP_NOT_FOUND);
        }

        try {
            $nome = $grupo->nome;
            $grupo->delete();

            Log::info('Grupo de participantes excluído', [
                'user_id' => $user->id,
                'grupo_id' => $id,
                'nome' => $nome,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Grupo excluído com sucesso.',
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao excluir grupo', [
                'user_id' => $user->id,
                'grupo_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erro ao excluir grupo. Tente novamente.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Associa participantes a um grupo.
     */
    public function associar(Request $request)
    {
        if (! Auth::check()) {
            return response()->json([
                'success' => false,
                'error' => 'Usuário não autenticado.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $user = Auth::user();
        $grupoId = $request->input('grupo_id');
        $participanteIds = $request->input('participantes', []);
        $acao = $request->input('acao', 'adicionar'); // adicionar ou remover

        if (empty($grupoId) || empty($participanteIds)) {
            return response()->json([
                'success' => false,
                'error' => 'Selecione um grupo e participantes.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $grupo = ParticipanteGrupo::where('id', $grupoId)
            ->where('user_id', $user->id)
            ->first();

        if (! $grupo) {
            return response()->json([
                'success' => false,
                'error' => 'Grupo não encontrado.',
            ], Response::HTTP_NOT_FOUND);
        }

        // Filtrar apenas participantes do usuário
        $participantesValidos = Participante::where('user_id', $user->id)
            ->whereIn('id', $participanteIds)
            ->pluck('id')
            ->toArray();

        if (empty($participantesValidos)) {
            return response()->json([
                'success' => false,
                'error' => 'Nenhum participante válido selecionado.',
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            if ($acao === 'remover') {
                $grupo->participantes()->detach($participantesValidos);
                $message = count($participantesValidos).' participante(s) removido(s) do grupo.';
            } else {
                $grupo->participantes()->syncWithoutDetaching($participantesValidos);
                $message = count($participantesValidos).' participante(s) adicionado(s) ao grupo.';
            }

            Log::info('Participantes associados ao grupo', [
                'user_id' => $user->id,
                'grupo_id' => $grupoId,
                'participantes' => count($participantesValidos),
                'acao' => $acao,
            ]);

            return response()->json([
                'success' => true,
                'message' => $message,
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao associar participantes ao grupo', [
                'user_id' => $user->id,
                'grupo_id' => $grupoId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erro ao associar participantes. Tente novamente.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
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
