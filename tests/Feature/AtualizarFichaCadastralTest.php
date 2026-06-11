<?php

use App\Models\Participante;
use App\Models\User;
use App\Services\Consultas\AtualizarFichaCadastralService;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function dadosCadastrais(array $over = []): array
{
    return array_merge([
        'razao_social' => 'ACME LTDA',
        'situacao_cadastral' => 'ATIVA',
        'regime_tributario' => 'Simples Nacional',
        'porte' => 'ME',
        'natureza_juridica' => '206-2',
        'endereco' => [
            'uf' => 'SP', 'cep' => '01001000', 'municipio' => 'SAO PAULO', 'bairro' => 'SE',
            'numero' => '10', 'logradouro' => 'DA SE', 'tipo_logradouro' => 'PRACA', 'codigo_municipio' => '3550308',
        ],
        'cnaes' => [
            ['codigo' => '6201500', 'descricao' => 'Desenvolvimento', 'principal' => true],
            ['codigo' => '6202300', 'descricao' => 'Suporte', 'principal' => false],
        ],
        'qsa' => [['nome' => 'Fulano', 'qualificacao' => 'Sócio']],
    ], $over);
}

it('preenche campos vazios da ficha a partir da consulta', function () {
    $user = User::factory()->create();
    $p = Participante::create(['user_id' => $user->id, 'documento' => '11111111000111']);

    $mudou = app(AtualizarFichaCadastralService::class)->aplicar($p, dadosCadastrais());
    $p->refresh();

    expect($mudou)->toBeTrue();
    expect($p->razao_social)->toBe('ACME LTDA');
    expect($p->situacao_cadastral)->toBe('ATIVA');
    expect($p->uf)->toBe('SP');
    expect($p->municipio)->toBe('SAO PAULO');
    expect($p->cnae_principal)->toBe('6201500');
    expect($p->cnaes_secundarios)->toHaveCount(1);
    expect($p->qsa)->toHaveCount(1);
    expect($p->ultima_consulta_em)->not->toBeNull();
});

it('atualiza voláteis (situação/regime) mesmo preenchidos, mas preserva a razão', function () {
    $user = User::factory()->create();
    $p = Participante::create([
        'user_id' => $user->id, 'documento' => '11111111000111',
        'razao_social' => 'NOME ANTIGO', 'situacao_cadastral' => 'BAIXADA', 'regime_tributario' => 'Lucro Real',
    ]);

    app(AtualizarFichaCadastralService::class)->aplicar($p, dadosCadastrais());
    $p->refresh();

    expect($p->situacao_cadastral)->toBe('ATIVA');           // volátil → sobrescreve
    expect($p->regime_tributario)->toBe('Simples Nacional'); // volátil → sobrescreve
    expect($p->razao_social)->toBe('NOME ANTIGO');           // identidade → preserva
});

it('não altera nada quando a consulta não trouxe bloco cadastral', function () {
    $user = User::factory()->create();
    $p = Participante::create(['user_id' => $user->id, 'documento' => '11111111000111']);

    $mudou = app(AtualizarFichaCadastralService::class)->aplicar($p, ['cnd_federal' => ['status' => 'ok']]);

    expect($mudou)->toBeFalse();
    expect($p->fresh()->razao_social)->toBeNull();
});
