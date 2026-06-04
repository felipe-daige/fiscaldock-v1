<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EfdNotaConsolidado extends Model
{
    protected $table = 'efd_notas_consolidados';

    protected $fillable = [
        'efd_nota_id', 'user_id', 'cst_icms', 'cfop', 'aliquota_icms',
        'valor_operacao', 'valor_bc_icms', 'valor_icms',
        'valor_bc_icms_st', 'valor_icms_st', 'valor_reducao_bc', 'valor_ipi',
        'cod_obs',
    ];

    protected function casts(): array
    {
        return [
            'cfop'             => 'integer',
            'aliquota_icms'    => 'decimal:2',
            'valor_operacao'   => 'decimal:2',
            'valor_bc_icms'    => 'decimal:2',
            'valor_icms'       => 'decimal:2',
            'valor_bc_icms_st' => 'decimal:2',
            'valor_icms_st'    => 'decimal:2',
            'valor_reducao_bc' => 'decimal:2',
            'valor_ipi'        => 'decimal:2',
        ];
    }

    public function nota(): BelongsTo
    {
        return $this->belongsTo(EfdNota::class, 'efd_nota_id');
    }
}
