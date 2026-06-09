<?php

namespace App\Actions\MercadoPago;

use App\Models\RecargaAutomatica;
use App\Services\MercadoPago\MercadoPagoClient;
use Illuminate\Support\Facades\DB;

/**
 * Ciclo de vida da recarga automática (preapproval). Consulta o MP como fonte de
 * verdade. NÃO concede crédito aqui — a concessão é por cobrança (authorized_payment).
 *
 * Retorna null quando o preapproval não pertence a uma recarga (deixa o webhook
 * tentar outros donos, ex.: assinatura de tier).
 */
class AtivarRecargaMercadoPago
{
    public function __construct(private MercadoPagoClient $client = new MercadoPagoClient) {}

    public function execute(string $preapprovalId): ?RecargaAutomatica
    {
        $dados = $this->client->buscarPreapproval($preapprovalId);
        $status = $dados['status'] ?? null;

        return DB::transaction(function () use ($preapprovalId, $status) {
            $recarga = RecargaAutomatica::lockForUpdate()
                ->where('mp_preapproval_id', $preapprovalId)->first();

            if ($recarga === null) {
                return null;
            }

            if ($status === 'authorized') {
                $recarga->update(['status' => RecargaAutomatica::STATUS_ATIVA]);
            } elseif (in_array($status, ['cancelled', 'finished'], true)) {
                $recarga->update(['status' => RecargaAutomatica::STATUS_CANCELADA]);
            } elseif ($status === 'paused') {
                $recarga->update(['status' => RecargaAutomatica::STATUS_INADIMPLENTE]);
            }

            return $recarga->fresh();
        });
    }
}
