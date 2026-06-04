<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EfdDivergencia extends Model
{
    protected $table = 'efd_divergencias';

    protected $fillable = [
        'importacao_id',
        'user_id',
        'bloco',
        'motivo',
        'severidade',
        'chave_acesso',
        'numero_documento',
        'numero_item',
        'payload_descartado',
        'mensagem',
        'detectado_em',
        'resolvido_em',
    ];

    protected function casts(): array
    {
        return [
            'payload_descartado' => 'array',
            'detectado_em' => 'datetime',
            'resolvido_em' => 'datetime',
        ];
    }

    public const SEVERIDADE_INFO = 'info';

    public const SEVERIDADE_AVISO = 'aviso';

    public const SEVERIDADE_ERRO = 'erro';

    public const MOTIVO_CANCELADA_DESCARTADA = 'cancelada_descartada';

    public const MOTIVO_COMPLEMENTAR_DESCARTADA = 'complementar_descartada';

    public const MOTIVO_REGULARIZACAO_DESCARTADA = 'regularizacao_descartada';

    public const MOTIVO_DUPLICADA_PROCESSAMENTO = 'duplicada_processamento';

    public const MOTIVO_CONSTRAINT_VIOLADA = 'constraint_violada';

    public const MOTIVO_PAI_NAO_ENCONTRADO = 'pai_nao_encontrado';

    public const MOTIVO_PARSE_INCONSISTENTE = 'parse_inconsistente';

    public const MOTIVO_VALOR_DIVERGENTE = 'valor_divergente';

    public function importacao(): BelongsTo
    {
        return $this->belongsTo(EfdImportacao::class, 'importacao_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeDoUsuario($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeDaImportacao($query, int $importacaoId)
    {
        return $query->where('importacao_id', $importacaoId);
    }

    public function scopeDoMotivo($query, string $motivo)
    {
        return $query->where('motivo', $motivo);
    }

    public function scopeNaoResolvidas($query)
    {
        return $query->whereNull('resolvido_em');
    }
}
