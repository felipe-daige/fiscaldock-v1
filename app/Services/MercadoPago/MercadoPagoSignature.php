<?php

namespace App\Services\MercadoPago;

use Illuminate\Http\Request;

/**
 * Validação da assinatura HMAC do webhook do Mercado Pago.
 *
 * O MP envia o header `x-signature: ts=<unix>,v1=<hmac>` e um `x-request-id`.
 * O manifest assinado é: `id:<data.id>;request-id:<x-request-id>;ts:<ts>;`
 * (data.id em minúsculas), HMAC-SHA256 com o `webhook_secret`. Comparamos com `v1`.
 *
 * Referência: painel MP -> Webhooks -> "Validar origem da notificação".
 */
class MercadoPagoSignature
{
    public function __construct(private ?string $secret = null)
    {
        $this->secret ??= (string) config('services.mercadopago.webhook_secret');
    }

    public function isValid(Request $request): bool
    {
        $secret = (string) $this->secret;

        if ($secret === '') {
            // Sem secret configurado não há como validar — recusa por segurança.
            return false;
        }

        [$ts, $v1] = $this->parseSignatureHeader($request->header('x-signature', ''));

        if ($ts === null || $v1 === null) {
            return false;
        }

        $dataId = $this->dataId($request);
        $requestId = (string) $request->header('x-request-id', '');

        $manifest = "id:{$dataId};request-id:{$requestId};ts:{$ts};";
        $expected = hash_hmac('sha256', $manifest, $secret);

        return hash_equals($expected, $v1);
    }

    /**
     * Extrai (ts, v1) do header x-signature.
     *
     * @return array{0: ?string, 1: ?string}
     */
    private function parseSignatureHeader(string $header): array
    {
        $ts = null;
        $v1 = null;

        foreach (explode(',', $header) as $part) {
            $pair = explode('=', trim($part), 2);

            if (count($pair) !== 2) {
                continue;
            }

            [$key, $value] = $pair;
            $key = trim($key);
            $value = trim($value);

            if ($key === 'ts') {
                $ts = $value;
            } elseif ($key === 'v1') {
                $v1 = $value;
            }
        }

        return [$ts, $v1];
    }

    /**
     * O id usado no manifest vem do query param `data.id` (minúsculo).
     *
     * O PHP transforma a chave `data.id` do query string em `data_id` (troca ponto
     * por underscore), então checamos as duas formas + o corpo JSON como fallback.
     */
    private function dataId(Request $request): string
    {
        $id = $request->query('data_id')
            ?? $request->query('data.id')
            ?? $request->input('data.id')
            ?? '';

        return strtolower((string) $id);
    }
}
