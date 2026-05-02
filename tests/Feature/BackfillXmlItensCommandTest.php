<?php

use App\Models\User;
use App\Models\XmlNota;
use App\Models\XmlNotaItem;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\artisan;

uses(RefreshDatabase::class);

function backfillCriarNota(User $user, string $chave, ?array $payload): XmlNota
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
        'valor_total' => 1500.00,
        'emit_cnpj' => '13305697000150',
        'dest_cnpj' => '00000000000191',
        'payload' => $payload,
    ]);
}

function payloadDetSimples(): array
{
    return [
        'ide' => ['mod' => '55', 'natOp' => 'Venda'],
        'det' => [
            [
                'nItem' => 1,
                'prod' => [
                    'cProd' => 'PROD-001',
                    'cEAN' => '7891234567890',
                    'xProd' => 'Produto A',
                    'NCM' => '84713012',
                    'CFOP' => '5102',
                    'uCom' => 'UN',
                    'qCom' => '2.0000',
                    'vUnCom' => '500.0000000000',
                    'vProd' => '1000.00',
                ],
                'imposto' => [
                    'ICMS' => [
                        'ICMS00' => [
                            'orig' => '0',
                            'CST' => '00',
                            'modBC' => '3',
                            'vBC' => '1000.00',
                            'pICMS' => '18.00',
                            'vICMS' => '180.00',
                        ],
                    ],
                    'PIS' => [
                        'PISAliq' => [
                            'CST' => '01',
                            'vBC' => '1000.00',
                            'pPIS' => '1.6500',
                            'vPIS' => '16.50',
                        ],
                    ],
                    'COFINS' => [
                        'COFINSAliq' => [
                            'CST' => '01',
                            'vBC' => '1000.00',
                            'pCOFINS' => '7.6000',
                            'vCOFINS' => '76.00',
                        ],
                    ],
                ],
            ],
        ],
    ];
}

it('insere itens tipados a partir de payload->det', function () {
    $user = User::factory()->create();
    $nota = backfillCriarNota($user, '35240413305697000150550000000404041953940001', payloadDetSimples());

    $exit = artisan('xml:backfill-itens')->run();

    expect($exit)->toBe(0);
    expect(XmlNotaItem::count())->toBe(1);

    $item = XmlNotaItem::first();
    expect($item->codigo_item)->toBe('PROD-001')
        ->and($item->cfop)->toBe('5102')
        ->and($item->ncm)->toBe('84713012')
        ->and((float) $item->aliquota_icms)->toBe(18.0)
        ->and((float) $item->valor_icms)->toBe(180.0)
        ->and($item->cst_icms)->toBe('00')
        ->and((float) $item->valor_pis)->toBe(16.5)
        ->and((float) $item->valor_cofins)->toBe(76.0);
});

it('trata cEAN="SEM" como null', function () {
    $user = User::factory()->create();
    $payload = payloadDetSimples();
    $payload['det'][0]['prod']['cEAN'] = 'SEM';

    backfillCriarNota($user, '35240413305697000150550000000404041953940002', $payload);

    artisan('xml:backfill-itens')->assertSuccessful();

    expect(XmlNotaItem::first()->ean)->toBeNull();
});

it('aceita CSOSN do Simples Nacional no cst_icms', function () {
    $user = User::factory()->create();
    $payload = payloadDetSimples();
    // Substitui ICMS regime normal por Simples
    $payload['det'][0]['imposto']['ICMS'] = [
        'ICMSSN101' => [
            'orig' => '0',
            'CSOSN' => '101',
            'pCredSN' => '2.5600',
            'vCredICMSSN' => '25.60',
        ],
    ];

    backfillCriarNota($user, '35240413305697000150550000000404041953940003', $payload);

    artisan('xml:backfill-itens')->assertSuccessful();

    expect(XmlNotaItem::first()->cst_icms)->toBe('101');
});

it('joga combustível e infAdProd em metadados jsonb', function () {
    $user = User::factory()->create();
    $payload = payloadDetSimples();
    $payload['det'][0]['comb'] = ['cProdANP' => '210203001', 'descANP' => 'GASOLINA C COMUM'];
    $payload['det'][0]['infAdProd'] = 'Lote 12345';

    backfillCriarNota($user, '35240413305697000150550000000404041953940004', $payload);

    artisan('xml:backfill-itens')->assertSuccessful();

    $item = XmlNotaItem::first();
    expect($item->metadados)->toBeArray()
        ->and($item->metadados['comb']['cProdANP'])->toBe('210203001')
        ->and($item->metadados['infAdProd'])->toBe('Lote 12345');
});

