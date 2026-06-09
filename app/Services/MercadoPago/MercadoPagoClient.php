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

    /**
     * Cria um preapproval_plan (template de assinatura por tier × ciclo).
     *
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    public function criarPreapprovalPlan(array $body): array
    {
        return $this->request()->post($this->baseUrl.'/preapproval_plan', $body)->json() ?? [];
    }

    /**
     * Cria um preapproval (assinatura do usuário a um plano, com card_token).
     *
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    public function criarPreapproval(array $body): array
    {
        return $this->request()->post($this->baseUrl.'/preapproval', $body)->json() ?? [];
    }

    /**
     * Consulta um preapproval por id (fonte de verdade do ciclo de vida da assinatura).
     *
     * @return array<string, mixed>
     */
    public function buscarPreapproval(string $id): array
    {
        return $this->request()->get($this->baseUrl.'/preapproval/'.$id)->json() ?? [];
    }

    /**
     * Cancela um preapproval (PUT status=cancelled).
     *
     * @return array<string, mixed>
     */
    public function cancelarPreapproval(string $id): array
    {
        return $this->request()->put($this->baseUrl.'/preapproval/'.$id, ['status' => 'cancelled'])->json() ?? [];
    }

    /**
     * Consulta uma cobrança recorrente (authorized_payment) por id.
     *
     * @return array<string, mixed>
     */
    public function buscarAuthorizedPayment(string $id): array
    {
        return $this->request()->get($this->baseUrl.'/authorized_payments/'.$id)->json() ?? [];
    }

    /**
     * Busca customers por email (evita duplicar customer no vault).
     *
     * @return array<string, mixed>
     */
    public function buscarCustomerPorEmail(string $email): array
    {
        return $this->request()->get($this->baseUrl.'/v1/customers/search', ['email' => $email])->json() ?? [];
    }

    /**
     * Cria um customer no MP (vault).
     *
     * @return array<string, mixed>
     */
    public function criarCustomer(string $email): array
    {
        return $this->request()->post($this->baseUrl.'/v1/customers', ['email' => $email])->json() ?? [];
    }

    /**
     * Salva um cartão (a partir de um card_token) num customer. PAN fica no MP.
     *
     * @return array<string, mixed>
     */
    public function salvarCartao(string $customerId, string $cardToken): array
    {
        return $this->request()
            ->post($this->baseUrl.'/v1/customers/'.$customerId.'/cards', ['token' => $cardToken])
            ->json() ?? [];
    }

    /**
     * Gera um card_token novo a partir de um cartão salvo (MIT, sem CVV).
     *
     * @return array<string, mixed>
     */
    public function tokenDeCartaoSalvo(string $cardId): array
    {
        return $this->request()
            ->post($this->baseUrl.'/v1/card_tokens', ['card_id' => $cardId])
            ->json() ?? [];
    }

    private function request(): PendingRequest
    {
        return Http::withToken($this->accessToken)
            ->acceptJson()
            ->timeout(20);
    }
}
