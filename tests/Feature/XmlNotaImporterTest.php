<?php

use App\Models\Cliente;
use App\Models\Participante;
use App\Models\User;
use App\Models\XmlImportacao;
use App\Models\XmlNota;
use App\Services\Xml\NfeXmlParser;
use App\Services\Xml\XmlNotaImporter;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function parsedFixture(): array
{
    $xml = file_get_contents(base_path('tests/Fixtures/nfe/50240197551165000193550010000248021000214750-nfe.xml'));

    return (new NfeXmlParser)->parse($xml);
}

function novaImportacaoXml(User $u, ?int $clienteId = null): XmlImportacao
{
    return XmlImportacao::create([
        'user_id' => $u->id, 'cliente_id' => $clienteId,
        'tipo_documento' => 'NFE', 'modo_envio' => 'zip',
        'status' => 'processando', 'iniciado_em' => now(),
    ]);
}

it('importa nota nova, cria itens e classifica saída quando o dono é o emitente', function () {
    $user = User::factory()->create();
    $imp = novaImportacaoXml($user);
    $parsed = parsedFixture(); // emit=97551165000193

    $status = app(XmlNotaImporter::class)->importar($parsed, '97551165000193', $imp);

    expect($status)->toBe('novo');
    $nota = XmlNota::where('user_id', $user->id)->first();
    expect($nota->tipo_nota)->toBe(XmlNota::TIPO_SAIDA);
    expect($nota->itens()->count())->toBe(7);
    // Dono = emitente → emitente é o cliente (não participante); só a contraparte (dest).
    expect($nota->emit_participante_id)->toBeNull();
    expect($nota->dest_participante_id)->not->toBeNull();
});

it('só a contraparte vira participante — o dono nunca', function () {
    $user = User::factory()->create();

    // Saída (dono = emitente): emit não é participante, dest é.
    $impSaida = novaImportacaoXml($user);
    app(XmlNotaImporter::class)->importar(parsedFixture(), '97551165000193', $impSaida);
    $saida = XmlNota::where('importacao_xml_id', $impSaida->id)->first();
    expect($saida->emit_participante_id)->toBeNull();
    expect($saida->dest_participante_id)->not->toBeNull();

    // Só 1 participante criado (a contraparte/dest).
    expect(Participante::where('user_id', $user->id)->count())->toBe(1);
});

it('entrada: o destinatário (dono) não vira participante, só o emitente', function () {
    $user = User::factory()->create();
    $imp = novaImportacaoXml($user);

    // Dono = destinatário (44373108000600) → entrada.
    app(XmlNotaImporter::class)->importar(parsedFixture(), '44373108000600', $imp);
    $nota = XmlNota::where('user_id', $user->id)->first();

    expect($nota->dest_participante_id)->toBeNull();
    expect($nota->emit_participante_id)->not->toBeNull();
});

it('criar pelo lado: cria o Cliente do lado escolhido e marca a contraparte como participante', function () {
    $user = User::factory()->create();
    $imp = novaImportacaoXml($user);

    // ownerLado='emit' (sem cliente pré-cadastrado) → emitente vira Cliente novo.
    $status = app(XmlNotaImporter::class)->importar(parsedFixture(), '', $imp, 'emit');

    expect($status)->toBe('novo');
    $cliente = Cliente::where('user_id', $user->id)->where('documento', '97551165000193')->first();
    expect($cliente)->not->toBeNull();
    expect($cliente->razao_social)->not->toBeNull();

    $nota = XmlNota::where('user_id', $user->id)->first();
    expect($nota->tipo_nota)->toBe(XmlNota::TIPO_SAIDA);
    expect($nota->emit_cliente_id)->toBe($cliente->id);
    expect($nota->emit_participante_id)->toBeNull();      // dono (cliente) não é participante
    expect($nota->dest_participante_id)->not->toBeNull(); // contraparte é participante
    expect(Participante::where('user_id', $user->id)->count())->toBe(1);
});

