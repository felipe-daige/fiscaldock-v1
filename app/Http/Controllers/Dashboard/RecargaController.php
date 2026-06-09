<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\MercadoPago\CancelarRecargaMercadoPago;
use App\Actions\MercadoPago\CriarRecargaAutomatica;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

class RecargaController extends Controller
{
    public function criar(Request $request, CriarRecargaAutomatica $action): JsonResponse
    {
        $dados = $request->validate([
            'pacote' => ['required', 'string'],
            'token' => ['required', 'string'],
            'amount' => ['nullable', 'numeric'],
        ]);

        try {
            $recarga = $action->execute(
                Auth::user(),
                $dados['pacote'],
                $dados['token'],
                isset($dados['amount']) ? (float) $dados['amount'] : null,
            );
        } catch (RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response()->json([
            'status' => $recarga->status,
            'recarga_id' => $recarga->id,
            'mensagem' => 'Recarga automática criada. Aguardando confirmação do pagamento.',
        ]);
    }

    public function cancelar(CancelarRecargaMercadoPago $action): JsonResponse
    {
        try {
            $action->execute(Auth::user());
        } catch (RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response()->json(['status' => 'cancelada', 'mensagem' => 'Recarga automática cancelada.']);
    }
}
