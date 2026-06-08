<?php

namespace App\Http\Controllers\Api;

use App\Actions\MercadoPago\ProcessarPagamentoMercadoPago;
use App\Http\Controllers\Controller;
use App\Services\MercadoPago\MercadoPagoSignature;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Webhook de notificações do Mercado Pago.
 *
 * Sem auth de sessão (endpoint público) — a autenticidade vem da assinatura HMAC
 * `x-signature`. NUNCA credita pelo corpo do webhook: delega ao action que consulta
 * o pagamento na API do MP (fonte de verdade) e libera créditos idempotentemente.
 */
class MercadoPagoWebhookController extends Controller
{
    public function __construct(
        private MercadoPagoSignature $signature = new MercadoPagoSignature,
        private ProcessarPagamentoMercadoPago $processar = new ProcessarPagamentoMercadoPago,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        if (! $this->signature->isValid($request)) {
            return response()->json(['error' => 'assinatura inválida'], 401);
        }

        // Só tratamos notificações de pagamento. Outras (merchant_order, etc.) -> 200 e ignora.
        $type = $request->input('type', $request->query('type'));

        if ($type !== 'payment') {
            return response()->json(['status' => 'ignorado'], 200);
        }

        // PHP troca `data.id` por `data_id` no query string; checamos corpo + as duas formas.
        $paymentId = (string) ($request->input('data.id')
            ?? $request->query('data_id')
            ?? $request->query('data.id')
            ?? '');

        if ($paymentId === '') {
            return response()->json(['error' => 'data.id ausente'], 422);
        }

        $this->processar->execute($paymentId);

        return response()->json(['status' => 'ok'], 200);
    }
}
