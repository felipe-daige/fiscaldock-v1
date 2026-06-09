<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class XmlNotaItem extends Model
{
    protected $table = 'xml_notas_itens';

    protected $fillable = [
        'xml_nota_id', 'user_id', 'numero_item', 'codigo_item', 'descricao',
        'quantidade', 'unidade_medida', 'valor_unitario', 'valor_total',
        'cfop', 'cst_icms', 'aliquota_icms', 'valor_icms',
        'cst_pis', 'aliquota_pis', 'valor_pis',
        'cst_cofins', 'aliquota_cofins', 'valor_cofins',
        'ncm', 'cest', 'ean', 'origem_mercadoria', 'cst_ipi', 'valor_ipi',
        'metadados',
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
        return $this->belongsTo(XmlNota::class, 'xml_nota_id');
    }
}
