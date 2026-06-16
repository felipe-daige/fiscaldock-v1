<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * LGPD fase 2.1 — registro auditável (append-only) de um evento de consentimento.
 *
 * Imutável por convenção: nunca atualizar/apagar uma linha — cada ato gera uma nova.
 * Só tem `created_at` (sem `updated_at`).
 */
class ConsentLog extends Model
{
    public const TIPO_TERMOS = 'termos';

    public const TIPO_PRIVACIDADE = 'privacidade';

    public const TIPO_MARKETING = 'marketing';

    public const TIPO_EXCLUSAO = 'exclusao';

    public const ACAO_ACEITE = 'aceite';

    public const ACAO_REVOGACAO = 'revogacao';

    public const ACAO_SOLICITACAO = 'solicitacao';

    public const ACAO_CANCELAMENTO = 'cancelamento';

    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'tipo',
        'acao',
        'valor',
        'versao',
        'ip',
        'user_agent',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'valor' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
