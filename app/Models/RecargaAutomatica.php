<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Recarga automática por tempo (Fase 2): recompra recorrente de um pacote de créditos
 * via preapproval do Mercado Pago. A cada cobrança aprovada (subscription_authorized_payment),
 * os créditos do pacote são liberados (idempotente por authorized_payment).
 */
class RecargaAutomatica extends Model
{
    public const STATUS_PENDENTE = 'pendente';

    public const STATUS_ATIVA = 'ativa';

    public const STATUS_INADIMPLENTE = 'inadimplente';

    public const STATUS_CANCELADA = 'cancelada';

    public const GATILHO_TEMPO = 'tempo';

    public const GATILHO_SALDO = 'saldo';

    protected $table = 'recarga_automaticas';

    protected $fillable = [
        'user_id', 'pacote', 'creditos', 'valor', 'frequencia_meses',
        'status', 'mp_preapproval_id', 'ultima_cobranca_em',
        'gatilho', 'limite_creditos', 'mp_customer_id', 'mp_card_id',
        'cobranca_em_andamento', 'ultima_tentativa_em',
    ];

    protected $casts = [
        'creditos' => 'integer',
        'valor' => 'decimal:2',
        'frequencia_meses' => 'integer',
        'limite_creditos' => 'integer',
        'cobranca_em_andamento' => 'boolean',
        'ultima_cobranca_em' => 'datetime',
        'ultima_tentativa_em' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function ehSaldo(): bool
    {
        return $this->gatilho === self::GATILHO_SALDO;
    }

    public function ehTempo(): bool
    {
        return $this->gatilho === self::GATILHO_TEMPO;
    }
}
