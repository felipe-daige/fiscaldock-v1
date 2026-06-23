<?php

use App\Models\EfdImportacao;
use App\Models\EfdNota;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

function seedBiUser(): array
{
    $user = User::factory()->create();
    $clienteId = DB::table('clientes')->insertGetId([
        'user_id' => $user->id, 'razao_social' => 'EMPRESA', 'documento' => '00000000000100',
        'is_empresa_propria' => true, 'created_at' => now(), 'updated_at' => now(),
    ]);

    return [$user, (int) $clienteId];
}

function biEfdItem(int $userId, int $clienteId, string $chave, string $codItem, float $valor): void
{
    $imp = EfdImportacao::create(['user_id' => $userId, 'cliente_id' => $clienteId, 'tipo_efd' => 'EFD ICMS/IPI', 'filename' => 'i.txt', 'status' => 'concluido', 'iniciado_em' => now()]);
    $nota = EfdNota::create([
        'user_id' => $userId, 'cliente_id' => $clienteId, 'importacao_id' => $imp->id, 'numero' => 1, 'serie' => '1',
        'data_emissao' => '2024-01-15', 'valor_desconto' => 0, 'cancelada' => false, 'chave_acesso' => $chave,
        'modelo' => '55', 'tipo_operacao' => 'saida', 'origem_arquivo' => 'fiscal', 'valor_total' => $valor,
    ]);
    DB::table('efd_notas_itens')->insert([
        'efd_nota_id' => $nota->id, 'user_id' => $userId, 'numero_item' => 1, 'codigo_item' => $codItem,
        'descricao' => 'item efd', 'quantidade' => 1, 'valor_total' => $valor, 'cfop' => 5102, 'aliquota_icms' => 18,
        'created_at' => now(), 'updated_at' => now(),
    ]);
}

function biCatalogo(int $userId, int $clienteId, string $codItem, string $ncm = '99887766'): void
{
    $imp = EfdImportacao::create(['user_id' => $userId, 'cliente_id' => $clienteId, 'tipo_efd' => 'EFD ICMS/IPI', 'filename' => 'c.txt', 'status' => 'concluido', 'iniciado_em' => now()]);
    DB::table('efd_catalogo_itens')->insert([
        'user_id' => $userId, 'cliente_id' => $clienteId, 'importacao_id' => $imp->id, 'cod_item' => $codItem,
        'descr_item' => 'TEM CATALOGO', 'tipo_item' => '00', 'cod_ncm' => $ncm, 'aliq_icms' => 18, 'unid_inv' => 'UN',
        'created_at' => now(), 'updated_at' => now(),
    ]);
}

it('exige autenticação', function () {
    $this->get('/app/bi/catalogo-itens')->assertRedirect(route('login'));
});

it('renderiza a tela com KPIs, itens e o alerta de item sem catálogo', function () {
    [$user, $clienteId] = seedBiUser();
    biCatalogo($user->id, $clienteId, 'COMCAT', '99887766');
    biEfdItem($user->id, $clienteId, str_pad('A', 44, '0', STR_PAD_LEFT), 'COMCAT', 100.0);
    biEfdItem($user->id, $clienteId, str_pad('C', 44, '0', STR_PAD_LEFT), 'SEMCAT', 40.0);

    $html = actingAs($user)->get('/app/bi/catalogo-itens')->assertOk()->getContent();

    expect($html)->toContain('COMCAT');
    expect($html)->toContain('SEMCAT');
    expect($html)->toContain('sem catálogo');
});

it('mostra colunas de procedência, NCM e os KPIs principais', function () {
    [$user, $clienteId] = seedBiUser();
    biCatalogo($user->id, $clienteId, 'P1', '12345678');
    biEfdItem($user->id, $clienteId, str_pad('A', 44, '0', STR_PAD_LEFT), 'P1', 100.0);

    $html = actingAs($user)->get('/app/bi/catalogo-itens')->assertOk()->getContent();

    expect($html)->toContain('Origem');           // cabeçalho da coluna procedência
    expect($html)->toContain('12345678');          // NCM (via catálogo)
    expect($html)->toContain('Valor movimentado'); // KPI
});

