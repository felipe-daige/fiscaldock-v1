<?php

use App\BI\Queries\FluxoMensalQuery;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
});

afterEach(function () {
    $this->user->forceDelete();
});

it('retorna sempre 12 meses', function () {
    $filtros = [
        'user_id' => $this->user->id,
        'data_inicio_iso' => now()->subMonths(11)->startOfMonth()->format('Y-m-d'),
        'data_fim_iso' => now()->endOfMonth()->format('Y-m-d'),
    ];

    $resultado = (new FluxoMensalQuery($filtros))->execute();

    expect($resultado)->toHaveCount(12);
});

it('cada mês tem as chaves mes, label, entradas, saidas, saldo', function () {
    $filtros = [
        'user_id' => $this->user->id,
        'data_inicio_iso' => now()->subMonths(11)->startOfMonth()->format('Y-m-d'),
        'data_fim_iso' => now()->endOfMonth()->format('Y-m-d'),
    ];

    $resultado = (new FluxoMensalQuery($filtros))->execute();

    expect($resultado[0])->toHaveKeys(['mes', 'label', 'entradas', 'saidas', 'saldo']);
});

it('meses sem dados retornam zeros', function () {
    $filtros = [
        'user_id' => $this->user->id,
        'data_inicio_iso' => now()->subMonths(11)->startOfMonth()->format('Y-m-d'),
        'data_fim_iso' => now()->endOfMonth()->format('Y-m-d'),
    ];

    $resultado = (new FluxoMensalQuery($filtros))->execute();

    foreach ($resultado as $mes) {
        expect((float) $mes['entradas'])->toBe(0.0);
        expect((float) $mes['saidas'])->toBe(0.0);
    }
});

it('meses são ordenados do mais antigo ao mais recente', function () {
    $filtros = [
        'user_id' => $this->user->id,
        'data_inicio_iso' => now()->subMonths(11)->startOfMonth()->format('Y-m-d'),
        'data_fim_iso' => now()->endOfMonth()->format('Y-m-d'),
    ];

    $resultado = (new FluxoMensalQuery($filtros))->execute();

    $meses = array_column($resultado, 'mes');
    $sorted = $meses;
    sort($sorted);
    expect($meses)->toBe($sorted);
});
