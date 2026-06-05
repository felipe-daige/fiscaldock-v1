<?php

use App\Services\Consultas\Fontes\CadastroFonte;
use App\Services\Consultas\Fontes\CndFederalFonte;
use App\Services\Consultas\FonteRegistry;

it('resolve fonte por chave', function () {
    $reg = new FonteRegistry([new CadastroFonte()]);
    expect($reg->get('cadastro'))->toBeInstanceOf(CadastroFonte::class);
    expect($reg->get('inexistente'))->toBeNull();
});

it('cobre o plano quando todos os sub-atributos têm fonte', function () {
    $reg = new FonteRegistry([new CadastroFonte()]);
    // plano Gratuito real
    expect($reg->cobre(['situacao_cadastral', 'dados_cadastrais', 'endereco']))->toBeTrue();
    // tem um atributo sem fonte (cnd_federal) → não cobre
    expect($reg->cobre(['situacao_cadastral', 'cnd_federal']))->toBeFalse();
    expect($reg->cobre([]))->toBeFalse();
});

it('não cobre fonte InfoSimples enquanto não estiver pronta (gate de cutover)', function () {
    $reg = new FonteRegistry([new CadastroFonte(), new CndFederalFonte()]);

    config()->set('consultas.infosimples_ativo', false);
    expect($reg->cobre(['cnd_federal']))->toBeFalse(); // não pronta → fica no n8n
    expect($reg->cobre(['situacao_cadastral']))->toBeTrue(); // cadastro sempre pronto

    config()->set('consultas.infosimples_ativo', true);
    config()->set('consultas.providers.infosimples.token', 'tok-123');
    expect($reg->cobre(['cnd_federal']))->toBeTrue(); // ativado → migra
});

it('devolve fontes deduplicadas para os sub-atributos do plano', function () {
    $reg = new FonteRegistry([new CadastroFonte()]);
    // 3 atributos, todos do cadastro → 1 fonte só (deduplicada)
    $fontes = $reg->fontesDe(['situacao_cadastral', 'dados_cadastrais', 'endereco', 'cnd_federal']);
    expect($fontes)->toHaveCount(1);
    expect($fontes[0]->chave())->toBe('cadastro');
});
