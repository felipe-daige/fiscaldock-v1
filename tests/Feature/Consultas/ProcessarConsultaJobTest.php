<?php

use App\Jobs\ProcessarConsultaJob;
use App\Models\ConsultaResultado;
use App\Services\Consultas\Dto\ResultadoFonte;
use App\Services\Consultas\Persistencia\PersistenciaCnpj;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

it('é batchable (Bus::batch exige o trait Batchable)', function () {
    expect(in_array(Illuminate\Bus\Batchable::class, class_uses(ProcessarConsultaJob::class), true))->toBeTrue();
});

it('consulta cadastro, persiste e posta progresso', function () {
    [$loteId, $participanteId, $userId] = montarLoteParticipante();

    Http::fake(['minhareceita.org/*' => Http::response([
        'razao_social' => 'EMPRESA X', 'descricao_situacao_cadastral' => 'ATIVA',
        'situacao_cadastral' => 2, 'uf' => 'MS', 'municipio' => 'CAMPO GRANDE',
        'cep' => '79005350', 'logradouro' => 'SALGADO FILHO', 'numero' => '2616', 'bairro' => 'JD AMERICA',
        'qsa' => [], 'cnaes_secundarios' => [], 'opcao_pelo_simples' => false, 'opcao_pelo_mei' => false,
    ], 200)]);

    ProcessarConsultaJob::dispatchSync(
        loteId: $loteId,
        alvoTipo: 'participante',
        alvoId: $participanteId,
        userId: $userId,
        tabId: 'tab-test',
        consultasIncluidas: ['situacao_cadastral', 'dados_cadastrais', 'endereco'],
        alvo: ['cnpj' => '00000000000191'],
        etapas: ['Preparando consulta', 'Dados cadastrais'],
    );

    $r = ConsultaResultado::where('consulta_lote_id', $loteId)->first();
    expect($r->resultado_dados['razao_social'])->toBe('EMPRESA X');
    expect($r->resultado_dados['situacao_cadastral'])->toBe('ATIVA');
    expect($r->status)->toBe('sucesso');

    $cache = Cache::get("progresso:{$userId}:tab-test");
    expect($cache)->not->toBeNull();
    expect($cache['total_etapas'])->toBe(2);
    // cadastro é grátis → nada a estornar
    expect((int) Cache::get("consulta_estorno:{$loteId}:participante:{$participanteId}"))->toBe(0);
});

it('acumula estorno no cache quando uma fonte paga falha (fatal)', function () {
    [$loteId, $participanteId, $userId] = montarLoteParticipante();

    Http::fake(['api.infosimples.com/*' => Http::response(['code' => 601, 'code_message' => 'token inválido'], 200)]);

    ProcessarConsultaJob::dispatchSync(
        loteId: $loteId, alvoTipo: 'participante', alvoId: $participanteId, userId: $userId, tabId: 'tab-test',
        consultasIncluidas: ['cnd_federal'], alvo: ['cnpj' => '19131243000197'],
        etapas: ['Preparando consulta', 'Certidões Federais'],
    );

    // cnd_federal custoCreditos (config, default 2) deve ir pro estorno
    expect((int) Cache::get("consulta_estorno:{$loteId}:participante:{$participanteId}"))
        ->toBe((int) config('consultas.fontes.cnd_federal', 2));
});

it('CND Estadual em UF sem cobertura: não chama o provedor e marca INDISPONIVEL', function () {
    [$loteId, $participanteId, $userId] = montarLoteParticipante();
    config()->set('consultas.cnd_estadual.ufs_cobertas', ['SP']);

    Http::fake(); // qualquer chamada falharia a asserção

    ProcessarConsultaJob::dispatchSync(
        loteId: $loteId, alvoTipo: 'participante', alvoId: $participanteId, userId: $userId, tabId: 'tab-test',
        consultasIncluidas: ['cnd_estadual'], alvo: ['cnpj' => '19131243000197', 'uf' => 'AC'],
        etapas: ['Preparando consulta', 'Certidões Estaduais'],
    );

    Http::assertNothingSent();
    $r = ConsultaResultado::where('consulta_lote_id', $loteId)->first();
    expect($r->resultado_dados['cnd_estadual']['status'])->toBe('INDISPONIVEL');
    // não conta como estorno (não é falha)
    expect((int) Cache::get("consulta_estorno:{$loteId}:participante:{$participanteId}"))->toBe(0);
});

