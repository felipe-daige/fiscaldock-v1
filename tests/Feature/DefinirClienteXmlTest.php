<?php

use App\Models\Cliente;
use App\Models\Participante;
use App\Models\User;
use App\Models\XmlImportacao;
use App\Models\XmlNota;
use App\Services\Xml\DefinirClienteXmlService;
use App\Services\Xml\NfeXmlParser;
use App\Services\Xml\XmlNotaImporter;
use Tests\Fixtures\NfeFixtureMint;

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

// === Fechamento da feature: simetria dest, ausência de match, idempotência, contadores ===

it('execute(dest) cria o cliente destinatário e associa o emitente como participante', function () {
    $user = User::factory()->create();
    $imp = seedDecidirDepois($user->id);

    $res = app(DefinirClienteXmlService::class)->execute($imp, 'dest');

    $cliente = Cliente::where('user_id', $user->id)->where('documento', '44373108000600')->first();
    expect($cliente)->not->toBeNull();

    $nota = XmlNota::where('importacao_xml_id', $imp->id)->first();
    expect($nota->tipo_nota)->toBe(XmlNota::TIPO_ENTRADA);
    expect($nota->dest_cliente_id)->toBe($cliente->id);
    expect($nota->dest_participante_id)->toBeNull();      // dono virou cliente
    expect($nota->emit_participante_id)->not->toBeNull(); // contraparte segue participante
    expect(Participante::find($nota->emit_participante_id)->cliente_id)->toBe($cliente->id);
    expect($res['participantes_removidos'])->toBe(1);
});

it('autoDefinir retorna null quando nenhum lado é cliente', function () {
    $user = User::factory()->create();
    $imp = seedDecidirDepois($user->id);

    expect(app(DefinirClienteXmlService::class)->autoDefinirSeClienteExistente($imp))->toBeNull();
    expect($imp->refresh()->cliente_id)->toBeNull();
});

it('a tela de detalhes mantém o picker quando nenhum lado é cliente', function () {
    $user = User::factory()->create();
    $imp = seedDecidirDepois($user->id);

    $resp = $this->actingAs($user)->get("/app/importacao/xml/{$imp->id}");

    $resp->assertOk();
    $resp->assertSee('Defina o cliente deste lote');
    $resp->assertDontSee('já é um cliente');
    expect($imp->refresh()->cliente_id)->toBeNull();
});

it('execute é idempotente: rodar duas vezes não duplica participante nem troca cliente', function () {
    $user = User::factory()->create();
    $imp = seedDecidirDepois($user->id);

    $service = app(DefinirClienteXmlService::class);
    $service->execute($imp, 'emit');
    $clienteId = $imp->refresh()->cliente_id;
    $partCount = Participante::where('user_id', $user->id)->count();

    $res2 = $service->execute($imp, 'emit');

    expect($imp->refresh()->cliente_id)->toBe($clienteId);
    expect(Participante::where('user_id', $user->id)->count())->toBe($partCount);
    expect($res2['participantes_removidos'])->toBe(0); // nada órfão na 2ª passada
});

it('GET é idempotente: reabrir após auto-vínculo não duplica participante nem troca cliente', function () {
    $user = User::factory()->create();
    $imp = seedDecidirDepois($user->id);
    Cliente::create([
        'user_id' => $user->id, 'documento' => '97551165000193',
        'tipo_pessoa' => 'PJ', 'razao_social' => 'Emit Já Cliente', 'ativo' => true, 'is_empresa_propria' => false,
    ]);

    $this->actingAs($user)->get("/app/importacao/xml/{$imp->id}")->assertOk();
    $clienteId = $imp->refresh()->cliente_id;
    $partCount = Participante::where('user_id', $user->id)->count();

    $this->actingAs($user)->get("/app/importacao/xml/{$imp->id}")->assertOk();

    expect($imp->refresh()->cliente_id)->toBe($clienteId);
    expect(Participante::where('user_id', $user->id)->count())->toBe($partCount);
});

