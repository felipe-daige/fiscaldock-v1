<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EfdRetencaoFonte extends Model
{
    protected $table = 'efd_retencoes_fonte';

    protected $fillable = [
        'importacao_id',
        'user_id',
        'cliente_id',
        'natureza',
        'data_retencao',
        'base_calculo',
        'valor_total',
        'cod_receita',
        'natureza_receita',
        'cnpj',
        'valor_pis',
        'valor_cofins',
        'ind_declarante',
        'dados_brutos',
    ];

    protected function casts(): array
    {
        return [
            'data_retencao' => 'date',
            'base_calculo' => 'decimal:2',
            'valor_total' => 'decimal:2',
            'valor_pis' => 'decimal:2',
            'valor_cofins' => 'decimal:2',
            'dados_brutos' => 'array',
        ];
    }

    // Relacionamentos

    public function importacao(): BelongsTo
    {
        return $this->belongsTo(EfdImportacao::class, 'importacao_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    // Acessores

    public function getNaturezaFormatadaAttribute(): string
    {
        return match ($this->natureza) {
            '01' => 'Previdenciária',
            '02' => 'Imposto de Renda',
            '03' => 'CSLL/PIS/COFINS',
            '99' => 'Outros',
            default => $this->natureza,
        };
    }

    public function getCnpjFormatadoAttribute(): string
    {
        $cnpj = $this->cnpj;
        if (strlen($cnpj) !== 14) {
            return $cnpj;
        }

        return substr($cnpj, 0, 2).'.'.substr($cnpj, 2, 3).'.'.substr($cnpj, 5, 3).'/'.substr($cnpj, 8, 4).'-'.substr($cnpj, 12, 2);
    }

    // Scopes

    public function scopeDoUsuario($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeDoCliente($query, int $clienteId)
    {
        return $query->where('cliente_id', $clienteId);
    }

    public function scopePorCnpj($query, string $cnpj)
    {
        return $query->where('cnpj', preg_replace('/\D/', '', $cnpj));
    }

    public function scopePeriodo($query, $dataInicio, $dataFim)
    {
        return $query->whereBetween('data_retencao', [$dataInicio, $dataFim]);
    }
}
