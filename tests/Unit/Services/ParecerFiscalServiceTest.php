<?php

use App\Services\ParecerFiscalService;

function parecerService(): ParecerFiscalService
{
    return new ParecerFiscalService();
}

function chavesDoParecer(array $parecer): array
{
    return array_values(array_map(fn ($i) => $i['chave'], $parecer));
}

it('retorna array vazio para payload vazio', function () {
    expect(parecerService()->gerar([]))->toBe([]);
});

it('destaca regime tributário com ano quando presente', function () {
    $parecer = parecerService()->gerar([
        'regime_tributario' => 'Lucro Real',
        'regime_tributario_ano' => 2024,
    ]);

    expect($parecer)->toHaveCount(1);
    expect($parecer[0]['chave'])->toBe('regime_tributario');
    expect($parecer[0]['severidade'])->toBe('info');
    expect($parecer[0]['titulo'])->toContain('Lucro Real');
    expect($parecer[0]['descricao'])->toContain('2024');
});

it('destaca regime tributário sem ano quando só regime está presente', function () {
    $parecer = parecerService()->gerar([
        'regime_tributario' => 'Presumido',
    ]);

    expect($parecer)->toHaveCount(1);
    expect($parecer[0]['chave'])->toBe('regime_tributario');
    expect($parecer[0]['descricao'])->not->toContain('ano-base');
});

it('ignora regime tributário quando vazio', function () {
    $parecer = parecerService()->gerar(['regime_tributario' => '']);

    expect($parecer)->toBe([]);
});

it('dispara histórico Simples quando ambas as datas estão preenchidas', function () {
    $parecer = parecerService()->gerar([
        'data_opcao_simples' => '2013-11-06',
        'data_exclusao_simples' => '2016-07-31',
    ]);

    $historico = collect($parecer)->firstWhere('chave', 'historico_simples');

    expect($historico)->not->toBeNull();
    expect($historico['severidade'])->toBe('media');
    expect($historico['descricao'])->toContain('06/11/2013');
    expect($historico['descricao'])->toContain('31/07/2016');
});

it('não dispara histórico Simples quando só uma data está preenchida', function () {
    $parecer = parecerService()->gerar([
        'data_opcao_simples' => '2013-11-06',
    ]);

    expect(chavesDoParecer($parecer))->not->toContain('historico_simples');
});

it('não dispara histórico Simples com data inválida', function () {
    $parecer = parecerService()->gerar([
        'data_opcao_simples' => 'nao-e-data',
        'data_exclusao_simples' => 'tambem-nao',
    ]);

    expect(chavesDoParecer($parecer))->not->toContain('historico_simples');
});

it('detecta sócio PJ quando cpf_cnpj tem 14 dígitos unmasked', function () {
    $parecer = parecerService()->gerar([
        'qsa' => [
            ['nome' => 'Pessoa Física Um', 'cpf_cnpj' => '***887398**', 'qualificacao' => 'Sócio'],
            ['nome' => 'Holding Ltda', 'cpf_cnpj' => '33700043000139', 'qualificacao' => 'Sócio'],
        ],
    ]);

    $socio = collect($parecer)->firstWhere('chave', 'socio_pj');

    expect($socio)->not->toBeNull();
    expect($socio['severidade'])->toBe('media');
    expect($socio['descricao'])->toContain('Holding Ltda');
    expect($socio['descricao'])->toContain('1 sócio pessoa jurídica');
});

it('não detecta sócio PJ quando todos os documentos estão mascarados', function () {
    $parecer = parecerService()->gerar([
        'qsa' => [
            ['nome' => 'Fulano', 'cpf_cnpj' => '***887398**', 'qualificacao' => 'Sócio'],
            ['nome' => 'Ciclano', 'cpf_cnpj' => '***059528**', 'qualificacao' => 'Administrador'],
        ],
    ]);

    expect(chavesDoParecer($parecer))->not->toContain('socio_pj');
});

it('pluraliza corretamente quando há múltiplos sócios PJ', function () {
    $parecer = parecerService()->gerar([
        'qsa' => [
            ['nome' => 'Alfa Ltda', 'cpf_cnpj' => '11222333000144', 'qualificacao' => 'Sócio'],
            ['nome' => 'Beta SA', 'cpf_cnpj' => '55666777000188', 'qualificacao' => 'Sócio'],
        ],
    ]);

    $socio = collect($parecer)->firstWhere('chave', 'socio_pj');

    expect($socio['descricao'])->toContain('2 sócios pessoa jurídica');
});

it('dispara divergência CNAE quando mais de 50% dos secundários estão em outras divisões', function () {
    $parecer = parecerService()->gerar([
        'cnaes' => [
            'principal' => ['codigo' => '4930-2/02', 'descricao' => 'Transporte rodoviário'],
            'secundarios' => [
                ['codigo' => '4930-2/03', 'descricao' => '...'],
                ['codigo' => '5211-7/99', 'descricao' => '...'],
                ['codigo' => '5250-8/03', 'descricao' => '...'],
                ['codigo' => '7020-4/00', 'descricao' => '...'],
                ['codigo' => '7711-0/00', 'descricao' => '...'],
            ],
        ],
    ]);

    $divergencia = collect($parecer)->firstWhere('chave', 'divergencia_cnae');

    expect($divergencia)->not->toBeNull();
    expect($divergencia['severidade'])->toBe('baixa');
    expect($divergencia['descricao'])->toContain('Transporte rodoviário');
    expect($divergencia['descricao'])->toContain('4 de 5');
});

