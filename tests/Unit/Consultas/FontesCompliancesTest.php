<?php

use App\Services\Consultas\Fontes\CguCncFonte;
use App\Services\Consultas\Fontes\CndEstadualFonte;
use App\Services\Consultas\Fontes\CnjImprobidadeFonte;
use App\Services\Consultas\Fontes\SintegraFonte;

it('CND Estadual: metadados + uf no param + sucesso/611', function () {
    $f = new CndEstadualFonte();
    expect($f->chave())->toBe('cnd_estadual');
    expect($f->slug())->toBe('sefaz/certidao-debitos');
    expect($f->params(['cnpj' => '19131243000197', 'uf' => 'sp'])['uf'])->toBe('SP');

    $ok = $f->normalizar(['data' => [['tipo' => 'Negativa', 'uf' => 'SP', 'validade_data' => '01/12/2026']]], 'sucesso');
    expect($ok['cnd_estadual']['status'])->toBe('Negativa');
    expect($ok['cnd_estadual']['uf'])->toBe('SP');

    expect($f->normalizar(['code' => 611], 'indeterminado')['cnd_estadual']['status'])->toBe('INDETERMINADO');
});

it('CND Estadual: cobertura por UF (aplicavelPara) + INDISPONIVEL fora da cobertura', function () {
    config()->set('consultas.cnd_estadual.ufs_cobertas', ['SP', 'RJ']);
    $f = new CndEstadualFonte();

    expect($f->aplicavelPara(['uf' => 'SP']))->toBeTrue();
    expect($f->aplicavelPara(['uf' => 'sp']))->toBeTrue(); // normaliza maiúsculas
    expect($f->aplicavelPara(['uf' => 'AC']))->toBeFalse(); // fora da cobertura
    expect($f->aplicavelPara(['uf' => '']))->toBeFalse();   // sem UF
    expect($f->aplicavelPara([]))->toBeFalse();

    $out = $f->normalizar([], 'nao_aplicavel');
    expect($out['cnd_estadual']['status'])->toBe('INDISPONIVEL');
});

it('SINTEGRA: cadastral (IE/situação)', function () {
    $f = new SintegraFonte();
    expect($f->chave())->toBe('sintegra');
    expect($f->slug())->toBe('sintegra/unificada');

    $ok = $f->normalizar(['data' => [[
        'uf' => 'SP', 'inscricao_estadual' => '111.111.111.111', 'situacao' => 'Habilitado',
    ]]], 'sucesso');
    expect($ok['sintegra']['inscricao_estadual'])->toBe('111.111.111.111');
    expect($ok['sintegra']['situacao'])->toBe('Habilitado');
    expect($ok['consultas_realizadas'])->toContain('sintegra');
});

it('CGU CNC: sem sanção (data vazio) vs com sanção', function () {
    $f = new CguCncFonte();
    $sem = $f->normalizar(['data' => []], 'sucesso');
    expect($sem['cgu_cnc']['possui_sancao'])->toBeFalse();
    expect($sem['cgu_cnc']['total_sancoes'])->toBe(0);

    $com = $f->normalizar(['data' => [['orgao' => 'X'], ['orgao' => 'Y']]], 'sucesso');
    expect($com['cgu_cnc']['possui_sancao'])->toBeTrue();
    expect($com['cgu_cnc']['total_sancoes'])->toBe(2);
});

it('CNJ Improbidade: sem condenação vs com condenação', function () {
    $f = new CnjImprobidadeFonte();
    expect($f->normalizar(['data' => []], 'sucesso')['cnj_improbidade']['possui_condenacao'])->toBeFalse();

    $com = $f->normalizar(['data' => [['processo' => '123']]], 'sucesso');
    expect($com['cnj_improbidade']['possui_condenacao'])->toBeTrue();
    expect($com['cnj_improbidade']['total_condenacoes'])->toBe(1);
});
