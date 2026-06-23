<?php

use App\Services\Admin\ComercialParametroService;
use App\Services\PricingCatalogService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('sem faixas, com preco por plano e peg mantido', function () {
    expect(ComercialParametroService::DEFAULTS)->not->toHaveKeys(['faixa_x_min', 'faixa_y_min', 'faixa_z_min']);
    expect(ComercialParametroService::DEFAULTS)->toHaveKeys(['credit_unit_price', 'minimum_deposit']);
    $s = new ComercialParametroService;
    expect($s->valor('preco_compliance'))->toBe(100);
});

it('retorna o default quando não há override', function () {
    $service = new ComercialParametroService;

    expect($service->valor('credit_unit_price'))->toBe(0.20);
    expect($service->valor('preco_validacao'))->toBe(15);
});

it('os defaults do registro batem com as constantes do PricingCatalogService (anti-drift)', function () {
    $defaults = ComercialParametroService::DEFAULTS;

    expect($defaults['credit_unit_price']['default'])->toBe(PricingCatalogService::CREDIT_UNIT_PRICE);
    expect($defaults['minimum_deposit']['default'])->toBe(PricingCatalogService::MINIMUM_DEPOSIT);
    expect($defaults)->not->toHaveKey('faixa_x_min');
    expect($defaults)->not->toHaveKey('faixa_y_min');
    expect($defaults)->not->toHaveKey('faixa_z_min');
    expect($defaults)->toHaveKey('preco_validacao');
    expect($defaults)->toHaveKey('preco_licitacao');
    expect($defaults)->toHaveKey('preco_compliance');
});

it('persiste e lê um override com tipagem correta', function () {
    $service = new ComercialParametroService;

    $service->definir('credit_unit_price', 0.25, null);

    expect($service->valor('credit_unit_price'))->toBe(0.25);
    $this->assertDatabaseHas('comercial_parametros', ['chave' => 'credit_unit_price', 'valor' => '0.25']);
});

it('faz cast de int para parâmetros inteiros', function () {
    $service = new ComercialParametroService;

    $service->definir('preco_licitacao', '60', null);

    expect($service->valor('preco_licitacao'))->toBe(60);
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
    expect($efetivos)->toHaveKey('preco_compliance');
    expect($efetivos)->not->toHaveKey('faixa_x_min');
});