function biXmlItem(int $userId, int $clienteId, string $chave, string $codItem, float $valor, string $ncm): void
{
    $nid = DB::table('xml_notas')->insertGetId([
        'user_id' => $userId, 'cliente_id' => $clienteId, 'chave_acesso' => $chave, 'tipo_documento' => 'NFE',
        'numero_documento' => '1', 'serie' => '1', 'data_emissao' => '2024-02-10', 'tipo_nota' => 1, 'modelo' => '55',
        'emit_documento' => '00000000000100', 'dest_documento' => '99999999000191', 'valor_total' => $valor,
        'created_at' => now(), 'updated_at' => now(),
    ]);
    DB::table('xml_notas_itens')->insert([
        'xml_nota_id' => $nid, 'user_id' => $userId, 'numero_item' => 1, 'codigo_item' => $codItem,
        'descricao' => 'item xml', 'quantidade' => 1, 'valor_total' => $valor, 'cfop' => 5102, 'aliquota_icms' => 18,
        'ncm' => $ncm, 'created_at' => now(), 'updated_at' => now(),
    ]);
}

it('mostra o KPI e o painel de NCM a revisar quando o item XML diverge do catálogo', function () {
    [$user, $clienteId] = seedBiUser();
    biCatalogo($user->id, $clienteId, 'DIVITEM', '11112222');
    biXmlItem($user->id, $clienteId, str_pad('D', 44, '0', STR_PAD_LEFT), 'DIVITEM', 100.0, '99998888');

    $html = actingAs($user)->get('/app/bi/catalogo-itens')->assertOk()->getContent();

    expect($html)->toContain('NCM a revisar');   // KPI + título do painel
    expect($html)->toContain('DIVITEM');          // linha do painel
    expect($html)->toContain('99998888');         // NCM documento
    expect($html)->toContain('11112222');         // NCM cadastro
});

it('mostra a faixa de reconciliação quando há nota XML documentada', function () {
    [$user, $clienteId] = seedBiUser();
    $chave = str_pad('R', 44, '0', STR_PAD_LEFT);
    biEfdItem($user->id, $clienteId, $chave, 'P', 100.0);
    biXmlItem($user->id, $clienteId, $chave, 'P', 100.0, '12345678');

    $html = actingAs($user)->get('/app/bi/catalogo-itens')->assertOk()->getContent();

    expect($html)->toContain('Reconciliação documento');
    expect($html)->toContain('documentadas');
});

it('não mostra o painel de divergência quando não há divergência', function () {
    [$user, $clienteId] = seedBiUser();
    biCatalogo($user->id, $clienteId, 'OKITEM', '12345678');
    biEfdItem($user->id, $clienteId, str_pad('A', 44, '0', STR_PAD_LEFT), 'OKITEM', 50.0);

    $html = actingAs($user)->get('/app/bi/catalogo-itens')->assertOk()->getContent();

    expect($html)->not->toContain('NCM a revisar (documento');  // título do painel ausente
});

it('renderiza o painel de divergência também no path AJAX (partial)', function () {
    [$user, $clienteId] = seedBiUser();
    biCatalogo($user->id, $clienteId, 'DIVAJAX', '11112222');
    biXmlItem($user->id, $clienteId, str_pad('D', 44, '0', STR_PAD_LEFT), 'DIVAJAX', 100.0, '99998888');

    $html = actingAs($user)
        ->get('/app/bi/catalogo-itens', ['X-Requested-With' => 'XMLHttpRequest'])
        ->assertOk()->getContent();

    expect($html)->toContain('NCM a revisar (documento'); // painel presente no partial
    expect($html)->toContain('DIVAJAX');
});

it('dispensar alerta de NCM remove do painel e reaparece em mostrar dispensados', function () {
    [$user, $clienteId] = seedBiUser();
    biCatalogo($user->id, $clienteId, 'DIVK', '11112222');
    biXmlItem($user->id, $clienteId, str_pad('D', 44, '0', STR_PAD_LEFT), 'DIVK', 100.0, '99998888');

    expect(actingAs($user)->get('/app/bi/catalogo-itens')->getContent())->toContain('NCM a revisar (documento');

    actingAs($user)->postJson('/app/bi/catalogo-itens/alerta/descartar', ['tipo' => 'ncm_divergente', 'codigo_item' => 'DIVK'])
        ->assertOk()->assertJson(['ok' => true]);

    // painel some (era o único alerta de NCM)
    $depois = actingAs($user)->get('/app/bi/catalogo-itens')->assertOk()->getContent();
    expect($depois)->not->toContain('NCM a revisar (documento');

    // reaparece com toggle + botão Restaurar
    $disp = actingAs($user)->get('/app/bi/catalogo-itens?dispensados=1')->assertOk()->getContent();
    expect($disp)->toContain('DIVK');
    expect($disp)->toContain('Restaurar');

    // restaurar traz de volta
    actingAs($user)->postJson('/app/bi/catalogo-itens/alerta/restaurar', ['tipo' => 'ncm_divergente', 'codigo_item' => 'DIVK'])
        ->assertOk();
    expect(actingAs($user)->get('/app/bi/catalogo-itens')->getContent())->toContain('NCM a revisar (documento');
});

