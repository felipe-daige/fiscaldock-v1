<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminActionLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'admin_user_id', 'target_user_id', 'acao', 'motivo', 'detalhe', 'ip', 'created_at',
    ];

    protected $casts = [
        'detalhe' => 'array',
        'created_at' => 'datetime',
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_user_id');
    }

    public function alvo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }
}
