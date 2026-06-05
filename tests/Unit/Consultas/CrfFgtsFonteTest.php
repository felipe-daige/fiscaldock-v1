<?php

use App\Services\Consultas\Fontes\CrfFgtsFonte;

it('expõe metadados da fonte CRF FGTS', function () {
    $f = new CrfFgtsFonte();
    expect($f->chave())->toBe('crf_fgts');
    expect($f->provider())->toBe('infosimples');
    expect($f->slug())->toBe('caixa/regularidade');
    expect($f->fornece())->toBe(['crf_fgts']);
    expect($f->custoCreditos())->toBeGreaterThan(0);
});

it('normaliza sucesso (situacao→status, numero_certificado→codigo) e 611→INDETERMINADO', function () {
    $f = new CrfFgtsFonte();
    $ok = $f->normalizar(['data' => [[
        'situacao' => 'Regular', 'numero_certificado' => 'FGTS999',
        'validade_data' => '15/07/2026', 'conseguiu_emitir_certidao_negativa' => true,
    ]]], 'sucesso');
    expect($ok['crf_fgts']['status'])->toBe('Regular');
    expect($ok['crf_fgts']['certidao_codigo'])->toBe('FGTS999');
    expect($ok['crf_fgts']['data_validade'])->toBe('15/07/2026');

    $ind = $f->normalizar(['code' => 611, 'errors' => ['indisponível']], 'indeterminado');
    expect($ind['crf_fgts']['status'])->toBe('INDETERMINADO');
    expect($ind['crf_fgts']['mensagem'])->toContain('indisponível');
});
