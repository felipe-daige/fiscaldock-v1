<?php

use App\Models\ConsultaLote;
use App\Models\CreditTransaction;
use App\Models\MonitoramentoPlano;
use App\Models\User;
use App\Services\PricingCatalogService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

function fazerLote(User $user, MonitoramentoPlano $plano, int $qtd, string $status = ConsultaLote::STATUS_PROCESSANDO): void
{
    ConsultaLote::create([
        'user_id' => $user->id,
        'plano_id' => $plano->id,
        'status' => $status,
        'total_participantes' => $qtd,
        'creditos_cobrados' => $qtd * $plano->custo_creditos,
        'tab_id' => (string) \Illuminate\Support\Str::uuid(),
    ]);
}

it('plano gratuito nunca e barrado pelo teto', function () {
    $svc = app(PricingCatalogService::class);
    $user = User::factory()->create();
    $gratuito = MonitoramentoPlano::porCodigo('gratuito');

    $status = $svc->trialCapStatus($user, $gratuito, 999);

    expect($status['aplicavel'])->toBeFalse();
    expect($status['bloqueado'])->toBeFalse();
});

it('validacao agora entra no teto de teste', function () {
    $svc = app(PricingCatalogService::class);
    $user = User::factory()->create();
    $validacao = MonitoramentoPlano::porCodigo('validacao');

    $status = $svc->trialCapStatus($user, $validacao, 0);

    expect($status['aplicavel'])->toBeTrue();
    expect($status['limite'])->toBe(5);
});

it('o teto e um pool unico somado entre todos os planos pagos', function () {
    $svc = app(PricingCatalogService::class);
    $user = User::factory()->create();
    $licitacao = MonitoramentoPlano::porCodigo('licitacao');
    $compliance = MonitoramentoPlano::porCodigo('compliance');

    // gasta 3 em licitacao e 1 em compliance -> pool global = 4
    fazerLote($user, $licitacao, 3);
    fazerLote($user, $compliance, 1);

    $status = $svc->trialCapStatus($user, MonitoramentoPlano::porCodigo('due_diligence'), 0);

    expect($status['aplicavel'])->toBeTrue();
    expect($status['usados'])->toBe(4);
    expect($status['restantes'])->toBe(1);
    expect($status['bloqueado'])->toBeFalse();
});

it('bloqueia quando a soma global mais os novos passa do limite', function () {
    $svc = app(PricingCatalogService::class);
    $user = User::factory()->create();
    $licitacao = MonitoramentoPlano::porCodigo('licitacao');

    fazerLote($user, $licitacao, 3);

    $compliance = MonitoramentoPlano::porCodigo('compliance');
    expect($svc->trialCapStatus($user, $compliance, 2)['bloqueado'])->toBeFalse(); // 3+2=5 ok
    expect($svc->trialCapStatus($user, $compliance, 3)['bloqueado'])->toBeTrue();  // 3+3=6 nao
});

it('lotes com status erro nao contam', function () {
    $svc = app(PricingCatalogService::class);
    $user = User::factory()->create();
    $licitacao = MonitoramentoPlano::porCodigo('licitacao');

    fazerLote($user, $licitacao, 5, ConsultaLote::STATUS_ERRO);

    expect($svc->trialCapStatus($user, $licitacao, 0)['usados'])->toBe(0);
});

it('esgotar o pool em um plano bloqueia os outros (pool unico)', function () {
    $svc = app(PricingCatalogService::class);
    $user = User::factory()->create();
    $licitacao = MonitoramentoPlano::porCodigo('licitacao');
    $compliance = MonitoramentoPlano::porCodigo('compliance');

    fazerLote($user, $licitacao, 5);

    expect($svc->trialCapStatus($user, $licitacao, 1)['bloqueado'])->toBeTrue();
    expect($svc->trialCapStatus($user, $compliance, 1)['bloqueado'])->toBeTrue();
    expect($svc->trialCapStatus($user, MonitoramentoPlano::porCodigo('validacao'), 1)['bloqueado'])->toBeTrue();
});

it('apos a primeira compra o teto some', function () {
    $svc = app(PricingCatalogService::class);
    $user = User::factory()->create();
    $licitacao = MonitoramentoPlano::porCodigo('licitacao');

    fazerLote($user, $licitacao, 5);
    CreditTransaction::create([
        'user_id' => $user->id,
        'amount' => 250,
        'balance_after' => 250,
        'type' => 'purchase',
    ]);

    $status = $svc->trialCapStatus($user, $licitacao, 999);

    expect($status['aplicavel'])->toBeFalse();
    expect($status['bloqueado'])->toBeFalse();
});
