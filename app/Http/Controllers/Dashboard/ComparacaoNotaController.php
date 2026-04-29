<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Concerns\RendersAuthView;
use App\Http\Controllers\Controller;
use App\Services\Clearance\Comparacao\ComparacaoNotaService;
use App\Services\Clearance\Comparacao\ComparacaoSourceResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;

class ComparacaoNotaController extends Controller
{
    use RendersAuthView;

    private const AUTH_VIEW_PREFIX = 'autenticado.clearance.';

    private const AUTH_LAYOUT_VIEW = 'autenticado.layouts.app';

    public function __construct(
        private readonly ComparacaoSourceResolver $resolver,
        private readonly ComparacaoNotaService $comparator,
    ) {}

    public function compararPorChave(Request $request, string $chave)
    {
        $userId = (int) Auth::id();
        $loteId = $request->integer('lote_id') ?: null;

        try {
            $resolved = $this->resolver->resolver($userId, $chave);
        } catch (InvalidArgumentException $e) {
            abort(404, $e->getMessage());
        }

        if ($resolved->declarado === null && $resolved->sefaz === null) {
            abort(404, 'Nota não encontrada no acervo.');
        }

        $declarado = $resolved->declarado?->carregar();
        $sefaz = $resolved->sefaz?->carregar();

        $comparacao = $this->comparator->comparar($declarado, $sefaz, $resolved->tipoDocumento);

        return $this->renderAuthView($request, 'comparacao', [
            'comparacao' => $comparacao,
            'lote_id' => $loteId,
            'tem_efd_alternativo' => $this->resolver->temEfdAlternativo($userId, $chave),
            'origem_declarado' => $resolved->declarado?->origemLabel(),
            'origem_sefaz' => $resolved->sefaz?->origemLabel(),
        ]);
    }
}
