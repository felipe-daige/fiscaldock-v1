<?php

namespace App\Actions\MercadoPago;

use App\Models\MercadoPagoPayment;
use App\Models\User;
use App\Services\MercadoPago\MercadoPagoClient;
use App\Services\PricingCatalogService;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Cria um pagamento no Mercado Pago para um pacote de créditos.
 *
 * Regra dura: valor e créditos vêm SEMPRE do catálogo do backend
 * (PricingCatalogService) — nunca do front. O front só informa o meio de
 * pagamento (token do cartão / Pix) coletado pelo Brick.
 */
class CriarPagamentoMercadoPago
{
    public function __construct(
        private MercadoPagoClient $client = new MercadoPagoClient,
        private PricingCatalogService $catalog = new PricingCatalogService,
    ) {}

    /**
     * @param  array<string, mixed>  $dadosPagamento  formData do Brick (token, payment_method_id, payer, installments)
     */
    public function execute(User $user, string $slug, ?float $amount, array $dadosPagamento): MercadoPagoPayment
    {
        $pacote = $this->catalog->resolveCheckoutSelection($slug, $amount);

        if ($pacote === null) {
            throw new RuntimeException('Pacote de créditos inválido.');
        }

        $valor = round((float) $pacote['preco'], 2);
        $creditos = (int) $pacote['creditos'];
        $idempotencyKey = (string) Str::uuid();

        // Persiste pending ANTES de chamar o MP — assim temos id p/ external_reference
        // e a chave de idempotência fica registrada mesmo se a chamada externa falhar.
        $payment = MercadoPagoPayment::create([
            'user_id' => $user->id,
            'pacote' => $pacote['slug'],
            'status' => MercadoPagoPayment::STATUS_PENDING,
            'valor' => $valor,
            'creditos' => $creditos,
            'idempotency_key' => $idempotencyKey,
        ]);

        $body = [
            'transaction_amount' => $valor,
            'description' => "FiscalDock — {$pacote['nome']} ({$creditos} créditos)",
            'external_reference' => (string) $payment->id,
            'notification_url' => route('api.mercadopago.webhook'),
            'metadata' => [
                'user_id' => $user->id,
                'pacote' => $pacote['slug'],
                'creditos' => $creditos,
                'payment_id' => $payment->id,
            ],
            'payer' => [
                'email' => $dadosPagamento['payer']['email'] ?? $user->email,
            ],
        ];

        if (! empty($dadosPagamento['payment_method_id'])) {
            $body['payment_method_id'] = $dadosPagamento['payment_method_id'];
        }

        // Cartão: token + parcelas. Pix: sem token (payment_method_id = 'pix').
        if (! empty($dadosPagamento['token'])) {
            $body['token'] = $dadosPagamento['token'];
            $body['installments'] = (int) ($dadosPagamento['installments'] ?? 1);

            if (! empty($dadosPagamento['issuer_id'])) {
                $body['issuer_id'] = $dadosPagamento['issuer_id'];
            }

            if (! empty($dadosPagamento['payer']['identification'])) {
                $body['payer']['identification'] = $dadosPagamento['payer']['identification'];
            }
        }

        $resposta = $this->client->criarPagamento($body, $idempotencyKey);

        // Sem `id` => o MP NÃO criou o pagamento (erro de validação/bad_request). Nesse caso
        // o campo `status` do corpo é o HTTP code (ex.: 400), não um status de pagamento —
        // não o usamos como status. Marcamos rejected e guardamos a causa.
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
            'payment_method' => $resposta['payment_method_id'] ?? ($body['payment_method_id'] ?? null),
            'payload' => $resposta,
        ]);

        return $payment->refresh();
    }
}
