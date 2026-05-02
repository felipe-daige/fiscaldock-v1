<?php

use App\Models\Cliente;
use App\Models\EfdCatalogoItem;
use App\Models\EfdImportacao;
use App\Models\EfdNota;
use App\Models\EfdNotaItem;
use App\Models\User;
use App\Models\XmlNota;
use App\Models\XmlNotaItem;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

function catCliente(User $user, string $documento = '00000000000191'): Cliente
{
    return Cliente::firstOrCreate(
        ['user_id' => $user->id, 'documento' => $documento],
        ['tipo_pessoa' => 'PJ', 'razao_social' => 'Empresa Própria', 'is_empresa_propria' => true]
    );
}

function catImportacao(User $user, Cliente $cliente): EfdImportacao
{
    return EfdImportacao::create([
        'user_id' => $user->id,
        'cliente_id' => $cliente->id,
        'tipo_efd' => 'EFD ICMS/IPI',
        'status' => 'concluido',
    ]);
}

function catCatalogoItem(User $user, Cliente $cliente, EfdImportacao $imp, string $codItem, array $overrides = []): EfdCatalogoItem
{
    return EfdCatalogoItem::create(array_merge([
        'user_id' => $user->id,
        'cliente_id' => $cliente->id,
        'importacao_id' => $imp->id,
        'cod_item' => $codItem,
        'descr_item' => "Produto {$codItem}",
        'tipo_item' => '00',
        'cod_ncm' => '84713012',
        'aliq_icms' => 18.00,
        'unid_inv' => 'UN',
    ], $overrides));
}

function catEfdNota(User $user, Cliente $cliente, EfdImportacao $imp, string $chave): EfdNota
{
    return EfdNota::create([
        'user_id' => $user->id,
        'cliente_id' => $cliente->id,
        'importacao_id' => $imp->id,
        'chave_acesso' => $chave,
        'modelo' => '55',
        'numero' => (int) substr($chave, -8),
        'serie' => '1',
        'data_emissao' => '2026-04-15',
        'tipo_operacao' => 'entrada',
        'valor_total' => 1000.00,
        'valor_desconto' => 0,
        'metadados' => [],
    ]);
}

function catXmlNota(User $user, string $chave, ?int $clienteId = null): XmlNota
{
    return XmlNota::create([
        'user_id' => $user->id,
        'cliente_id' => $clienteId,
        'nfe_id' => $chave,
        'tipo_documento' => 'NFE',
        'tipo_nota' => XmlNota::TIPO_ENTRADA,
        'origem' => 'xml_upload',
        'numero_nota' => (int) substr($chave, -8),
        'serie' => 1,
        'data_emissao' => '2026-04-15',
        'valor_total' => 1500.00,
        'emit_cnpj' => '13305697000150',
        'dest_cnpj' => '00000000000191',
        'payload' => [],
    ]);
}

it('view renderiza para usuario autenticado', function () {
    $user = User::factory()->create();
    actingAs($user)->get('/app/catalogo')->assertOk();
});

it('inclui item movimentado APENAS via XML nos KPIs', function () {
    $user = User::factory()->create();
    $cliente = catCliente($user);
    $imp = catImportacao($user, $cliente);

    catCatalogoItem($user, $cliente, $imp, 'PROD-XML-ONLY');

    // Movimentação só em XML — não há EFD pra essa nota
    $xml = catXmlNota($user, '35240413305697000150550000000404041953940101');
    XmlNotaItem::create([
        'xml_nota_id' => $xml->id,
        'user_id' => $user->id,
        'numero_item' => 1,
        'codigo_item' => 'PROD-XML-ONLY',
        'descricao' => 'Produto vindo só do XML',
        'cfop' => '5102',
        'quantidade' => 5,
        'valor_total' => 750.00,
        'cst_icms' => '00',
        'aliquota_icms' => 18.00,
    ]);

    $response = actingAs($user)->get('/app/catalogo');
    $response->assertOk();

    $kpis = $response->original->getData()['kpis'];
    expect($kpis['total_produtos'])->toBe(1)
        ->and($kpis['com_movimentacao'])->toBe(1)
        ->and($kpis['sem_movimentacao'])->toBe(0)
        ->and((float) $kpis['valor_movimentado'])->toBe(750.0);
});

it('mistura XML e EFD agrega movimentação no item do catálogo', function () {
    $user = User::factory()->create();
    $cliente = catCliente($user);
    $imp = catImportacao($user, $cliente);

    catCatalogoItem($user, $cliente, $imp, 'PROD-MIX');

    // EFD com chave A
    $efd = catEfdNota($user, $cliente, $imp, '35240413305697000150550000000404041953940102');
    EfdNotaItem::create([
        'efd_nota_id' => $efd->id,
        'user_id' => $user->id,
        'numero_item' => 1,
        'codigo_item' => 'PROD-MIX',
        'descricao' => 'Misto EFD',
        'quantidade' => 2,
        'valor_total' => 200.00,
        'cfop' => 5102,
        'aliquota_icms' => 18.00,
    ]);

    // XML com chave B (não está em EFD)
    $xml = catXmlNota($user, '35240413305697000150550000000404041953940103');
    XmlNotaItem::create([
        'xml_nota_id' => $xml->id,
        'user_id' => $user->id,
        'numero_item' => 1,
        'codigo_item' => 'PROD-MIX',
        'descricao' => 'Misto XML',
        'cfop' => '5102',
        'quantidade' => 3,
        'valor_total' => 300.00,
        'cst_icms' => '00',
        'aliquota_icms' => 18.00,
    ]);

    $response = actingAs($user)->get('/app/catalogo');
    $itens = $response->original->getData()['itens'];

    expect($itens)->toHaveCount(1);
    $item = $itens->first();
    expect($item->total_movimentacoes)->toBe(2)        // 1 EFD + 1 XML
        ->and((float) $item->valor_movimentado)->toBe(500.0)
        ->and($item->origens_movimentacao)->toContain('efd', 'xml');
});

