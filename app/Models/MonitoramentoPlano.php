<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MonitoramentoPlano extends Model
{
    use HasFactory;

    protected $table = 'monitoramento_planos';

    protected $fillable = [
        'codigo',
        'nome',
        'descricao',
        'consultas_incluidas',
        'etapas',
        'custo_creditos',
        'is_gratuito',
        'is_active',
        'ordem',
    ];

    protected $casts = [
        'consultas_incluidas' => 'array',
        'etapas' => 'array',
        'custo_creditos' => 'integer',
        'is_gratuito' => 'boolean',
        'is_active' => 'boolean',
        'ordem' => 'integer',
    ];

    /**
     * Assinaturas que usam este plano.
     */
    public function assinaturas(): HasMany
    {
        return $this->hasMany(MonitoramentoAssinatura::class, 'plano_id');
    }

    /**
     * Consultas realizadas com este plano.
     */
    public function consultas(): HasMany
    {
        return $this->hasMany(MonitoramentoConsulta::class, 'plano_id');
    }

    /**
     * Retorna planos ativos ordenados.
     */
    public static function ativos()
    {
        return static::where('is_active', true)->orderBy('ordem')->get();
    }

    /**
     * Encontra plano por código.
     */
    public static function porCodigo(string $codigo): ?self
    {
        return static::where('codigo', $codigo)->first();
    }
}