it('não dispara divergência CNAE quando todos os secundários estão na mesma divisão do principal', function () {
    $parecer = parecerService()->gerar([
        'cnaes' => [
            'principal' => ['codigo' => '4930-2/02', 'descricao' => 'Transporte rodoviário'],
            'secundarios' => [
                ['codigo' => '4930-2/03', 'descricao' => '...'],
                ['codigo' => '4930-2/04', 'descricao' => '...'],
            ],
        ],
    ]);

    expect(chavesDoParecer($parecer))->not->toContain('divergencia_cnae');
});

it('não dispara divergência CNAE quando exatamente 50% divergem', function () {
    $parecer = parecerService()->gerar([
        'cnaes' => [
            'principal' => ['codigo' => '4930-2/02'],
            'secundarios' => [
                ['codigo' => '4930-2/03'],
                ['codigo' => '5211-7/99'],
            ],
        ],
    ]);

    expect(chavesDoParecer($parecer))->not->toContain('divergencia_cnae');
});

it('dispara alerta de situação inativa para BAIXADA, INAPTA, SUSPENSA, NULA', function () {
    foreach (['BAIXADA', 'INAPTA', 'SUSPENSA', 'NULA'] as $situacao) {
        $parecer = parecerService()->gerar(['situacao_cadastral' => $situacao]);
        $alerta = collect($parecer)->firstWhere('chave', 'situacao_inativa');

        expect($alerta)->not->toBeNull();
        expect($alerta['severidade'])->toBe('alta');
        expect($alerta['descricao'])->toContain($situacao);
    }
});

it('inclui motivo quando presente na situação inativa', function () {
    $parecer = parecerService()->gerar([
        'situacao_cadastral' => 'BAIXADA',
        'motivo_situacao_cadastral' => 'EXTINCAO POR ENCERRAMENTO',
    ]);

    $alerta = collect($parecer)->firstWhere('chave', 'situacao_inativa');

    expect($alerta['descricao'])->toContain('EXTINCAO POR ENCERRAMENTO');
});

it('não dispara situação inativa para ATIVA ou vazia', function () {
    expect(chavesDoParecer(parecerService()->gerar(['situacao_cadastral' => 'ATIVA'])))
        ->not->toContain('situacao_inativa');

    expect(chavesDoParecer(parecerService()->gerar(['situacao_cadastral' => ''])))
        ->not->toContain('situacao_inativa');
});

it('ordena itens por severidade decrescente (alta → info)', function () {
    $parecer = parecerService()->gerar([
        'situacao_cadastral' => 'BAIXADA',
        'regime_tributario' => 'Lucro Real',
        'qsa' => [['nome' => 'Holding', 'cpf_cnpj' => '11222333000144']],
        'data_opcao_simples' => '2013-11-06',
        'data_exclusao_simples' => '2016-07-31',
        'cnaes' => [
            'principal' => ['codigo' => '4930-2/02'],
            'secundarios' => [
                ['codigo' => '5211-7/99'],
                ['codigo' => '7020-4/00'],
            ],
        ],
    ]);

    expect(chavesDoParecer($parecer))->toBe([
        'situacao_inativa',
        'socio_pj',
        'historico_simples',
        'divergencia_cnae',
        'regime_tributario',
    ]);
});

it('cenário realista TE LOG LOGISTICA produz 4 alertas', function () {
    $parecer = parecerService()->gerar([
        'situacao_cadastral' => 'ATIVA',
        'simples_nacional' => false,
        'data_opcao_simples' => '2013-11-06',
        'data_exclusao_simples' => '2016-07-31',
        'regime_tributario' => 'Lucro Real',
        'regime_tributario_ano' => 2024,
        'qsa' => [
            ['nome' => 'NEUZA MARIA', 'cpf_cnpj' => '***887398**', 'qualificacao' => 'Sócio'],
            ['nome' => 'OTAVIO', 'cpf_cnpj' => '***059528**', 'qualificacao' => 'Administrador'],
            ['nome' => 'REI DA ESTRADA TRANSPORTE LTDA', 'cpf_cnpj' => '33700043000139', 'qualificacao' => 'Sócio'],
        ],
        'cnaes' => [
            'principal' => ['codigo' => '4930-2/02', 'descricao' => 'Transporte rodoviário de carga'],
            'secundarios' => [
                ['codigo' => '4930-2/03'],
                ['codigo' => '4930-2/04'],
                ['codigo' => '5211-7/99'],
                ['codigo' => '5250-8/03'],
                ['codigo' => '5250-8/04'],
                ['codigo' => '7020-4/00'],
                ['codigo' => '7711-0/00'],
                ['codigo' => '7731-4/00'],
                ['codigo' => '7739-0/99'],
                ['codigo' => '7820-5/00'],
            ],
        ],
    ]);

    expect(chavesDoParecer($parecer))->toBe([
        'socio_pj',
        'historico_simples',
        'divergencia_cnae',
        'regime_tributario',
    ]);
});

it('gera resumo sem itens apenas contextuais', function () {
    $parecer = parecerService()->gerarResumo([
        'regime_tributario' => 'Lucro Presumido',
    ]);

    expect($parecer)->toBe([]);
});

it('gera resumo com badge curto e tooltip completo para listagens', function () {
    $parecer = parecerService()->gerarResumo([
        'situacao_cadastral' => 'BAIXADA',
        'motivo_situacao_cadastral' => 'EXTINCAO POR ENCERRAMENTO',
        'regime_tributario' => 'Lucro Presumido',
    ]);

    expect($parecer)->toHaveCount(1);
    expect($parecer[0]['chave'])->toBe('situacao_inativa');
    expect($parecer[0]['badge_label'])->toBe('Inativa na RF');
    expect($parecer[0]['tooltip'])->toContain('Empresa inativa na Receita Federal');
    expect($parecer[0]['tooltip'])->toContain('EXTINCAO POR ENCERRAMENTO');
});
