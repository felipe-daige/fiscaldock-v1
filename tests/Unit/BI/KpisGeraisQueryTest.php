<?php

use App\BI\Queries\KpisGeraisQuery;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
});

afterEach(function () {
    $this->user->forceDelete();
});

it('retorna estrutura correta com zeros quando não há notas', function () {
    $filtros = [
        'user_id' => $this->user->id,
        'data_inicio_iso' => now()->startOfMonth()->format('Y-m-d'),
        'data_fim_iso' => now()->endOfMonth()->format('Y-m-d'),
    ];

    $resultado = (new KpisGeraisQuery($filtros))->execute();

    expect($resultado)->toHaveKeys([
        'total_entradas_valor',
        'total_entradas_notas',
        'total_saidas_valor',
        'total_saidas_notas',
        'saldo_liquido',
        'carga_tributaria',
        'participantes_ativos',
        'notas_em_risco',
    ]);

    expect((float) $resultado['total_entradas_valor'])->toBe(0.0);
    expect((int) $resultado['total_entradas_notas'])->toBe(0);
    expect((float) $resultado['total_saidas_valor'])->toBe(0.0);
    expect((int) $resultado['total_saidas_notas'])->toBe(0);
    expect((float) $resultado['saldo_liquido'])->toBe(0.0);
    expect((float) $resultado['carga_tributaria'])->toBe(0.0);
    expect((int) $resultado['participantes_ativos'])->toBe(0);
    expect((int) $resultado['notas_em_risco'])->toBe(0);
});
