<?php

use App\Models\AccountSubscription;
use App\Models\MonitoramentoAssinatura;
use App\Models\MonitoramentoConsulta;
use App\Models\MonitoramentoPlano;
use App\Models\Participante;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\Entitlements\EntitlementService;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(SubscriptionPlanSeeder::class);
});

function fase5Assinar(User $user, string $codigo, array $overrides = []): void
{
    $plano = SubscriptionPlan::where('codigo', $codigo)->first();
    AccountSubscription::create(array_merge([
        'user_id' => $user->id,
        'subscription_plan_id' => $plano->id,
        'status' => 'ativa',
        'ciclo' => 'mensal',
    ], $overrides));
}

function fase5Participante(User $user, string $doc = '11222333000181'): Participante
{
    return Participante::create([
        'user_id' => $user->id, 'documento' => $doc, 'razao_social' => 'ACME',
    ]);
}

// ---- Freio de consumo (§6.2) ----

it('soma o consumo do auto-monitor no ciclo e detecta o cap estourado', function () {
    $user = User::factory()->create();
    fase5Assinar($user, 'essencial', ['ultimo_grant_em' => now()->subDay(), 'limite_consumo_automatico' => 5]);

    // consumo de monitor DENTRO do ciclo (conta)
    DB::table('credit_transactions')->insert([
        'user_id' => $user->id, 'amount' => -5, 'balance_after' => 0,
        'type' => 'monitoramento_assinatura', 'created_at' => now(), 'updated_at' => now(),
    ]);
    // consumo de monitor ANTES do ciclo (não conta)
    DB::table('credit_transactions')->insert([
        'user_id' => $user->id, 'amount' => -50, 'balance_after' => 0,
        'type' => 'monitoramento_assinatura', 'created_at' => now()->subDays(10), 'updated_at' => now(),
    ]);
    // dedução de outro tipo no ciclo (não conta)
    DB::table('credit_transactions')->insert([
        'user_id' => $user->id, 'amount' => -99, 'balance_after' => 0,
        'type' => 'consulta_lote', 'created_at' => now(), 'updated_at' => now(),
    ]);

    $svc = app(EntitlementService::class);

    expect($svc->consumoMonitoramentoNoCiclo($user))->toBe(5);
    expect($svc->monitoramentoCapEstourado($user, 1))->toBeTrue();   // 5+1 > 5
    expect($svc->monitoramentoCapEstourado($user, 0))->toBeFalse();  // 5+0 > 5 = false
});

it('cap <= 0 (Free sem inclusos) não aciona o freio', function () {
    $user = User::factory()->create(); // sem assinatura → Free, creditos_inclusos = 0
    $svc = app(EntitlementService::class);

    expect($svc->consumptionCap($user))->toBe(0);
    expect($svc->monitoramentoCapEstourado($user, 10))->toBeFalse();
});

it('o motor pausa a assinatura e não dispara ao estourar o cap de consumo', function () {
    Bus::fake();
    $user = User::factory()->create(['credits' => 100]); // saldo sobrando — não é o saldo que pausa
    fase5Assinar($user, 'essencial', ['ultimo_grant_em' => now()->subDay(), 'limite_consumo_automatico' => 5]);
    DB::table('credit_transactions')->insert([
        'user_id' => $user->id, 'amount' => -5, 'balance_after' => 95,
        'type' => 'monitoramento_assinatura', 'created_at' => now(), 'updated_at' => now(),
    ]);
    $p = fase5Participante($user);
    $a = MonitoramentoAssinatura::create([
        'user_id' => $user->id, 'participante_id' => $p->id,
        'plano_id' => MonitoramentoPlano::porCodigo('licitacao')->id, // custo 10
        'status' => 'ativo', 'frequencia_dias' => 30, 'proxima_execucao_em' => now()->subDay(),
    ]);

    $this->artisan('monitoramento:executar-pendentes')->assertSuccessful();

    expect($a->fresh()->status)->toBe('pausado');
    expect(MonitoramentoConsulta::where('assinatura_id', $a->id)->count())->toBe(0);
});

// ---- Gating de CNPJs monitorados ----

it('bloqueia ativar monitoramento acima do limite de CNPJs do plano', function () {
    $user = User::factory()->create(); // free → limite_cnpjs = 1
    fase5Assinar($user, 'free');

    // já tem 1 ativo (criado direto, fora do gate)
    $p1 = fase5Participante($user, '11222333000181');
    MonitoramentoAssinatura::create([
        'user_id' => $user->id, 'participante_id' => $p1->id,
        'plano_id' => MonitoramentoPlano::porCodigo('licitacao')->id,
        'status' => 'ativo', 'frequencia_dias' => 30, 'proxima_execucao_em' => now()->addDay(),
    ]);

    $p2 = fase5Participante($user, '11444777000161');
    actingAs($user)->postJson(route('app.monitoramento.assinatura.criar'), [
        'participante_id' => $p2->id,
        'plano_id' => MonitoramentoPlano::porCodigo('licitacao')->id,
        'frequencia' => 'mensal',
    ])->assertStatus(403);

    expect(MonitoramentoAssinatura::where('participante_id', $p2->id)->count())->toBe(0);
});

it('trial ativo não tem teto de CNPJs monitorados', function () {
    $user = User::factory()->create([
        'trial_used' => true,
        'trial_expires_at' => now()->addDays(10),
        'trial_credits_remaining' => 50,
    ]);
    $svc = app(EntitlementService::class);

    expect($svc->limiteCnpjsMonitorados($user))->toBeNull();
    expect($svc->podeMonitorarMaisCnpj($user, 999))->toBeTrue();
});

// ---- Edição do cap pelo contador ----

it('o contador define o cap de consumo na assinatura', function () {
    $user = User::factory()->create();
    fase5Assinar($user, 'essencial');

    actingAs($user)->postJson(route('app.monitoramento.limite-consumo'), ['limite' => 20])
        ->assertOk()
        ->assertJson(['success' => true, 'cap_efetivo' => 20]);

    expect((int) $user->subscription()->first()->limite_consumo_automatico)->toBe(20);
});

it('definir cap sem assinatura retorna 409', function () {
    $user = User::factory()->create();

    actingAs($user)->postJson(route('app.monitoramento.limite-consumo'), ['limite' => 20])
        ->assertStatus(409);
});
