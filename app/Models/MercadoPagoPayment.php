<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pagamento via Mercado Pago (Fase 1 — fundação de pagamentos + pacote avulso).
 *
 * Estados: pending -> approved (credita) | rejected | cancelled | refunded (futuro).
 * Idempotência: UNIQUE em mp_payment_id + idempotency_key; credited_at trava liberação dupla.
 */
class MercadoPagoPayment extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_REFUNDED = 'refunded';

    protected $fillable = [
        'user_id',
        'pacote',
        'mp_payment_id',
        'mp_preference_id',
        'status',
        'status_detail',
        'payment_method',
        'valor',
        'creditos',
        'idempotency_key',
        'credited_at',
        'payload',
    ];

    protected $casts = [
        'valor' => 'decimal:2',
        'creditos' => 'integer',
        'credited_at' => 'datetime',
        'payload' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * Já liberou créditos? (guard de idempotência da liberação).
     */
    public function jaCreditado(): bool
    {
        return $this->credited_at !== null;
    }
}