it('classifica entrada quando o dono é o destinatário', function () {
    $user = User::factory()->create();
    $imp = novaImportacaoXml($user);

    app(XmlNotaImporter::class)->importar(parsedFixture(), '44373108000600', $imp);

    expect(XmlNota::where('user_id', $user->id)->first()->tipo_nota)->toBe(XmlNota::TIPO_ENTRADA);
});

it('faz dedup por chave: segunda importação retorna duplicado e não duplica itens', function () {
    $user = User::factory()->create();
    $imp1 = novaImportacaoXml($user);
    app(XmlNotaImporter::class)->importar(parsedFixture(), '97551165000193', $imp1);

    $imp2 = novaImportacaoXml($user);
    $status = app(XmlNotaImporter::class)->importar(parsedFixture(), '97551165000193', $imp2);

    expect($status)->toBe('duplicado');
    expect(XmlNota::where('user_id', $user->id)->count())->toBe(1);
    expect(XmlNota::where('user_id', $user->id)->first()->itens()->count())->toBe(7);
});

it('backfilla protNFe quando a nota existia sem protocolo', function () {
    $user = User::factory()->create();
    $imp1 = novaImportacaoXml($user);
    $semProt = parsedFixture();
    $semProt['header']['protocolo_autorizacao'] = null;
    $semProt['header']['status_autorizacao'] = null;
    app(XmlNotaImporter::class)->importar($semProt, '97551165000193', $imp1);

    $imp2 = novaImportacaoXml($user);
    $status = app(XmlNotaImporter::class)->importar(parsedFixture(), '97551165000193', $imp2);

    expect($status)->toBe('duplicado_atualizado');
    expect(XmlNota::where('user_id', $user->id)->first()->status_autorizacao)->toBe('100');
});

it('marca sem_dono quando o owner não aparece em nenhum lado', function () {
    $user = User::factory()->create();
    $imp = novaImportacaoXml($user);

    $status = app(XmlNotaImporter::class)->importar(parsedFixture(), '99999999999999', $imp);

    expect($status)->toBe('sem_dono');
    $nota = XmlNota::where('user_id', $user->id)->first();
    expect($nota->payload['_dono_ausente'])->toBeTrue();
});

it('liga emit_cliente_id quando o documento casa com cliente existente', function () {
    $user = User::factory()->create();
    $cliente = Cliente::create([
        'user_id' => $user->id, 'documento' => '97551165000193',
        'razao_social' => 'HIDRATOP', 'is_empresa_propria' => true,
    ]);
    $imp = novaImportacaoXml($user, $cliente->id);

    app(XmlNotaImporter::class)->importar(parsedFixture(), '97551165000193', $imp);

    expect(XmlNota::where('user_id', $user->id)->first()->emit_cliente_id)->toBe($cliente->id);
});

it('cliente selecionado reutiliza participante existente da contraparte e associa ao cliente', function () {
    $user = User::factory()->create();
    $cliente = Cliente::create([
        'user_id' => $user->id,
        'documento' => '97551165000193',
        'tipo_pessoa' => 'PJ',
        'razao_social' => 'HIDRATOP',
        'ativo' => true,
        'is_empresa_propria' => true,
    ]);
    $participante = Participante::create([
        'user_id' => $user->id,
        'documento' => '44373108000600',
        'razao_social' => 'COCAL COMERCIO EXISTENTE',
        'origem_tipo' => 'manual',
    ]);
    $imp = novaImportacaoXml($user, $cliente->id);

    app(XmlNotaImporter::class)->importar(parsedFixture(), '97551165000193', $imp);

    $nota = XmlNota::where('user_id', $user->id)->first();
    expect($nota->dest_participante_id)->toBe($participante->id);
    expect($nota->cliente_id)->toBe($cliente->id);
    expect($participante->refresh()->cliente_id)->toBe($cliente->id);
});

