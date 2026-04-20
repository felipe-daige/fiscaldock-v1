<?php

use App\BI\Queries\VolumePorBlocoQuery;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
});

afterEach(function () {
    $this->user->forceDelete();
});

it('retorna estrutura com blocos notas_servicos, notas_mercadorias, notas_transportes', function () {
    $filtros = [
        'user_id' => $this->user->id,
        'data_inicio_iso' => now()->startOfMonth()->format('Y-m-d'),
        'data_fim_iso' => now()->endOfMonth()->format('Y-m-d'),
    ];

    $resultado = (new VolumePorBlocoQuery($filtros))->execute();

    expect($resultado)->toHaveKeys(['notas_servicos', 'notas_mercadorias', 'notas_transportes']);
    expect($resultado['notas_servicos'])->toHaveKeys(['valor', 'notas']);
    expect($resultado['notas_mercadorias'])->toHaveKeys(['valor', 'notas']);
    expect($resultado['notas_transportes'])->toHaveKeys(['valor', 'notas']);
});

it('retorna zeros quando não há notas', function () {
    $filtros = [
        'user_id' => $this->user->id,
        'data_inicio_iso' => now()->startOfMonth()->format('Y-m-d'),
        'data_fim_iso' => now()->endOfMonth()->format('Y-m-d'),
    ];

    $resultado = (new VolumePorBlocoQuery($filtros))->execute();

    expect((float) $resultado['notas_servicos']['valor'])->toBe(0.0);
    expect((int) $resultado['notas_servicos']['notas'])->toBe(0);
    expect((float) $resultado['notas_mercadorias']['valor'])->toBe(0.0);
    expect((float) $resultado['notas_transportes']['valor'])->toBe(0.0);
});
