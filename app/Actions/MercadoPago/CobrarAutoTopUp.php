<?php

namespace App\Actions\MercadoPago;

use App\Models\MercadoPagoPayment;
use App\Models\RecargaAutomatica;
use App\Services\MercadoPago\MercadoPagoClient;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Cobra um auto top-up por saldo: gera um token do cartão salvo (MIT) e cria um
 * pagamento avulso (/v1/payments) do pacote fixo. O CRÉDITO é liberado pelo webhook
 * `payment` → ProcessarPagamentoMercadoPago (idempotente), não aqui.
 */
class CobrarAutoTopUp
{
    public function __construct(private MercadoPagoClient $client = new MercadoPagoClient) {}

    public function execute(RecargaAutomatica $recarga): MercadoPagoPayment
    {
        if ($recarga->mp_card_id === null || $recarga->mp_customer_id === null) {
            throw new RuntimeException('Recarga por saldo sem cartão salvo.');
        }

        $valor = round((float) $recarga->valor, 2);
        $creditos = (int) $recarga->creditos;
        $idempotencyKey = (string) Str::uuid();

        $payment = MercadoPagoPayment::create([
            'user_id' => $recarga->user_id,
            'tipo' => 'auto_topup',
            'pacote' => $recarga->pacote,
            'status' => MercadoPagoPayment::STATUS_PENDING,
            'valor' => $valor,
            'creditos' => $creditos,
            'idempotency_key' => $idempotencyKey,
        ]);

        $token = $this->client->tokenDeCartaoSalvo($recarga->mp_card_id);
        $tokenId = $token['id'] ?? null;

        if ($tokenId === null) {
            $payment->update([
                'status' => MercadoPagoPayment::STATUS_REJECTED,
                'status_detail' => 'falha ao tokenizar cartão salvo',
                'payload' => $token,
            ]);

            return $payment->refresh();
        }

        $body = [
            'transaction_amount' => $valor,
            'token' => $tokenId,
            'installments' => 1,
            'description' => "FiscalDock — auto top-up ({$creditos} créditos)",
            'external_reference' => (string) $payment->id,
            'notification_url' => route('api.mercadopago.webhook'),
            'payer' => ['type' => 'customer', 'id' => $recarga->mp_customer_id],
            'metadata' => [
                'user_id' => $recarga->user_id,
                'pacote' => $recarga->pacote,
                'creditos' => $creditos,
                'payment_id' => $payment->id,
                'tipo' => 'auto_topup',
            ],
        ];

        $resposta = $this->client->criarPagamento($body, $idempotencyKey);
        $mpId = isset($resposta['id']) ? (string) $resposta['id'] : null;
        $erroApi = $mpId === null;

        $payment->update([
            'mp_payment_id' => $mpId,
            'status' => $erroApi
                ? MercadoPagoPayment::STATUS_REJECTED
                : ($resposta['status'] ?? MercadoPagoPayment::STATUS_PENDING),
            'status_detail' => $erroApi
                ? (data_get($resposta, 'cause.0.description') ?? ($resposta['message'] ?? 'erro na criação do pagamento'))
                : ($resposta['status_detail'] ?? null),
            'payment_method' => $resposta['payment_method_id'] ?? null,
            'payload' => $resposta,
        ]);

        return $payment->refresh();
    }
}