it('descartar valida o tipo de alerta', function () {
    [$user] = seedBiUser();
    actingAs($user)->postJson('/app/bi/catalogo-itens/alerta/descartar', ['tipo' => 'xxx', 'codigo_item' => '1'])
        ->assertStatus(422);
});

it('descartar exige autenticação', function () {
    $this->postJson('/app/bi/catalogo-itens/alerta/descartar', ['tipo' => 'ncm_divergente', 'codigo_item' => '1'])
        ->assertStatus(401);
});

function biEfdItemCfop(int $userId, int $clienteId, string $chave, string $codItem, int $cfop): void
{
    $imp = EfdImportacao::create(['user_id' => $userId, 'cliente_id' => $clienteId, 'tipo_efd' => 'EFD ICMS/IPI', 'filename' => 'i.txt', 'status' => 'concluido', 'iniciado_em' => now()]);
    $nota = EfdNota::create([
        'user_id' => $userId, 'cliente_id' => $clienteId, 'importacao_id' => $imp->id, 'numero' => 1, 'serie' => '1',
        'data_emissao' => '2024-01-15', 'valor_desconto' => 0, 'cancelada' => false, 'chave_acesso' => $chave,
        'modelo' => '55', 'tipo_operacao' => 'saida', 'origem_arquivo' => 'fiscal', 'valor_total' => 100.0,
    ]);
    DB::table('efd_notas_itens')->insert([
        'efd_nota_id' => $nota->id, 'user_id' => $userId, 'numero_item' => 1, 'codigo_item' => $codItem,
        'descricao' => 'item efd', 'quantidade' => 1, 'valor_total' => 100.0, 'cfop' => $cfop, 'cst_icms' => '00', 'aliquota_icms' => 18,
        'created_at' => now(), 'updated_at' => now(),
    ]);
}

it('filtra a tabela de itens por CFOP via query (1 ou mais)', function () {
    [$user, $clienteId] = seedBiUser();
    // ambos com catálogo: isola o filtro à tabela de itens (fora dos painéis de alerta)
    biCatalogo($user->id, $clienteId, 'KEEPME', '11112222');
    biCatalogo($user->id, $clienteId, 'DROPME', '11112222');
    biEfdItemCfop($user->id, $clienteId, str_pad('A', 44, '0', STR_PAD_LEFT), 'KEEPME', 5102);
    biEfdItemCfop($user->id, $clienteId, str_pad('B', 44, '0', STR_PAD_LEFT), 'DROPME', 6102);

    $html = actingAs($user)->get('/app/bi/catalogo-itens?cfops[]=5102')->assertOk()->getContent();

    expect($html)->toContain('KEEPME');
    expect($html)->not->toContain('DROPME');
});

it('expõe os filtros de CFOP e CST na tela', function () {
    [$user, $clienteId] = seedBiUser();
    biCatalogo($user->id, $clienteId, 'P', '11112222');
    biEfdItemCfop($user->id, $clienteId, str_pad('A', 44, '0', STR_PAD_LEFT), 'P', 5102);

    $html = actingAs($user)->get('/app/bi/catalogo-itens')->assertOk()->getContent();

    expect($html)->toContain('name="cfops[]"');
    expect($html)->toContain('name="csts[]"');
});

it('mostra a descrição CONFAZ de cada CFOP nas opções do filtro', function () {
    [$user, $clienteId] = seedBiUser();
    biCatalogo($user->id, $clienteId, 'P', '11112222');
    biEfdItemCfop($user->id, $clienteId, str_pad('A', 44, '0', STR_PAD_LEFT), 'P', 5102);

    $html = actingAs($user)->get('/app/bi/catalogo-itens')->assertOk()->getContent();

    expect($html)->toContain('5102');
    expect($html)->toContain('Venda de mercadoria adquirida'); // descrição do CFOP 5102
});

