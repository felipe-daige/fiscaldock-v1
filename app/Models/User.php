<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'sobrenome',
        'email',
        'password',
        'telefone',
        'credits',
        'empresa',
        'cargo',
        'cnpj',
        'faturamento_anual',
        'desafio_principal',
        'desafio_secundario',
        'terms_accepted_at',
        'marketing_opt_in',
        'marketing_opt_in_at',
        'deletion_requested_at',
        'terms_version',
        'privacy_version',
        'anonimizado_em',
        'is_admin',
        'bloqueado_em',
        'trial_used',
        'trial_started_at',
        'trial_expires_at',
        'trial_credits_granted',
        'trial_credits_remaining',
        'trial_credits_expired',
        'trial_source',
        'alertas_operacionais',
        'alertas_monitoramento',
        'resumo_periodico',
        'dashboard_prefs',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'credits' => 'integer',
            'terms_accepted_at' => 'datetime',
            'marketing_opt_in' => 'boolean',
            'marketing_opt_in_at' => 'datetime',
            'deletion_requested_at' => 'datetime',
            'anonimizado_em' => 'datetime',
            'is_admin' => 'boolean',
            'bloqueado_em' => 'datetime',
            'trial_used' => 'boolean',
            'trial_started_at' => 'datetime',
            'trial_expires_at' => 'datetime',
            'trial_credits_granted' => 'integer',
            'trial_credits_remaining' => 'integer',
            'trial_credits_expired' => 'integer',
            'alertas_operacionais' => 'boolean',
            'alertas_monitoramento' => 'boolean',
            'resumo_periodico' => 'boolean',
            'dashboard_prefs' => 'array',
        ];
    }

    public const DASHBOARD_PREFS_DEFAULT = [
        'cards' => [
            'tendencia'    => ['visivel' => true, 'ordem' => 0],
            'risco'        => ['visivel' => true, 'ordem' => 1],
            'triagem'      => ['visivel' => true, 'ordem' => 2],
            'fornecedores' => ['visivel' => true, 'ordem' => 3],
            'atividade'    => ['visivel' => true, 'ordem' => 4],
            'atalhos'      => ['visivel' => true, 'ordem' => 5],
        ],
        'atalhos_fixos' => ['consulta_nova', 'importar_efd', 'verificar_notas', 'bi_dashboard'],
        'atalhos_ordem' => ['consulta_nova', 'importar_efd', 'verificar_notas', 'bi_dashboard'],
    ];

    /** Prefs do dashboard mescladas sobre o default — sempre shape completo, nunca null. */
    public function dashboardPrefs(): array
    {
        $salvas = $this->dashboard_prefs ?? [];
        $default = self::DASHBOARD_PREFS_DEFAULT;

        $cards = $default['cards'];
        foreach (($salvas['cards'] ?? []) as $chave => $cfg) {
            if (isset($cards[$chave]) && is_array($cfg)) {
                $cards[$chave] = array_merge($cards[$chave], array_intersect_key($cfg, $cards[$chave]));
            }
        }

        return [
            'cards' => $cards,
            'atalhos_fixos' => $salvas['atalhos_fixos'] ?? $default['atalhos_fixos'],
            'atalhos_ordem' => $salvas['atalhos_ordem'] ?? $default['atalhos_ordem'],
        ];
    }

    protected function cnpj(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => $value ? preg_replace('/\D/', '', $value) : null,
        );
    }

    // Relacionamentos
    public function clientes()
    {
        return $this->hasMany(Cliente::class);
    }

    public function subscription()
    {
        return $this->hasOne(\App\Models\AccountSubscription::class);
    }

    // Helper - mantém compatibilidade com código antigo
    public function empresas()
    {
        return $this->clientes();
    }

    /**
     * Retorna a empresa propria do usuario (is_empresa_propria = true).
     */
    public function empresaPropria(): ?Cliente
    {
        return $this->clientes()
            ->where('is_empresa_propria', true)
            ->first();
    }

    public function hasActiveTrial(): bool
    {
        return $this->trial_used
            && $this->trial_expires_at !== null
            && now()->lt($this->trial_expires_at)
            && $this->trial_credits_remaining > 0;
    }

    public function isTrialExpired(): bool
    {
        return $this->trial_used
            && $this->trial_expires_at !== null
            && now()->gte($this->trial_expires_at);
    }
}