it('achata det quando vem como objeto único (NF-e com 1 item)', function () {
    $user = User::factory()->create();
    $payload = payloadDetSimples();
    $payload['det'] = $payload['det'][0]; // colapsa array em objeto único

    backfillCriarNota($user, '35240413305697000150550000000404041953940005', $payload);

    artisan('xml:backfill-itens')->assertSuccessful();

    expect(XmlNotaItem::count())->toBe(1)
        ->and(XmlNotaItem::first()->codigo_item)->toBe('PROD-001');
});

it('é idempotente sem --force (não duplica em rerun)', function () {
    $user = User::factory()->create();
    backfillCriarNota($user, '35240413305697000150550000000404041953940006', payloadDetSimples());

    artisan('xml:backfill-itens')->assertSuccessful();
    artisan('xml:backfill-itens')->assertSuccessful();
    artisan('xml:backfill-itens')->assertSuccessful();

    expect(XmlNotaItem::count())->toBe(1);
});

it('--force reprocessa notas que ja tem itens', function () {
    $user = User::factory()->create();
    $nota = backfillCriarNota($user, '35240413305697000150550000000404041953940007', payloadDetSimples());

    artisan('xml:backfill-itens')->assertSuccessful();
    $itemOriginal = XmlNotaItem::first();

    // Edita o payload pra simular reimport
    $novoPayload = payloadDetSimples();
    $novoPayload['det'][0]['prod']['xProd'] = 'Produto A — DESCRIÇÃO ATUALIZADA';
    $nota->update(['payload' => $novoPayload]);

    artisan('xml:backfill-itens', ['--force' => true])->assertSuccessful();

    $atualizado = XmlNotaItem::first();
    expect(XmlNotaItem::count())->toBe(1)
        ->and($atualizado->descricao)->toBe('Produto A — DESCRIÇÃO ATUALIZADA');
});

it('--user filtra apenas o usuario informado', function () {
    $alice = User::factory()->create();
    $bob = User::factory()->create();

    backfillCriarNota($alice, '35240413305697000150550000000404041953940008', payloadDetSimples());
    backfillCriarNota($bob, '35240413305697000150550000000404041953940009', payloadDetSimples());

    artisan('xml:backfill-itens', ['--user' => $alice->id])->assertSuccessful();

    expect(XmlNotaItem::where('user_id', $alice->id)->count())->toBe(1);
    expect(XmlNotaItem::where('user_id', $bob->id)->count())->toBe(0);
});

it('pula nota sem det no payload sem erro', function () {
    $user = User::factory()->create();
    backfillCriarNota($user, '35240413305697000150550000000404041953940010', ['ide' => ['mod' => '55']]);
    backfillCriarNota($user, '35240413305697000150550000000404041953940011', null);

    $exit = artisan('xml:backfill-itens')->run();

    expect($exit)->toBe(0);
    expect(XmlNotaItem::count())->toBe(0);
});

it('aceita multiplos itens no mesmo det', function () {
    $user = User::factory()->create();
    $payload = payloadDetSimples();
    $payload['det'][] = [
        'nItem' => 2,
        'prod' => [
            'cProd' => 'PROD-002',
            'xProd' => 'Produto B',
            'NCM' => '94054210',
            'CFOP' => '5102',
            'uCom' => 'PC',
            'qCom' => '5',
            'vUnCom' => '20.00',
            'vProd' => '100.00',
        ],
        'imposto' => [
            'ICMS' => ['ICMS40' => ['orig' => '0', 'CST' => '40']],
        ],
    ];

    backfillCriarNota($user, '35240413305697000150550000000404041953940012', $payload);

    artisan('xml:backfill-itens')->assertSuccessful();

    expect(XmlNotaItem::count())->toBe(2);
    expect(XmlNotaItem::orderBy('numero_item')->pluck('codigo_item')->all())
        ->toBe(['PROD-001', 'PROD-002']);
});
