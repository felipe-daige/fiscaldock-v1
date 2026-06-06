<?php

use App\Models\ConsultaResultado;
use App\Services\Consultas\ResultadoDetalhePresenter;

function resultadoComDados(array $dados): ConsultaResultado
{
    $r = new ConsultaResultado();
    $r->status = ConsultaResultado::STATUS_SUCESSO;
    $r->resultado_dados = $dados;

    return $r;
}

function bloco(array $blocos, string $chave): ?array
{
    foreach ($blocos as $b) {
        if (($b['chave'] ?? null) === $chave) {
            return $b;
        }
    }

    return null;
}

it('monta bloco de dados cadastrais com itens e listas (CNAEs/QSA)', function () {
    $blocos = (new ResultadoDetalhePresenter())->blocos(resultadoComDados([
        'razao_social' => 'ACME LTDA',
        'nome_fantasia' => 'Acme',
        'situacao_cadastral' => 'ATIVA',
        'motivo_situacao_cadastral' => 'SEM MOTIVO',
        'natureza_juridica' => 'LTDA',
        'porte' => 'DEMAIS',
        'capital_social' => 100000,
        'data_inicio_atividade' => '2010-01-01',
        'regime_tributario' => 'Lucro Presumido',
        'endereco' => ['logradouro' => 'Rua X', 'numero' => '10', 'municipio' => 'Dourados', 'uf' => 'MS'],
        'cnaes' => [['codigo' => '6201-5/01', 'descricao' => 'Software', 'principal' => true]],
        'qsa' => [['nome' => 'João', 'qualificacao' => 'Sócio', 'data_entrada' => '2010-01-01']],
    ]));

    $cad = bloco($blocos, 'cadastro');
    expect($cad)->not->toBeNull();
    expect($cad['titulo'])->toBe('Dados cadastrais');

    $labels = array_column($cad['itens'], 'label');
    expect($labels)->toContain('Capital social')->toContain('Endereço')->toContain('Regime tributário');

    $tituloListas = array_column($cad['listas'], 'titulo');
    expect($tituloListas)->toContain('CNAEs')->toContain('Quadro societário (QSA)');
});

it('monta blocos de certidões com código, validade e mensagem oficial', function () {
    $blocos = (new ResultadoDetalhePresenter())->blocos(resultadoComDados([
        'cnd_federal' => [
            'status' => 'Positiva com efeitos de negativa',
            'mensagem' => 'CERTIDÃO POSITIVA COM EFEITOS DE NEGATIVA',
            'certidao_codigo' => 'A3E5.6BE2',
            'emissao_data' => '19/05/2026',
            'data_validade' => '15/11/2026',
            'debitos_rfb' => true,
            'debitos_pgfn' => false,
            'conseguiu_emitir' => true,
        ],
    ]));

    $b = bloco($blocos, 'cnd_federal');
    expect($b)->not->toBeNull();
    expect($b['badge']['label'])->toBe('Regular'); // Positiva com efeitos = regular
    expect($b['mensagem'])->toContain('CERTIDÃO POSITIVA');

    $itens = collect($b['itens'])->keyBy('label');
    expect($itens->get('Certidão nº')['valor'])->toBe('A3E5.6BE2');
    expect($itens->get('Validade')['valor'])->toBe('15/11/2026');
});

it('inclui CND Estadual e SINTEGRA que hoje não aparecem na tabela', function () {
    $blocos = (new ResultadoDetalhePresenter())->blocos(resultadoComDados([
        'cnd_estadual' => ['status' => 'Negativa', 'certidao_codigo' => '573628/2026', 'data_validade' => '04/08/2026'],
        'sintegra' => ['situacao' => 'HABILITADO', 'inscricao_estadual' => '28.368.441-0', 'atividade_economica' => 'C3314702'],
    ]));

    $est = bloco($blocos, 'cnd_estadual');
    expect($est)->not->toBeNull();
    expect($est['badge']['label'])->toBe('Regular');

    $sin = bloco($blocos, 'sintegra');
    expect($sin)->not->toBeNull();
    $itens = collect($sin['itens'])->keyBy('label');
    expect($itens->get('Inscrição estadual')['valor'])->toBe('28.368.441-0');
});

it('monta bloco de sanções (CGU) com bases e classificação regular quando nada consta', function () {
    $blocos = (new ResultadoDetalhePresenter())->blocos(resultadoComDados([
        'cgu_cnc' => [
            'possui_sancao' => false,
            'bases' => [
                ['nome' => 'CEIS', 'situacao' => 'Nada Consta'],
                ['nome' => 'CNEP', 'situacao' => 'Nada Consta'],
            ],
            'comprovante' => 'https://exemplo/comprovante.pdf',
        ],
    ]));

    $b = bloco($blocos, 'cgu_cnc');
    expect($b)->not->toBeNull();
    expect($b['badge']['label'])->toBe('Regular');
    expect($b['comprovante_url'])->toBe('https://exemplo/comprovante.pdf');
    $tituloListas = array_column($b['listas'], 'titulo');
    expect($tituloListas)->toContain('Bases consultadas');
});

it('monta bloco de improbidade com comprovante e badge regular sem condenação', function () {
    $blocos = (new ResultadoDetalhePresenter())->blocos(resultadoComDados([
        'cnj_improbidade' => [
            'possui_condenacao' => false,
            'total_condenacoes' => 0,
            'comprovante' => 'https://exemplo/cnj.pdf',
        ],
    ]));

    $b = bloco($blocos, 'cnj_improbidade');
    expect($b)->not->toBeNull();
    expect($b['badge']['label'])->toBe('Regular');
    expect($b['comprovante_url'])->toBe('https://exemplo/cnj.pdf');
});

it('mostra CND Municipal INDISPONIVEL com mensagem em vez de sumir', function () {
    $blocos = (new ResultadoDetalhePresenter())->blocos(resultadoComDados([
        'cnd_municipal' => ['status' => 'INDISPONIVEL', 'mensagem' => 'CND Municipal não disponível para DOURADOS/MS.'],
    ]));

    $b = bloco($blocos, 'cnd_municipal');
    expect($b)->not->toBeNull();
    expect($b['badge']['label'])->toBe('Indisponível');
    expect($b['mensagem'])->toContain('DOURADOS/MS');
});

it('não cria bloco para fonte ausente', function () {
    $blocos = (new ResultadoDetalhePresenter())->blocos(resultadoComDados([
        'situacao_cadastral' => 'ATIVA',
    ]));

    expect(bloco($blocos, 'cndt'))->toBeNull();
    expect(bloco($blocos, 'cgu_cnc'))->toBeNull();
});

it('ordena cadastro primeiro e mantém ordem canônica das fontes', function () {
    $blocos = (new ResultadoDetalhePresenter())->blocos(resultadoComDados([
        'cnj_improbidade' => ['possui_condenacao' => false],
        'cnd_federal' => ['status' => 'Negativa'],
        'razao_social' => 'ACME',
        'situacao_cadastral' => 'ATIVA',
    ]));

    $chaves = array_column($blocos, 'chave');
    expect($chaves[0])->toBe('cadastro');
    expect(array_search('cnd_federal', $chaves, true))->toBeLessThan(array_search('cnj_improbidade', $chaves, true));
});
