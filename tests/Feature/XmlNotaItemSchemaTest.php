<?php

use App\Models\User;
use App\Models\XmlNota;
use App\Models\XmlNotaItem;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function criarXmlNotaParaItens(User $user, string $chave): XmlNota
{
    return XmlNota::create([
        'user_id' => $user->id,
        'nfe_id' => $chave,
        'tipo_documento' => 'NFE',
        'tipo_nota' => XmlNota::TIPO_ENTRADA,
        'origem' => 'xml_upload',
        'numero_nota' => (int) substr($chave, -8),
        'serie' => 1,
        'data_emissao' => '2026-04-01',
        'valor_total' => 1000.00,
        'emit_cnpj' => '13305697000150',
        'dest_cnpj' => '00000000000191',
        'payload' => [],
    ]);
}

it('persiste item com colunas tipadas e cast de metadados como array', function () {
    $user = User::factory()->create();
    $nota = criarXmlNotaParaItens($user, '35240413305697000150550000000404041953940001');

    $item = XmlNotaItem::create([
        'xml_nota_id' => $nota->id,
        'user_id' => $user->id,
        'numero_item' => 1,
        'codigo_item' => 'PROD-001',
        'ean' => '7891234567890',
        'descricao' => 'Produto teste de schema',
        'ncm' => '84713012',
        'cest' => '0100100',
        'cfop' => '5102',
        'unidade_medida' => 'UN',
        'quantidade' => 2.0000,
        'valor_unitario' => 500.0000000000,
        'valor_total' => 1000.00,
        'cst_icms' => '00',
        'aliquota_icms' => 18.00,
        'valor_icms' => 180.00,
        'metadados' => ['comb' => ['cProdANP' => '210203001']],
    ]);

    $fresh = XmlNotaItem::find($item->id);

    expect($fresh->numero_item)->toBe(1)
        ->and($fresh->codigo_item)->toBe('PROD-001')
        ->and($fresh->cst_icms)->toBe('00')
        ->and((float) $fresh->aliquota_icms)->toBe(18.0)
        ->and($fresh->metadados)->toBeArray()
        ->and($fresh->metadados['comb']['cProdANP'])->toBe('210203001');
});

it('aceita CSOSN do Simples Nacional no cst_icms (3 chars)', function () {
    $user = User::factory()->create();
    $nota = criarXmlNotaParaItens($user, '35240413305697000150550000000404041953940002');

    $item = XmlNotaItem::create([
        'xml_nota_id' => $nota->id,
        'user_id' => $user->id,
        'numero_item' => 1,
        'codigo_item' => 'X',
        'descricao' => 'Item Simples',
        'cfop' => '5102',
        'quantidade' => 1,
        'valor_total' => 50.00,
        'cst_icms' => '101',
    ]);

    expect($item->fresh()->cst_icms)->toBe('101');
});

it('relation hasMany itens devolve coleção do XmlNota', function () {
    $user = User::factory()->create();
    $nota = criarXmlNotaParaItens($user, '35240413305697000150550000000404041953940003');

    foreach ([1, 2, 3] as $n) {
        XmlNotaItem::create([
            'xml_nota_id' => $nota->id,
            'user_id' => $user->id,
            'numero_item' => $n,
            'codigo_item' => "PROD-00{$n}",
            'descricao' => "Item {$n}",
            'cfop' => '5102',
            'quantidade' => 1,
            'valor_total' => 100.00,
        ]);
    }

    expect($nota->itens()->count())->toBe(3)
        ->and($nota->itens()->orderBy('numero_item')->pluck('codigo_item')->all())
        ->toBe(['PROD-001', 'PROD-002', 'PROD-003']);
});

it('cascade delete remove os itens quando o XmlNota é deletado', function () {
    $user = User::factory()->create();
    $nota = criarXmlNotaParaItens($user, '35240413305697000150550000000404041953940004');

    XmlNotaItem::create([
        'xml_nota_id' => $nota->id,
        'user_id' => $user->id,
        'numero_item' => 1,
        'codigo_item' => 'A',
        'descricao' => 'Item cascata',
        'cfop' => '5102',
        'quantidade' => 1,
        'valor_total' => 10.00,
    ]);

    expect(XmlNotaItem::where('xml_nota_id', $nota->id)->count())->toBe(1);

    $nota->delete();

    expect(XmlNotaItem::where('xml_nota_id', $nota->id)->count())->toBe(0);
});

it('unique (xml_nota_id, numero_item) bloqueia duplicidade', function () {
    $user = User::factory()->create();
    $nota = criarXmlNotaParaItens($user, '35240413305697000150550000000404041953940005');

    XmlNotaItem::create([
        'xml_nota_id' => $nota->id,
        'user_id' => $user->id,
        'numero_item' => 1,
        'codigo_item' => 'PRIMEIRO',
        'descricao' => 'Primeiro item',
        'cfop' => '5102',
        'quantidade' => 1,
        'valor_total' => 10.00,
    ]);

    expect(fn () => XmlNotaItem::create([
        'xml_nota_id' => $nota->id,
        'user_id' => $user->id,
        'numero_item' => 1,
        'codigo_item' => 'SEGUNDO',
        'descricao' => 'Numero_item duplicado',
        'cfop' => '5102',
        'quantidade' => 1,
        'valor_total' => 20.00,
    ]))->toThrow(QueryException::class);
});

it('preserva 10 casas decimais em valor_unitario', function () {
    $user = User::factory()->create();
    $nota = criarXmlNotaParaItens($user, '35240413305697000150550000000404041953940006');

    $item = XmlNotaItem::create([
        'xml_nota_id' => $nota->id,
        'user_id' => $user->id,
        'numero_item' => 1,
        'codigo_item' => 'PRECISAO',
        'descricao' => 'Item com precisão fiscal',
        'cfop' => '5102',
        'quantidade' => 3,
        'valor_unitario' => 12.3456789012,
        'valor_total' => 37.04,
    ]);

    expect((string) $item->fresh()->valor_unitario)->toBe('12.3456789012');
});
