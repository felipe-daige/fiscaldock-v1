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

    protected $table = 'recarga_automaticas';

    protected $fillable = [
        'user_id', 'pacote', 'creditos', 'valor', 'frequencia_meses',
        'status', 'mp_preapproval_id', 'ultima_cobranca_em',
    ];

    protected $casts = [
        'creditos' => 'integer',
        'valor' => 'decimal:2',
        'frequencia_meses' => 'integer',
        'ultima_cobranca_em' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
