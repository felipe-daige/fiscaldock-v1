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

it('plano nao-premium nunca e barrado', function () {
    $svc = app(PricingCatalogService::class);
    $user = User::factory()->create();
    $validacao = MonitoramentoPlano::porCodigo('validacao');

    $status = $svc->trialCapStatus($user, $validacao, 999);

    expect($status['aplicavel'])->toBeFalse();
    expect($status['bloqueado'])->toBeFalse();
});

it('conta CNPJs consumidos e calcula restantes por plano', function () {
    $svc = app(PricingCatalogService::class);
    $user = User::factory()->create();
    $licitacao = MonitoramentoPlano::porCodigo('licitacao');

    fazerLote($user, $licitacao, 3);

    $status = $svc->trialCapStatus($user, $licitacao, 0);

    expect($status['aplicavel'])->toBeTrue();
    expect($status['limite'])->toBe(5);
    expect($status['usados'])->toBe(3);
    expect($status['restantes'])->toBe(2);
    expect($status['bloqueado'])->toBeFalse();
});

it('bloqueia quando usados mais novos passa do limite', function () {
    $svc = app(PricingCatalogService::class);
    $user = User::factory()->create();
    $licitacao = MonitoramentoPlano::porCodigo('licitacao');

    fazerLote($user, $licitacao, 3);

    expect($svc->trialCapStatus($user, $licitacao, 2)['bloqueado'])->toBeFalse(); // 3+2=5 ok
    expect($svc->trialCapStatus($user, $licitacao, 3)['bloqueado'])->toBeTrue();  // 3+3=6 nao
});

it('lotes com status erro nao contam', function () {
    $svc = app(PricingCatalogService::class);
    $user = User::factory()->create();
    $licitacao = MonitoramentoPlano::porCodigo('licitacao');

    fazerLote($user, $licitacao, 5, ConsultaLote::STATUS_ERRO);

    expect($svc->trialCapStatus($user, $licitacao, 0)['usados'])->toBe(0);
});

it('teto e por plano: esgotar licitacao nao afeta compliance', function () {
    $svc = app(PricingCatalogService::class);
    $user = User::factory()->create();
    $licitacao = MonitoramentoPlano::porCodigo('licitacao');
    $compliance = MonitoramentoPlano::porCodigo('compliance');

    fazerLote($user, $licitacao, 5);

    expect($svc->trialCapStatus($user, $licitacao, 1)['bloqueado'])->toBeTrue();
    expect($svc->trialCapStatus($user, $compliance, 1)['bloqueado'])->toBeFalse();
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