it('execute não sobrescreve o cliente_id de uma contraparte já vinculada a outro cliente', function () {
    $user = User::factory()->create();
    $imp = seedDecidirDepois($user->id);
    $nota = XmlNota::where('importacao_xml_id', $imp->id)->first();

    // A contraparte (dest) já pertence ao Cliente A antes de decidir o dono do lote.
    $clienteA = Cliente::create([
        'user_id' => $user->id, 'documento' => '11111111000191',
        'tipo_pessoa' => 'PJ', 'razao_social' => 'CLIENTE A', 'ativo' => true, 'is_empresa_propria' => false,
    ]);
    Participante::where('id', $nota->dest_participante_id)->update(['cliente_id' => $clienteA->id]);

    app(DefinirClienteXmlService::class)->execute($imp, 'emit');

    // Vínculo original preservado — o reclassify não rouba a contraparte pro novo cliente.
    expect(Participante::find($nota->dest_participante_id)->cliente_id)->toBe($clienteA->id);
});

it('após reclassificar, participante_ids da importação contém só a contraparte', function () {
    $user = User::factory()->create();
    $imp = seedDecidirDepois($user->id);
    $nota = XmlNota::where('importacao_xml_id', $imp->id)->first();
    $emitProvisorio = $nota->emit_participante_id;
    $destProvisorio = $nota->dest_participante_id;
    expect($emitProvisorio)->not->toBeNull();

    app(DefinirClienteXmlService::class)->execute($imp, 'emit');

    $imp->refresh();
    expect($imp->participante_ids)->toContain($destProvisorio);
    expect($imp->participante_ids)->not->toContain($emitProvisorio);
});

// === Cap de clientes (Free = empresa própria + 1) nos fluxos XML que nascem cliente ===

it('execute(emit) NÃO cria cliente quando o cap do Free está cheio', function () {
    $user = User::factory()->create();
    Cliente::create(['user_id' => $user->id, 'documento' => '10000000000191', 'tipo_pessoa' => 'PJ', 'razao_social' => 'Propria', 'ativo' => true, 'is_empresa_propria' => true]);
    Cliente::create(['user_id' => $user->id, 'documento' => '22222222000191', 'tipo_pessoa' => 'PJ', 'razao_social' => 'Outro', 'ativo' => true, 'is_empresa_propria' => false]);

    $imp = seedDecidirDepois($user->id);
    app(DefinirClienteXmlService::class)->execute($imp, 'emit');

    expect(Cliente::where('user_id', $user->id)->where('documento', '97551165000193')->exists())->toBeFalse();
    $nota = XmlNota::where('importacao_xml_id', $imp->id)->first();
    expect($nota->emit_cliente_id)->toBeNull();
    expect($nota->cliente_id)->toBeNull();
});

it('execute(emit) cria normalmente sob trial mesmo com clientes além do cap Free', function () {
    $user = User::factory()->trialAtivo()->create();
    Cliente::create(['user_id' => $user->id, 'documento' => '10000000000191', 'tipo_pessoa' => 'PJ', 'razao_social' => 'Propria', 'ativo' => true, 'is_empresa_propria' => true]);
    Cliente::create(['user_id' => $user->id, 'documento' => '22222222000191', 'tipo_pessoa' => 'PJ', 'razao_social' => 'Outro', 'ativo' => true, 'is_empresa_propria' => false]);

    $imp = seedDecidirDepois($user->id);
    app(DefinirClienteXmlService::class)->execute($imp, 'emit');

    expect(Cliente::where('user_id', $user->id)->where('documento', '97551165000193')->exists())->toBeTrue();
});

it('import criar_cliente_lado=emit NÃO cria cliente quando o cap do Free está cheio', function () {
    $user = User::factory()->create();
    Cliente::create(['user_id' => $user->id, 'documento' => '10000000000191', 'tipo_pessoa' => 'PJ', 'razao_social' => 'Propria', 'ativo' => true, 'is_empresa_propria' => true]);
    Cliente::create(['user_id' => $user->id, 'documento' => '22222222000191', 'tipo_pessoa' => 'PJ', 'razao_social' => 'Outro', 'ativo' => true, 'is_empresa_propria' => false]);

    $imp = XmlImportacao::create(['user_id' => $user->id, 'tipo_documento' => 'NFE', 'modo_envio' => 'xml', 'status' => 'concluido', 'iniciado_em' => now()]);
    $xml = file_get_contents(base_path('tests/Fixtures/nfe/50240197551165000193550010000248021000214750-nfe.xml'));
    // 4º arg = ownerLado ('emit' = criar cliente pelo lado emitente)
    app(XmlNotaImporter::class)->importar(app(NfeXmlParser::class)->parse($xml), '', $imp, 'emit');

    expect(Cliente::where('user_id', $user->id)->where('documento', '97551165000193')->exists())->toBeFalse();
    $nota = XmlNota::where('importacao_xml_id', $imp->id)->first();
    expect($nota)->not->toBeNull();
    expect($nota->emit_cliente_id)->toBeNull();
});

