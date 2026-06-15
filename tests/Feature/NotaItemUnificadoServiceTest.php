<?php

use App\Models\EfdImportacao;
use App\Models\EfdNota;
use App\Models\User;
use App\Services\Catalogo\NotaItemUnificadoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function seedCatalogoUser(): array
{
    $user = User::factory()->create();
    $clienteId = DB::table('clientes')->insertGetId([
        'user_id' => $user->id, 'razao_social' => 'EMPRESA', 'documento' => '00000000000100',
        'is_empresa_propria' => true, 'created_at' => now(), 'updated_at' => now(),
    ]);

    return [$user, (int) $clienteId];
}

function efdNotaComItem(int $userId, int $clienteId, string $chave, string $codItem, float $valor, float $aliq = 18, string $origem = 'fiscal', $cfop = 5102, string $dataEmissao = '2024-01-15'): void
{
    $imp = EfdImportacao::create(['user_id' => $userId, 'cliente_id' => $clienteId, 'tipo_efd' => 'EFD ICMS/IPI', 'filename' => 'i.txt', 'status' => 'concluido', 'iniciado_em' => now()]);
    $nota = EfdNota::create([
        'user_id' => $userId, 'cliente_id' => $clienteId, 'importacao_id' => $imp->id, 'numero' => 1, 'serie' => '1',
        'data_emissao' => $dataEmissao, 'valor_desconto' => 0, 'cancelada' => false, 'chave_acesso' => $chave,
        'modelo' => '55', 'tipo_operacao' => 'saida', 'origem_arquivo' => $origem, 'valor_total' => $valor,
    ]);
    DB::table('efd_notas_itens')->insert([
        'efd_nota_id' => $nota->id, 'user_id' => $userId, 'numero_item' => 1, 'codigo_item' => $codItem,
        'descricao' => 'item efd', 'quantidade' => 1, 'valor_total' => $valor, 'cfop' => $cfop, 'aliquota_icms' => $aliq,
        'created_at' => now(), 'updated_at' => now(),
    ]);
}

function xmlNotaComItem(int $userId, int $clienteId, string $chave, string $codItem, float $valor, ?string $ncm = '12345678', float $aliq = 18, string $dataEmissao = '2024-02-10'): void
{
    $notaId = DB::table('xml_notas')->insertGetId([
        'user_id' => $userId, 'cliente_id' => $clienteId, 'chave_acesso' => $chave, 'tipo_documento' => 'NFE',
        'numero_documento' => '1', 'serie' => '1', 'data_emissao' => $dataEmissao, 'tipo_nota' => 1, 'modelo' => '55',
        'emit_documento' => '00000000000100', 'dest_documento' => '99999999000191', 'valor_total' => $valor,
        'created_at' => now(), 'updated_at' => now(),
    ]);
    DB::table('xml_notas_itens')->insert([
        'xml_nota_id' => $notaId, 'user_id' => $userId, 'numero_item' => 1, 'codigo_item' => $codItem,
        'descricao' => 'item xml', 'quantidade' => 1, 'valor_total' => $valor, 'cfop' => 5102, 'aliquota_icms' => $aliq,
        'ncm' => $ncm, 'created_at' => now(), 'updated_at' => now(),
    ]);
}

function catalogoItem(int $userId, int $clienteId, string $codItem, string $ncm = '99887766', float $aliq = 18): void
{
    DB::table('efd_catalogo_itens')->insert([
        'user_id' => $userId, 'cliente_id' => $clienteId, 'cod_item' => $codItem, 'descr_item' => 'PRODUTO CATALOGO',
        'tipo_item' => '00', 'cod_ncm' => $ncm, 'aliq_icms' => $aliq, 'unid_inv' => 'UN', 'created_at' => now(), 'updated_at' => now(),
    ]);
}

it('agrega itens das duas fontes deduplicando por chave (EFD vence)', function () {
    [$user, $clienteId] = seedCatalogoUser();
    $chaveComum = str_pad('A', 44, '0', STR_PAD_LEFT);
    $chaveSoXml = str_pad('B', 44, '0', STR_PAD_LEFT);

    efdNotaComItem($user->id, $clienteId, $chaveComum, 'SKU1', 100.0);
    xmlNotaComItem($user->id, $clienteId, $chaveComum, 'SKU1', 999.0); // ignorado (chave no EFD)
    xmlNotaComItem($user->id, $clienteId, $chaveSoXml, 'SKU2', 50.0);

    $itens = app(NotaItemUnificadoService::class)->itensAgregados($user->id)->keyBy('codigo_item');

    expect($itens)->toHaveCount(2);
    expect((float) $itens['SKU1']['valor_total'])->toBe(100.0);
    expect($itens['SKU1']['ocorrencias'])->toBe(1);
    expect($itens['SKU1']['fontes'])->toBe('efd');
    expect((float) $itens['SKU2']['valor_total'])->toBe(50.0);
    expect($itens['SKU2']['fontes'])->toBe('xml');
});

it('marca procedência ambas quando o item aparece nas duas fontes em notas diferentes', function () {
    [$user, $clienteId] = seedCatalogoUser();
    efdNotaComItem($user->id, $clienteId, str_pad('A', 44, '0', STR_PAD_LEFT), 'SKU9', 100.0);
    xmlNotaComItem($user->id, $clienteId, str_pad('B', 44, '0', STR_PAD_LEFT), 'SKU9', 70.0);

    $itens = app(NotaItemUnificadoService::class)->itensAgregados($user->id)->keyBy('codigo_item');

    expect($itens['SKU9']['fontes'])->toBe('ambas');
    expect((float) $itens['SKU9']['valor_total'])->toBe(170.0);
    expect($itens['SKU9']['ocorrencias'])->toBe(2);
});
