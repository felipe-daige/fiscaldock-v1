<?php

use App\Models\User;
use App\Services\PricingCatalogService;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

it('libera o pacote de R$1 só para e-mail na allowlist', function () {
    config(['services.mercadopago.teste_emails' => ['dono@fiscaldock.com.br']]);

    $autorizado = User::factory()->create(['email' => 'dono@fiscaldock.com.br']);
    $catalog = new PricingCatalogService;

    $pacote = $catalog->resolveCheckoutSelection(PricingCatalogService::TEST_DEPOSIT_SLUG, null, $autorizado);

    expect($pacote)->not->toBeNull();
    expect($pacote['preco'])->toBe(1.00);
    expect($pacote['creditos'])->toBe(5); // R$1 / 0,20
    expect($pacote['slug'])->toBe(PricingCatalogService::TEST_DEPOSIT_SLUG);
});

it('nega o pacote de R$1 para usuário fora da allowlist', function () {
    config(['services.mercadopago.teste_emails' => ['dono@fiscaldock.com.br']]);

    $outro = User::factory()->create(['email' => 'cliente.qualquer@gmail.com']);
    $catalog = new PricingCatalogService;

    $pacote = $catalog->resolveCheckoutSelection(PricingCatalogService::TEST_DEPOSIT_SLUG, null, $outro);

    expect($pacote)->toBeNull();
});

it('nega o pacote de R$1 quando não há usuário (não vaza no catálogo)', function () {
    $catalog = new PricingCatalogService;

    $pacote = $catalog->resolveCheckoutSelection(PricingCatalogService::TEST_DEPOSIT_SLUG);

    expect($pacote)->toBeNull();
});

it('não altera a resolução dos pacotes normais', function () {
    $catalog = new PricingCatalogService;

    $business = $catalog->resolveCheckoutSelection('business');

    expect($business)->not->toBeNull();
    expect($business['preco'])->toBe(200.00);
    expect($business['creditos'])->toBe(1000);
});
