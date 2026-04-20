<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Alerta extends Model
{
    protected $table = 'alertas';

    protected $fillable = [
        'user_id',
        'cliente_id',
        'participante_id',
        'importacao_id',
        'tipo',
        'categoria',
        'severidade',
        'titulo',
        'descricao',
        'detalhes',
        'total_afetados',
        'prioridade',
        'status',
        'notificado_em',
        'visto_em',
        'resolvido_em',
        'hash',
        'notas',
    ];

    protected function casts(): array
    {
        return [
            'detalhes' => 'array',
            'total_afetados' => 'integer',
            'prioridade' => 'integer',
            'notificado_em' => 'datetime',
            'visto_em' => 'datetime',
            'resolvido_em' => 'datetime',
        ];
    }

    // Relacionamentos

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function participante(): BelongsTo
    {
        return $this->belongsTo(Participante::class);
    }

    public function importacao(): BelongsTo
    {
        return $this->belongsTo(EfdImportacao::class, 'importacao_id');
    }

    // Scopes

    public function scopeAtivos($query)
    {
        return $query->where('status', 'ativo');
    }

    public function scopePorSeveridade($query, string $severidade)
    {
        return $query->where('severidade', $severidade);
    }

    public function scopePorCategoria($query, string $categoria)
    {
        return $query->where('categoria', $categoria);
    }

    public function scopeDoUsuario($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}
