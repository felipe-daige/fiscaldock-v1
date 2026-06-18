<?php

use App\Models\User;
use App\Services\Dashboard\DashboardDataService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

it('monta o cockpit completo com periodo normalizado', function () {
    $user = User::factory()->create();

    $dados = app(DashboardDataService::class)->cockpit($user->id, $user, null, 99);

    expect($dados)->toHaveKeys(['kpis', 'triagem', 'tendencia', 'top_fornecedores', 'risco_distribuicao', 'meta'])
        ->and($dados['meta']['periodo'])->toBe(6) // 99 -> default 6
        ->and($dados['tendencia'])->toHaveKeys(['meses', 'saida_valor', 'saida_qtd', 'entrada_valor', 'entrada_qtd'])
        ->and($dados['triagem'])->toBeArray()
        ->and($dados['top_fornecedores'])->toBeArray()
        ->and($dados['risco_distribuicao'])->toBeArray();
});

it('tendencia alinha entrada e saida no mesmo eixo de meses', function () {
    $user = User::factory()->create();

    $t = app(DashboardDataService::class)->cockpit($user->id, $user, null, 6)['tendencia'];

    // 6 meses no eixo, e as 4 séries têm o mesmo comprimento do eixo
    expect($t['meses'])->toHaveCount(6)
        ->and($t['saida_valor'])->toHaveCount(6)
        ->and($t['entrada_valor'])->toHaveCount(6)
        ->and($t['saida_qtd'])->toHaveCount(6)
        ->and($t['entrada_qtd'])->toHaveCount(6);
});
