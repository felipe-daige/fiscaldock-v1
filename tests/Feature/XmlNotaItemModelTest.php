<?php

use App\Models\User;
use App\Models\XmlImportacao;
use App\Models\XmlNota;
use App\Models\XmlNotaItem;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('cria item ligado à nota e expõe a relação', function () {
    $user = User::factory()->create();
    $imp = XmlImportacao::create([
        'user_id' => $user->id, 'tipo_documento' => 'NFE', 'modo_envio' => 'xml',
        'status' => 'concluido',
    ]);
    $nota = XmlNota::create([
        'user_id'          => $user->id,
        'importacao_xml_id' => $imp->id,
        'chave_acesso'     => str_repeat('1', 44),
        'tipo_documento'   => 'NFE',
        'numero_documento' => 1,
        'serie'            => 1,
        'data_emissao'     => now(),
        'valor_total'      => 100,
        'tipo_nota'        => 1,
        'emit_documento'   => '00000000000191',
        'dest_documento'   => '00000000000191',
        'modelo'           => '55',
    ]);

    $item = XmlNotaItem::create([
        'xml_nota_id' => $nota->id, 'user_id' => $user->id,
        'numero_item' => 1, 'codigo_item' => 'ABC', 'descricao' => 'Produto',
        'quantidade' => 2, 'unidade_medida' => 'UN', 'valor_unitario' => 50,
        'valor_total' => 100, 'cfop' => '5102', 'ncm' => '84339090',
        'metadados' => ['indTot' => '1'],
    ]);

    expect($nota->itens)->toHaveCount(1);
    expect($nota->itens->first()->ncm)->toBe('84339090');
    expect($item->nota->id)->toBe($nota->id);
    expect($item->metadados['indTot'])->toBe('1');
});
