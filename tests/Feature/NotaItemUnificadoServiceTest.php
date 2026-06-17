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

function efdNotaComItem(int $userId, int $clienteId, ?string $chave, string $codItem, float $valor, float $aliq = 18, string $origem = 'fiscal', $cfop = 5102, string $dataEmissao = '2024-01-15'): void
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
    $impId = DB::table('efd_importacoes')->insertGetId([
        'user_id' => $userId, 'cliente_id' => $clienteId, 'tipo_efd' => 'EFD ICMS/IPI',
        'filename' => 'cat.txt', 'status' => 'concluido', 'iniciado_em' => now(),
        'created_at' => now(), 'updated_at' => now(),
    ]);
    DB::table('efd_catalogo_itens')->insert([
        'user_id' => $userId, 'cliente_id' => $clienteId, 'importacao_id' => $impId,
        'cod_item' => $codItem, 'descr_item' => 'PRODUTO CATALOGO',
        'tipo_item' => '00', 'cod_ncm' => $ncm, 'aliq_icms' => $aliq, 'unid_inv' => 'UN',
        'created_at' => now(), 'updated_at' => now(),
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

it('liga o item ao catálogo e marca tem_catalogo + NCM do catálogo para item EFD', function () {
    [$user, $clienteId] = seedCatalogoUser();
    catalogoItem($user->id, $clienteId, 'SKU1', '99887766', 18);
    efdNotaComItem($user->id, $clienteId, str_pad('A', 44, '0', STR_PAD_LEFT), 'SKU1', 100.0);

    $itens = app(NotaItemUnificadoService::class)->itensAgregados($user->id)->keyBy('codigo_item');

    expect($itens['SKU1']['tem_catalogo'])->toBeTrue();
    expect($itens['SKU1']['catalogo']['cod_ncm'])->toBe('99887766');
    expect($itens['SKU1']['ncm'])->toBe('99887766'); // EFD não tem NCM próprio → vem do catálogo
});

it('usa o NCM do próprio item XML e marca sem catálogo quando o código não está no cadastro', function () {
    [$user, $clienteId] = seedCatalogoUser();
    xmlNotaComItem($user->id, $clienteId, str_pad('B', 44, '0', STR_PAD_LEFT), 'FORN-X', 80.0, '76543210');

    $itens = app(NotaItemUnificadoService::class)->itensAgregados($user->id)->keyBy('codigo_item');

    expect($itens['FORN-X']['tem_catalogo'])->toBeFalse();
    expect($itens['FORN-X']['catalogo'])->toBeNull();
    expect($itens['FORN-X']['ncm'])->toBe('76543210'); // NCM do item XML
});

it('filtra por fonte (só EFD, só XML)', function () {
    [$user, $clienteId] = seedCatalogoUser();
    efdNotaComItem($user->id, $clienteId, str_pad('A', 44, '0', STR_PAD_LEFT), 'E1', 100.0);
    xmlNotaComItem($user->id, $clienteId, str_pad('B', 44, '0', STR_PAD_LEFT), 'X1', 50.0);

    $svc = app(NotaItemUnificadoService::class);
    expect($svc->itensAgregados($user->id, ['fonte' => 'efd'])->pluck('codigo_item')->all())->toBe(['E1']);
    expect($svc->itensAgregados($user->id, ['fonte' => 'xml'])->pluck('codigo_item')->all())->toBe(['X1']);
});

it('filtra por período (data_emissao)', function () {
    [$user, $clienteId] = seedCatalogoUser();
    efdNotaComItem($user->id, $clienteId, str_pad('A', 44, '0', STR_PAD_LEFT), 'JAN', 100.0, 18, 'fiscal', 5102, '2024-01-10');
    efdNotaComItem($user->id, $clienteId, str_pad('C', 44, '0', STR_PAD_LEFT), 'MAR', 100.0, 18, 'fiscal', 5102, '2024-03-10');

    $itens = app(NotaItemUnificadoService::class)->itensAgregados($user->id, ['periodo_de' => '2024-03-01', 'periodo_ate' => '2024-03-31'])->pluck('codigo_item')->all();
    expect($itens)->toBe(['MAR']);
});

it('filtra por cliente_id', function () {
    [$user, $clienteId] = seedCatalogoUser();
    $outroCliente = DB::table('clientes')->insertGetId([
        'user_id' => $user->id, 'razao_social' => 'OUTRA', 'documento' => '00000000000200',
        'is_empresa_propria' => false, 'created_at' => now(), 'updated_at' => now(),
    ]);
    efdNotaComItem($user->id, $clienteId, str_pad('A', 44, '0', STR_PAD_LEFT), 'DOMECLI', 100.0);
    efdNotaComItem($user->id, (int) $outroCliente, str_pad('C', 44, '0', STR_PAD_LEFT), 'OUTROCLI', 100.0);

    $itens = app(NotaItemUnificadoService::class)->itensAgregados($user->id, ['cliente_id' => $clienteId])->pluck('codigo_item')->all();
    expect($itens)->toBe(['DOMECLI']);
});

it('XML não some quando o EFD tem nota com chave_acesso nula (NFS-e / bloco A)', function () {
    [$user, $clienteId] = seedCatalogoUser();
    // EFD nota SEM chave (NFS-e) — não pode "envenenar" o dedup e sumir com o XML inteiro.
    efdNotaComItem($user->id, $clienteId, null, 'EFDNULL', 10.0);
    // XML com chave real, sem contraparte no EFD → deve aparecer.
    xmlNotaComItem($user->id, $clienteId, str_pad('B', 44, '0', STR_PAD_LEFT), 'XMLOK', 50.0);

    $itens = app(NotaItemUnificadoService::class)->itensAgregados($user->id)->keyBy('codigo_item');

    expect($itens)->toHaveKey('XMLOK');  // não some por causa da chave nula no EFD
    expect($itens)->toHaveKey('EFDNULL');
});

it('detecta divergência de NCM do XML mesmo quando a chave também está no EFD (não-deduplicado)', function () {
    [$user, $clienteId] = seedCatalogoUser();
    $chave = str_pad('A', 44, '0', STR_PAD_LEFT);
    catalogoItem($user->id, $clienteId, 'DIV', '11112222', 18);
    efdNotaComItem($user->id, $clienteId, $chave, 'DIV', 100.0);          // mesma chave → XML é deduplicado em itensAgregados
    xmlNotaComItem($user->id, $clienteId, $chave, 'DIV', 100.0, '99998888'); // mas o documento carregou outro NCM

    $mapa = app(NotaItemUnificadoService::class)->divergenciasNcmPorItem($user->id);

    expect($mapa)->toHaveKey('DIV');
    expect($mapa['DIV']['ncm_divergente'])->toBeTrue();
    expect($mapa['DIV']['ncm_xml'])->toBe('99998888');
    expect($mapa['DIV']['cat_ncm'])->toBe('11112222');
    expect($mapa['DIV']['tem_catalogo'])->toBeTrue();
});

it('não marca divergência quando o NCM do XML bate com o catálogo', function () {
    [$user, $clienteId] = seedCatalogoUser();
    catalogoItem($user->id, $clienteId, 'OK', '12345678', 18);
    xmlNotaComItem($user->id, $clienteId, str_pad('B', 44, '0', STR_PAD_LEFT), 'OK', 50.0, '12345678');

    $mapa = app(NotaItemUnificadoService::class)->divergenciasNcmPorItem($user->id);

    expect($mapa['OK']['ncm_divergente'])->toBeFalse();
});

it('normaliza máscara de NCM (pontuação) antes de comparar', function () {
    // NCM column is varchar(8) — mask must fit. '1234-567' (8 chars) strips to '1234567' (7 digits),
    // matching catalog '1234567'. Dot-separated NCMs (e.g. '8412.21.10') would be 10 chars and
    // overflow the column; the regexp_replace logic itself is validated here with a hyphen mask.
    [$user, $clienteId] = seedCatalogoUser();
    catalogoItem($user->id, $clienteId, 'MASK', '1234567', 18);
    xmlNotaComItem($user->id, $clienteId, str_pad('B', 44, '0', STR_PAD_LEFT), 'MASK', 50.0, '1234-567');

    $mapa = app(NotaItemUnificadoService::class)->divergenciasNcmPorItem($user->id);

    expect($mapa['MASK']['ncm_divergente'])->toBeFalse();
});

it('marca tem_catalogo=false para item XML fora do cadastro e não acusa divergência de NCM', function () {
    [$user, $clienteId] = seedCatalogoUser();
    xmlNotaComItem($user->id, $clienteId, str_pad('B', 44, '0', STR_PAD_LEFT), 'NOCAT', 80.0, '55556666');

    $mapa = app(NotaItemUnificadoService::class)->divergenciasNcmPorItem($user->id);

    expect($mapa['NOCAT']['tem_catalogo'])->toBeFalse();
    expect($mapa['NOCAT']['ncm_divergente'])->toBeFalse();
});

it('detecta divergência de alíquota ICMS (XML × catálogo)', function () {
    [$user, $clienteId] = seedCatalogoUser();
    catalogoItem($user->id, $clienteId, 'ALIQ', '12345678', 18);
    xmlNotaComItem($user->id, $clienteId, str_pad('B', 44, '0', STR_PAD_LEFT), 'ALIQ', 50.0, '12345678', 12);

    $mapa = app(NotaItemUnificadoService::class)->divergenciasNcmPorItem($user->id);

    expect($mapa['ALIQ']['aliquota_divergente'])->toBeTrue();
    expect($mapa['ALIQ']['ncm_divergente'])->toBeFalse();
});
