<?php

use App\Models\AccountSubscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\Entitlements\EntitlementService;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(SubscriptionPlanSeeder::class);
    $this->svc = new EntitlementService();
});

function assinar(User $user, string $codigo, array $overrides = []): void
{
    $plano = SubscriptionPlan::where('codigo', $codigo)->first();
    AccountSubscription::create(array_merge([
        'user_id' => $user->id,
        'subscription_plan_id' => $plano->id,
        'status' => 'ativa',
        'ciclo' => 'mensal',
    ], $overrides));
}

it('sem assinatura resolve para o plano Free', function () {
    $user = User::factory()->create();
    expect($this->svc->planFor($user)->codigo)->toBe('free');
});

it('can() respeita capabilities booleanas do plano', function () {
    $user = User::factory()->create();
    assinar($user, 'essencial');

    expect($this->svc->can($user, 'clearance_lote'))->toBeTrue();
    expect($this->svc->can($user, 'clearance_full'))->toBeFalse();
});

it('Free não tem clearance em lote', function () {
    $user = User::factory()->create();
    expect($this->svc->can($user, 'clearance_lote'))->toBeFalse();
});

it('exportFormats e capability cru', function () {
    $user = User::factory()->create();
    assinar($user, 'profissional');

    expect($this->svc->exportFormats($user))->toBe(['csv', 'excel']);
    expect($this->svc->capability($user, 'bi'))->toBe('completo');
    expect($this->svc->capability($user, 'retencao_meses'))->toBeNull();
});

it('limit retorna o teto do tier (null = ilimitado)', function () {
    $user = User::factory()->create();
    assinar($user, 'essencial');
    expect($this->svc->limit($user, 'limite_cnpjs_monitorados'))->toBe(10);

    $ent = User::factory()->create();
    assinar($ent, 'enterprise');
    expect($this->svc->limit($ent, 'limite_cnpjs_monitorados'))->toBeNull();
});

it('faixaFor reflete a faixa comprada pelo tier', function () {
    $user = User::factory()->create();
    assinar($user, 'escritorio');
    expect($this->svc->faixaFor($user))->toBe('y');
});

it('consumptionCap = default créditos inclusos quando não setado', function () {
    $user = User::factory()->create();
    assinar($user, 'essencial');
    expect($this->svc->consumptionCap($user))->toBe(300);
});

it('consumptionCap respeita o limite explícito do cliente', function () {
    $user = User::factory()->create();
    assinar($user, 'essencial', ['limite_consumo_automatico' => 120]);
    expect($this->svc->consumptionCap($user))->toBe(120);
});

function ativarTrial(User $user, int $creditos = 50): void
{
    $user->forceFill([
        'trial_used' => true,
        'trial_started_at' => now(),
        'trial_expires_at' => now()->addDays(30),
        'trial_credits_remaining' => $creditos,
    ])->save();
}

it('permits(): Free sem trial é bloqueado nas capabilities pagas', function () {
    $user = User::factory()->create();
    expect($this->svc->permits($user, 'clearance_lote'))->toBeFalse();
    expect($this->svc->permits($user, 'score_historico'))->toBeFalse();
    expect($this->svc->permits($user, 'export'))->toBeFalse();
});

it('permits(): trial ativo libera tudo (mesmo no plano Free)', function () {
    $user = User::factory()->create();
    ativarTrial($user);
    expect($this->svc->permits($user, 'clearance_lote'))->toBeTrue();
    expect($this->svc->permits($user, 'score_historico'))->toBeTrue();
    expect($this->svc->permits($user, 'export'))->toBeTrue();
});

it('permits(): trial expirado NÃO libera (volta a valer o plano Free)', function () {
    $user = User::factory()->create();
    $user->forceFill([
        'trial_used' => true,
        'trial_expires_at' => now()->subDay(),
        'trial_credits_remaining' => 50,
    ])->save();
    expect($this->svc->permits($user, 'clearance_lote'))->toBeFalse();
});

it('permits(): plano pago libera conforme a capability', function () {
    $user = User::factory()->create();
    assinar($user, 'essencial'); // clearance_lote=true, export=[csv], score_historico=false
    expect($this->svc->permits($user, 'clearance_lote'))->toBeTrue();
    expect($this->svc->permits($user, 'export'))->toBeTrue();
    expect($this->svc->permits($user, 'score_historico'))->toBeFalse();
});
