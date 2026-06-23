<?php

use App\Services\Admin\ComercialParametroService;
use App\Services\PricingCatalogService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('sem override, os valores são idênticos aos atuais (garantia anti-regressão de preço)', function () {
    $pricing = new PricingCatalogService;

    expect($pricing->creditUnitPrice())->toBe(0.20);
    expect($pricing->getMinimumDeposit())->toBe(100.00);

    $tiers = collect($pricing->getTiers())->keyBy('slug');
    expect($tiers['base']['min_paid_credits'])->toBe(0);
    expect($tiers['base']['max_paid_credits'])->toBe(999);
    expect($tiers['x']['min_paid_credits'])->toBe(1000);
    expect($tiers['x']['max_paid_credits'])->toBe(4999);
    expect($tiers['y']['min_paid_credits'])->toBe(5000);
    expect($tiers['y']['max_paid_credits'])->toBe(19999);
    expect($tiers['z']['min_paid_credits'])->toBe(20000);
    expect($tiers['z']['max_paid_credits'])->toBeNull();
});

it('override de credit_unit_price é lido pelo PricingCatalogService', function () {
    (new ComercialParametroService)->definir('credit_unit_price', 0.25, null);

    expect((new PricingCatalogService)->creditUnitPrice())->toBe(0.25);
});

it('override de minimum_deposit é lido pelo PricingCatalogService', function () {
    (new ComercialParametroService)->definir('minimum_deposit', 80.00, null);

    expect((new PricingCatalogService)->getMinimumDeposit())->toBe(80.00);
});

it('override de limiar de faixa desloca as fronteiras de getTiers', function () {
    (new ComercialParametroService)->definir('faixa_y_min', 6000, null);

    $tiers = collect((new PricingCatalogService)->getTiers())->keyBy('slug');

    expect($tiers['x']['max_paid_credits'])->toBe(5999); // y_min - 1
    expect($tiers['y']['min_paid_credits'])->toBe(6000);
});
