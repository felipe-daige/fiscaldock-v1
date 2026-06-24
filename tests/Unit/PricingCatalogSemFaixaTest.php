<?php

use App\Models\MonitoramentoPlano;
use App\Models\User;
use App\Services\PricingCatalogService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

it('preco do plano sem faixa', function () {
    $user = User::factory()->create();
    $plano = MonitoramentoPlano::where('codigo', 'compliance')->first();
    expect(app(PricingCatalogService::class)->getProductCreditsByPlan($plano, $user))->toBe(25);
});

it('faixas removidas', function () {
    expect(method_exists(PricingCatalogService::class, 'getTiers'))->toBeFalse();
});

it('conversao credito->reais mantida', function () {
    expect(app(PricingCatalogService::class)->creditsToCurrency(100))->toBe(20.0);
});
