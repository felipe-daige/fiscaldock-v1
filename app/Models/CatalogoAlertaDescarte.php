<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Descarte (dispensa) de um alerta de catálogo por usuário e item.
 * tipo: ncm_divergente | sem_catalogo.
 */
class CatalogoAlertaDescarte extends Model
{
    protected $table = 'catalogo_alerta_descartes';

    protected $fillable = ['user_id', 'tipo', 'codigo_item'];

    public const TIPOS = ['ncm_divergente', 'sem_catalogo'];
}
