<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EfdNota extends Model
{
    protected $table = 'efd_notas';

    protected $fillable = [
        'user_id', 'cliente_id', 'participante_id', 'importacao_id', 'chave_acesso', 'modelo', 'numero',
        'serie', 'data_emissao', 'tipo_operacao', 'valor_total',
        'valor_desconto', 'origem_arquivo', 'metadados', 'validacao',
    ];

    protected function casts(): array
    {
        return [
            'data_emissao'   => 'date',
            'valor_total'    => 'decimal:2',
            'valor_desconto' => 'decimal:2',
            'metadados'      => 'array',
            'validacao'      => 'array',
        ];
    }

    public function importacao(): BelongsTo
    {
        return $this->belongsTo(EfdImportacao::class, 'importacao_id');
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function participante(): BelongsTo
    {
        return $this->belongsTo(Participante::class, 'participante_id');
    }

    public function itens(): HasMany
    {
        return $this->hasMany(EfdNotaItem::class, 'efd_nota_id');
    }

    // Acessores

    public function getIsEntradaAttribute(): bool
    {
        return $this->tipo_operacao === 'entrada';
    }

    public function getIsSaidaAttribute(): bool
    {
        return $this->tipo_operacao === 'saida';
    }

    public function getTipoNotaFormatadoAttribute(): string
    {
        return $this->tipo_operacao === 'entrada' ? 'Entrada' : 'Saída';
    }

    public function getModeloDocFormatadoAttribute(): string
    {
        return match ($this->modelo) {
            '00' => 'NFS-e',
            '01' => 'Nota Fiscal',
            '1B' => 'Nota Fiscal Avulsa',
            '04' => 'Nota Fiscal de Produtor',
            '55' => 'NF-e',
            '57' => 'CT-e',
            '65' => 'NFC-e',
            '67' => 'CT-e OS',
            default => $this->modelo ?? 'N/A',
        };
    }

    // Scopes

    public function scopeDoUsuario($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeEntradas($query)
    {
        return $query->where('tipo_operacao', 'entrada');
    }

    public function scopeSaidas($query)
    {
        return $query->where('tipo_operacao', 'saida');
    }

    public function scopeEfdFiscal($query)
    {
        return $query->where('origem_arquivo', 'fiscal');
    }

    public function scopeEfdContrib($query)
    {
        return $query->where('origem_arquivo', 'contribuicoes');
    }

    public function scopePeriodo($query, $dataInicio, $dataFim)
    {
        return $query->whereBetween('data_emissao', [$dataInicio, $dataFim]);
    }
}
