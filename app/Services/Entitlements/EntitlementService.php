<?php

namespace App\Services\Entitlements;

use App\Models\SubscriptionPlan;
use App\Models\User;

class EntitlementService
{
    /** Capabilities tratadas como booleanas por can(). */
    private const BOOLEAN_CAPS = ['pdf_executivo', 'clearance_lote', 'clearance_full', 'score_historico'];

    public function planFor(User $user): SubscriptionPlan
    {
        $subscription = $user->relationLoaded('subscription')
            ? $user->subscription
            : $user->subscription()->with('plan')->first();

        return $subscription?->plan ?? SubscriptionPlan::free();
    }

    public function can(User $user, string $cap): bool
    {
        return $this->planFor($user)->capability($cap, false) === true;
    }

    /**
     * Gate efetivo de acesso a um recurso pago.
     *
     * Política (definida 2026-06-16): **trial ativo libera tudo** — durante o
     * trial o usuário experimenta os recursos pagos gastando créditos do trial;
     * quando o trial expira e ele vira Free puro, o gate volta a valer pelo plano.
     * `export` não é booleana (é lista de formatos) → permitida se houver ≥1 formato.
     */
    public function permits(User $user, string $cap): bool
    {
        if ($user->hasActiveTrial()) {
            return true;
        }

        if ($cap === 'export') {
            return $this->exportFormats($user) !== [];
        }

        return $this->can($user, $cap);
    }

    public function capability(User $user, string $key, mixed $default = null): mixed
    {
        return $this->planFor($user)->capability($key, $default);
    }

    /** @return array<int, string> */
    public function exportFormats(User $user): array
    {
        $formats = $this->capability($user, 'export', []);

        return is_array($formats) ? $formats : [];
    }

    public function limit(User $user, string $key): ?int
    {
        $value = $this->planFor($user)->{$key};

        return $value === null ? null : (int) $value;
    }

    public function faixaFor(User $user): string
    {
        return $this->planFor($user)->faixa_slug;
    }

    public function consumptionCap(User $user): int
    {
        $subscription = $user->relationLoaded('subscription')
            ? $user->subscription
            : $user->subscription()->first();

        if ($subscription !== null && $subscription->limite_consumo_automatico !== null) {
            return (int) $subscription->limite_consumo_automatico;
        }

        return (int) $this->planFor($user)->creditos_inclusos;
    }

    // ---- Fase 5: freio de consumo do auto-monitor (§6.2) + gating de CNPJs monitorados ----

    /** Início do ciclo de consumo do monitor: âncora do grant da assinatura, senão o mês corrente. */
    public function cicloInicioMonitoramento(User $user): \Illuminate\Support\Carbon
    {
        $subscription = $user->relationLoaded('subscription')
            ? $user->subscription
            : $user->subscription()->first();

        $anchor = $subscription?->ultimo_grant_em ?? $subscription?->iniciada_em;

        return $anchor ? \Illuminate\Support\Carbon::parse($anchor) : now()->startOfMonth();
    }

    /** Créditos já consumidos pelo auto-monitor no ciclo corrente (deduções type=monitoramento_assinatura). */
    public function consumoMonitoramentoNoCiclo(User $user): int
    {
        $desde = $this->cicloInicioMonitoramento($user);

        return (int) abs((float) \Illuminate\Support\Facades\DB::table('credit_transactions')
            ->where('user_id', $user->id)
            ->where('type', 'monitoramento_assinatura')
            ->where('amount', '<', 0)
            ->where('created_at', '>=', $desde)
            ->sum('amount'));
    }

    /**
     * Disparar um ciclo de custo `$custo` estouraria o cap de consumo automático do usuário?
     * Cap <= 0 (ex.: Free sem inclusos) = sem freio — o saldo é o limite real (monitor grátis é custo 0).
     */
    public function monitoramentoCapEstourado(User $user, int $custo): bool
    {
        $cap = $this->consumptionCap($user);

        if ($cap <= 0) {
            return false;
        }

        return ($this->consumoMonitoramentoNoCiclo($user) + max(0, $custo)) > $cap;
    }

    /** Teto de CNPJs monitorados ativos do tier. null = ilimitado (inclui trial ativo). */
    public function limiteCnpjsMonitorados(User $user): ?int
    {
        if ($user->hasActiveTrial()) {
            return null;
        }

        return $this->limit($user, 'limite_cnpjs_monitorados');
    }

    /** Pode ativar mais um monitoramento? `$ativos` = nº de assinaturas ativas/pausadas hoje. */
    public function podeMonitorarMaisCnpj(User $user, int $ativos): bool
    {
        $limite = $this->limiteCnpjsMonitorados($user);

        return $limite === null || $ativos < $limite;
    }
}
