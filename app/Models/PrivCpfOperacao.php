<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrivCpfOperacao extends Model
{
    use HasFactory;

    protected $table = 'priv_cpf_operacoes';

    public $timestamps = false;

    protected $fillable = [
        'cpf_id',
        'cnpj_empresa',
        'tipo_participacao',
        'nfe_id',
        'modelo',
        'serie',
        'numero_doc',
        'data_emissao',
        'data_operacao',
        'tipo_operacao',
        'valor_total',
        'valor_mercadorias',
        'valor_frete',
        'valor_desconto',
        'uf_origem',
        'uf_destino',
        'ncm_principal',
        'descricao_resumo',
        'arquivo_origem',
        'created_at',
    ];

    protected $casts = [
        'data_emissao' => 'date',
        'data_operacao' => 'date',
        'valor_total' => 'decimal:2',
        'valor_mercadorias' => 'decimal:2',
        'valor_frete' => 'decimal:2',
        'valor_desconto' => 'decimal:2',
        'created_at' => 'datetime',
    ];

    // Relacionamentos
    public function cadastro()
    {
        return $this->belongsTo(PrivCpfCadastro::class, 'cpf_id');
    }
}





