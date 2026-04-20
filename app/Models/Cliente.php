<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    use HasFactory;

    protected $table = 'clientes';

    protected $fillable = [
        'user_id',
        'tipo_pessoa',
        'documento',
        'nome',
        'razao_social',
        'nome_fantasia',
        'inscricao_estadual',
        'crt',
        'telefone',
        'email',
        'uf',
        'cep',
        'municipio',
        'endereco',
        'numero',
        'complemento',
        'bairro',
        'situacao_cadastral',
        'regime_tributario',
        'cnpj_matriz',
        'suframa',
        'codigo_municipal',
        'capital_social',
        'natureza_juridica',
        'porte',
        'data_inicio_atividade',
        'cnae_principal',
        'cnae_principal_descricao',
        'cnaes_secundarios',
        'qsa',
        'origem_tipo',
        'origem_ref',
        'ativo',
        'is_empresa_propria',
    ];

    protected $casts = [
        'ativo' => 'boolean',
        'is_empresa_propria' => 'boolean',
        'crt' => 'integer',
        'capital_social' => 'decimal:2',
        'data_inicio_atividade' => 'date',
        'cnaes_secundarios' => 'array',
        'qsa' => 'array',
        'origem_ref' => 'array',
    ];

    protected function documento(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => $value ? preg_replace('/\D/', '', $value) : null,
        );
    }

    // Relacionamentos
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function participantes()
    {
        return $this->hasMany(Participante::class);
    }

    // Helpers
    public function isPessoaFisica(): bool
    {
        return $this->tipo_pessoa === 'PF';
    }

    public function isPessoaJuridica(): bool
    {
        return $this->tipo_pessoa === 'PJ';
    }

    /**
     * Scope para filtrar empresas proprias do usuario.
     */
    public function scopeEmpresaPropria($query)
    {
        return $query->where('is_empresa_propria', true);
    }

    public function getDocumentoFormatadoAttribute(): string
    {
        $documento = preg_replace('/\D/', '', $this->documento);

        if ($this->tipo_pessoa === 'PF') {
            // Formato CPF: 000.000.000-00
            return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $documento);
        }

        // Formato CNPJ: 00.000.000/0000-00
        return preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $documento);
    }
}