it('cliente selecionado cria participante novo da contraparte associado ao cliente', function () {
    $user = User::factory()->create();
    $cliente = Cliente::create([
        'user_id' => $user->id,
        'documento' => '97551165000193',
        'tipo_pessoa' => 'PJ',
        'razao_social' => 'HIDRATOP',
        'ativo' => true,
        'is_empresa_propria' => false,
    ]);
    $imp = novaImportacaoXml($user, $cliente->id);

    app(XmlNotaImporter::class)->importar(parsedFixture(), '97551165000193', $imp);

    $nota = XmlNota::where('user_id', $user->id)->first();
    $participante = Participante::find($nota->dest_participante_id);
    expect($participante)->not->toBeNull();
    expect($participante->documento)->toBe('44373108000600');
    expect($participante->cliente_id)->toBe($cliente->id);
});

it('auto com empresa propria no emitente cria contraparte associada ao cliente proprio', function () {
    $user = User::factory()->create();
    $cliente = Cliente::create([
        'user_id' => $user->id,
        'documento' => '97551165000193',
        'tipo_pessoa' => 'PJ',
        'razao_social' => 'HIDRATOP',
        'ativo' => true,
        'is_empresa_propria' => true,
    ]);
    $imp = novaImportacaoXml($user);

    app(XmlNotaImporter::class)->importar(parsedFixture(), null, $imp);

    $nota = XmlNota::where('user_id', $user->id)->first();
    $participante = Participante::find($nota->dest_participante_id);
    expect($nota->cliente_id)->toBe($cliente->id);
    expect($nota->emit_cliente_id)->toBe($cliente->id);
    expect($participante->cliente_id)->toBe($cliente->id);
});

// --- Modo AUTO (ownerDoc nulo): infere o dono pelo cliente que casa ---

it('auto: empresa própria no emitente classifica como saída', function () {
    $user = User::factory()->create();
    Cliente::create(['user_id' => $user->id, 'documento' => '97551165000193', 'razao_social' => 'HIDRATOP', 'is_empresa_propria' => true]);
    $imp = novaImportacaoXml($user);

    $status = app(XmlNotaImporter::class)->importar(parsedFixture(), null, $imp);

    expect($status)->toBe('novo');
    expect(XmlNota::where('user_id', $user->id)->first()->tipo_nota)->toBe(XmlNota::TIPO_SAIDA);
});

it('auto: empresa própria no destinatário classifica como entrada', function () {
    $user = User::factory()->create();
    Cliente::create(['user_id' => $user->id, 'documento' => '44373108000600', 'razao_social' => 'COCAL', 'is_empresa_propria' => true]);
    $imp = novaImportacaoXml($user);

    app(XmlNotaImporter::class)->importar(parsedFixture(), null, $imp);

    expect(XmlNota::where('user_id', $user->id)->first()->tipo_nota)->toBe(XmlNota::TIPO_ENTRADA);
});

it('auto: cliente comum (não própria) só no emitente classifica como saída', function () {
    $user = User::factory()->create();
    Cliente::create(['user_id' => $user->id, 'documento' => '97551165000193', 'razao_social' => 'HIDRATOP', 'is_empresa_propria' => false]);
    $imp = novaImportacaoXml($user);

    app(XmlNotaImporter::class)->importar(parsedFixture(), null, $imp);

    expect(XmlNota::where('user_id', $user->id)->first()->tipo_nota)->toBe(XmlNota::TIPO_SAIDA);
});

it('auto: nenhum lado cadastrado marca sem_dono e flag _dono_ausente', function () {
    $user = User::factory()->create();
    $imp = novaImportacaoXml($user);

    $status = app(XmlNotaImporter::class)->importar(parsedFixture(), null, $imp);

    expect($status)->toBe('sem_dono');
    expect(XmlNota::where('user_id', $user->id)->first()->payload['_dono_ausente'])->toBeTrue();
});

it('auto: empresa própria vence quando os dois lados são clientes', function () {
    $user = User::factory()->create();
    Cliente::create(['user_id' => $user->id, 'documento' => '97551165000193', 'razao_social' => 'HIDRATOP', 'is_empresa_propria' => false]);
    Cliente::create(['user_id' => $user->id, 'documento' => '44373108000600', 'razao_social' => 'COCAL', 'is_empresa_propria' => true]);
    $imp = novaImportacaoXml($user);

    // dest (COCAL) é a empresa própria → entrada
    app(XmlNotaImporter::class)->importar(parsedFixture(), null, $imp);

    expect(XmlNota::where('user_id', $user->id)->first()->tipo_nota)->toBe(XmlNota::TIPO_ENTRADA);
});

