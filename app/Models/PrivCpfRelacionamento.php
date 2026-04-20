<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrivCpfRelacionamento extends Model
{
    use HasFactory;

    protected $table = 'priv_cpf_relacionamentos';

    protected $fillable = [
        'cpf_id',
        'cnpj',
        'razao_social',
        'tipo_relacao',
        'total_operacoes',
        'valor_total',
        'primeira_operacao',
        'ultima_operacao',
    ];

    protected $casts = [
        'total_operacoes' => 'integer',
        'valor_total' => 'decimal:2',
        'primeira_operacao' => 'date',
        'ultima_operacao' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relacionamentos
    public function cadastro()
    {
        return $this->belongsTo(PrivCpfCadastro::class, 'cpf_id');
    }
}





