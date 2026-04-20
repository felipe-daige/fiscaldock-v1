<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EfdNotaItem extends Model
{
    protected $table = 'efd_notas_itens';

    protected $fillable = [
        'efd_nota_id', 'user_id', 'numero_item', 'codigo_item', 'descricao',
        'quantidade', 'unidade_medida', 'valor_unitario', 'valor_total',
        'cfop', 'cst_icms', 'aliquota_icms', 'valor_icms',
        'cst_pis', 'aliquota_pis', 'valor_pis',
        'cst_cofins', 'aliquota_cofins', 'valor_cofins', 'metadados',
    ];

    protected function casts(): array
    {
        return [
            'quantidade'     => 'decimal:4',
            'valor_unitario' => 'decimal:4',
            'valor_total'    => 'decimal:2',
            'metadados'      => 'array',
        ];
    }

    public function nota(): BelongsTo
    {
        return $this->belongsTo(EfdNota::class, 'efd_nota_id');
    }

    public function scopeComCatalogo($query)
    {
        return $query
            ->join('efd_notas', 'efd_notas.id', '=', 'efd_notas_itens.efd_nota_id')
            ->join('efd_catalogo_itens as cat', function ($join) {
                $join->on('cat.cod_item', '=', 'efd_notas_itens.codigo_item')
                     ->on('cat.importacao_id', '=', 'efd_notas.importacao_id');
            })
            ->select('efd_notas_itens.*', 'cat.cod_ncm', 'cat.tipo_item', 'cat.cod_barra');
    }
}
