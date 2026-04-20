<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class XmlNota extends Model
{
    protected $table = 'xml_notas';

    protected $fillable = [
        'user_id',
        'importacao_xml_id',
        'cliente_id',
        'nfe_id',
        'tipo_documento',
        'origem',
        'numero_nota',
        'serie',
        'data_emissao',
        'natureza_operacao',
        'valor_total',
        'tipo_nota',
        'finalidade',
        'chave_referenciada',
        'emit_cnpj',
        'emit_razao_social',
        'emit_uf',
        'emit_participante_id',
        'emit_cliente_id',
        'dest_cnpj',
        'dest_razao_social',
        'dest_uf',
        'dest_participante_id',
        'dest_cliente_id',
        'icms_valor',
        'icms_st_valor',
        'pis_valor',
        'cofins_valor',
        'ipi_valor',
        'tributos_total',
        'payload',
        'validacao',
    ];

    protected function casts(): array
    {
        return [
            'numero_nota' => 'integer',
            'serie' => 'integer',
            'data_emissao' => 'datetime',
            'valor_total' => 'decimal:2',
            'tipo_nota' => 'integer',
            'finalidade' => 'integer',
            'icms_valor' => 'decimal:2',
            'icms_st_valor' => 'decimal:2',
            'pis_valor' => 'decimal:2',
            'cofins_valor' => 'decimal:2',
            'ipi_valor' => 'decimal:2',
            'tributos_total' => 'decimal:2',
            'payload' => 'array',
            'validacao' => 'array',
        ];
    }

    // Constantes para tipo_nota
    public const TIPO_ENTRADA = 0;

    public const TIPO_SAIDA = 1;

    // Constantes para finalidade
    public const FINALIDADE_NORMAL = 1;

    public const FINALIDADE_COMPLEMENTAR = 2;

    public const FINALIDADE_AJUSTE = 3;

    public const FINALIDADE_DEVOLUCAO = 4;

    // Relacionamentos

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function importacao(): BelongsTo
    {
        return $this->belongsTo(XmlImportacao::class, 'importacao_xml_id');
    }

    /**
     * Alias for importacao() - kept for compatibility.
     */
    public function importacaoXml(): BelongsTo
    {
        return $this->importacao();
    }

    public function emitente(): BelongsTo
    {
        return $this->belongsTo(Participante::class, 'emit_participante_id');
    }

    public function destinatario(): BelongsTo
    {
        return $this->belongsTo(Participante::class, 'dest_participante_id');
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function emitCliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'emit_cliente_id');
    }

    public function destCliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'dest_cliente_id');
    }

    /**
     * Nota XML referenciada (para devoluções).
     */
    public function notaReferenciada(): ?XmlNota
    {
        if (! $this->chave_referenciada) {
            return null;
        }

        return static::where('user_id', $this->user_id)
            ->where('nfe_id', $this->chave_referenciada)
            ->first();
    }

    // Acessores

    /**
     * CNPJ do emitente formatado.
     */
    public function getEmitCnpjFormatadoAttribute(): string
    {
        $cnpj = preg_replace('/[^0-9]/', '', $this->emit_cnpj);
        if (strlen($cnpj) === 14) {
            return preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $cnpj);
        }

        return $this->emit_cnpj;
    }

    /**
     * CNPJ do destinatário formatado.
     */
    public function getDestCnpjFormatadoAttribute(): string
    {
        $cnpj = preg_replace('/[^0-9]/', '', $this->dest_cnpj);
        if (strlen($cnpj) === 14) {
            return preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $cnpj);
        }

        return $this->dest_cnpj;
    }

    /**
     * Valor formatado em BRL.
     */
    public function getValorFormatadoAttribute(): string
    {
        return 'R$ '.number_format((float) $this->valor_total, 2, ',', '.');
    }

    /**
     * Verifica se é uma devolução.
     */
    public function isDevolucao(): bool
    {
        return $this->finalidade === self::FINALIDADE_DEVOLUCAO;
    }

    /**
     * Verifica se é nota de entrada.
     */
    public function isEntrada(): bool
    {
        return $this->tipo_nota === self::TIPO_ENTRADA;
    }

    /**
     * Verifica se é nota de saída.
     */
    public function isSaida(): bool
    {
        return $this->tipo_nota === self::TIPO_SAIDA;
    }

    /**
     * Verifica se o emitente é empresa própria.
     */
    public function isEmitenteProprio(): bool
    {
        return $this->emitCliente?->is_empresa_propria === true;
    }

    /**
     * Verifica se o destinatário é empresa própria.
     */
    public function isDestinatarioProprio(): bool
    {
        return $this->destCliente?->is_empresa_propria === true;
    }

    /**
     * Verifica se é uma venda própria (empresa própria como emitente).
     */
    public function isVendaPropria(): bool
    {
        return $this->isEmitenteProprio();
    }

    /**
     * Verifica se é uma compra própria (empresa própria como destinatário).
     */
    public function isCompraPropria(): bool
    {
        return $this->isDestinatarioProprio();
    }

    /**
     * Descrição legível do tipo de nota.
     */
    public function getTipoNotaDescricaoAttribute(): string
    {
        return match ($this->tipo_nota) {
            self::TIPO_ENTRADA => 'Entrada',
            self::TIPO_SAIDA => 'Saída',
            default => 'Desconhecido',
        };
    }

    /**
     * Descrição legível da finalidade.
     */
    public function getFinalidadeDescricaoAttribute(): string
    {
        return match ($this->finalidade) {
            self::FINALIDADE_NORMAL => 'Normal',
            self::FINALIDADE_COMPLEMENTAR => 'Complementar',
            self::FINALIDADE_AJUSTE => 'Ajuste',
            self::FINALIDADE_DEVOLUCAO => 'Devolução',
            default => 'Não informado',
        };
    }

    /**
     * Total de tributos (soma dos campos individuais).
     */
    public function getTotalTributosCalculadoAttribute(): float
    {
        return (float) $this->icms_valor
            + (float) $this->icms_st_valor
            + (float) $this->pis_valor
            + (float) $this->cofins_valor
            + (float) $this->ipi_valor;
    }

    /**
     * Verifica se a nota foi validada.
     */
    public function isValidada(): bool
    {
        return $this->validacao !== null;
    }

    /**
     * Retorna o score total da validacao.
     */
    public function getValidacaoScoreAttribute(): ?int
    {
        return $this->validacao['score_total'] ?? null;
    }

    /**
     * Retorna a classificacao da validacao.
     */
    public function getValidacaoClassificacaoAttribute(): ?string
    {
        return $this->validacao['classificacao'] ?? null;
    }

    /**
     * Retorna os alertas da validacao.
     */
    public function getValidacaoAlertasAttribute(): array
    {
        return $this->validacao['alertas'] ?? [];
    }

    /**
     * Retorna a data da validacao.
     */
    public function getValidadoEmAttribute(): ?string
    {
        return $this->validacao['validado_em'] ?? null;
    }

    /**
     * Retorna a classe CSS para o badge de classificacao de validacao.
     */
    public function getValidacaoBadgeClassAttribute(): string
    {
        return match ($this->validacao_classificacao) {
            'conforme' => 'bg-green-100 text-green-800',
            'atencao' => 'bg-yellow-100 text-yellow-800',
            'irregular' => 'bg-orange-100 text-orange-800',
            'critico' => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-700',
        };
    }

    /**
     * Retorna o label legivel para a classificacao de validacao.
     */
    public function getValidacaoClassificacaoLabelAttribute(): string
    {
        return match ($this->validacao_classificacao) {
            'conforme' => 'Conforme',
            'atencao' => 'Atencao',
            'irregular' => 'Irregular',
            'critico' => 'Critico',
            default => 'Nao Validada',
        };
    }

    /**
     * Conta alertas por nivel.
     */
    public function countAlertasByNivel(string $nivel): int
    {
        return collect($this->validacao_alertas)
            ->where('nivel', $nivel)
            ->count();
    }

    // Scopes

    public function scopeDoUsuario($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeDoTipo($query, string $tipo)
    {
        return $query->where('tipo_documento', strtoupper($tipo));
    }

    public function scopeEntradas($query)
    {
        return $query->where('tipo_nota', self::TIPO_ENTRADA);
    }

    public function scopeSaidas($query)
    {
        return $query->where('tipo_nota', self::TIPO_SAIDA);
    }

    public function scopeDevolucoes($query)
    {
        return $query->where('finalidade', self::FINALIDADE_DEVOLUCAO);
    }

    public function scopePorEmitente($query, string $cnpj)
    {
        return $query->where('emit_cnpj', preg_replace('/[^0-9]/', '', $cnpj));
    }

    public function scopePorDestinatario($query, string $cnpj)
    {
        return $query->where('dest_cnpj', preg_replace('/[^0-9]/', '', $cnpj));
    }

    public function scopeNoPeriodo($query, $inicio, $fim)
    {
        return $query->whereBetween('data_emissao', [$inicio, $fim]);
    }

    public function scopeDoCliente($query, int $clienteId)
    {
        return $query->where('cliente_id', $clienteId);
    }

    public function scopeValidadas($query)
    {
        return $query->whereNotNull('validacao');
    }

    public function scopeNaoValidadas($query)
    {
        return $query->whereNull('validacao');
    }

    public function scopeComAlertasBloqueantes($query)
    {
        return $query->whereNotNull('validacao')
            ->whereRaw("jsonb_array_length(validacao->'alertas') > 0")
            ->whereRaw("EXISTS (SELECT 1 FROM jsonb_array_elements(validacao->'alertas') AS a WHERE a->>'nivel' = 'bloqueante')");
    }

    /**
     * Notas onde a empresa própria é o emitente (vendas próprias).
     */
    public function scopeVendasProprias($query)
    {
        return $query->whereHas('emitCliente', fn ($q) => $q->where('is_empresa_propria', true));
    }

    /**
     * Notas onde a empresa própria é o destinatário (compras próprias).
     */
    public function scopeComprasProprias($query)
    {
        return $query->whereHas('destCliente', fn ($q) => $q->where('is_empresa_propria', true));
    }

    /**
     * Notas de um cliente específico (como emitente ou destinatário).
     */
    public function scopeNotasDeCliente($query, int $clienteId)
    {
        return $query->where(function ($q) use ($clienteId) {
            $q->where('emit_cliente_id', $clienteId)
                ->orWhere('dest_cliente_id', $clienteId);
        });
    }

    /**
     * Notas entre empresa própria e clientes (operações internas).
     */
    public function scopeOperacoesInternas($query)
    {
        return $query->where(function ($q) {
            $q->where(function ($sub) {
                $sub->whereHas('emitCliente', fn ($q2) => $q2->where('is_empresa_propria', true))
                    ->whereHas('destCliente', fn ($q2) => $q2->where('is_empresa_propria', false));
            })->orWhere(function ($sub) {
                $sub->whereHas('emitCliente', fn ($q2) => $q2->where('is_empresa_propria', false))
                    ->whereHas('destCliente', fn ($q2) => $q2->where('is_empresa_propria', true));
            });
        });
    }
}
