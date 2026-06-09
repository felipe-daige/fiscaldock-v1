<?php

namespace App\Actions\MercadoPago;

use App\Models\RecargaAutomatica;
use App\Models\User;
use App\Services\MercadoPago\MercadoPagoClient;
use RuntimeException;

/**
 * Cancela a recarga automática ativa do usuário: PUT status=cancelled no preapproval
 * do MP e marca a linha como cancelada. O webhook de preapproval confirma idempotente.
 */
class CancelarRecargaMercadoPago
{
    public function __construct(private MercadoPagoClient $client = new MercadoPagoClient) {}

    public function execute(User $user): RecargaAutomatica
    {
        $recarga = RecargaAutomatica::where('user_id', $user->id)
            ->whereIn('status', [RecargaAutomatica::STATUS_ATIVA, RecargaAutomatica::STATUS_INADIMPLENTE])
            ->first();

        if ($recarga === null || $recarga->mp_preapproval_id === null) {
            throw new RuntimeException('Nenhuma recarga automática ativa para cancelar.');
        }

        $this->client->cancelarPreapproval($recarga->mp_preapproval_id);
        $recarga->update(['status' => RecargaAutomatica::STATUS_CANCELADA]);

        return $recarga->fresh();
    }
}