function biTrialUserCliente(): array
{
    $user = User::factory()->trialAtivo()->create(['credits' => 100]);
    $clienteId = DB::table('clientes')->insertGetId([
        'user_id' => $user->id, 'razao_social' => 'EMPRESA', 'documento' => '00000000000100',
        'is_empresa_propria' => true, 'created_at' => now(), 'updated_at' => now(),
    ]);

    return [$user, (int) $clienteId];
}

it('exporta CSV (200 text/csv) respeitando o filtro de CFOP', function () {
    [$user, $clienteId] = biTrialUserCliente();
    biCatalogo($user->id, $clienteId, 'KEEPCSV', '11112222');
    biCatalogo($user->id, $clienteId, 'DROPCSV', '11112222');
    biEfdItemCfop($user->id, $clienteId, str_pad('A', 44, '0', STR_PAD_LEFT), 'KEEPCSV', 5102);
    biEfdItemCfop($user->id, $clienteId, str_pad('B', 44, '0', STR_PAD_LEFT), 'DROPCSV', 6102);

    $r = actingAs($user)->get('/app/bi/catalogo-itens/exportar?cfops[]=5102')->assertOk();

    expect($r->headers->get('content-type'))->toContain('text/csv');
    expect($r->headers->get('content-disposition'))->toContain('.csv');
    $body = $r->streamedContent();
    expect($body)->toContain('KEEPCSV');
    expect($body)->not->toContain('DROPCSV');
});

it('exporta CSV com TODOS os itens quando não há filtro', function () {
    [$user, $clienteId] = biTrialUserCliente();
    biEfdItemCfop($user->id, $clienteId, str_pad('A', 44, '0', STR_PAD_LEFT), 'ITEM1', 5102);
    biEfdItemCfop($user->id, $clienteId, str_pad('B', 44, '0', STR_PAD_LEFT), 'ITEM2', 6102);

    $body = actingAs($user)->get('/app/bi/catalogo-itens/exportar')->assertOk()->streamedContent();

    expect($body)->toContain('ITEM1');
    expect($body)->toContain('ITEM2');
});

it('exporta PDF (200 application/pdf) respeitando o filtro', function () {
    [$user, $clienteId] = biTrialUserCliente();
    biCatalogo($user->id, $clienteId, 'PDFKEEP', '11112222');
    biCatalogo($user->id, $clienteId, 'PDFDROP', '11112222');
    biEfdItemCfop($user->id, $clienteId, str_pad('A', 44, '0', STR_PAD_LEFT), 'PDFKEEP', 5102);
    biEfdItemCfop($user->id, $clienteId, str_pad('B', 44, '0', STR_PAD_LEFT), 'PDFDROP', 6102);

    $r = actingAs($user)->get('/app/bi/catalogo-itens/exportar-pdf?cfops[]=5102')->assertOk();

    expect($r->headers->get('content-type'))->toContain('application/pdf');
    expect(substr((string) $r->getContent(), 0, 4))->toBe('%PDF');
});

it('mostra os botões de exportar CSV e PDF na tela', function () {
    [$user, $clienteId] = biTrialUserCliente();
    biEfdItemCfop($user->id, $clienteId, str_pad('A', 44, '0', STR_PAD_LEFT), 'X', 5102);

    $html = actingAs($user)->get('/app/bi/catalogo-itens')->assertOk()->getContent();

    expect($html)->toContain('catalogo-itens/exportar');
    expect($html)->toContain('catalogo-itens/exportar-pdf');
});

it('exportar é barrado para Free puro (403)', function () {
    $this->seed(\Database\Seeders\SubscriptionPlanSeeder::class);
    $user = User::factory()->create();

    actingAs($user)->get('/app/bi/catalogo-itens/exportar')->assertStatus(403);
    actingAs($user)->get('/app/bi/catalogo-itens/exportar-pdf')->assertStatus(403);
});

it('mostra a coluna Arquivo de origem com link para a importação na tabela de itens', function () {
    [$user, $clienteId] = seedBiUser();
    biEfdItem($user->id, $clienteId, str_pad('A', 44, '0', STR_PAD_LEFT), 'SKUL', 100.0);

    $html = actingAs($user)->get('/app/bi/catalogo-itens')->assertOk()->getContent();

    expect($html)->toContain('Arquivo de origem');
    expect($html)->toContain('/app/importacao/efd/'); // link para o documento de origem
});
