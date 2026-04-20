<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Participante extends Model
{
    use HasFactory;

    protected $table = 'participantes';

    protected $fillable = [
        'user_id',
        'cliente_id',
        'importacao_efd_id',
        'importacao_xml_id',
        'documento',
        'tipo_documento',
        'razao_social',
        'nome_fantasia',
        'situacao_cadastral',
        'regime_tributario',
        'uf',
        'cep',
        'municipio',
        'telefone',
        'crt',
        'inscricao_estadual',
        'endereco',
        'numero',
        'complemento',
        'bairro',
        // Receita Federal fields
        'capital_social',
        'natureza_juridica',
        'porte',
        'data_inicio_atividade',
        'cnae_principal',
        'cnae_principal_descricao',
        'cnaes_secundarios',
        'qsa',
        // Origin tracking
        'origem_tipo',
        'origem_ref',
        'ultima_consulta_em',
        'latitude',
        'longitude',
    ];

    protected $casts = [
        'origem_ref' => 'array',
        'cnaes_secundarios' => 'array',
        'qsa' => 'array',
        'capital_social' => 'decimal:2',
        'data_inicio_atividade' => 'date',
        'ultima_consulta_em' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $participante) {
            $documento = $participante->documentoNormalizado();

            if (strlen($documento) === 11) {
                $participante->tipo_documento = 'PF';
            } elseif (strlen($documento) === 14) {
                $participante->tipo_documento = 'PJ';
            }
        });
    }

    /**
     * Usuário dono do participante.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Cliente associado (opcional).
     */
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    /**
     * Importação EFD que criou este participante (opcional).
     */
    public function importacaoEfd(): BelongsTo
    {
        return $this->belongsTo(EfdImportacao::class, 'importacao_efd_id');
    }

    /**
     * Importação XML que criou este participante (opcional).
     */
    public function importacaoXml(): BelongsTo
    {
        return $this->belongsTo(XmlImportacao::class, 'importacao_xml_id');
    }

    /**
     * Assinaturas de monitoramento do participante.
     */
    public function assinaturas(): HasMany
    {
        return $this->hasMany(MonitoramentoAssinatura::class);
    }

    /**
     * Consultas realizadas para o participante.
     */
    public function consultas(): HasMany
    {
        return $this->hasMany(MonitoramentoConsulta::class);
    }

    /**
     * Grupos aos quais o participante pertence.
     */
    public function grupos(): BelongsToMany
    {
        return $this->belongsToMany(ParticipanteGrupo::class, 'participantes_grupos_pivot', 'participante_id', 'participantes_grupo_id')
            ->withTimestamps();
    }

    /**
     * Notas XML onde o participante é o emitente.
     */
    public function notasXmlComoEmitente(): HasMany
    {
        return $this->hasMany(XmlNota::class, 'emit_participante_id');
    }

    /**
     * Notas XML onde o participante é o destinatário.
     */
    public function notasXmlComoDestinatario(): HasMany
    {
        return $this->hasMany(XmlNota::class, 'dest_participante_id');
    }

    /**
     * Notas EFD onde o participante é o emitente.
     */
    public function notasEfdComoEmitente(): HasMany
    {
        return $this->hasMany(EfdNota::class, 'emit_participante_id');
    }

    /**
     * Notas EFD onde o participante é o destinatário.
     */
    public function notasEfdComoDestinatario(): HasMany
    {
        return $this->hasMany(EfdNota::class, 'dest_participante_id');
    }

    public function efdNotas(): HasMany
    {
        return $this->hasMany(EfdNota::class, 'participante_id');
    }

    /**
     * Score de risco do participante.
     */
    public function score(): HasOne
    {
        return $this->hasOne(ParticipanteScore::class);
    }

    /**
     * Exclui participantes cujo cliente é empresa própria.
     */
    public function scopeExcluindoEmpresaPropria($query)
    {
        $query->whereDoesntHave('cliente', fn ($q) => $q->where('is_empresa_propria', true));

        $userCnpj = auth()->user()?->cnpj;
        if ($userCnpj) {
            $query->where('documento', '!=', $userCnpj);
        }

        return $query;
    }

    public function scopeExcludingEmpresaPropria($query)
    {
        return $this->scopeExcluindoEmpresaPropria($query);
    }

    public function scopeSomenteCpf($query)
    {
        return $query->whereRaw(
            "length(regexp_replace(coalesce(documento, ''), '[^0-9]', '', 'g')) = 11"
        );
    }

    public function scopeSomenteCnpj($query)
    {
        return $query->whereRaw(
            "length(regexp_replace(coalesce(documento, ''), '[^0-9]', '', 'g')) = 14"
        );
    }

    public function documentoNormalizado(): string
    {
        return preg_replace('/[^0-9]/', '', (string) $this->documento);
    }

    public function getDocumentoNormalizadoAttribute(): string
    {
        return $this->documentoNormalizado();
    }

    public function getIsCpfAttribute(): bool
    {
        return strlen($this->documentoNormalizado()) === 11;
    }

    public function getIsCnpjAttribute(): bool
    {
        return strlen($this->documentoNormalizado()) === 14;
    }

    /**
     * CNPJ formatado.
     */
    public function getCnpjFormatadoAttribute(): string
    {
        $doc = $this->documentoNormalizado();
        if (strlen($doc) === 14) {
            return preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $doc);
        }
        if (strlen($doc) === 11) {
            return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $doc);
        }

        return $this->documento;
    }

    /**
     * Verifica se o participante tem assinatura ativa.
     */
    public function temAssinaturaAtiva(): bool
    {
        return $this->assinaturas()->where('status', 'ativo')->exists();
    }

    /**
     * Retorna a última consulta realizada.
     */
    public function ultimaConsulta(): ?MonitoramentoConsulta
    {
        return $this->consultas()->latest('executado_em')->first();
    }
}
