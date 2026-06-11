<?php

namespace App\Models;

use App\Support\SystemCriticalError;
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
        'filename',
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
     *
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
            return number_format($bytes / 1048576, 2).' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2).' KB';
        }

        return $bytes.' bytes';
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

    public function scopeTravadas($query)
    {
        return $query->where('status', 'processando')
            ->where('updated_at', '<', now()->subMinutes((int) config('importacao.stale_minutos')));
    }

    public function marcarComoTravada(): void
    {
        $this->update([
            'status' => 'erro',
            'erro_mensagem' => 'Importação interrompida — o processamento ficou sem resposta. Tente reenviar o arquivo.',
        ]);
    }

    /**
     * Retorna o erro pronto para exibição pública.
     *
     * @return array<string, string>
     */
    public function publicErrorUi(array $context = []): array
    {
        if ($this->status !== 'erro' && blank($this->erro_mensagem)) {
            return [];
        }

        $defaultContext = [
            'context' => 'importacao-xml',
            'reference' => $this->id ? 'Importação #'.$this->id : null,
        ];

        return app(SystemCriticalError::class)->forAsyncFailure(
            $this->erro_mensagem,
            null,
            array_merge($defaultContext, $context)
        );
    }

    public function publicErrorMessage(array $context = []): string
    {
        return $this->publicErrorUi($context)['message'] ?? '';
    }

    /**
     * Nº de clientes-DONO distintos resolvidos nas notas deste lote (por-nota).
     * Usado pra exibir "Vários (N clientes)" quando o header não tem FK única.
     */
    public function clientesResolvidos(): int
    {
        return (int) XmlNota::where('importacao_xml_id', $this->id)
            ->whereNotNull('cliente_id')
            ->distinct()
            ->count('cliente_id');
    }

    /**
     * Cliente-dono único do lote, ou null. Header recebe FK só quando o lote está
     * TODO resolvido para exatamente 1 dono; >1 dono ou nota sem dono → null ("Vários").
     */
    public function resolverHeaderClienteId(): ?int
    {
        $temSemDono = XmlNota::where('importacao_xml_id', $this->id)->whereNull('cliente_id')->exists();
        $donos = XmlNota::where('importacao_xml_id', $this->id)->whereNotNull('cliente_id')->distinct()->pluck('cliente_id');

        return (! $temSemDono && $donos->count() === 1) ? (int) $donos->first() : null;
    }

    /**
     * Regra de header no modo ÂNCORA (cliente escolhido no upload): >1 dono distinto
     * resolvido → null ("Vários"); exatamente 1 → esse dono; nenhum resolvido → a âncora
     * (honra a escolha). Notas sem dono NÃO rebaixam o header (diferente de resolverHeaderClienteId).
     */
    public function resolverHeaderClienteIdAncora(?int $anchor): ?int
    {
        $donos = XmlNota::where('importacao_xml_id', $this->id)
            ->whereNotNull('cliente_id')
            ->distinct()
            ->pluck('cliente_id');

        if ($donos->count() > 1) {
            return null;
        }
        if ($donos->count() === 1) {
            return (int) $donos->first();
        }

        return $anchor ?: null;
    }
}
