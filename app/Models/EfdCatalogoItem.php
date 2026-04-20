<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EfdCatalogoItem extends Model
{
    protected $table = 'efd_catalogo_itens';

    protected $fillable = [
        'user_id', 'cliente_id', 'importacao_id',
        'cod_item', 'descr_item', 'cod_barra', 'tipo_item',
        'cod_ncm', 'cod_gen', 'aliq_icms', 'unid_inv', 'dados_brutos',
    ];

    protected function casts(): array
    {
        return [
            'aliq_icms' => 'decimal:4',
            'dados_brutos' => 'array',
        ];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function importacao(): BelongsTo
    {
        return $this->belongsTo(EfdImportacao::class, 'importacao_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
