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
use Tests\Fixtures\NfeFixtureMint;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function payloadImportar(int $clienteId): array
{
    $xml = file_get_contents(base_path('tests/Fixtures/nfe/50240197551165000193550010000248021000214750-nfe.xml'));
    return [
        'tipo_documento' => 'NFE', 'modo_envio' => 'xml', 'cliente_id' => $clienteId,
        'tab_id' => 'tab-xyz',
        'arquivos' => [[
            'nome' => 'nota.xml', 'tipo' => 'text/xml',
            'conteudo_base64' => base64_encode($xml),
        ]],
    ];
}

it('exige cliente_id (escolha do cliente é obrigatória no upload)', function () {
    Bus::fake();
    Storage::fake('local');
    $user = User::factory()->create();
    $payload = payloadImportar(1);
    unset($payload['cliente_id']);

    $this->actingAs($user)->postJson('/app/importacao/xml/importar', $payload)
        ->assertStatus(422)->assertJsonValidationErrorFor('cliente_id');

    Bus::assertNotDispatched(ProcessarXmlImportacaoJob::class);
});

it('aceita criar_cliente_lado sem cliente_id e despacha com ownerLado', function () {
    Bus::fake();
    Storage::fake('local');
    $user = User::factory()->create();
    $payload = payloadImportar(1);
    unset($payload['cliente_id']);
    $payload['criar_cliente_lado'] = 'emit';

    $this->actingAs($user)->postJson('/app/importacao/xml/importar', $payload)
        ->assertOk()->assertJson(['success' => true]);

    Bus::assertDispatched(ProcessarXmlImportacaoJob::class, fn ($job) => $job->ownerLado === 'emit' && $job->ownerDoc === '');
});

it('despacha o Job e persiste os XMLs no storage', function () {
    Bus::fake();
    Storage::fake('local');
    $user = User::factory()->create();
    $cliente = Cliente::create(['user_id' => $user->id, 'documento' => '97551165000193', 'razao_social' => 'HIDRATOP', 'is_empresa_propria' => true]);

    $this->actingAs($user)->postJson('/app/importacao/xml/importar', payloadImportar($cliente->id))
        ->assertOk()->assertJson(['success' => true]);

    $imp = XmlImportacao::where('user_id', $user->id)->firstOrFail();
    expect($imp->status)->toBe('processando');
    expect(Storage::disk('local')->files("xml-imports/{$imp->id}"))->not->toBeEmpty();
    Bus::assertDispatched(ProcessarXmlImportacaoJob::class, fn ($job) => $job->ownerDoc === '97551165000193');
});

it('rejeita cliente de outro usuário', function () {
    $user = User::factory()->create();
    $outro = User::factory()->create();
    $cliente = Cliente::create(['user_id' => $outro->id, 'documento' => '11111111000111', 'razao_social' => 'X']);

    $this->actingAs($user)->postJson('/app/importacao/xml/importar', payloadImportar($cliente->id))
        ->assertStatus(422)->assertJsonValidationErrorFor('cliente_id');
});

it('endpoint definir-cliente-documento classifica o grupo e responde sucesso', function () {
    $user = User::factory()->create();
    $imp = XmlImportacao::create(['user_id' => $user->id, 'tipo_documento' => 'NFE', 'modo_envio' => 'xml', 'status' => 'concluido', 'iniciado_em' => now()]);
    foreach ([['11111111000191', '22222222000191', '1'], ['33333333000191', '44444444000191', '2']] as [$e, $d, $n]) {
        $xml = NfeFixtureMint::make($e, $d, str_pad($n, 44, '0'));
        app(XmlNotaImporter::class)->importar(app(NfeXmlParser::class)->parse($xml), '', $imp);
    }

    $this->actingAs($user)
        ->postJson("/app/importacao/xml/{$imp->id}/definir-cliente-documento", ['documento' => '11111111000191', 'lado' => 'emit'])
        ->assertOk()->assertJson(['success' => true]);

    $nota = XmlNota::where('importacao_xml_id', $imp->id)->where('emit_documento', '11111111000191')->first();
    expect($nota->cliente_id)->not->toBeNull();
});

it('show() mostra o card de grupos para lote multi-candidato sem dono', function () {
    $user = User::factory()->create();
    $imp = XmlImportacao::create(['user_id' => $user->id, 'tipo_documento' => 'NFE', 'modo_envio' => 'xml', 'status' => 'concluido', 'iniciado_em' => now()]);
    foreach ([['11111111000191', '22222222000191', '1'], ['33333333000191', '44444444000191', '2']] as [$e, $d, $n]) {
        $xml = NfeFixtureMint::make($e, $d, str_pad($n, 44, '0'));
        app(XmlNotaImporter::class)->importar(app(NfeXmlParser::class)->parse($xml), '', $imp);
    }

    $resp = $this->actingAs($user)->get("/app/importacao/xml/{$imp->id}");

    $resp->assertOk();
    $resp->assertSee('Atribua o cliente de cada parte'); // título do card de grupos
    $resp->assertDontSee('Defina o cliente deste lote');  // picker single-side NÃO aparece
});

it('show() mostra banner Vários quando o lote já resolveu múltiplos clientes', function () {
    $user = User::factory()->create();
    Cliente::create(['user_id' => $user->id, 'documento' => '11111111000191', 'tipo_pessoa' => 'PJ', 'razao_social' => 'A', 'ativo' => true, 'is_empresa_propria' => false]);
    Cliente::create(['user_id' => $user->id, 'documento' => '33333333000191', 'tipo_pessoa' => 'PJ', 'razao_social' => 'B', 'ativo' => true, 'is_empresa_propria' => false]);
    $imp = XmlImportacao::create(['user_id' => $user->id, 'tipo_documento' => 'NFE', 'modo_envio' => 'xml', 'status' => 'concluido', 'iniciado_em' => now()]);
    foreach ([['11111111000191', '22222222000191', '1'], ['33333333000191', '44444444000191', '2']] as [$e, $d, $n]) {
        $xml = NfeFixtureMint::make($e, $d, str_pad($n, 44, '0'));
        app(XmlNotaImporter::class)->importar(app(NfeXmlParser::class)->parse($xml), '', $imp);
    }

    $resp = $this->actingAs($user)->get("/app/importacao/xml/{$imp->id}");

    $resp->assertOk();
    $resp->assertSee('Vários (2 clientes)');
});

it('definir-cliente (whole-lote) é bloqueado num lote multi-cliente', function () {
    $user = User::factory()->create();
    $imp = XmlImportacao::create(['user_id' => $user->id, 'tipo_documento' => 'NFE', 'modo_envio' => 'xml', 'status' => 'concluido', 'iniciado_em' => now()]);
    // Lote misto (emit e dest variam → ehMultiCandidato true), ambos sem cliente cadastrado.
    foreach ([['11111111000191', '22222222000191', '1'], ['33333333000191', '44444444000191', '2']] as [$e, $d, $n]) {
        $xml = NfeFixtureMint::make($e, $d, str_pad($n, 44, '0'));
        app(XmlNotaImporter::class)->importar(app(NfeXmlParser::class)->parse($xml), '', $imp);
    }

    $this->actingAs($user)
        ->postJson("/app/importacao/xml/{$imp->id}/definir-cliente", ['lado' => 'emit'])
        ->assertStatus(409)
        ->assertJson(['success' => false]);

    // nenhuma nota foi reclassificada (continuam sem dono)
    expect(XmlNota::where('importacao_xml_id', $imp->id)->whereNotNull('cliente_id')->count())->toBe(0);
});