it('clientesResolvidos conta donos distintos das notas do lote', function () {
    $user = User::factory()->create();
    // 2 clientes cadastrados (cada um emitente de 1 nota → 2 donos) + 1 nota sem dono.
    Cliente::create(['user_id' => $user->id, 'documento' => '11111111000191', 'tipo_pessoa' => 'PJ', 'razao_social' => 'A', 'ativo' => true, 'is_empresa_propria' => false]);
    Cliente::create(['user_id' => $user->id, 'documento' => '33333333000191', 'tipo_pessoa' => 'PJ', 'razao_social' => 'B', 'ativo' => true, 'is_empresa_propria' => false]);

    $imp = XmlImportacao::create(['user_id' => $user->id, 'tipo_documento' => 'NFE', 'modo_envio' => 'xml', 'status' => 'concluido', 'iniciado_em' => now()]);
    foreach ([
        ['11111111000191', '22222222000191', '1'], // dono = cliente A
        ['33333333000191', '44444444000191', '2'], // dono = cliente B
        ['55555555000191', '66666666000191', '3'], // nenhum lado cadastrado → sem dono (cliente_id null)
    ] as [$emit, $dest, $n]) {
        $xml = NfeFixtureMint::make($emit, $dest, str_pad($n, 44, '0'));
        app(XmlNotaImporter::class)->importar(app(NfeXmlParser::class)->parse($xml), '', $imp);
    }

    expect($imp->clientesResolvidos())->toBe(2); // a nota sem dono (null) não conta
});

// === Guard multi-candidato ===

// Helper local: importa N notas via decidir_depois (ownerDoc='') num lote.
function seedLoteMisto(int $userId, array $notas): XmlImportacao
{
    $imp = XmlImportacao::create(['user_id' => $userId, 'tipo_documento' => 'NFE', 'modo_envio' => 'xml', 'status' => 'concluido', 'iniciado_em' => now()]);
    foreach ($notas as $n) {
        $xml = NfeFixtureMint::make($n['emit'], $n['dest'], $n['chave']);
        app(XmlNotaImporter::class)->importar(app(NfeXmlParser::class)->parse($xml), '', $imp);
    }

    return $imp;
}

it('ehMultiCandidato é true quando ambos os lados têm múltiplos documentos', function () {
    $user = User::factory()->create();
    $imp = seedLoteMisto($user->id, [
        ['emit' => '11111111000191', 'dest' => '22222222000191', 'chave' => str_pad('1', 44, '0')],
        ['emit' => '33333333000191', 'dest' => '44444444000191', 'chave' => str_pad('2', 44, '0')],
    ]);

    expect(app(DefinirClienteXmlService::class)->ehMultiCandidato($imp))->toBeTrue();
});

it('ehMultiCandidato é false para 1 vendedor → N compradores (single-client)', function () {
    $user = User::factory()->create();
    $imp = seedLoteMisto($user->id, [
        ['emit' => '11111111000191', 'dest' => '22222222000191', 'chave' => str_pad('1', 44, '0')],
        ['emit' => '11111111000191', 'dest' => '44444444000191', 'chave' => str_pad('2', 44, '0')],
    ]);

    expect(app(DefinirClienteXmlService::class)->ehMultiCandidato($imp))->toBeFalse();
});

it('autoDefinir não reclassifica um lote multi-candidato', function () {
    $user = User::factory()->create();
    // Cliente A é emitente de UMA das notas; mesmo assim o lote é multi (emit e dest variam).
    Cliente::create(['user_id' => $user->id, 'documento' => '11111111000191', 'tipo_pessoa' => 'PJ', 'razao_social' => 'A', 'ativo' => true, 'is_empresa_propria' => false]);
    $imp = seedLoteMisto($user->id, [
        ['emit' => '11111111000191', 'dest' => '22222222000191', 'chave' => str_pad('1', 44, '0')],
        ['emit' => '33333333000191', 'dest' => '44444444000191', 'chave' => str_pad('2', 44, '0')],
    ]);

    expect(app(DefinirClienteXmlService::class)->autoDefinirSeClienteExistente($imp))->toBeNull();
});

