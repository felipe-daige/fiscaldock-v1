<?php

use App\Services\Consultas\Fontes\CndtFonte;

it('expõe metadados da fonte CNDT', function () {
    $f = new CndtFonte();
    expect($f->chave())->toBe('cndt');
    expect($f->provider())->toBe('infosimples');
    expect($f->slug())->toBe('tribunal/tst/cndt');
    expect($f->fornece())->toBe(['cndt']);
    expect($f->custoCreditos())->toBeGreaterThan(0);
    expect($f->params(['cnpj' => '19.131.243/0001-97'])['cnpj'])->toBe('19131243000197');
});

it('normaliza sucesso e 611→INDETERMINADO', function () {
    $f = new CndtFonte();
    $ok = $f->normalizar(['data' => [[
        'tipo' => 'Negativa', 'numero_certidao' => 'TST123',
        'validade_data' => '01/12/2026', 'conseguiu_emitir_certidao_negativa' => true, 'mensagem' => 'm',
    ]]], 'sucesso');
    expect($ok['cndt']['status'])->toBe('Negativa');
    expect($ok['cndt']['certidao_codigo'])->toBe('TST123');
    expect($ok['cndt']['data_validade'])->toBe('01/12/2026');
    expect($ok['consultas_realizadas'])->toContain('cndt');

    $ind = $f->normalizar(['code' => 611, 'code_message' => 'sem dados'], 'indeterminado');
    expect($ind['cndt']['status'])->toBe('INDETERMINADO');
});

it('pronta() só com InfoSimples ativo + token', function () {
    $f = new CndtFonte();
    config()->set('consultas.infosimples_ativo', false);
    expect($f->pronta())->toBeFalse();

    config()->set('consultas.infosimples_ativo', true);
    config()->set('consultas.providers.infosimples.token', 'tok-123');
    expect($f->pronta())->toBeTrue();
});