// === Fechamento da feature: associação participante↔cliente nos cenários restantes ===

it('cliente selecionado no destinatário: entrada e emitente vira participante associado ao cliente', function () {
    $user = User::factory()->create();
    $cliente = Cliente::create([
        'user_id' => $user->id, 'documento' => '44373108000600',
        'tipo_pessoa' => 'PJ', 'razao_social' => 'COCAL DEST', 'ativo' => true, 'is_empresa_propria' => false,
    ]);
    $imp = novaImportacaoXml($user, $cliente->id);

    // ownerDoc = documento do destinatário (o cliente selecionado é o dest).
    app(XmlNotaImporter::class)->importar(parsedFixture(), '44373108000600', $imp);

    $nota = XmlNota::where('user_id', $user->id)->first();
    expect($nota->tipo_nota)->toBe(XmlNota::TIPO_ENTRADA);
    expect($nota->dest_cliente_id)->toBe($cliente->id);
    expect($nota->dest_participante_id)->toBeNull();      // dono (cliente) não é participante
    expect($nota->emit_participante_id)->not->toBeNull(); // contraparte (emitente) é participante
    expect(Participante::find($nota->emit_participante_id)->cliente_id)->toBe($cliente->id);
});

it('criar pelo lado dest: cria o Cliente destinatário e associa o emitente como participante', function () {
    $user = User::factory()->create();
    $imp = novaImportacaoXml($user);

    $status = app(XmlNotaImporter::class)->importar(parsedFixture(), '', $imp, 'dest');

    expect($status)->toBe('novo');
    $cliente = Cliente::where('user_id', $user->id)->where('documento', '44373108000600')->first();
    expect($cliente)->not->toBeNull();

    $nota = XmlNota::where('user_id', $user->id)->first();
    expect($nota->tipo_nota)->toBe(XmlNota::TIPO_ENTRADA);
    expect($nota->dest_cliente_id)->toBe($cliente->id);
    expect($nota->dest_participante_id)->toBeNull();
    expect($nota->emit_participante_id)->not->toBeNull();
    expect(Participante::find($nota->emit_participante_id)->cliente_id)->toBe($cliente->id);
    expect(Participante::where('user_id', $user->id)->count())->toBe(1);
});

it('criar pelo lado: contraparte preexistente sem cliente_id passa a ter cliente_id', function () {
    $user = User::factory()->create();
    $participante = Participante::create([
        'user_id' => $user->id, 'documento' => '44373108000600',
        'razao_social' => 'COCAL ANTIGO', 'origem_tipo' => 'manual', // sem cliente_id
    ]);
    $imp = novaImportacaoXml($user);

    app(XmlNotaImporter::class)->importar(parsedFixture(), '', $imp, 'emit');

    $cliente = Cliente::where('user_id', $user->id)->where('documento', '97551165000193')->first();
    expect($participante->refresh()->cliente_id)->toBe($cliente->id);
    expect(Participante::where('user_id', $user->id)->count())->toBe(1); // reutilizou, não duplicou
});

it('não sobrescreve campos cadastrais do n8n na contraparte já existente', function () {
    $user = User::factory()->create();
    $cliente = Cliente::create([
        'user_id' => $user->id, 'documento' => '97551165000193',
        'tipo_pessoa' => 'PJ', 'razao_social' => 'HIDRATOP', 'ativo' => true, 'is_empresa_propria' => true,
    ]);
    $participante = Participante::create([
        'user_id' => $user->id, 'documento' => '44373108000600',
        'razao_social' => 'COCAL CONSULTADO', 'origem_tipo' => 'consulta',
        'situacao_cadastral' => 'ATIVA', 'regime_tributario' => 'Simples Nacional',
        'ultima_consulta_em' => now()->subDay(),
    ]);
    $imp = novaImportacaoXml($user, $cliente->id);

    app(XmlNotaImporter::class)->importar(parsedFixture(), '97551165000193', $imp);

    $participante->refresh();
    expect($participante->situacao_cadastral)->toBe('ATIVA');
    expect($participante->regime_tributario)->toBe('Simples Nacional');
    expect($participante->ultima_consulta_em)->not->toBeNull();
    expect($participante->cliente_id)->toBe($cliente->id); // só o vínculo é tocado
});

