<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrivCpfCadastro extends Model
{
    use HasFactory;

    protected $table = 'priv_cpf_cadastro';

    protected $fillable = [
        'user_id',
        'cpf',
        'nome',
        'cod_pais',
        'uf',
        'codigo_municipal',
        'municipio_nome',
        'cep',
        'bairro',
        'endereco',
        'numero',
        'complemento',
        'inscricao_estadual',
        'suframa',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relacionamentos
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function operacoes()
    {
        return $this->hasMany(PrivCpfOperacao::class, 'cpf_id');
    }

    public function relacionamentos()
    {
        return $this->hasMany(PrivCpfRelacionamento::class, 'cpf_id');
    }
}

