<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CertificadoDigital extends Model
{
    protected $table = 'certificados_digitais';

    protected $guarded = [];

    protected $casts = [
        'validade' => 'date',
    ];

    // Nunca serializar o segredo.
    protected $hidden = ['senha_cifrada'];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }
}
