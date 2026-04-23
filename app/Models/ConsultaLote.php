<?php

namespace App\Models;

use App\Support\SystemCriticalError;
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

    public const STATUS_FINALIZADO = 'finalizado';

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
        return $this->isFinalizado();
    }

    /**
     * Verifica se o lote foi finalizado com sucesso.
     */
    public function isFinalizado(): bool
    {
        return self::isSuccessfulStatus($this->status);
    }

    /**
     * Verifica se o lote teve erro.
     */
    public function isErro(): bool
    {
        return $this->status === self::STATUS_ERRO;
    }

    /**
     * Status de sucesso aceitos para compatibilidade.
     */
    public static function successfulStatuses(): array
    {
        return [
            self::STATUS_FINALIZADO,
            self::STATUS_CONCLUIDO,
        ];
    }

    /**
     * Verifica se o valor representa um status terminal de sucesso.
     */
    public static function isSuccessfulStatus(?string $status): bool
    {
        return in_array($status, self::successfulStatuses(), true);
    }

    /**
     * Normaliza o status legado para a semântica atual.
     */
    public static function normalizeStatus(?string $status): string
    {
        if (self::isSuccessfulStatus($status)) {
            return self::STATUS_FINALIZADO;
        }

        return $status ?: self::STATUS_PENDENTE;
    }

    /**
     * Retorna label legível para o status.
     */
    public static function statusLabel(?string $status): string
    {
        return match (self::normalizeStatus($status)) {
            self::STATUS_PROCESSANDO => 'Processando',
            self::STATUS_FINALIZADO => 'Finalizado',
            self::STATUS_ERRO => 'Erro',
            default => 'Pendente',
        };
    }

    /**
     * Retorna a badge de status formatada.
     */
    public function getStatusBadgeAttribute(): array
    {
        return match (self::normalizeStatus($this->status)) {
            self::STATUS_PENDENTE => ['label' => 'Pendente', 'class' => 'bg-gray-100 text-gray-800'],
            self::STATUS_PROCESSANDO => ['label' => 'Processando', 'class' => 'bg-blue-100 text-blue-800'],
            self::STATUS_FINALIZADO => ['label' => 'Finalizado', 'class' => 'bg-green-100 text-green-800'],
            self::STATUS_ERRO => ['label' => 'Erro', 'class' => 'bg-red-100 text-red-800'],
            default => ['label' => $this->status, 'class' => 'bg-gray-100 text-gray-800'],
        };
    }

    /**
     * Retorna o erro pronto para exibição pública.
     *
     * @return array<string, string>
     */
    public function publicErrorUi(array $context = []): array
    {
        $defaultContext = [
            'context' => 'consulta-lote',
            'reference' => $this->id ? 'Lote #'.$this->id : null,
        ];

        return app(SystemCriticalError::class)->forAsyncFailure(
            $this->error_message,
            $this->error_code,
            array_merge($defaultContext, $context)
        );
    }

    public function publicErrorMessage(array $context = []): string
    {
        return $this->publicErrorUi($context)['message'];
    }
}
