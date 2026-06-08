<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\MercadoPago\CriarPagamentoMercadoPago;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Cria pagamentos de pacotes de crédito via Mercado Pago (Checkout Bricks).
 *
 * O front (Payment Brick) coleta o meio de pagamento (token do cartão ou Pix) e
 * envia para cá. Valor/créditos são resolvidos no backend pelo catálogo — o front
 * só informa o pacote escolhido e o meio de pagamento.
 */
class PagamentoMercadoPagoController extends Controller
{
    public function __construct(
        private CriarPagamentoMercadoPago $criarPagamento = new CriarPagamentoMercadoPago,
    ) {}

    public function criar(Request $request): JsonResponse
    {
        $dados = $request->validate([
            'pacote' => ['required', 'string', 'max:50'],
            'amount' => ['nullable', 'numeric'],
            'payment_data' => ['nullable', 'array'],
            'payment_data.token' => ['nullable', 'string'],
            'payment_data.payment_method_id' => ['nullable', 'string'],
            'payment_data.installments' => ['nullable', 'integer', 'min:1'],
            'payment_data.issuer_id' => ['nullable', 'string'],
            'payment_data.payer' => ['nullable', 'array'],
        ]);

        try {
            $payment = $this->criarPagamento->execute(
                $request->user(),
                $dados['pacote'],
                isset($dados['amount']) ? (float) $dados['amount'] : null,
                $dados['payment_data'] ?? [],
            );
        } catch (RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response()->json([
            'id' => $payment->id,
            'mp_payment_id' => $payment->mp_payment_id,
            'status' => $payment->status,
            'status_detail' => $payment->status_detail,
            'pacote' => $payment->pacote,
            'creditos' => $payment->creditos,
            'valor' => $payment->valor,
            // Para Pix: o front renderiza o QR a partir do payload do MP.
            'point_of_interaction' => data_get($payment->payload, 'point_of_interaction'),
        ]);
    }
}
