<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pendência/nota operacional do operador FiscalDock (admin). Compartilhada entre admins,
 * NÃO é per-tenant (ao contrário de App\Models\Alerta, que é fiscal e por usuário).
 * "Vencida" = aberta com lembrar_em <= hoje (derivado, não persistido).
 */
class AdminPendencia extends Model
{
    public const STATUS_ABERTA = 'aberta';
    public const STATUS_RESOLVIDA = 'resolvida';

    protected $table = 'admin_pendencias';

    protected $fillable = [
        'titulo', 'nota', 'lembrar_em', 'status',
        'criado_por', 'resolvido_por', 'resolvido_em',
    ];

    protected function casts(): array
    {
        return [
            'lembrar_em' => 'date',
            'resolvido_em' => 'datetime',
        ];
    }

    public function scopeAbertas(Builder $q): Builder
    {
        return $q->where('status', self::STATUS_ABERTA);
    }

    public function scopeResolvidas(Builder $q): Builder
    {
        return $q->where('status', self::STATUS_RESOLVIDA);
    }

    public function scopeVencidas(Builder $q): Builder
    {
        return $q->abertas()->whereNotNull('lembrar_em')->whereDate('lembrar_em', '<=', now());
    }

    public function getEstaVencidaAttribute(): bool
    {
        return $this->status === self::STATUS_ABERTA
            && $this->lembrar_em !== null
            && $this->lembrar_em->lte(now());
    }

    public function criadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'criado_por');
    }

    public function resolvidoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolvido_por');
    }
}
