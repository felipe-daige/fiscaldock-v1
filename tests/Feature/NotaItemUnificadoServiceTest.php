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

function efdItemComCst(int $userId, int $clienteId, string $codItem, string $cst, int $cfop = 5102): void
{
    $imp = EfdImportacao::create(['user_id' => $userId, 'cliente_id' => $clienteId, 'tipo_efd' => 'EFD ICMS/IPI', 'filename' => 'i.txt', 'status' => 'concluido', 'iniciado_em' => now()]);
    $nota = EfdNota::create([
        'user_id' => $userId, 'cliente_id' => $clienteId, 'importacao_id' => $imp->id, 'numero' => 1, 'serie' => '1',
        'data_emissao' => '2024-01-15', 'valor_desconto' => 0, 'cancelada' => false, 'chave_acesso' => str_pad($codItem, 44, '0', STR_PAD_LEFT),
        'modelo' => '55', 'tipo_operacao' => 'saida', 'origem_arquivo' => 'fiscal', 'valor_total' => 100.0,
    ]);
    DB::table('efd_notas_itens')->insert([
        'efd_nota_id' => $nota->id, 'user_id' => $userId, 'numero_item' => 1, 'codigo_item' => $codItem,
        'descricao' => 'item efd', 'quantidade' => 1, 'valor_total' => 100.0, 'cfop' => $cfop, 'cst_icms' => $cst, 'aliquota_icms' => 18,
        'created_at' => now(), 'updated_at' => now(),
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

it('divergenciasNcmPorItem filtra por cliente_id', function () {
    [$user, $clienteId] = seedCatalogoUser();
    $outroCliente = DB::table('clientes')->insertGetId([
        'user_id' => $user->id, 'razao_social' => 'OUTRA', 'documento' => '00000000000200',
        'is_empresa_propria' => false, 'created_at' => now(), 'updated_at' => now(),
    ]);
    catalogoItem($user->id, $clienteId, 'DOME', '11112222', 18);
    catalogoItem($user->id, (int) $outroCliente, 'OUTRO', '11112222', 18);
    xmlNotaComItem($user->id, $clienteId, str_pad('A', 44, '0', STR_PAD_LEFT), 'DOME', 100.0, '99998888');
    xmlNotaComItem($user->id, (int) $outroCliente, str_pad('C', 44, '0', STR_PAD_LEFT), 'OUTRO', 100.0, '99998888');

    $mapa = app(NotaItemUnificadoService::class)->divergenciasNcmPorItem($user->id, ['cliente_id' => $clienteId]);

    expect($mapa)->toHaveKey('DOME');
    expect($mapa)->not->toHaveKey('OUTRO');
});

it('divergenciasNcmPorItem filtra por período (data_emissao)', function () {
    [$user, $clienteId] = seedCatalogoUser();
    catalogoItem($user->id, $clienteId, 'JAN', '11112222', 18);
    catalogoItem($user->id, $clienteId, 'MAR', '11112222', 18);
    xmlNotaComItem($user->id, $clienteId, str_pad('A', 44, '0', STR_PAD_LEFT), 'JAN', 100.0, '99998888', 18, '2024-01-10');
    xmlNotaComItem($user->id, $clienteId, str_pad('C', 44, '0', STR_PAD_LEFT), 'MAR', 100.0, '99998888', 18, '2024-03-10');

    $mapa = app(NotaItemUnificadoService::class)->divergenciasNcmPorItem($user->id, ['periodo_de' => '2024-03-01', 'periodo_ate' => '2024-03-31']);

    expect($mapa)->toHaveKey('MAR');
    expect($mapa)->not->toHaveKey('JAN');
});

it('não acusa divergência de NCM quando o item XML não tem NCM mas o catálogo tem', function () {
    [$user, $clienteId] = seedCatalogoUser();
    catalogoItem($user->id, $clienteId, 'SEMNCMXML', '11112222', 18);
    xmlNotaComItem($user->id, $clienteId, str_pad('B', 44, '0', STR_PAD_LEFT), 'SEMNCMXML', 50.0, null);

    $mapa = app(NotaItemUnificadoService::class)->divergenciasNcmPorItem($user->id);

    expect($mapa['SEMNCMXML']['ncm_divergente'])->toBeFalse();
    expect($mapa['SEMNCMXML']['tem_catalogo'])->toBeTrue();
});

it('agrega múltiplas notas do mesmo código e sinaliza divergência se qualquer XML divergir (BOOL_OR)', function () {
    [$user, $clienteId] = seedCatalogoUser();
    catalogoItem($user->id, $clienteId, 'MULTI', '11112222', 18);
    // duas notas XML do mesmo código: uma bate o NCM, a outra diverge
    xmlNotaComItem($user->id, $clienteId, str_pad('A', 44, '0', STR_PAD_LEFT), 'MULTI', 100.0, '11112222');
    xmlNotaComItem($user->id, $clienteId, str_pad('B', 44, '0', STR_PAD_LEFT), 'MULTI', 100.0, '99998888');

    $mapa = app(NotaItemUnificadoService::class)->divergenciasNcmPorItem($user->id);

    expect($mapa)->toHaveCount(1);                       // 1 linha por código
    expect($mapa['MULTI']['ncm_divergente'])->toBeTrue(); // BOOL_OR pega a ocorrência divergente
});

it('na agregação, usa a descrição do catálogo quando o item da nota está sem descrição', function () {
    [$user, $clienteId] = seedCatalogoUser();
    catalogoItem($user->id, $clienteId, 'SEMDESC', '12345678', 18); // descr_item = 'PRODUTO CATALOGO'
    $imp = EfdImportacao::create(['user_id' => $user->id, 'cliente_id' => $clienteId, 'tipo_efd' => 'EFD ICMS/IPI', 'filename' => 'i.txt', 'status' => 'concluido', 'iniciado_em' => now()]);
    $nota = EfdNota::create([
        'user_id' => $user->id, 'cliente_id' => $clienteId, 'importacao_id' => $imp->id, 'numero' => 1, 'serie' => '1',
        'data_emissao' => '2024-01-15', 'valor_desconto' => 0, 'cancelada' => false,
        'chave_acesso' => str_pad('A', 44, '0', STR_PAD_LEFT), 'modelo' => '55', 'tipo_operacao' => 'saida',
        'origem_arquivo' => 'fiscal', 'valor_total' => 100.0,
    ]);
    DB::table('efd_notas_itens')->insert([
        'efd_nota_id' => $nota->id, 'user_id' => $user->id, 'numero_item' => 1, 'codigo_item' => 'SEMDESC',
        'descricao' => null, 'quantidade' => 1, 'valor_total' => 100.0, 'cfop' => 5102, 'aliquota_icms' => 18,
        'created_at' => now(), 'updated_at' => now(),
    ]);

    $itens = app(NotaItemUnificadoService::class)->itensAgregados($user->id)->keyBy('codigo_item');

    expect($itens['SEMDESC']['descricao'])->toBe('PRODUTO CATALOGO'); // fallback do catálogo
});

it('divergenciasNcmPorItem traz a referência da importação XML do item', function () {
    [$user, $clienteId] = seedCatalogoUser();
    catalogoItem($user->id, $clienteId, 'DIVIMP', '11112222', 18);
    $impId = DB::table('xml_importacoes')->insertGetId([
        'user_id' => $user->id, 'cliente_id' => $clienteId, 'tipo_documento' => 'NFE',
        'filename' => 'lote-julho.zip', 'created_at' => now(), 'updated_at' => now(),
    ]);
    $nid = DB::table('xml_notas')->insertGetId([
        'user_id' => $user->id, 'cliente_id' => $clienteId, 'importacao_xml_id' => $impId,
        'chave_acesso' => str_pad('Z', 44, '0', STR_PAD_LEFT), 'tipo_documento' => 'NFE',
        'numero_documento' => '1', 'serie' => '1', 'data_emissao' => '2024-02-10', 'tipo_nota' => 1, 'modelo' => '55',
        'emit_documento' => '00000000000100', 'dest_documento' => '99999999000191', 'valor_total' => 10.0,
        'created_at' => now(), 'updated_at' => now(),
    ]);
    DB::table('xml_notas_itens')->insert([
        'xml_nota_id' => $nid, 'user_id' => $user->id, 'numero_item' => 1, 'codigo_item' => 'DIVIMP',
        'descricao' => 'i', 'quantidade' => 1, 'valor_total' => 10.0, 'cfop' => 5102, 'aliquota_icms' => 18,
        'ncm' => '99998888', 'created_at' => now(), 'updated_at' => now(),
    ]);

    $mapa = app(NotaItemUnificadoService::class)->divergenciasNcmPorItem($user->id);

    expect($mapa['DIVIMP']['ncm_divergente'])->toBeTrue();
    expect($mapa['DIVIMP']['importacoes'])->toContain('lote-julho.zip');
});

it('itensAgregados traz as importações de origem do item (id, fonte e label p/ link)', function () {
    [$user, $clienteId] = seedCatalogoUser();
    efdNotaComItem($user->id, $clienteId, str_pad('A', 44, '0', STR_PAD_LEFT), 'SKUI', 100.0);

    $itens = app(NotaItemUnificadoService::class)->itensAgregados($user->id)->keyBy('codigo_item');

    expect($itens['SKUI']['importacoes'])->toBeArray()->not->toBeEmpty();
    expect($itens['SKUI']['importacoes'][0]['fonte'])->toBe('efd');
    expect($itens['SKUI']['importacoes'][0]['id'])->toBeGreaterThan(0);
    expect($itens['SKUI']['importacoes'][0]['label'])->toContain('EFD');
});

it('filtra por cfop (lista, 1 ou mais)', function () {
    [$user, $clienteId] = seedCatalogoUser();
    efdNotaComItem($user->id, $clienteId, str_pad('A', 44, '0', STR_PAD_LEFT), 'CF5102', 100.0, 18, 'fiscal', 5102);
    efdNotaComItem($user->id, $clienteId, str_pad('B', 44, '0', STR_PAD_LEFT), 'CF6102', 100.0, 18, 'fiscal', 6102);
    efdNotaComItem($user->id, $clienteId, str_pad('C', 44, '0', STR_PAD_LEFT), 'CF5405', 100.0, 18, 'fiscal', 5405);

    $svc = app(NotaItemUnificadoService::class);

    expect($svc->itensAgregados($user->id, ['cfops' => ['5102']])->pluck('codigo_item')->all())->toBe(['CF5102']);
    expect($svc->itensAgregados($user->id, ['cfops' => ['5102', '5405']])->pluck('codigo_item')->sort()->values()->all())
        ->toBe(['CF5102', 'CF5405']);
    // lista vazia = sem filtro
    expect($svc->itensAgregados($user->id, ['cfops' => []])->count())->toBe(3);
});

it('filtra por cst_icms (lista, 1 ou mais)', function () {
    [$user, $clienteId] = seedCatalogoUser();
    efdItemComCst($user->id, $clienteId, 'CST00', '00');
    efdItemComCst($user->id, $clienteId, 'CST60', '60');
    efdItemComCst($user->id, $clienteId, 'CST20', '20');

    $svc = app(NotaItemUnificadoService::class);

    expect($svc->itensAgregados($user->id, ['csts' => ['00']])->pluck('codigo_item')->all())->toBe(['CST00']);
    expect($svc->itensAgregados($user->id, ['csts' => ['00', '60']])->pluck('codigo_item')->sort()->values()->all())
        ->toBe(['CST00', 'CST60']);
});

it('combina cfop + cst (interseção)', function () {
    [$user, $clienteId] = seedCatalogoUser();
    efdItemComCst($user->id, $clienteId, 'A', '00', 5102);
    efdItemComCst($user->id, $clienteId, 'B', '00', 6102);
    efdItemComCst($user->id, $clienteId, 'C', '60', 5102);

    $svc = app(NotaItemUnificadoService::class);
    expect($svc->itensAgregados($user->id, ['cfops' => ['5102'], 'csts' => ['00']])->pluck('codigo_item')->all())
        ->toBe(['A']);
});

it('facetas retorna cfops e csts distintos do universo do usuário', function () {
    [$user, $clienteId] = seedCatalogoUser();
    efdItemComCst($user->id, $clienteId, 'X1', '00', 5102);
    efdItemComCst($user->id, $clienteId, 'X2', '60', 6102);

    $f = app(NotaItemUnificadoService::class)->facetas($user->id);

    expect($f['cfops'])->toContain('5102')->toContain('6102');
    expect($f['csts'])->toContain('00')->toContain('60');
});

it('itensSemCatalogo lista item movimentado sem 0200 com a referência da importação', function () {
    [$user, $clienteId] = seedCatalogoUser();
    catalogoItem($user->id, $clienteId, 'TEM', '11112222', 18);
    efdNotaComItem($user->id, $clienteId, str_pad('A', 44, '0', STR_PAD_LEFT), 'SEMCAT', 100.0); // sem catálogo
    efdNotaComItem($user->id, $clienteId, str_pad('B', 44, '0', STR_PAD_LEFT), 'TEM', 50.0);     // com catálogo

    $lista = app(NotaItemUnificadoService::class)->itensSemCatalogo($user->id)->keyBy('codigo_item');

    expect($lista)->toHaveKey('SEMCAT');
    expect($lista)->not->toHaveKey('TEM');
    expect($lista['SEMCAT']['importacoes'])->toContain('EFD'); // rótulo da importação EFD
});