it('auto: empresa própria no destinatário associa o emitente ao cliente próprio', function () {
    $user = User::factory()->create();
    $cliente = Cliente::create([
        'user_id' => $user->id, 'documento' => '44373108000600',
        'tipo_pessoa' => 'PJ', 'razao_social' => 'COCAL', 'ativo' => true, 'is_empresa_propria' => true,
    ]);
    $imp = novaImportacaoXml($user);

    app(XmlNotaImporter::class)->importar(parsedFixture(), null, $imp);

    $nota = XmlNota::where('user_id', $user->id)->first();
    expect($nota->emit_participante_id)->not->toBeNull();
    expect(Participante::find($nota->emit_participante_id)->cliente_id)->toBe($cliente->id);
});

it('auto: dois clientes comuns → emitente vence e o destinatário é o participante', function () {
    $user = User::factory()->create();
    $emitCliente = Cliente::create(['user_id' => $user->id, 'documento' => '97551165000193', 'razao_social' => 'HIDRATOP', 'is_empresa_propria' => false]);
    Cliente::create(['user_id' => $user->id, 'documento' => '44373108000600', 'razao_social' => 'COCAL', 'is_empresa_propria' => false]);
    $imp = novaImportacaoXml($user);

    app(XmlNotaImporter::class)->importar(parsedFixture(), null, $imp);

    $nota = XmlNota::where('user_id', $user->id)->first();
    expect($nota->tipo_nota)->toBe(XmlNota::TIPO_SAIDA);
    expect($nota->emit_participante_id)->toBeNull();
    expect($nota->dest_participante_id)->not->toBeNull();
    expect(Participante::find($nota->dest_participante_id)->cliente_id)->toBe($emitCliente->id);
});

it('não sobrescreve o cliente_id de uma contraparte que já pertence a outro cliente', function () {
    $user = User::factory()->create();
    // Cliente A (qualquer documento) e a contraparte COCAL já vinculada a ele.
    $clienteA = Cliente::create([
        'user_id' => $user->id, 'documento' => '11111111000191',
        'tipo_pessoa' => 'PJ', 'razao_social' => 'CLIENTE A', 'ativo' => true, 'is_empresa_propria' => false,
    ]);
    $participante = Participante::create([
        'user_id' => $user->id, 'documento' => '44373108000600',
        'razao_social' => 'COCAL', 'origem_tipo' => 'xml', 'cliente_id' => $clienteA->id,
    ]);

    // Importa uma nota cujo dono é o emitente (Cliente B). COCAL é a contraparte (dest).
    $clienteB = Cliente::create([
        'user_id' => $user->id, 'documento' => '97551165000193',
        'tipo_pessoa' => 'PJ', 'razao_social' => 'CLIENTE B', 'ativo' => true, 'is_empresa_propria' => false,
    ]);
    $imp = novaImportacaoXml($user, $clienteB->id);
    app(XmlNotaImporter::class)->importar(parsedFixture(), '97551165000193', $imp);

    // O vínculo original (Cliente A) é preservado — NÃO migra pro Cliente B.
    expect($participante->refresh()->cliente_id)->toBe($clienteA->id);
});

it('dedup: reimportar a mesma chave não cria participante duplicado', function () {
    $user = User::factory()->create();
    $imp1 = novaImportacaoXml($user);
    app(XmlNotaImporter::class)->importar(parsedFixture(), '97551165000193', $imp1);
    expect(Participante::where('user_id', $user->id)->count())->toBe(1);

    $imp2 = novaImportacaoXml($user);
    app(XmlNotaImporter::class)->importar(parsedFixture(), '97551165000193', $imp2);

    expect(Participante::where('user_id', $user->id)->count())->toBe(1);
});
