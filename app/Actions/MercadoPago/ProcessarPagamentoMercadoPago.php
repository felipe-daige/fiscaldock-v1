<?php

namespace App\Actions\MercadoPago;

use App\Models\MercadoPagoPayment;
use App\Services\CreditService;
use App\Services\MercadoPago\MercadoPagoClient;
use Illuminate\Support\Facades\DB;

/**
 * Processa uma notificação de pagamento do Mercado Pago.
 *
 * Fonte de verdade: consulta o pagamento na API do MP (nunca confia no corpo do
 * webhook). Se `approved` e ainda não creditado, libera os créditos do pacote
 * de forma IDEMPOTENTE — o webhook pode chegar N vezes e credita 1×.
 */
class ProcessarPagamentoMercadoPago
{
    public function __construct(
        private MercadoPagoClient $client = new MercadoPagoClient,
        private CreditService $credits = new CreditService,
    ) {}

    public function execute(string $mpPaymentId): ?MercadoPagoPayment
    {
        $dadosMp = $this->client->buscarPagamento($mpPaymentId);

        $status = $dadosMp['status'] ?? null;
        $externalReference = $dadosMp['external_reference'] ?? null;

        return DB::transaction(function () use ($mpPaymentId, $dadosMp, $status, $externalReference) {
            // Localiza nossa linha pelo id do MP ou pela external_reference (nosso id).
            $query = MercadoPagoPayment::query()->lockForUpdate();

            $payment = (clone $query)->where('mp_payment_id', $mpPaymentId)->first()
                ?? ($externalReference ? (clone $query)->whereKey($externalReference)->first() : null);

            if ($payment === null) {
                return null;
            }

            $payment->fill([
                'mp_payment_id' => $mpPaymentId,
                'status' => $status ?? $payment->status,
                'status_detail' => $dadosMp['status_detail'] ?? $payment->status_detail,
                'payment_method' => $dadosMp['payment_method_id'] ?? $payment->payment_method,
                'payload' => $dadosMp,
            ]);

            // Libera créditos só uma vez, só quando aprovado.
            if ($status === MercadoPagoPayment::STATUS_APPROVED && ! $payment->jaCreditado()) {
                $this->credits->add(
                    $payment->user,
                    (float) $payment->creditos,
                    'purchase',
                    "Compra de créditos — pacote {$payment->pacote} (Mercado Pago #{$mpPaymentId})",
                    $payment,
                );

                $payment->credited_at = now();
            }

            $payment->save();

            return $payment;
        });
    }
}
