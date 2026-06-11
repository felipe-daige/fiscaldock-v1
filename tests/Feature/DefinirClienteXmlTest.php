<?php

use App\Models\Cliente;
use App\Models\Participante;
use App\Models\User;
use App\Models\XmlImportacao;
use App\Models\XmlNota;
use App\Services\Xml\DefinirClienteXmlService;
use App\Services\Xml\NfeXmlParser;
use App\Services\Xml\XmlNotaImporter;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function seedDecidirDepois(int $userId): XmlImportacao
{
    $imp = XmlImportacao::create([
        'user_id' => $userId, 'tipo_documento' => 'NFE', 'modo_envio' => 'xml',
        'status' => 'concluido', 'iniciado_em' => now(),
    ]);
    // ownerDoc '' + sem cliente cadastrado → sem_dono: ambos os lados viram participante.
    $xml = file_get_contents(base_path('tests/Fixtures/nfe/50240197551165000193550010000248021000214750-nfe.xml'));
    app(XmlNotaImporter::class)->importar(app(NfeXmlParser::class)->parse($xml), '', $imp);

    return $imp;
}

it('candidatos retorna a parte dominante de cada lado', function () {
    $user = User::factory()->create();
    $imp = seedDecidirDepois($user->id);

    $c = app(DefinirClienteXmlService::class)->candidatos($imp);

    expect($c['emit']['documento'])->toBe('97551165000193');
    expect($c['emit']['razao'])->not->toBeNull();
    expect($c['dest']['documento'])->toBe('44373108000600');
});

it('execute(emit) cria o cliente, reclassifica e remove o participante órfão do dono', function () {
    $user = User::factory()->create();
    $imp = seedDecidirDepois($user->id);
    expect(Participante::where('user_id', $user->id)->count())->toBe(2); // provisórios

    $res = app(DefinirClienteXmlService::class)->execute($imp, 'emit');

    $cliente = Cliente::where('user_id', $user->id)->where('documento', '97551165000193')->first();
    expect($cliente)->not->toBeNull();

    $nota = XmlNota::where('importacao_xml_id', $imp->id)->first();
    expect($nota->tipo_nota)->toBe(XmlNota::TIPO_SAIDA);
    expect($nota->emit_cliente_id)->toBe($cliente->id);
    expect($nota->emit_participante_id)->toBeNull();      // dono virou cliente
    expect($nota->dest_participante_id)->not->toBeNull(); // contraparte segue participante
    expect(Participante::find($nota->dest_participante_id)->cliente_id)->toBe($cliente->id);

    $imp->refresh();
    expect($imp->cliente_id)->toBe($cliente->id);
    expect(Participante::where('user_id', $user->id)->count())->toBe(1); // emit órfão removido
    expect($res['participantes_removidos'])->toBe(1);
});

it('execute(emit) cria e associa a contraparte quando o participante ainda não existe', function () {
    $user = User::factory()->create();
    $imp = seedDecidirDepois($user->id);
    $nota = XmlNota::where('importacao_xml_id', $imp->id)->first();
    $destPartId = $nota->dest_participante_id;

    $nota->update(['dest_participante_id' => null]);
    Participante::where('id', $destPartId)->delete();

    app(DefinirClienteXmlService::class)->execute($imp, 'emit');

    $cliente = Cliente::where('user_id', $user->id)->where('documento', '97551165000193')->first();
    $nota->refresh();
    $participante = Participante::find($nota->dest_participante_id);

    expect($cliente)->not->toBeNull();
    expect($participante)->not->toBeNull();
    expect($participante->documento)->toBe('44373108000600');
    expect($participante->cliente_id)->toBe($cliente->id);
});

it('autoDefinir reclassifica quando só o emitente dominante já é cliente', function () {
    $user = User::factory()->create();
    $imp = seedDecidirDepois($user->id);
    $cliente = Cliente::create([
        'user_id' => $user->id,
        'documento' => '97551165000193',
        'tipo_pessoa' => 'PJ',
        'razao_social' => 'Emit Já Cliente',
        'ativo' => true,
        'is_empresa_propria' => false,
    ]);

    $res = app(DefinirClienteXmlService::class)->autoDefinirSeClienteExistente($imp);

    expect($res)->not->toBeNull();
    expect($res['lado'])->toBe('emit');
    expect($res['cliente']->id)->toBe($cliente->id);

    $imp->refresh();
    $nota = XmlNota::where('importacao_xml_id', $imp->id)->first();
    expect($imp->cliente_id)->toBe($cliente->id);
    expect($nota->tipo_nota)->toBe(XmlNota::TIPO_SAIDA);
    expect($nota->emit_cliente_id)->toBe($cliente->id);
    expect(Participante::find($nota->dest_participante_id)->cliente_id)->toBe($cliente->id);
});

it('autoDefinir mantém a escolha manual quando os dois lados já são clientes', function () {
    $user = User::factory()->create();
    $imp = seedDecidirDepois($user->id);

    Cliente::create(['user_id' => $user->id, 'documento' => '97551165000193', 'tipo_pessoa' => 'PJ', 'razao_social' => 'Emit', 'ativo' => true, 'is_empresa_propria' => false]);
    Cliente::create(['user_id' => $user->id, 'documento' => '44373108000600', 'tipo_pessoa' => 'PJ', 'razao_social' => 'Dest', 'ativo' => true, 'is_empresa_propria' => false]);

    expect(app(DefinirClienteXmlService::class)->autoDefinirSeClienteExistente($imp))->toBeNull();
    expect($imp->refresh()->cliente_id)->toBeNull();
});

it('a tela de detalhes auto-vincula cliente existente e não mostra o picker', function () {
    $user = User::factory()->create();
    $imp = seedDecidirDepois($user->id);
    Cliente::create([
        'user_id' => $user->id,
        'documento' => '97551165000193',
        'tipo_pessoa' => 'PJ',
        'razao_social' => 'Emit Já Cliente',
        'ativo' => true,
        'is_empresa_propria' => false,
    ]);

    $resp = $this->actingAs($user)->get("/app/importacao/xml/{$imp->id}");

    $resp->assertOk();
    $resp->assertSee('já é um cliente');
    $resp->assertDontSee('Defina o cliente deste lote');
    expect($imp->refresh()->cliente_id)->not->toBeNull();
});

it('endpoint definir-cliente reclassifica e responde sucesso', function () {
    $user = User::factory()->create();
    $imp = seedDecidirDepois($user->id);

    $this->actingAs($user)
        ->postJson("/app/importacao/xml/{$imp->id}/definir-cliente", ['lado' => 'dest'])
        ->assertOk()->assertJson(['success' => true]);

    $imp->refresh();
    expect($imp->cliente_id)->not->toBeNull();
    expect(XmlNota::where('importacao_xml_id', $imp->id)->first()->tipo_nota)->toBe(XmlNota::TIPO_ENTRADA);
});

it('não permite definir cliente de importação de outro usuário', function () {
    $user = User::factory()->create();
    $outro = User::factory()->create();
    $imp = seedDecidirDepois($outro->id);

    $this->actingAs($user)
        ->postJson("/app/importacao/xml/{$imp->id}/definir-cliente", ['lado' => 'emit'])
        ->assertNotFound();
});
