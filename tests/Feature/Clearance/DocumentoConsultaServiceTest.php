<?php

use App\Services\Clearance\Sefaz\DocumentoConsultaService;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config()->set('consultas.providers.infosimples.token', 'tok');
    config()->set('consultas.providers.infosimples.rate_limit_por_segundo', 0); // sem throttle no teste
});

function chaveNfe(): string
{
    return substr_replace(str_repeat('5', 44), '55', 20, 2);
}

it('rejeita chave fora de 44 dígitos', function () {
    expect(fn () => app(DocumentoConsultaService::class)->consultar('123', 'nfe'))
        ->toThrow(InvalidArgumentException::class);
});

it('rejeita modelo incompatível com o tipo', function () {
    $chaveCte = substr_replace(str_repeat('5', 44), '57', 20, 2);
    expect(fn () => app(DocumentoConsultaService::class)->consultar($chaveCte, 'nfe'))
        ->toThrow(InvalidArgumentException::class);
});

it('consulta NF-e por chave e devolve snapshot AUTORIZADA', function () {
    Http::fake(['api.infosimples.com/*' => Http::response(
        json_decode(file_get_contents(base_path('tests/Fixtures/Clearance/nfe_200_autorizada.json')), true), 200
    )]);

    $s = app(DocumentoConsultaService::class)->consultar(chaveNfe(), 'nfe');

    expect($s->status)->toBe('AUTORIZADA');
    expect($s->persistivel)->toBeTrue();
    // InfoSimples receita-federal/nfe espera o argumento 'nfe' (não 'chave'/'chave_acesso').
    Http::assertSent(fn ($req) => str_contains($req->url(), 'receita-federal/nfe') && ($req['nfe'] ?? null) === chaveNfe());
});

it('retry: 1ª resposta retryável + 2ª sucesso → AUTORIZADA (não vira TIMEOUT)', function () {
    $sucesso = json_decode(file_get_contents(base_path('tests/Fixtures/Clearance/nfe_200_autorizada.json')), true);
    Http::fakeSequence('api.infosimples.com/*')
        ->push(['code' => 613, 'code_message' => 'instável', 'header' => ['billable' => false]], 200)
        ->push($sucesso, 200);

    $s = app(DocumentoConsultaService::class)->consultar(chaveNfe(), 'nfe');
    expect($s->status)->toBe('AUTORIZADA');
});

it('retry esgotado → TIMEOUT', function () {
    Http::fake(['api.infosimples.com/*' => Http::response(['code' => 613, 'header' => ['billable' => false]], 200)]);

    $s = app(DocumentoConsultaService::class)->consultar(chaveNfe(), 'nfe');
    expect($s->status)->toBe('TIMEOUT');
    expect($s->estornavel)->toBeTrue();
});
