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
        private \App\Actions\MercadoPago\AtivarAssinaturaMercadoPago $ativarAssinatura = new \App\Actions\MercadoPago\AtivarAssinaturaMercadoPago,
        private \App\Actions\MercadoPago\RegistrarCobrancaAssinatura $registrarCobranca = new \App\Actions\MercadoPago\RegistrarCobrancaAssinatura,
        private \App\Actions\MercadoPago\AtivarRecargaMercadoPago $ativarRecarga = new \App\Actions\MercadoPago\AtivarRecargaMercadoPago,
        private \App\Actions\MercadoPago\CobrarRecargaMercadoPago $cobrarRecarga = new \App\Actions\MercadoPago\CobrarRecargaMercadoPago,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        if (! $this->signature->isValid($request)) {
            return response()->json(['error' => 'assinatura inválida'], 401);
        }

        $type = (string) ($request->input('type')
            ?? $request->query('type')
            ?? $request->query('topic')
            ?? '');

        // PHP troca `data.id` por `data_id` no query string; checamos corpo + as formas + `id` (IPN legado).
        $resourceId = (string) ($request->input('data.id')
            ?? $request->query('data_id')
            ?? $request->query('data.id')
            ?? $request->query('id')
            ?? '');

        if ($resourceId === '') {
            return response()->json(['error' => 'id do recurso ausente'], 422);
        }

        // payment = avulso (Fase 1); preapproval = ciclo de vida da assinatura;
        // subscription_authorized_payment = cobrança recorrente (audit/dunning).
        return match (true) {
            $type === 'payment' => tap(
                response()->json(['status' => 'ok'], 200),
                fn () => $this->processar->execute($resourceId),
            ),
            $type === 'subscription_authorized_payment' => tap(
                response()->json(['status' => 'ok'], 200),
                function () use ($resourceId) {
                    // Assinatura de tier (audit, sem crédito) OU recarga automática (credita o pacote).
                    if ($this->registrarCobranca->execute($resourceId) === null) {
                        $this->cobrarRecarga->execute($resourceId);
                    }
                },
            ),
            str_contains($type, 'preapproval') => tap(
                response()->json(['status' => 'ok'], 200),
                function () use ($resourceId) {
                    // Ciclo de vida: tenta assinatura de tier; se não for dela, tenta recarga.
                    if ($this->ativarAssinatura->execute($resourceId) === null) {
                        $this->ativarRecarga->execute($resourceId);
                    }
                },
            ),
            default => response()->json(['status' => 'ignorado'], 200),
        };
    }
}
