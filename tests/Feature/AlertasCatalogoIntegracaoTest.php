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

it('view alertas mostra bloco Catálogo × Notas com 4 KPIs e top items', function () {
    $user = User::factory()->create();
    $cliente = Cliente::firstOrCreate(
        ['user_id' => $user->id, 'documento' => '00000000000191'],
        ['tipo_pessoa' => 'PJ', 'razao_social' => 'Empresa', 'is_empresa_propria' => true]
    );
    $imp = EfdImportacao::create([
        'user_id' => $user->id,
        'cliente_id' => $cliente->id,
        'tipo_efd' => 'EFD ICMS/IPI',
        'status' => 'concluido',
    ]);
    EfdCatalogoItem::create([
        'user_id' => $user->id,
        'cliente_id' => $cliente->id,
        'importacao_id' => $imp->id,
        'cod_item' => 'PROD-CAD',
        'descr_item' => 'Item cadastrado',
        'tipo_item' => '00',
        'cod_ncm' => '84713012',
        'aliq_icms' => 18.00,
        'unid_inv' => 'UN',
    ]);

    // Item 1: NCM divergente (cadastrado 84713012 / declarado 94054210)
    $n1 = XmlNota::create([
        'user_id' => $user->id,
        'nfe_id' => '35240413305697000150550000000404041953940301',
        'tipo_documento' => 'NFE',
        'tipo_nota' => 0,
        'origem' => 'xml_upload',
        'numero_nota' => 1,
        'serie' => 1,
        'data_emissao' => '2026-04-15',
        'valor_total' => 1000.00,
        'emit_cnpj' => '13305697000150',
        'dest_cnpj' => '00000000000191',
        'payload' => [],
    ]);
    XmlNotaItem::create([
        'xml_nota_id' => $n1->id,
        'user_id' => $user->id,
        'numero_item' => 1,
        'codigo_item' => 'PROD-CAD',
        'descricao' => 'Item cadastrado declarado errado',
        'cfop' => '5102',
        'quantidade' => 1,
        'valor_total' => 1000.00,
        'cst_icms' => '00',
        'aliquota_icms' => 18.00,
        'unidade_medida' => 'UN',
        'ncm' => '94054210',
    ]);

    // Item 2: sem catálogo
    $n2 = XmlNota::create([
        'user_id' => $user->id,
        'nfe_id' => '35240413305697000150550000000404041953940302',
        'tipo_documento' => 'NFE',
        'tipo_nota' => 0,
        'origem' => 'xml_upload',
        'numero_nota' => 2,
        'serie' => 1,
        'data_emissao' => '2026-04-15',
        'valor_total' => 500.00,
        'emit_cnpj' => '13305697000150',
        'dest_cnpj' => '00000000000191',
        'payload' => [],
    ]);
    XmlNotaItem::create([
        'xml_nota_id' => $n2->id,
        'user_id' => $user->id,
        'numero_item' => 1,
        'codigo_item' => 'PROD-FANTASMA',
        'descricao' => 'Sem cadastro',
        'cfop' => '5102',
        'quantidade' => 1,
        'valor_total' => 500.00,
        'cst_icms' => '00',
        'aliquota_icms' => 18.00,
    ]);

    $response = actingAs($user)->get('/app/clearance/alertas');

    $response->assertOk()
        ->assertSee('Catálogo × Notas', false)
        ->assertSee('Sem catálogo', false)
        ->assertSee('NCM divergente', false)
        ->assertSee('PROD-FANTASMA')
        ->assertSee('PROD-CAD');

    $resumo = $response->original->getData()['catalogoAlertasResumo'];
    expect($resumo['sem_catalogo'])->toBe(1)
        ->and($resumo['ncm_divergente'])->toBe(1);
});

it('quando não há alertas de catálogo, bloco fica oculto', function () {
    $user = User::factory()->create();

    $response = actingAs($user)->get('/app/clearance/alertas');

    $response->assertOk()->assertDontSee('Catálogo × Notas', false);
});
