<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Change-log do catálogo (0200): uma linha por mudança de campo rastreado
 * (cod_ncm, aliq_icms, unid_inv, descr_item) de um item entre importações.
 * Populado por trigger no UPDATE de efd_catalogo_itens — ver migration central.
 */
class EfdCatalogoHistorico extends Model
{
    protected $table = 'efd_catalogo_historico';

    public $timestamps = false;

    protected $fillable = [
        'user_id', 'cliente_id', 'cod_item', 'campo',
        'valor_anterior', 'valor_novo', 'importacao_id', 'changed_at',
    ];

    protected function casts(): array
    {
        return ['changed_at' => 'datetime'];
    }
}
