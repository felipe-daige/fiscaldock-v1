<?php

use App\Services\Clearance\DivergenciaService;
use Illuminate\Support\Collection;
use Tests\TestCase;

uses(TestCase::class);

function snapEnr(array $o = []): object
{
    return (object) array_merge([
        'chave_acesso' => str_repeat('5', 44), 'status' => 'AUTORIZADA', 'status_label' => 'AUTORIZADA',
        'valor_total' => 100.0, 'emit_cnpj' => '11111111000111', 'dest_cnpj' => '00000000000191',
        'situacao_ambiente' => 'produção', 'data_emissao' => '2026-01-15',
    ], $o);
}

function svcComDeclarado(array $declarado): DivergenciaService
{
    $svc = Mockery::mock(DivergenciaService::class)->makePartial();
    $svc->shouldReceive('buscarDeclaradoPorChave')->andReturn([str_repeat('5', 44) => $declarado]);

    return $svc;
}

it('contraparte declarada ausente no SEFAZ vira partes_divergentes crítica (valor>0)', function () {
    $svc = svcComDeclarado(['valor_total' => 100.0, 'contraparte_cnpj' => '99999999000199', 'data_emissao' => '2026-01-15', 'origem' => 'efd', 'id' => 1]);

    $r = $svc->analisar(new Collection([snapEnr()]), 1, 3);
    expect($r['breakdown']['partes_divergentes']['count'])->toBe(1);
    expect($r['veredito']['severidade'])->toBe('critica');
});

it('homologação escriturada vira operacionais crítica', function () {
    $svc = svcComDeclarado(['valor_total' => 100.0, 'contraparte_cnpj' => '11111111000111', 'data_emissao' => '2026-01-15', 'origem' => 'efd', 'id' => 1]);

    $r = $svc->analisar(new Collection([snapEnr(['situacao_ambiente' => 'homologação'])]), 1, 3);
    expect($r['breakdown']['operacionais']['count'])->toBe(1);
    expect($r['veredito']['severidade'])->toBe('critica');
});

it('data de emissão divergente vira revisar', function () {
    $svc = svcComDeclarado(['valor_total' => 100.0, 'contraparte_cnpj' => '11111111000111', 'data_emissao' => '2026-01-20', 'origem' => 'efd', 'id' => 1]);

    $r = $svc->analisar(new Collection([snapEnr(['data_emissao' => '2026-01-15'])]), 1, 3);
    expect($r['veredito']['total_revisar'])->toBeGreaterThanOrEqual(1);
});

it('contraparte presente e ambiente produção = ok', function () {
    $svc = svcComDeclarado(['valor_total' => 100.0, 'contraparte_cnpj' => '11111111000111', 'data_emissao' => '2026-01-15', 'origem' => 'efd', 'id' => 1]);

    $r = $svc->analisar(new Collection([snapEnr()]), 1, 3);
    expect($r['veredito']['severidade'])->toBe('ok');
});
