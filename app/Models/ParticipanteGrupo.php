<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ParticipanteGrupo extends Model
{
    use HasFactory;

    protected $table = 'participantes_grupos';

    protected $fillable = [
        'user_id',
        'nome',
        'cor',
        'descricao',
        'is_auto',
    ];

    protected $casts = [
        'is_auto' => 'boolean',
    ];

    /**
     * Cores predefinidas para grupos.
     */
    public const CORES_PREDEFINIDAS = [
        '#3B82F6', // blue-500
        '#10B981', // emerald-500
        '#F59E0B', // amber-500
        '#EF4444', // red-500
        '#8B5CF6', // violet-500
        '#EC4899', // pink-500
        '#06B6D4', // cyan-500
        '#84CC16', // lime-500
    ];

    /**
     * Usuário dono do grupo.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Participantes do grupo.
     */
    public function participantes(): BelongsToMany
    {
        return $this->belongsToMany(Participante::class, 'participantes_grupos_pivot', 'participantes_grupo_id', 'participante_id')
            ->withTimestamps();
    }

    /**
     * Retorna a contagem de participantes.
     */
    public function getParticipantesCountAttribute(): int
    {
        return $this->participantes()->count();
    }

    /**
     * Retorna a cor com fallback.
     */
    public function getCorComFallbackAttribute(): string
    {
        return $this->cor ?? self::CORES_PREDEFINIDAS[0];
    }

    /**
     * Scope para grupos do usuário.
     */
    public function scopeDoUsuario($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope para grupos manuais (não automáticos).
     */
    public function scopeManuais($query)
    {
        return $query->where('is_auto', false);
    }

    /**
     * Scope para grupos automáticos.
     */
    public function scopeAutomaticos($query)
    {
        return $query->where('is_auto', true);
    }
}
