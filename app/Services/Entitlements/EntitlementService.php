<?php

namespace App\Services\Entitlements;

use App\Models\Cliente;
use App\Models\SubscriptionPlan;
use App\Models\User;

class EntitlementService
{
    /** Capabilities tratadas como booleanas por can(). */
    private const BOOLEAN_CAPS = ['pdf_executivo', 'clearance_lote', 'clearance_full', 'score_historico'];

    /** Rank de profundidade do auto-monitor (cadastral=gratuito é o mais raso). */
    private const RANK_PROFUNDIDADE = [
        'gratuito' => 0, 'cadastral' => 0, 'validacao' => 1,
        'licitacao' => 2, 'compliance' => 3, 'due_diligence' => 4, 'enterprise' => 4,
    ];

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

    /**
     * Teto de clientes cadastrados do tier (NÃO conta a empresa própria). null = ilimitado
     * (inclui trial ativo). Espelha limiteCnpjsMonitorados — política "trial libera tudo".
     */
    public function limiteClientes(User $user): ?int
    {
        if ($user->hasActiveTrial()) {
            return null;
        }

        return $this->limit($user, 'limite_clientes');
    }

    /** Clientes que contam pro cap (exclui a empresa própria, que é auto-criada). */
    public function clientesAtuais(User $user): int
    {
        return Cliente::where('user_id', $user->id)
            ->where('is_empresa_propria', false)
            ->count();
    }

    /** Pode adicionar mais `$novos` cliente(s) não-própria sem estourar o teto do tier? */
    public function podeAdicionarCliente(User $user, int $novos = 1): bool
    {
        $limite = $this->limiteClientes($user);

        return $limite === null || $this->clientesAtuais($user) + $novos <= $limite;
    }

    /**
     * firstOrCreate de cliente respeitando o cap do tier. Chokepoint dos fluxos que
     * nascem cliente automaticamente (import XML, "decidir depois", salvar CNPJs):
     * vincula a um existente (não conta), cria um novo se couber, ou devolve null quando
     * o teto já foi atingido — deixando a nota sem dono (estado "decidir depois" válido).
     */
    public function firstOrCreateClienteComCap(int $userId, string $documento, array $atributos = []): ?Cliente
    {
        $existente = Cliente::where('user_id', $userId)->where('documento', $documento)->first();
        if ($existente !== null) {
            return $existente;
        }

        $user = User::find($userId);
        if ($user === null || ! $this->podeAdicionarCliente($user)) {
            return null;
        }

        return Cliente::create(array_merge($atributos, [
            'user_id' => $userId,
            'documento' => $documento,
            'is_empresa_propria' => false,
        ]));
    }

    // ---- Fase 5.1: gating de frequência e profundidade do auto-monitor por tier ----

    /**
     * Intervalo mínimo permitido entre execuções (dias) — quanto menor, mais frequente.
     * Trial = 1 (sem teto). Usa a capability `frequencia_minima_dias` se houver; senão cai no
     * `frequencia_padrao_dias` do plano (fallback gracioso enquanto o seeder não foi reaplicado).
     */
    public function frequenciaMinimaMonitoramento(User $user): int
    {
        if ($user->hasActiveTrial()) {
            return 1;
        }

        $plano = $this->planFor($user);
        $cap = $plano->capability('frequencia_minima_dias', null);

        return $cap !== null ? (int) $cap : (int) ($plano->frequencia_padrao_dias ?? 30);
    }

    public function permiteFrequenciaMonitoramento(User $user, int $dias): bool
    {
        return $dias >= $this->frequenciaMinimaMonitoramento($user);
    }

    /** Profundidade máxima do auto-monitor do tier (trial = due_diligence, libera tudo). */
    public function profundidadeMaximaMonitoramento(User $user): string
    {
        if ($user->hasActiveTrial()) {
            return 'due_diligence';
        }

        return (string) ($this->planFor($user)->profundidade_auto_monitor ?? 'cadastral');
    }

    /** O plano de monitoramento escolhido (`$codigoPlano`) cabe na profundidade do tier? */
    public function permiteProfundidadeMonitoramento(User $user, string $codigoPlano): bool
    {
        $rankEscolhido = self::RANK_PROFUNDIDADE[$codigoPlano] ?? null;
        if ($rankEscolhido === null) {
            return true; // código desconhecido: não bloqueia (best-effort)
        }

        $rankMax = self::RANK_PROFUNDIDADE[$this->profundidadeMaximaMonitoramento($user)] ?? 4;

        return $rankEscolhido <= $rankMax;
    }
}
