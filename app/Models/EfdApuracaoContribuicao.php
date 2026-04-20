<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EfdApuracaoContribuicao extends Model
{
    protected $table = 'efd_apuracoes_contribuicoes';

    protected $fillable = [
        'importacao_id',
        'user_id',
        'cliente_id',

        // M200 — Consolidação PIS
        'pis_nao_cumulativo',
        'pis_credito_descontado',
        'pis_credito_desc_ant',
        'pis_nc_devida',
        'pis_retencao_nc',
        'pis_outras_deducoes_nc',
        'pis_nc_recolher',
        'pis_cumulativo',
        'pis_retencao_cum',
        'pis_outras_deducoes_cum',
        'pis_cum_recolher',
        'pis_total_recolher',

        // M600 — Consolidação COFINS
        'cofins_nao_cumulativo',
        'cofins_credito_descontado',
        'cofins_credito_desc_ant',
        'cofins_nc_devida',
        'cofins_retencao_nc',
        'cofins_outras_deducoes_nc',
        'cofins_nc_recolher',
        'cofins_cumulativo',
        'cofins_retencao_cum',
        'cofins_outras_deducoes_cum',
        'cofins_cum_recolher',
        'cofins_total_recolher',

        // JSONB — Detalhes variáveis
        'pis_detalhes',
        'pis_nao_tributado',
        'cofins_detalhes',
        'cofins_recolher_detalhe',
        'pis_creditos_nc',
        'cofins_creditos_nc',
        'dados_brutos',
    ];

    protected function casts(): array
    {
        return [
            // M200
            'pis_nao_cumulativo' => 'decimal:2',
            'pis_credito_descontado' => 'decimal:2',
            'pis_credito_desc_ant' => 'decimal:2',
            'pis_nc_devida' => 'decimal:2',
            'pis_retencao_nc' => 'decimal:2',
            'pis_outras_deducoes_nc' => 'decimal:2',
            'pis_nc_recolher' => 'decimal:2',
            'pis_cumulativo' => 'decimal:2',
            'pis_retencao_cum' => 'decimal:2',
            'pis_outras_deducoes_cum' => 'decimal:2',
            'pis_cum_recolher' => 'decimal:2',
            'pis_total_recolher' => 'decimal:2',

            // M600
            'cofins_nao_cumulativo' => 'decimal:2',
            'cofins_credito_descontado' => 'decimal:2',
            'cofins_credito_desc_ant' => 'decimal:2',
            'cofins_nc_devida' => 'decimal:2',
            'cofins_retencao_nc' => 'decimal:2',
            'cofins_outras_deducoes_nc' => 'decimal:2',
            'cofins_nc_recolher' => 'decimal:2',
            'cofins_cumulativo' => 'decimal:2',
            'cofins_retencao_cum' => 'decimal:2',
            'cofins_outras_deducoes_cum' => 'decimal:2',
            'cofins_cum_recolher' => 'decimal:2',
            'cofins_total_recolher' => 'decimal:2',

            // JSONB
            'pis_detalhes' => 'array',
            'pis_nao_tributado' => 'array',
            'cofins_detalhes' => 'array',
            'cofins_recolher_detalhe' => 'array',
            'pis_creditos_nc' => 'array',
            'cofins_creditos_nc' => 'array',
            'dados_brutos' => 'array',
        ];
    }

    // Relacionamentos

    public function importacao(): BelongsTo
    {
        return $this->belongsTo(EfdImportacao::class, 'importacao_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    // Acessores

    public function getTotalRecolherAttribute(): string
    {
        return bcadd($this->pis_total_recolher, $this->cofins_total_recolher, 2);
    }

    public function getRegimeAttribute(): string
    {
        $temNc = bccomp($this->pis_nao_cumulativo, '0', 2) > 0
              || bccomp($this->cofins_nao_cumulativo, '0', 2) > 0;

        $temCum = bccomp($this->pis_cumulativo, '0', 2) > 0
               || bccomp($this->cofins_cumulativo, '0', 2) > 0;

        if ($temNc && $temCum) {
            return 'misto';
        }

        return $temNc ? 'nao_cumulativo' : 'cumulativo';
    }

    public function getTemCreditosNcAttribute(): bool
    {
        return ! empty($this->pis_creditos_nc) || ! empty($this->cofins_creditos_nc);
    }

    // Scopes

    public function scopeDoUsuario($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeDoCliente($query, int $clienteId)
    {
        return $query->where('cliente_id', $clienteId);
    }

    public function scopePeriodo($query, $dataInicio, $dataFim)
    {
        $importacaoIds = EfdNota::whereBetween('data_emissao', [$dataInicio, $dataFim])
            ->distinct()
            ->pluck('importacao_id');

        return $query->whereIn('importacao_id', $importacaoIds);
    }
}
