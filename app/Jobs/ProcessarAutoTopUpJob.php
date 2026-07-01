<?php

namespace App\Jobs;

use App\Actions\MercadoPago\CobrarAutoTopUp;
use App\Mail\RecargaAutomaticaPausada;
use App\Models\MercadoPagoPayment;
use App\Models\RecargaAutomatica;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * Processa um auto top-up por saldo baixo (cobrança on-demand).
 *
 * Sob lock: re-checa gatilho/status/saldo/flag/cooldown e o teto diário (defesa em
 * profundidade contra runaway). Só então marca "em voo" e dispara a cobrança via
 * CobrarAutoTopUp. O crédito em si vem pelo webhook `payment` (idempotente).
 */
class ProcessarAutoTopUpJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public int $userId) {}

    public function handle(CobrarAutoTopUp $cobrar = new CobrarAutoTopUp): void
    {
        $emailPausado = null;

        $recarga = DB::transaction(function () use (&$emailPausado) {
            $r = RecargaAutomatica::where('user_id', $this->userId)
                ->where('gatilho', RecargaAutomatica::GATILHO_SALDO)
                ->where('status', RecargaAutomatica::STATUS_ATIVA)
                ->lockForUpdate()
                ->first();

            if ($r === null || $r->cobranca_em_andamento || $r->limite_creditos === null) {
                return null;
            }

            $user = User::find($this->userId);
            if ($user === null || (int) $user->credits >= (int) $r->limite_creditos) {
                return null;
            }

            $cooldown = (int) config('services.mercadopago.auto_topup.cooldown_minutos', 5);
            if ($r->ultima_tentativa_em && $r->ultima_tentativa_em->gt(now()->subMinutes($cooldown))) {
                return null;
            }

            // Teto diário (defesa em profundidade contra runaway).
            $max = (int) config('services.mercadopago.auto_topup.max_por_dia', 3);
            $hoje = MercadoPagoPayment::where('user_id', $this->userId)
                ->where('tipo', 'auto_topup')
                ->where('created_at', '>=', now()->startOfDay())
                ->count();
            if ($hoje >= $max) {
                $r->update(['status' => RecargaAutomatica::STATUS_INADIMPLENTE]);
                $emailPausado = $user;

                return null;
            }

            $r->update(['cobranca_em_andamento' => true, 'ultima_tentativa_em' => now()]);

            return $r;
        });

        if ($emailPausado !== null) {
            Mail::to($emailPausado->email)->queue(new RecargaAutomaticaPausada($emailPausado, 'limite diário de recargas atingido'));
        }

        if ($recarga !== null) {
            $cobrar->execute($recarga);
        }
    }
}
