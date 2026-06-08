<?php

namespace App\Services\MercadoPago;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

/**
 * Cliente HTTP da API do Mercado Pago.
 *
 * Chamadas diretas (sem SDK) porque `vendor/` não é montado do host — instalar o
 * SDK oficial exigiria rebuild da imagem Docker e não refletiria em dev/prod ao vivo.
 */
class MercadoPagoClient
{
    public function __construct(
        private ?string $accessToken = null,
        private ?string $baseUrl = null,
    ) {
        $this->accessToken ??= (string) config('services.mercadopago.access_token');
        $this->baseUrl ??= rtrim((string) config('services.mercadopago.base_url', 'https://api.mercadopago.com'), '/');
    }

    /**
     * Cria um pagamento (Payments API). Recebe o corpo já validado no backend
     * e a chave de idempotência (mesma chave -> MP devolve o mesmo pagamento).
     *
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    public function criarPagamento(array $body, string $idempotencyKey): array
    {
        $response = $this->request()
            ->withHeaders(['X-Idempotency-Key' => $idempotencyKey])
            ->post($this->baseUrl.'/v1/payments', $body);

        return $response->json() ?? [];
    }

    /**
     * Consulta um pagamento por id (fonte de verdade após o webhook).
     *
     * @return array<string, mixed>
     */
    public function buscarPagamento(string $paymentId): array
    {
        $response = $this->request()
            ->get($this->baseUrl.'/v1/payments/'.$paymentId);

        return $response->json() ?? [];
    }

    private function request(): PendingRequest
    {
        return Http::withToken($this->accessToken)
            ->acceptJson()
            ->timeout(20);
    }
}
