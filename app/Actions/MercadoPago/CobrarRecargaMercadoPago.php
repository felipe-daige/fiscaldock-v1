<?php

namespace App\Actions\MercadoPago;

use App\Models\MercadoPagoPayment;
use App\Models\RecargaAutomatica;
use App\Services\CreditService;
use App\Services\MercadoPago\MercadoPagoClient;
use Illuminate\Support\Facades\DB;

/**
 * Processa uma cobrança recorrente (authorized_payment) de uma recarga automática.
 *
 * Diferente da assinatura de tier (onde o crédito é do scheduler), aqui CADA cobrança
 * aprovada LIBERA os créditos do pacote — de forma idempotente (1 linha por
 * authorized_payment em mercado_pago_payments, credited_at trava liberação dupla).
 *
 * Retorna null quando o authorized_payment não pertence a uma recarga (deixa o webhook
 * tentar outros donos, ex.: assinatura de tier).
 */
class CobrarRecargaMercadoPago
{
    public function __construct(
        private MercadoPagoClient $client = new MercadoPagoClient,
        private CreditService $credits = new CreditService,
    ) {}

    public function execute(string $authorizedPaymentId): ?MercadoPagoPayment
    {
        $dados = $this->client->buscarAuthorizedPayment($authorizedPaymentId);
        $status = $dados['status'] ?? null;
        $preapprovalId = $dados['preapproval_id'] ?? null;

        return DB::transaction(function () use ($authorizedPaymentId, $status, $preapprovalId, $dados) {
            $recarga = $preapprovalId
                ? RecargaAutomatica::lockForUpdate()->where('mp_preapproval_id', $preapprovalId)->first()
                : null;

            if ($recarga === null) {
                return null;
            }

            // Idempotência: 1 linha por authorized_payment.
            $pagamento = MercadoPagoPayment::lockForUpdate()
                ->firstOrNew(['mp_payment_id' => $authorizedPaymentId]);

            if (! $pagamento->exists) {
                $pagamento->fill([
                    'user_id' => $recarga->user_id,
                    'tipo' => 'recarga',
                    'pacote' => $recarga->pacote,
                    'valor' => $recarga->valor,
                    'creditos' => $recarga->creditos,
                    'idempotency_key' => 'recarga-ap-'.$authorizedPaymentId,
                ]);
            }
            $pagamento->fill(['status' => $status ?? 'unknown', 'payload' => $dados])->save();

            // Libera créditos só uma vez, só quando aprovado.
            if ($status === 'approved' && ! $pagamento->jaCreditado()) {
                $this->credits->add(
                    $recarga->user,
                    (float) $recarga->creditos,
                    'purchase',
                    "Recarga automática — {$recarga->creditos} créditos (Mercado Pago #{$authorizedPaymentId})",
                    $pagamento,
                );
                $pagamento->credited_at = now();
                $pagamento->save();

                $recarga->update([
                    'status' => RecargaAutomatica::STATUS_ATIVA,
                    'ultima_cobranca_em' => now(),
                ]);
            } elseif (in_array($status, ['rejected', 'cancelled'], true)) {
                $recarga->update(['status' => RecargaAutomatica::STATUS_INADIMPLENTE]);
            }

            return $pagamento;
        });
    }
}
