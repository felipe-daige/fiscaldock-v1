<?php

use App\Models\User;
use App\Services\Dashboard\DashboardDataService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

it('monta o cockpit completo com periodo normalizado', function () {
    $user = User::factory()->create();

    $dados = app(DashboardDataService::class)->cockpit($user->id, $user, null, 99);

    expect($dados)->toHaveKeys(['kpis', 'triagem', 'tendencia', 'meta'])
        ->and($dados['meta']['periodo'])->toBe(6) // 99 -> default 6
        ->and($dados['tendencia'])->toHaveKeys(['meses', 'valor', 'qtd'])
        ->and($dados['triagem'])->toBeArray();
});
