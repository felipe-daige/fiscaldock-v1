<?php

use App\Services\Admin\ComercialParametroService;
use App\Services\PricingCatalogService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('retorna o default quando não há override', function () {
    $service = new ComercialParametroService;

    expect($service->valor('credit_unit_price'))->toBe(0.20);
    expect($service->valor('faixa_x_min'))->toBe(1000);
});

it('os defaults do registro batem com as constantes do PricingCatalogService (anti-drift)', function () {
    $defaults = ComercialParametroService::DEFAULTS;

    expect($defaults['credit_unit_price']['default'])->toBe(PricingCatalogService::CREDIT_UNIT_PRICE);
    expect($defaults['minimum_deposit']['default'])->toBe(PricingCatalogService::MINIMUM_DEPOSIT);
});

it('persiste e lê um override com tipagem correta', function () {
    $service = new ComercialParametroService;

    $service->definir('credit_unit_price', 0.25, null);

    expect($service->valor('credit_unit_price'))->toBe(0.25);
    $this->assertDatabaseHas('comercial_parametros', ['chave' => 'credit_unit_price', 'valor' => '0.25']);
});

it('faz cast de int para parâmetros inteiros', function () {
    $service = new ComercialParametroService;

    $service->definir('faixa_y_min', '6000', null);

    expect($service->valor('faixa_y_min'))->toBe(6000);
});

it('resetar remove o override e volta ao default', function () {
    $service = new ComercialParametroService;
    $service->definir('credit_unit_price', 0.25, null);

    $service->resetar('credit_unit_price');

    expect($service->valor('credit_unit_price'))->toBe(0.20);
    $this->assertDatabaseMissing('comercial_parametros', ['chave' => 'credit_unit_price']);
});

it('rejeita chave desconhecida (não deixa criar parâmetro fora do registro)', function () {
    $service = new ComercialParametroService;

    expect(fn () => $service->definir('chave_inexistente', 1, null))
        ->toThrow(InvalidArgumentException::class);
});

it('efetivos() expõe default, override e valor efetivo por parâmetro', function () {
    $service = new ComercialParametroService;
    $service->definir('minimum_deposit', 80.00, null);

    $efetivos = $service->efetivos();

    expect($efetivos['minimum_deposit']['default'])->toBe(100.00);
    expect($efetivos['minimum_deposit']['override'])->toBe(80.00);
    expect($efetivos['minimum_deposit']['efetivo'])->toBe(80.00);
    expect($efetivos['credit_unit_price']['override'])->toBeNull();
    expect($efetivos['credit_unit_price']['efetivo'])->toBe(0.20);
});
