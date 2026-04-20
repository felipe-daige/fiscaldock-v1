<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EfdApuracaoIcms extends Model
{
    protected $table = 'efd_apuracoes_icms';

    protected $fillable = [
        'importacao_id',
        'user_id',
        'cliente_id',

        // E100 — Período
        'periodo_inicio',
        'periodo_fim',

        // E110 — ICMS Próprio
        'icms_tot_debitos',
        'icms_aj_debitos',
        'icms_tot_aj_debitos',
        'icms_estornos_credito',
        'icms_tot_creditos',
        'icms_aj_creditos',
        'icms_tot_aj_creditos',
        'icms_estornos_debito',
        'icms_sld_credor_ant',
        'icms_sld_apurado',
        'icms_tot_deducoes',
        'icms_a_recolher',
        'icms_sld_credor_transportar',
        'icms_deb_especiais',

        // E210 — ICMS-ST
        'st_uf',
        'st_ind_movimentacao',
        'st_sld_credor_ant',
        'st_devolucoes',
        'st_ressarcimentos',
        'st_outros_creditos',
        'st_aj_creditos',
        'st_retencao',
        'st_outros_debitos',
        'st_aj_debitos',
        'st_sld_devedor_ant',
        'st_deducoes',
        'st_icms_recolher',
        'st_sld_credor_transportar',
        'st_deb_especiais',

        // JSONB
        'icms_obrigacoes',
        'st_obrigacoes',
        'difal_fcp',
        'ipi',
        'dados_brutos',
    ];

    protected function casts(): array
    {
        return [
            'periodo_inicio' => 'date',
            'periodo_fim' => 'date',

            // E110
            'icms_tot_debitos' => 'decimal:2',
            'icms_aj_debitos' => 'decimal:2',
            'icms_tot_aj_debitos' => 'decimal:2',
            'icms_estornos_credito' => 'decimal:2',
            'icms_tot_creditos' => 'decimal:2',
            'icms_aj_creditos' => 'decimal:2',
            'icms_tot_aj_creditos' => 'decimal:2',
            'icms_estornos_debito' => 'decimal:2',
            'icms_sld_credor_ant' => 'decimal:2',
            'icms_sld_apurado' => 'decimal:2',
            'icms_tot_deducoes' => 'decimal:2',
            'icms_a_recolher' => 'decimal:2',
            'icms_sld_credor_transportar' => 'decimal:2',
            'icms_deb_especiais' => 'decimal:2',

            // E210
            'st_sld_credor_ant' => 'decimal:2',
            'st_devolucoes' => 'decimal:2',
            'st_ressarcimentos' => 'decimal:2',
            'st_outros_creditos' => 'decimal:2',
            'st_aj_creditos' => 'decimal:2',
            'st_retencao' => 'decimal:2',
            'st_outros_debitos' => 'decimal:2',
            'st_aj_debitos' => 'decimal:2',
            'st_sld_devedor_ant' => 'decimal:2',
            'st_deducoes' => 'decimal:2',
            'st_icms_recolher' => 'decimal:2',
            'st_sld_credor_transportar' => 'decimal:2',
            'st_deb_especiais' => 'decimal:2',

            // JSONB
            'icms_obrigacoes' => 'array',
            'st_obrigacoes' => 'array',
            'difal_fcp' => 'array',
            'ipi' => 'array',
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
        return bcadd(
            bcadd($this->icms_a_recolher, $this->icms_deb_especiais, 2),
            bcadd($this->st_icms_recolher, $this->st_deb_especiais, 2),
            2
        );
    }

    public function getTemStAttribute(): bool
    {
        return bccomp($this->st_icms_recolher, '0', 2) > 0
            || bccomp($this->st_deb_especiais, '0', 2) > 0;
    }

    public function getTemDifalAttribute(): bool
    {
        return ! empty($this->difal_fcp);
    }

    public function getTemIpiAttribute(): bool
    {
        return ! empty($this->ipi);
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
        return $query->whereBetween('periodo_inicio', [$dataInicio, $dataFim]);
    }
}