it('dedup quando mesma chave existe em XML e EFD não infla métricas', function () {
    $user = User::factory()->create();
    $cliente = catCliente($user);
    $imp = catImportacao($user, $cliente);

    catCatalogoItem($user, $cliente, $imp, 'PROD-DEDUP');

    $chave = '35240413305697000150550000000404041953940104';

    $efd = catEfdNota($user, $cliente, $imp, $chave);
    EfdNotaItem::create([
        'efd_nota_id' => $efd->id,
        'user_id' => $user->id,
        'numero_item' => 1,
        'codigo_item' => 'PROD-DEDUP',
        'descricao' => 'Dedup EFD',
        'quantidade' => 5,
        'valor_total' => 500.00,
        'cfop' => 5102,
        'aliquota_icms' => 18.00,
    ]);

    $xml = catXmlNota($user, $chave);
    XmlNotaItem::create([
        'xml_nota_id' => $xml->id,
        'user_id' => $user->id,
        'numero_item' => 1,
        'codigo_item' => 'PROD-DEDUP',
        'descricao' => 'Dedup XML',
        'cfop' => '5102',
        'quantidade' => 5,
        'valor_total' => 500.00,
        'cst_icms' => '00',
        'aliquota_icms' => 18.00,
    ]);

    $response = actingAs($user)->get('/app/catalogo');
    $kpis = $response->original->getData()['kpis'];
    $item = $response->original->getData()['itens']->first();

    // Não duplica: 500 (não 1000)
    expect((float) $kpis['valor_movimentado'])->toBe(500.0)
        ->and((float) $item->valor_movimentado)->toBe(500.0)
        ->and($item->total_movimentacoes)->toBe(1)
        ->and($item->origens_movimentacao)->toBe(['efd']);
});

it('detecta divergencia de aliquota entre catalogo (18%) e movimentação real (4%)', function () {
    $user = User::factory()->create();
    $cliente = catCliente($user);
    $imp = catImportacao($user, $cliente);

    catCatalogoItem($user, $cliente, $imp, 'PROD-DIV', ['aliq_icms' => 18.00]);

    $xml = catXmlNota($user, '35240413305697000150550000000404041953940105');
    XmlNotaItem::create([
        'xml_nota_id' => $xml->id,
        'user_id' => $user->id,
        'numero_item' => 1,
        'codigo_item' => 'PROD-DIV',
        'descricao' => 'Item com alíquota errada',
        'cfop' => '5102',
        'quantidade' => 1,
        'valor_total' => 100.00,
        'cst_icms' => '00',
        'aliquota_icms' => 4.00, // bem diferente do cadastrado
    ]);

    $response = actingAs($user)->get('/app/catalogo');
    $kpis = $response->original->getData()['kpis'];

    expect($kpis['aliq_divergente'])->toBe(1);
});

it('charts (top CFOPs/CSTs) consideram itens XML do catálogo', function () {
    $user = User::factory()->create();
    $cliente = catCliente($user);
    $imp = catImportacao($user, $cliente);

    catCatalogoItem($user, $cliente, $imp, 'PROD-CHART');

    $xml = catXmlNota($user, '35240413305697000150550000000404041953940106');
    XmlNotaItem::create([
        'xml_nota_id' => $xml->id,
        'user_id' => $user->id,
        'numero_item' => 1,
        'codigo_item' => 'PROD-CHART',
        'descricao' => 'Item de chart',
        'cfop' => '5102',
        'quantidade' => 1,
        'valor_total' => 100.00,
        'cst_icms' => '00',
        'aliquota_icms' => 18.00,
    ]);

    $response = actingAs($user)->get('/app/catalogo');
    $cfops = $response->original->getData()['cfops'];
    $csts = $response->original->getData()['csts_icms'];

    expect($cfops)->toHaveCount(1)
        ->and($cfops[0]->cfop)->toBe('5102')
        ->and($cfops[0]->total)->toBe(1);

    expect($csts)->toHaveCount(1)
        ->and($csts[0]->cst_icms)->toBe('00');
});

it('isola por user_id (não vaza catálogo de outro usuário)', function () {
    $alice = User::factory()->create();
    $bob = User::factory()->create();

    $clienteA = catCliente($alice);
    $impA = catImportacao($alice, $clienteA);
    catCatalogoItem($alice, $clienteA, $impA, 'ALICE-1');

    $clienteB = catCliente($bob, '99999999000111');
    $impB = catImportacao($bob, $clienteB);
    catCatalogoItem($bob, $clienteB, $impB, 'BOB-1');

    $response = actingAs($alice)->get('/app/catalogo');
    $itens = $response->original->getData()['itens'];

    expect($itens->pluck('cod_item')->all())->toBe(['ALICE-1']);
});
