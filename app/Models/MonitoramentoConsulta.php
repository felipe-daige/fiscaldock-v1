<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonitoramentoConsulta extends Model
{
    use HasFactory;

    protected $table = 'monitoramento_consultas';

    protected $fillable = [
        'user_id',
        'participante_id',
        'plano_id',
        'assinatura_id',
        'tipo',
        'status',
        'resultado',
        'situacao_geral',
        'tem_pendencias',
        'proxima_validade',
        'creditos_cobrados',
        'error_code',
        'error_message',
        'executado_em',
    ];

    protected $casts = [
        'resultado' => 'array',
        'tem_pendencias' => 'boolean',
        'proxima_validade' => 'date',
        'creditos_cobrados' => 'integer',
        'executado_em' => 'datetime',
    ];

    /**
     * Usuário que solicitou a consulta.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Participante consultado.
     */
    public function participante(): BelongsTo
    {
        return $this->belongsTo(Participante::class);
    }

    /**
     * Plano usado na consulta.
     */
    public function plano(): BelongsTo
    {
        return $this->belongsTo(MonitoramentoPlano::class, 'plano_id');
    }

    /**
     * Assinatura associada (se for consulta de assinatura).
     */
    public function assinatura(): BelongsTo
    {
        return $this->belongsTo(MonitoramentoAssinatura::class, 'assinatura_id');
    }

    /**
     * Verifica se a consulta está pendente.
     */
    public function isPendente(): bool
    {
        return $this->status === 'pendente';
    }

    /**
     * Verifica se a consulta está processando.
     */
    public function isProcessando(): bool
    {
        return $this->status === 'processando';
    }

    /**
     * Verifica se a consulta foi concluída com sucesso.
     */
    public function isSucesso(): bool
    {
        return $this->status === 'sucesso';
    }

    /**
     * Verifica se a consulta teve erro.
     */
    public function isErro(): bool
    {
        return $this->status === 'erro';
    }

    /**
     * Verifica se a situacao geral e regular.
     */
    public function isRegular(): bool
    {
        return $this->situacao_geral === 'regular';
    }

    /**
     * Verifica se a situacao geral requer atencao.
     */
    public function isAtencao(): bool
    {
        return $this->situacao_geral === 'atencao';
    }

    /**
     * Verifica se a situacao geral e irregular.
     */
    public function isIrregular(): bool
    {
        return $this->situacao_geral === 'irregular';
    }

    /**
     * Marca como processando.
     */
    public function marcarProcessando(): void
    {
        $this->update(['status' => 'processando']);
    }

    /**
     * Marca como sucesso com resultado e campos de filtro.
     */
    public function marcarSucesso(
        array $resultado,
        ?string $situacaoGeral = null,
        bool $temPendencias = false,
        ?string $proximaValidade = null
    ): void {
        $this->update([
            'status' => 'sucesso',
            'resultado' => $resultado,
            'situacao_geral' => $situacaoGeral,
            'tem_pendencias' => $temPendencias,
            'proxima_validade' => $proximaValidade,
            'executado_em' => now(),
        ]);
    }

    /**
     * Marca como erro.
     */
    public function marcarErro(string $code, string $message): void
    {
        $this->update([
            'status' => 'erro',
            'error_code' => $code,
            'error_message' => $message,
            'executado_em' => now(),
        ]);
    }

    /**
     * Consultas do usuário.
     */
    public static function doUsuario(int $userId)
    {
        return static::where('user_id', $userId)->orderBy('created_at', 'desc');
    }

    /**
     * Scope: consultas com pendencias.
     */
    public function scopeComPendencias($query)
    {
        return $query->where('tem_pendencias', true);
    }

    /**
     * Scope: consultas por situacao geral.
     */
    public function scopePorSituacao($query, string $situacao)
    {
        return $query->where('situacao_geral', $situacao);
    }

    /**
     * Scope: certidoes com validade proxima (dentro de X dias).
     */
    public function scopeValidadeProxima($query, int $dias = 30)
    {
        return $query->whereNotNull('proxima_validade')
            ->where('proxima_validade', '<=', now()->addDays($dias));
    }

    /**
     * Scope: ultima consulta por participante.
     */
    public function scopeUltimaPorParticipante($query)
    {
        return $query->whereIn('id', function ($subquery) {
            $subquery->selectRaw('MAX(id)')
                ->from('monitoramento_consultas')
                ->groupBy('participante_id');
        });
    }
}
