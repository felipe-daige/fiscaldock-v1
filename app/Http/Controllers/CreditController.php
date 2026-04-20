<?php

namespace App\Http\Controllers;

use App\Services\CreditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CreditController extends Controller
{
    public function __construct(
        protected CreditService $creditService
    ) {}

    /**
     * Retorna o saldo de créditos do usuário autenticado.
     */
    public function balance(Request $request)
    {
        $user = Auth::user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Usuário não autenticado.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        return response()->json([
            'success' => true,
            'credits' => $this->creditService->getBalance($user),
        ]);
    }
}
