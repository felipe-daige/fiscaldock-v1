<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ConsultaLote extends Model
{
    use HasFactory;

    protected $table = 'consulta_lotes';

    public const STATUS_PENDENTE = 'pendente';

    public const STATUS_PROCESSANDO = 'processando';

    public const STATUS_CONCLUIDO = 'concluido';

    public const STATUS_ERRO = 'erro';

    protected $fillable = [
        'user_id',
        'cliente_id',
        'plano_id',
        'status',
        'total_participantes',
        'creditos_cobrados',
        'tab_id',
        'resultado_resumo',
        'error_code',
        'error_message',
        'processado_em',
    ];

    protected $casts = [
        'total_participantes' => 'integer',
        'creditos_cobrados' => 'integer',
        'resultado_resumo' => 'array',
        'processado_em' => 'datetime',
    ];

    /**
     * Usuário dono do lote.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Cliente associado (opcional).
     */
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    /**
     * Plano de consulta utilizado.
     */
    public function plano(): BelongsTo
    {
        return $this->belongsTo(MonitoramentoPlano::class, 'plano_id');
    }

    /**
     * Participantes incluídos neste lote.
     */
    public function participantes(): BelongsToMany
    {
        return $this->belongsToMany(Participante::class, 'consulta_lote_participantes', 'consulta_lote_id', 'participante_id')
            ->withPivot('created_at');
    }

    /**
     * Resultados individuais das consultas (um por participante).
     */
    public function resultados(): HasMany
    {
        return $this->hasMany(ConsultaResultado::class, 'consulta_lote_id');
    }

    /**
     * Verifica se há resultados disponíveis para gerar relatório.
     */
    public function hasResultados(): bool
    {
        return $this->resultados()->where('status', ConsultaResultado::STATUS_SUCESSO)->exists();
    }

    /**
     * Conta resultados por status.
     */
    public function getContadoresResultados(): array
    {
        return [
            'total' => $this->resultados()->count(),
            'sucesso' => $this->resultados()->where('status', ConsultaResultado::STATUS_SUCESSO)->count(),
            'erro' => $this->resultados()->whereIn('status', [ConsultaResultado::STATUS_ERRO, ConsultaResultado::STATUS_TIMEOUT])->count(),
            'pendente' => $this->resultados()->where('status', ConsultaResultado::STATUS_PENDENTE)->count(),
        ];
    }

    /**
     * Verifica se o lote está em processamento.
     */
    public function isProcessando(): bool
    {
        return $this->status === self::STATUS_PROCESSANDO;
    }

    /**
     * Verifica se o lote foi concluído.
     */
    public function isConcluido(): bool
    {
        return $this->status === self::STATUS_CONCLUIDO;
    }

    /**
     * Verifica se o lote teve erro.
     */
    public function isErro(): bool
    {
        return $this->status === self::STATUS_ERRO;
    }

    /**
     * Retorna a badge de status formatada.
     */
    public function getStatusBadgeAttribute(): array
    {
        return match ($this->status) {
            self::STATUS_PENDENTE => ['label' => 'Pendente', 'class' => 'bg-gray-100 text-gray-800'],
            self::STATUS_PROCESSANDO => ['label' => 'Processando', 'class' => 'bg-blue-100 text-blue-800'],
            self::STATUS_CONCLUIDO => ['label' => 'Concluído', 'class' => 'bg-green-100 text-green-800'],
            self::STATUS_ERRO => ['label' => 'Erro', 'class' => 'bg-red-100 text-red-800'],
            default => ['label' => $this->status, 'class' => 'bg-gray-100 text-gray-800'],
        };
    }
}
