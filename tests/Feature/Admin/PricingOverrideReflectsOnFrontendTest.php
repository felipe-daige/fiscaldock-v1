<?php

use App\Models\User;
use App\Services\Admin\ComercialParametroService;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

/**
 * Regressão: os overrides do painel admin comercial (credit_unit_price / minimum_deposit)
 * precisam REFLETIR nas telas, não só no backend. Antes, copy hardcoded ("R$ 50", "R$ 0,20")
 * ignorava o override. Ver bug 2026-06-22.
 *
 * Views da landing e do plano atualizadas na Task UI (pós Task 4 que mata faixas de volume).
 */
beforeEach(function () {
    (new ComercialParametroService)->definir('minimum_deposit', 80.00, null);
    (new ComercialParametroService)->definir('credit_unit_price', 0.25, null);
});

it('a landing /precos reflete o depósito mínimo e o preço por crédito do override', function () {
    get('/precos')
        ->assertOk()
        ->assertSee('R$ 80 em créditos')      // hero
        ->assertSee('R$ 0,25 por crédito')    // tabela de consumo
        ->assertDontSee('R$ 50 em créditos');
});

it('a tela /app/consulta/nova reflete o preço por crédito do override', function () {
    actingAs(User::factory()->create())
        ->get('/app/consulta/nova')
        ->assertOk()
        ->assertSee('1 crédito = R$ 0,25')
        ->assertDontSee('1 crédito = R$ 0,20');
});

it('a tela /app/planos reflete o preço por crédito do override (via saldo incluso)', function () {
    app(\App\Services\Admin\ComercialParametroService::class)->definir('credit_unit_price', '0.25', null);

    actingAs(User::factory()->create())
        ->get('/app/planos')
        ->assertOk()
        ->assertSee('R$ 75,00 em saldo/mês')      // 300 créditos inclusos × R$0,25 (override)
        ->assertDontSee('R$ 60,00 em saldo/mês');  // valor com o peg padrão R$0,20
});
