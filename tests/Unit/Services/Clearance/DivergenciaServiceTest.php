<?php

use App\Services\Clearance\DivergenciaService;
use Illuminate\Support\Collection;

it('retorna estrutura vazia quando não há snapshots', function () {
    $service = new DivergenciaService();

    $resultado = $service->analisar(new Collection(), userId: 1, creditosCobrados: 0);

    expect($resultado)->toHaveKeys(['veredito', 'kpis', 'breakdown', 'divergencias', 'sem_divergencia', 'ruido']);
    expect($resultado['veredito']['severidade'])->toBe('ok');
    expect($resultado['veredito']['total_criticas'])->toBe(0);
    expect($resultado['veredito']['total_revisar'])->toBe(0);
    expect($resultado['veredito']['valor_divergente'])->toBe(0.0);
    expect($resultado['kpis']['existencia']['total'])->toBe(0);
    expect($resultado['divergencias'])->toBeInstanceOf(Collection::class)->toHaveCount(0);
    expect($resultado['sem_divergencia'])->toBeInstanceOf(Collection::class)->toHaveCount(0);
    expect($resultado['ruido'])->toBeInstanceOf(Collection::class)->toHaveCount(0);
});
