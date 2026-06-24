<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParticipanteScore extends Model
{
    protected $table = 'participante_scores';

    protected $fillable = [
        'participante_id',
        'cliente_id',
        'user_id',
        'score_cadastral',
        'score_cnd_federal',
        'score_cnd_estadual',
        'score_fgts',
        'score_trabalhista',
        'score_compliance',
        'score_esg',
        'score_protestos',
        'score_total',
        'score_credito_reforma',
        'classificacao',
        'ultima_consulta_em',
        'proxima_consulta_em',
        'dados_consultados',
    ];

    protected function casts(): array
    {
        return [
            'score_cadastral' => 'integer',
            'score_cnd_federal' => 'integer',
            'score_cnd_estadual' => 'integer',
            'score_fgts' => 'integer',
            'score_trabalhista' => 'integer',
            'score_compliance' => 'integer',
            'score_esg' => 'integer',
            'score_protestos' => 'integer',
            'score_total' => 'integer',
            'score_credito_reforma' => 'integer',
            'ultima_consulta_em' => 'datetime',
            'proxima_consulta_em' => 'datetime',
            'dados_consultados' => 'array',
        ];
    }

    // Relacionamentos

    public function participante(): BelongsTo
    {
        return $this->belongsTo(Participante::class);
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Entidade pontuada: participante (contraparte) OU cliente (empresa gerida/própria).
     */
    public function alvo(): ?Model
    {
        return $this->participante ?: $this->cliente;
    }

    public function getAlvoTipoAttribute(): string
    {
        return $this->participante_id ? 'participante' : 'cliente';
    }

    public function getAlvoNomeAttribute(): string
    {
        return $this->alvo()?->razao_social ?? 'N/A';
    }

    public function getAlvoNomeFantasiaAttribute(): ?string
    {
        return $this->alvo()?->nome_fantasia;
    }

    public function getAlvoUfAttribute(): ?string
    {
        return $this->alvo()?->uf;
    }

    /**
     * CNPJ formatado do alvo (00.000.000/0000-00). Sem formatar se não tiver 14 dígitos.
     */
    public function getAlvoDocumentoAttribute(): string
    {
        $doc = preg_replace('/\D/', '', (string) ($this->alvo()?->documento ?? ''));

        if (strlen($doc) !== 14) {
            return $doc;
        }

        return preg_replace('/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})$/', '$1.$2.$3/$4-$5', $doc);
    }

    // Acessores

    /**
     * Retorna o label legivel da classificacao.
     */
    public function getClassificacaoLabelAttribute(): string
    {
        return match ($this->classificacao) {
            'baixo' => 'Baixo Risco',
            'medio' => 'Medio Risco',
            'alto' => 'Alto Risco',
            'critico' => 'Risco Critico',
            default => 'Nao Avaliado',
        };
    }

    /**
     * Retorna a cor CSS para a classificacao.
     */
    public function getClassificacaoCorAttribute(): string
    {
        return match ($this->classificacao) {
            'baixo' => 'green',
            'medio' => 'yellow',
            'alto' => 'orange',
            'critico' => 'red',
            default => 'gray',
        };
    }

    /**
     * Retorna as classes Tailwind para o badge de classificacao.
     */
    public function getClassificacaoBadgeClassAttribute(): string
    {
        return match ($this->classificacao) {
            'baixo' => 'bg-green-100 text-green-700',
            'medio' => 'bg-yellow-100 text-yellow-700',
            'alto' => 'bg-orange-100 text-orange-700',
            'critico' => 'bg-red-100 text-red-700',
            default => 'bg-gray-100 text-gray-700',
        };
    }

    /**
     * Categorias avaliáveis com seus subscores. `score = null` → não avaliado nesta consulta
     * (fonte não consultada / INDETERMINADA). ESG e Protestos ficam de fora (sem fonte —
     * exibidos como "em breve" na UI; ver docs/score-fiscal/README.md).
     */
    public function getScoresDetalhadosAttribute(): array
    {
        return [
            'cadastral' => [
                'label' => 'Situação Cadastral',
                'score' => $this->score_cadastral,
                'avaliado' => $this->score_cadastral !== null,
            ],
            'cnd_federal' => [
                'label' => 'CND Federal',
                'score' => $this->score_cnd_federal,
                'avaliado' => $this->score_cnd_federal !== null,
            ],
            'cnd_estadual' => [
                'label' => 'CND Estadual',
                'score' => $this->score_cnd_estadual,
                'avaliado' => $this->score_cnd_estadual !== null,
            ],
            'fgts' => [
                'label' => 'FGTS/CRF',
                'score' => $this->score_fgts,
                'avaliado' => $this->score_fgts !== null,
            ],
            'trabalhista' => [
                'label' => 'CNDT (Trabalhista)',
                'score' => $this->score_trabalhista,
                'avaliado' => $this->score_trabalhista !== null,
            ],
            'compliance' => [
                'label' => 'Sanções (CGU/CNJ)',
                'score' => $this->score_compliance,
                'avaliado' => $this->score_compliance !== null,
            ],
        ];
    }

    /**
     * Verifica se o score esta desatualizado (mais de 30 dias).
     */
    public function isDesatualizado(): bool
    {
        if (! $this->ultima_consulta_em) {
            return true;
        }

        return $this->ultima_consulta_em->diffInDays(now()) > 30;
    }

    // Scopes

    public function scopeDoUsuario($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeBaixoRisco($query)
    {
        return $query->where('classificacao', 'baixo');
    }

    public function scopeMedioRisco($query)
    {
        return $query->where('classificacao', 'medio');
    }

    public function scopeAltoRisco($query)
    {
        return $query->where('classificacao', 'alto');
    }

    public function scopeCritico($query)
    {
        return $query->where('classificacao', 'critico');
    }

    public function scopeDesatualizados($query)
    {
        return $query->where('ultima_consulta_em', '<', now()->subDays(30));
    }
}
