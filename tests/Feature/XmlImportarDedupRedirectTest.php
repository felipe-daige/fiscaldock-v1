<?php

use App\Jobs\ProcessarXmlImportacaoJob;
use App\Models\Cliente;
use App\Models\User;
use App\Models\XmlImportacao;
use App\Models\XmlNota;
use App\Services\Xml\NfeXmlParser;
use App\Services\Xml\XmlNotaImporter;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function fixtureNfeConteudo(string $nome): string
{
    return file_get_contents(base_path('tests/Fixtures/nfe/'.$nome));
}

function clienteXml(int $userId): Cliente
{
    return Cliente::create([
        'user_id' => $userId, 'documento' => '97551165000193',
        'razao_social' => 'HIDRATOP', 'is_empresa_propria' => true,
    ]);
}

function payloadXmlAvulso(array $arquivos, int $clienteId): array
{
    return [
        'tipo_documento' => 'NFE',
        'modo_envio' => 'xml',
        'cliente_id' => $clienteId,
        'tab_id' => 'tab-dedup',
        'arquivos' => $arquivos,
    ];
}

function arquivoBase64(string $nomeFixture, string $nomeUpload): array
{
    return [
        'nome' => $nomeUpload,
        'tipo' => 'text/xml',
        'conteudo_base64' => base64_encode(fixtureNfeConteudo($nomeFixture)),
    ];
}

it('redireciona pra view da nota quando reimporta um XML único já no acervo', function () {
    Bus::fake();
    Storage::fake('local');
    $user = User::factory()->create();

    // Semeia a nota existente importando o mesmo XML uma vez (via importer, sem Job).
    $fixture = '50240197551165000193550010000248021000214750-nfe.xml';
    $imp1 = XmlImportacao::create([
        'user_id' => $user->id, 'tipo_documento' => 'NFE', 'modo_envio' => 'xml',
        'status' => 'concluido', 'iniciado_em' => now(),
    ]);
    $parsed = app(NfeXmlParser::class)->parse(fixtureNfeConteudo($fixture));
    app(XmlNotaImporter::class)->importar($parsed, '', $imp1);

    $notaExistente = XmlNota::where('user_id', $user->id)->firstOrFail();

    // Reimporta o MESMO XML (avulso, 1 arquivo).
    $resp = $this->actingAs($user)->postJson('/app/importacao/xml/importar', payloadXmlAvulso([
        arquivoBase64($fixture, 'nota.xml'),
    ], clienteXml($user->id)->id));

    $resp->assertOk()->assertJson([
        'success' => true,
        'duplicado' => true,
        'nota_id' => $notaExistente->id,
    ]);
    expect($resp->json('nota_url'))->toContain('/app/notas/xml/'.$notaExistente->id);

    // Não cria uma segunda importação nem despacha Job.
    expect(XmlImportacao::where('user_id', $user->id)->count())->toBe(1);
    Bus::assertNotDispatched(ProcessarXmlImportacaoJob::class);
});

it('segue o fluxo normal num lote com vários XMLs distintos', function () {
    Bus::fake();
    Storage::fake('local');
    $user = User::factory()->create();

    $fixtures = collect(glob(base_path('tests/Fixtures/nfe/*-nfe.xml')))
        ->take(2)
        ->map(fn ($p) => basename($p))
        ->values();

    $resp = $this->actingAs($user)->postJson('/app/importacao/xml/importar', payloadXmlAvulso([
        arquivoBase64($fixtures[0], 'a.xml'),
        arquivoBase64($fixtures[1], 'b.xml'),
    ], clienteXml($user->id)->id));

    $resp->assertOk()->assertJson(['success' => true]);
    expect($resp->json('duplicado'))->toBeNull();
    expect($resp->json('importacao_id'))->not->toBeNull();
    Bus::assertDispatched(ProcessarXmlImportacaoJob::class);
});

it('grava o filename do arquivo enviado (1 arquivo = o próprio nome)', function () {
    Bus::fake();
    Storage::fake('local');
    $user = User::factory()->create();

    $this->actingAs($user)->postJson('/app/importacao/xml/importar', payloadXmlAvulso([
        arquivoBase64('50240197551165000193550010000248021000214750-nfe.xml', 'NFe-janeiro.xml'),
    ], clienteXml($user->id)->id))->assertOk();

    expect(XmlImportacao::where('user_id', $user->id)->firstOrFail()->filename)
        ->toBe('NFe-janeiro.xml');
});

it('grava o filename com sufixo de contagem em lote de vários arquivos', function () {
    Bus::fake();
    Storage::fake('local');
    $user = User::factory()->create();

    $fixtures = collect(glob(base_path('tests/Fixtures/nfe/*-nfe.xml')))
        ->take(2)->map(fn ($p) => basename($p))->values();

    $this->actingAs($user)->postJson('/app/importacao/xml/importar', payloadXmlAvulso([
        arquivoBase64($fixtures[0], 'a.xml'),
        arquivoBase64($fixtures[1], 'b.xml'),
    ], clienteXml($user->id)->id))->assertOk();

    expect(XmlImportacao::where('user_id', $user->id)->firstOrFail()->filename)
        ->toBe('a.xml (+1)');
});

it('segue o fluxo normal quando o XML único ainda não está no acervo', function () {
    Bus::fake();
    Storage::fake('local');
    $user = User::factory()->create();

    $resp = $this->actingAs($user)->postJson('/app/importacao/xml/importar', payloadXmlAvulso([
        arquivoBase64('50240197551165000193550010000248021000214750-nfe.xml', 'nova.xml'),
    ], clienteXml($user->id)->id));

    $resp->assertOk()->assertJson(['success' => true]);
    expect($resp->json('duplicado'))->toBeNull();
    expect($resp->json('importacao_id'))->not->toBeNull();
    Bus::assertDispatched(ProcessarXmlImportacaoJob::class);
});