// === Atribuição por documento (gruposPorDocumento + executePorDocumento) ===

it('gruposPorDocumento lista só documentos das notas sem dono, por lado', function () {
    $user = User::factory()->create();
    // 2 notas sem dono (clientes não cadastrados), emitentes distintos.
    $imp = seedLoteMisto($user->id, [
        ['emit' => '11111111000191', 'dest' => '22222222000191', 'chave' => str_pad('1', 44, '0')],
        ['emit' => '33333333000191', 'dest' => '44444444000191', 'chave' => str_pad('2', 44, '0')],
    ]);

    $grupos = app(DefinirClienteXmlService::class)->gruposPorDocumento($imp);

    expect(collect($grupos['emit'])->pluck('documento')->sort()->values()->all())
        ->toBe(['11111111000191', '33333333000191']);
    expect($grupos['emit'][0])->toHaveKeys(['documento', 'razao', 'qtd']);
});

it('executePorDocumento classifica só as notas do documento naquele lado', function () {
    $user = User::factory()->create();
    $imp = seedLoteMisto($user->id, [
        ['emit' => '11111111000191', 'dest' => '22222222000191', 'chave' => str_pad('1', 44, '0')],
        ['emit' => '33333333000191', 'dest' => '44444444000191', 'chave' => str_pad('2', 44, '0')],
    ]);

    $res = app(DefinirClienteXmlService::class)->executePorDocumento($imp, '11111111000191', 'emit');

    $clienteA = Cliente::where('user_id', $user->id)->where('documento', '11111111000191')->first();
    expect($clienteA)->not->toBeNull();

    $notaA = XmlNota::where('importacao_xml_id', $imp->id)->where('emit_documento', '11111111000191')->first();
    $notaB = XmlNota::where('importacao_xml_id', $imp->id)->where('emit_documento', '33333333000191')->first();

    expect($notaA->tipo_nota)->toBe(XmlNota::TIPO_SAIDA);
    expect($notaA->emit_cliente_id)->toBe($clienteA->id);
    expect($notaA->cliente_id)->toBe($clienteA->id);
    // A nota do OUTRO documento permanece intocada (ainda sem dono).
    expect($notaB->cliente_id)->toBeNull();
    expect($res['notas'])->toBe(1);
});

it('executePorDocumento deixa o header como Vários enquanto resta mais de um dono', function () {
    // lote multi-cliente exige conta sem cap (Free = própria + 1); trial libera.
    $user = User::factory()->trialAtivo()->create();
    $imp = seedLoteMisto($user->id, [
        ['emit' => '11111111000191', 'dest' => '22222222000191', 'chave' => str_pad('1', 44, '0')],
        ['emit' => '33333333000191', 'dest' => '44444444000191', 'chave' => str_pad('2', 44, '0')],
    ]);

    app(DefinirClienteXmlService::class)->executePorDocumento($imp, '11111111000191', 'emit');
    expect($imp->refresh()->cliente_id)->toBeNull(); // ainda há a nota B sem dono

    app(DefinirClienteXmlService::class)->executePorDocumento($imp, '33333333000191', 'emit');
    // agora 2 donos resolvidos → continua "Vários" (null)
    expect($imp->refresh()->cliente_id)->toBeNull();
    expect($imp->clientesResolvidos())->toBe(2);
});

it('executePorDocumento é idempotente (rodar 2x não duplica nem troca)', function () {
    $user = User::factory()->create();
    $imp = seedLoteMisto($user->id, [
        ['emit' => '11111111000191', 'dest' => '22222222000191', 'chave' => str_pad('1', 44, '0')],
    ]);

    $svc = app(DefinirClienteXmlService::class);
    $svc->executePorDocumento($imp, '11111111000191', 'emit');
    $clienteA = Cliente::where('user_id', $user->id)->where('documento', '11111111000191')->first();
    expect($imp->refresh()->cliente_id)->toBe($clienteA->id); // lote 1-nota totalmente resolvido → header setado
    $partCount = Participante::where('user_id', $user->id)->count();

    $res2 = $svc->executePorDocumento($imp, '11111111000191', 'emit');
    expect(Participante::where('user_id', $user->id)->count())->toBe($partCount);
    expect($res2['notas'])->toBe(0); // nada mais sem dono nesse doc
});
