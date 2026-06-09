<?php

namespace App\Actions\MercadoPago;

use App\Models\RecargaAutomatica;
use App\Models\User;
use App\Services\MercadoPago\MercadoPagoClient;
use App\Services\PricingCatalogService;
use RuntimeException;

/**
 * Cria uma recarga automática por tempo (Fase 2): preapproval recorrente do Mercado
 * Pago que recompra um pacote de créditos numa frequência fixa (mensal por padrão).
 *
 * Regra dura: valor e créditos vêm SEMPRE do catálogo backend (PricingCatalogService),
 * nunca do front. O front só envia o card_token do Brick.
 */
class CriarRecargaAutomatica
{
    public function __construct(
        private MercadoPagoClient $client = new MercadoPagoClient,
        private PricingCatalogService $catalog = new PricingCatalogService,
    ) {}

    public function execute(User $user, string $pacoteSlug, string $cardToken, ?float $amount = null): RecargaAutomatica
    {
        $pacote = $this->catalog->resolveCheckoutSelection($pacoteSlug, $amount);

        if ($pacote === null) {
            throw new RuntimeException('Pacote de recarga inválido.');
        }

        $valor = round((float) $pacote['preco'], 2);
        $creditos = (int) $pacote['creditos'];
        $centavos = (int) round($valor * 100);

        // Teto do preapproval MP: acima disso a cobrança recorrente é recusada.
        $teto = (int) config('services.mercadopago.preapproval_teto_centavos', 400000);
        if ($centavos > $teto) {
            throw new RuntimeException('Valor acima do limite de cobrança automática. Fale com o atendimento.');
        }

        // Uma recarga por usuário (unique user_id): reusa a linha ao reconfigurar.
        $recarga = RecargaAutomatica::updateOrCreate(
            ['user_id' => $user->id],
            [
                'pacote' => $pacote['slug'],
                'creditos' => $creditos,
                'valor' => $valor,
                'frequencia_meses' => 1,
                'status' => RecargaAutomatica::STATUS_PENDENTE,
                'mp_preapproval_id' => null,
            ],
        );

        $resp = $this->client->criarPreapproval([
            'reason' => "FiscalDock — recarga automática ({$creditos} créditos)",
            'payer_email' => $user->email,
            'card_token_id' => $cardToken,
            'auto_recurring' => [
                'frequency' => 1,
                'frequency_type' => 'months',
                'transaction_amount' => $valor,
                'currency_id' => 'BRL',
            ],
            'back_url' => url('/app/creditos'),
            'status' => 'authorized',
            'external_reference' => 'recarga:'.$recarga->id,
        ]);

        $mpId = $resp['id'] ?? null;

        if ($mpId === null) {
            $recarga->update(['status' => RecargaAutomatica::STATUS_CANCELADA]);
            throw new RuntimeException('Mercado Pago não criou a recarga automática: '.json_encode($resp));
        }

        $recarga->update(['mp_preapproval_id' => (string) $mpId]);

        return $recarga->fresh();
    }
}
