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

it('certidão sem campo tipo deriva status de conseguiu_emitir (CNDT/Estadual)', function () {
    // resposta real de CNDT/Estadual não traz `tipo` — deriva: emitiu negativa = Negativa
    $f = new CndEstadualFonte();
    expect($f->normalizar(['data' => [['conseguiu_emitir_certidao_negativa' => true, 'mensagem' => 'CERTIDÃO NEGATIVA']]], 'sucesso')['cnd_estadual']['status'])->toBe('Negativa');
    expect($f->normalizar(['data' => [['conseguiu_emitir_certidao_negativa' => false]]], 'sucesso')['cnd_estadual']['status'])->toBe('Positiva');
    // se vier `tipo`, usa ele
    expect($f->normalizar(['data' => [['tipo' => 'Positiva com efeitos de negativa']]], 'sucesso')['cnd_estadual']['status'])->toBe('Positiva com efeitos de negativa');
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

it('CGU CNC: certidão NADA CONSTA = sem sanção; base com registro = sanção', function () {
    $f = new CguCncFonte();
    // shape real: data[0] é a certidão, com conseguiu_emitir_certidao_negativa + bases
    $nada = $f->normalizar(['data' => [[
        'conseguiu_emitir_certidao_negativa' => true,
        'bases_dados_consultas' => [['nome' => 'CEIS', 'situacao' => 'Nada Consta'], ['nome' => 'CNEP', 'situacao' => 'Nada Consta']],
    ]]], 'sucesso');
    expect($nada['cgu_cnc']['possui_sancao'])->toBeFalse();

    $com = $f->normalizar(['data' => [[
        'conseguiu_emitir_certidao_negativa' => false,
        'bases_dados_consultas' => [['nome' => 'CEIS', 'situacao' => 'Consta 1 registro']],
    ]]], 'sucesso');
    expect($com['cgu_cnc']['possui_sancao'])->toBeTrue();
    expect($com['cgu_cnc']['bases_com_registro'])->toContain('CEIS');
});

it('CNJ Improbidade: certidao_negativa = sem condenação; senão possui', function () {
    $f = new CnjImprobidadeFonte();
    $neg = $f->normalizar(['data' => [['certidao_negativa' => true, 'registros' => 0, 'registros_lista' => []]]], 'sucesso');
    expect($neg['cnj_improbidade']['possui_condenacao'])->toBeFalse();

    $com = $f->normalizar(['data' => [['certidao_negativa' => false, 'registros' => 2, 'registros_lista' => [['p' => 1]]]]], 'sucesso');
    expect($com['cnj_improbidade']['possui_condenacao'])->toBeTrue();
    expect($com['cnj_improbidade']['total_condenacoes'])->toBe(2);
});
