<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class XmlImportacao extends Model
{
    protected $table = 'xml_importacoes';

    protected $fillable = [
        'user_id',
        'cliente_id',
        'tipo_documento',
        'modo_envio',
        'total_arquivos',
        'total_xmls',
        'tamanho_total_bytes',
        'xmls_processados',
        'xmls_novos',
        'xmls_duplicados_processados',
        'xmls_com_erro',
        'participantes_novos',
        'participantes_atualizados',
        'participantes_ignorados',
        'valor_total',
        'status',
        'erro_mensagem',
        'participante_ids',
        'erros_detalhados',
        'iniciado_em',
        'concluido_em',
        'tempo_processamento_segundos',
    ];

    protected function casts(): array
    {
        return [
            'total_arquivos' => 'integer',
            'total_xmls' => 'integer',
            'tamanho_total_bytes' => 'integer',
            'xmls_processados' => 'integer',
            'xmls_novos' => 'integer',
            'xmls_duplicados_processados' => 'integer',
            'xmls_com_erro' => 'integer',
            'participantes_novos' => 'integer',
            'participantes_atualizados' => 'integer',
            'participantes_ignorados' => 'integer',
            'valor_total' => 'decimal:2',
            'participante_ids' => 'array',
            'erros_detalhados' => 'array',
            'iniciado_em' => 'datetime',
            'concluido_em' => 'datetime',
            'tempo_processamento_segundos' => 'integer',
        ];
    }

    // Relacionamentos

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function notas(): HasMany
    {
        return $this->hasMany(XmlNota::class, 'importacao_xml_id');
    }

    /**
     * Alias para notas() - mantido para compatibilidade.
     * @deprecated Use notas() instead
     */
    public function notasFiscais(): HasMany
    {
        return $this->notas();
    }

    // Acessores

    /**
     * Total de participantes processados (novos + atualizados).
     */
    public function getTotalParticipantesAttribute(): int
    {
        return $this->participantes_novos + $this->participantes_atualizados;
    }

    /**
     * Tempo de processamento formatado (ex: "2m 34s").
     */
    public function getTempoProcessamentoAttribute(): string
    {
        $seconds = $this->tempo_processamento_segundos;

        if ($seconds === null) {
            if (! $this->iniciado_em || ! $this->concluido_em) {
                return '—';
            }
            $seconds = (int) $this->iniciado_em->diffInSeconds($this->concluido_em);
        }

        $h = intdiv($seconds, 3600);
        $m = intdiv($seconds % 3600, 60);
        $s = $seconds % 60;

        if ($h > 0) {
            return $h.'h '.$m.'m';
        }
        if ($m > 0) {
            return $m.'m '.$s.'s';
        }
        if ($s > 0) {
            return $s.'s';
        }

        return '< 1s';
    }

    /**
     * Tamanho formatado em MB.
     */
    public function getTamanhoFormatadoAttribute(): string
    {
        $bytes = $this->tamanho_total_bytes;
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' bytes';
    }

    // Scopes

    public function scopeDoUsuario($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopePendentes($query)
    {
        return $query->where('status', 'pendente');
    }

    public function scopeProcessando($query)
    {
        return $query->where('status', 'processando');
    }

    public function scopeConcluidas($query)
    {
        return $query->where('status', 'concluido');
    }

    public function scopeComErro($query)
    {
        return $query->where('status', 'erro');
    }
}
