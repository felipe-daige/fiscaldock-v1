<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class XmlNotaItem extends Model
{
    protected $table = 'xml_notas_itens';

    protected $fillable = [
        'xml_nota_id',
        'user_id',
        'numero_item',
        'codigo_item',
        'ean',
        'descricao',
        'ncm',
        'cest',
        'cfop',
        'unidade_medida',
        'quantidade',
        'valor_unitario',
        'valor_total',
        'cst_icms',
        'aliquota_icms',
        'valor_icms',
        'aliquota_icms_st',
        'valor_icms_st',
        'cst_pis',
        'aliquota_pis',
        'valor_pis',
        'cst_cofins',
        'aliquota_cofins',
        'valor_cofins',
        'cst_ipi',
        'aliquota_ipi',
        'valor_ipi',
        'metadados',
    ];

    protected function casts(): array
    {
        return [
            'numero_item' => 'integer',
            'quantidade' => 'decimal:4',
            'valor_unitario' => 'decimal:10',
            'valor_total' => 'decimal:2',
            'aliquota_icms' => 'decimal:2',
            'valor_icms' => 'decimal:2',
            'aliquota_icms_st' => 'decimal:2',
            'valor_icms_st' => 'decimal:2',
            'aliquota_pis' => 'decimal:4',
            'valor_pis' => 'decimal:2',
            'aliquota_cofins' => 'decimal:4',
            'valor_cofins' => 'decimal:2',
            'aliquota_ipi' => 'decimal:2',
            'valor_ipi' => 'decimal:2',
            'metadados' => 'array',
        ];
    }

    public function xmlNota(): BelongsTo
    {
        return $this->belongsTo(XmlNota::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
