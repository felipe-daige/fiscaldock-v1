<?php

use App\Models\Cliente;
use App\Models\EfdCatalogoItem;
use App\Models\EfdImportacao;
use App\Models\User;
use App\Models\XmlNota;
use App\Models\XmlNotaItem;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

it('renderiza 200 sem dados (mostra mensagem de vazio)', function () {
    $user = User::factory()->create();

    $response = actingAs($user)->get('/app/bi/catalogo-itens');

    $response->assertOk()
        ->assertSee('Catálogo × Itens', false)
        ->assertSee('Nenhum dado de itens encontrado', false);
});

it('renderiza top NCMs e itens vendidos sem catálogo com dados reais', function () {
    $user = User::factory()->create();

    // Saída sem catálogo (deve aparecer no bloco "vendidos sem catálogo")
    $venda = XmlNota::create([
        'user_id' => $user->id,
        'nfe_id' => '35240413305697000150550000000404041953940601',
        'tipo_documento' => 'NFE',
        'tipo_nota' => 1, // saída
        'origem' => 'xml_upload',
        'numero_nota' => 1,
        'serie' => 1,
        'data_emissao' => '2026-04-15',
        'valor_total' => 5000,
        'emit_cnpj' => '13305697000150',
        'dest_cnpj' => '00000000000191',
        'payload' => [],
    ]);
    XmlNotaItem::create([
        'xml_nota_id' => $venda->id,
        'user_id' => $user->id,
        'numero_item' => 1,
        'codigo_item' => 'VENDA-FANTASMA',
        'descricao' => 'Item vendido sem cadastro',
        'cfop' => '5102',
        'quantidade' => 10,
        'valor_total' => 5000,
        'cst_icms' => '00',
        'aliquota_icms' => 18.00,
        'unidade_medida' => 'UN',
        'ncm' => '84713012',
    ]);

    $response = actingAs($user)->get('/app/bi/catalogo-itens');

    $response->assertOk()
        ->assertSee('Top NCMs por valor movimentado', false)
        ->assertSee('84713012')
        ->assertSee('Itens vendidos sem catálogo', false)
        ->assertSee('VENDA-FANTASMA');
});

it('isolamento por user_id (não vaza dados entre usuários)', function () {
    $alice = User::factory()->create();
    $bob = User::factory()->create();

    $venda = XmlNota::create([
        'user_id' => $alice->id,
        'nfe_id' => '35240413305697000150550000000404041953940602',
        'tipo_documento' => 'NFE',
        'tipo_nota' => 1,
        'origem' => 'xml_upload',
        'numero_nota' => 1,
        'serie' => 1,
        'data_emissao' => '2026-04-15',
        'valor_total' => 100,
        'emit_cnpj' => '13305697000150',
        'dest_cnpj' => '00000000000191',
        'payload' => [],
    ]);
    XmlNotaItem::create([
        'xml_nota_id' => $venda->id,
        'user_id' => $alice->id,
        'numero_item' => 1,
        'codigo_item' => 'ALICE-X',
        'descricao' => 'Apenas da Alice',
        'cfop' => '5102',
        'quantidade' => 1,
        'valor_total' => 100,
        'ncm' => '84713012',
    ]);

    actingAs($bob)->get('/app/bi/catalogo-itens')
        ->assertOk()
        ->assertDontSee('ALICE-X')
        ->assertDontSee('84713012');
});

it('aplica filtro de cliente_id', function () {
    $user = User::factory()->create();
    $cliente = Cliente::create([
        'user_id' => $user->id,
        'tipo_pessoa' => 'PJ',
        'documento' => '99999999000111',
        'razao_social' => 'Cliente Filtrado',
        'is_empresa_propria' => false,
    ]);

    $venda = XmlNota::create([
        'user_id' => $user->id,
        'cliente_id' => $cliente->id,
        'nfe_id' => '35240413305697000150550000000404041953940603',
        'tipo_documento' => 'NFE',
        'tipo_nota' => 1,
        'origem' => 'xml_upload',
        'numero_nota' => 1,
        'serie' => 1,
        'data_emissao' => '2026-04-15',
        'valor_total' => 200,
        'emit_cnpj' => '13305697000150',
        'dest_cnpj' => '00000000000191',
        'payload' => [],
    ]);
    XmlNotaItem::create([
        'xml_nota_id' => $venda->id,
        'user_id' => $user->id,
        'numero_item' => 1,
        'codigo_item' => 'COM-CLIENTE',
        'descricao' => 'Item do cliente filtrado',
        'cfop' => '5102',
        'quantidade' => 1,
        'valor_total' => 200,
        'ncm' => '84713012',
    ]);

    actingAs($user)->get('/app/bi/catalogo-itens?cliente_id='.$cliente->id)
        ->assertOk()
        ->assertSee('COM-CLIENTE');

    actingAs($user)->get('/app/bi/catalogo-itens?cliente_id=999999')
        ->assertOk()
        ->assertDontSee('COM-CLIENTE');
});
