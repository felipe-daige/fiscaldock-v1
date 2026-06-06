<?php

use App\Services\Consultas\Fontes\ProtestosFonte;

it('Protestos: metadados + sem/com protesto', function () {
    $f = new ProtestosFonte();
    expect($f->chave())->toBe('protestos');
    expect($f->slug())->toBe('ieptb/protestos');
    expect($f->fornece())->toBe(['protestos']);

    expect($f->normalizar(['data' => []], 'sucesso')['protestos']['possui_protesto'])->toBeFalse();
    $com = $f->normalizar(['data' => [['cartorio' => 'X'], ['cartorio' => 'Y']]], 'sucesso');
    expect($com['protestos']['possui_protesto'])->toBeTrue();
    expect($com['protestos']['total_protestos'])->toBe(2);
});

it('fontes de lista mostram INDISPONIVEL quando bloqueadas (nao_aplicavel)', function () {
    foreach ([new ProtestosFonte(),
        new App\Services\Consultas\Fontes\CguCncFonte(), new App\Services\Consultas\Fontes\CnjImprobidadeFonte()] as $f) {
        $out = $f->normalizar(['_motivo' => 'Modo teste'], 'nao_aplicavel');
        expect($out[$f->chave()]['status'])->toBe('INDISPONIVEL');
        expect($out[$f->chave()]['mensagem'])->toBe('Modo teste');
    }
    // sintegra usa 'situacao'
    $s = (new App\Services\Consultas\Fontes\SintegraFonte())->normalizar(['_motivo' => 'x'], 'nao_aplicavel');
    expect($s['sintegra']['situacao'])->toBe('INDISPONIVEL');
});