it('usa a UF autoritativa do cadastro p/ liberar a CND Estadual (alvo sem UF)', function () {
    [$loteId, $participanteId, $userId] = montarLoteParticipante();
    config()->set('consultas.cnd_estadual.ufs_cobertas', ['SP']);
    config()->set('consultas.infosimples_ativo', true);
    config()->set('consultas.providers.infosimples.token', 'tok');

    Http::fake([
        // cadastro: minhareceita devolve UF=SP (autoritativa)
        'minhareceita.org/*' => Http::response(['razao_social' => 'X', 'descricao_situacao_cadastral' => 'ATIVA', 'situacao_cadastral' => 2, 'uf' => 'SP', 'qsa' => [], 'cnaes_secundarios' => []], 200),
        // CND estadual: sucesso
        'api.infosimples.com/*' => Http::response(['code' => 200, 'data' => [['tipo' => 'Negativa', 'uf' => 'SP']]], 200),
    ]);

    // alvo SEM uf — deve vir do cadastro
    ProcessarConsultaJob::dispatchSync(
        loteId: $loteId, alvoTipo: 'participante', alvoId: $participanteId, userId: $userId, tabId: 'tab-test',
        consultasIncluidas: ['situacao_cadastral', 'cnd_estadual'], alvo: ['cnpj' => '19131243000197'],
        etapas: ['Preparando', 'Cadastrais', 'Estaduais'],
    );

    $r = ConsultaResultado::where('consulta_lote_id', $loteId)->first();
    // CND estadual foi consultada (não ficou INDISPONIVEL) porque a UF veio do cadastro
    expect($r->resultado_dados['cnd_estadual']['status'])->toBe('Negativa');
    Http::assertSent(fn ($req) => str_contains($req->url(), 'sefaz/sp/certidao-debitos'));
});

it('idempotência: fonte paga já persistida não é re-consultada (retry não re-cobra)', function () {
    [$loteId, $participanteId, $userId] = montarLoteParticipante();
    config()->set('consultas.infosimples_ativo', true);
    config()->set('consultas.providers.infosimples.token', 'tok');

    // Simula uma tentativa anterior do job: cnd_federal já consultada e persistida.
    app(PersistenciaCnpj::class)->gravar($loteId, 'participante', $participanteId, new ResultadoFonte(
        'cnd_federal', ['cnd_federal' => ['status' => 'Negativa']], 'sucesso', 2, null,
    ));

    Http::fake(); // qualquer chamada ao InfoSimples falharia a asserção abaixo

    ProcessarConsultaJob::dispatchSync(
        loteId: $loteId, alvoTipo: 'participante', alvoId: $participanteId, userId: $userId, tabId: 'tab-test',
        consultasIncluidas: ['cnd_federal'], alvo: ['cnpj' => '19131243000197'],
        etapas: ['Preparando', 'Federais'],
    );

    Http::assertNothingSent(); // não re-chamou o provedor pago
    $r = ConsultaResultado::where('consulta_lote_id', $loteId)->first();
    expect($r->resultado_dados['cnd_federal']['status'])->toBe('Negativa'); // preservado
    expect((int) Cache::get("consulta_estorno:{$loteId}:participante:{$participanteId}"))->toBe(0);
});

it('processa escopo cliente gravando cliente_id', function () {
    [$loteId, , $userId] = montarLoteParticipante();
    $clienteId = \Illuminate\Support\Facades\DB::table('clientes')->where('user_id', $userId)->value('id');

    Http::fake(['minhareceita.org/*' => Http::response([
        'razao_social' => 'EMPRESA PROPRIA', 'descricao_situacao_cadastral' => 'ATIVA', 'situacao_cadastral' => 2,
        'qsa' => [], 'cnaes_secundarios' => [],
    ], 200)]);

    ProcessarConsultaJob::dispatchSync(
        loteId: $loteId, alvoTipo: 'cliente', alvoId: $clienteId, userId: $userId, tabId: 'tab-test',
        consultasIncluidas: ['situacao_cadastral', 'endereco'], alvo: ['cnpj' => '00000000000100'],
        etapas: ['Preparando consulta', 'Dados cadastrais'],
    );

    $r = ConsultaResultado::where('consulta_lote_id', $loteId)->where('cliente_id', $clienteId)->firstOrFail();
    expect($r->participante_id)->toBeNull();
    expect($r->resultado_dados['razao_social'])->toBe('EMPRESA PROPRIA');
});
