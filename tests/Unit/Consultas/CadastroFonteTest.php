<?php

use App\Services\Consultas\Fontes\CadastroFonte;

it('normaliza o raw da minhareceita para o shape achatado de prod', function () {
    $raw = json_decode(file_get_contents(base_path('tests/Fixtures/Consultas/minhareceita-19131243000197.json')), true);

    $out = (new CadastroFonte())->normalizar($raw);

    foreach (['razao_social', 'situacao_cadastral', 'situacao_cadastral_codigo', 'endereco', 'qsa', 'cnaes', 'simples_nacional', 'mei', 'consultas_realizadas'] as $k) {
        expect($out)->toHaveKey($k);
    }
    expect($out['razao_social'])->toBe('OPEN KNOWLEDGE BRASIL');
    expect($out['situacao_cadastral'])->toBe('ATIVA');
    expect($out['situacao_cadastral_codigo'])->toBe(2);
    expect($out['endereco'])->toHaveKeys(['uf', 'cep', 'municipio', 'logradouro', 'numero', 'bairro']);
    expect($out['endereco']['uf'])->toBe('SP');
    expect($out['qsa'])->toBeArray()->not->toBeEmpty();
    expect($out['qsa'][0])->toHaveKeys(['nome', 'cpf_cnpj', 'data_entrada', 'qualificacao']);
    expect($out['cnaes'][0]['principal'])->toBeTrue();
    expect($out['consultas_realizadas'])->toContain('situacao_cadastral');
    // derivados do cadastro (plano Validação)
    expect($out)->toHaveKey('regime_tributario')->toHaveKey('historico_simples');
    expect($out['regime_tributario'])->toBeString();
    expect($out['historico_simples'])->toHaveKey('optante');
});

it('deriva regime_tributario real (MEI > Simples > forma RFB > Não informado)', function () {
    $f = new CadastroFonte();
    expect($f->normalizar(['opcao_pelo_mei' => true, 'opcao_pelo_simples' => true])['regime_tributario'])->toBe('MEI');
    expect($f->normalizar(['opcao_pelo_simples' => true])['regime_tributario'])->toBe('Simples Nacional');
    // usa a forma de tributação do ano mais recente publicada pela RFB
    $out = $f->normalizar(['regime_tributario' => [
        ['ano' => 2023, 'forma_de_tributacao' => 'LUCRO PRESUMIDO'],
        ['ano' => 2024, 'forma_de_tributacao' => 'LUCRO REAL'],
    ]]);
    expect($out['regime_tributario'])->toBe('Lucro Real');
    expect($out['regime_tributario_historico'])->toHaveCount(2);
    // sem Simples/MEI e sem histórico → Não informado
    expect($f->normalizar([])['regime_tributario'])->toBe('Não informado');
});

it('fornece regime_tributario e historico_simples (plano Validação)', function () {
    $fornece = (new CadastroFonte())->fornece();
    expect($fornece)->toContain('regime_tributario')->toContain('historico_simples');
});

it('expõe chave/provider/custo da fonte cadastro', function () {
    $f = new CadastroFonte();
    expect($f->chave())->toBe('cadastro');
    expect($f->provider())->toBe('minhareceita');
    expect($f->custoCreditos())->toBe(0);
    expect($f->params(['cnpj' => '19.131.243/0001-97'])['cnpj'])->toBe('19131243000197');
});
