<?php

use App\Models\EfdImportacao;
use App\Models\EfdNota;
use App\Models\User;
use App\Services\Catalogo\ReconciliacaoXmlEfdService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function reconSeedUser(): array
{
    $user = User::factory()->create();
    $clienteId = DB::table('clientes')->insertGetId([
        'user_id' => $user->id, 'razao_social' => 'EMPRESA', 'documento' => '00000000000100',
        'is_empresa_propria' => true, 'created_at' => now(), 'updated_at' => now(),
    ]);

    return [$user, (int) $clienteId];
}

function reconXml(int $userId, int $clienteId, string $chave, float $valor, string $data = '2024-01-10'): void
{
    DB::table('xml_notas')->insert([
        'user_id' => $userId, 'cliente_id' => $clienteId, 'chave_acesso' => $chave, 'tipo_documento' => 'NFE',
        'numero_documento' => '1', 'serie' => '1', 'data_emissao' => $data, 'tipo_nota' => 1, 'modelo' => '55',
        'emit_documento' => '00000000000100', 'dest_documento' => '99999999000191', 'valor_total' => $valor,
        'created_at' => now(), 'updated_at' => now(),
    ]);
}

function reconEfd(int $userId, int $clienteId, string $chave, float $valor, string $origem = 'fiscal', string $data = '2024-01-10'): void
{
    $imp = EfdImportacao::create(['user_id' => $userId, 'cliente_id' => $clienteId, 'tipo_efd' => 'EFD ICMS/IPI', 'filename' => 'i.txt', 'status' => 'concluido', 'iniciado_em' => now()]);
    EfdNota::create([
        'user_id' => $userId, 'cliente_id' => $clienteId, 'importacao_id' => $imp->id, 'numero' => 1, 'serie' => '1',
        'data_emissao' => $data, 'valor_desconto' => 0, 'cancelada' => false, 'chave_acesso' => $chave,
        'modelo' => '55', 'tipo_operacao' => 'saida', 'origem_arquivo' => $origem, 'valor_total' => $valor,
    ]);
}

it('conta chave em ambos com totais batendo como reconciliada', function () {
    [$user, $cli] = reconSeedUser();
    $chave = str_pad('A', 44, '0', STR_PAD_LEFT);
    reconXml($user->id, $cli, $chave, 100.0);
    reconEfd($user->id, $cli, $chave, 100.0);

    $r = app(ReconciliacaoXmlEfdService::class)->resumo($user->id);

    expect($r['documentadas'])->toBe(1);
    expect($r['reconciliadas'])->toBe(1);
    expect($r['divergencia_total'])->toBe(0);
    expect($r['nao_declaradas'])->toBe(0);
});

it('marca divergência de total quando XML e EFD diferem além da tolerância', function () {
    [$user, $cli] = reconSeedUser();
    $chave = str_pad('A', 44, '0', STR_PAD_LEFT);
    reconXml($user->id, $cli, $chave, 100.0);
    reconEfd($user->id, $cli, $chave, 90.0);

    $r = app(ReconciliacaoXmlEfdService::class)->resumo($user->id);

    expect($r['divergencia_total'])->toBe(1);
    expect($r['reconciliadas'])->toBe(0);
});

it('marca nota XML sem contraparte no EFD como não declarada', function () {
    [$user, $cli] = reconSeedUser();
    reconXml($user->id, $cli, str_pad('B', 44, '0', STR_PAD_LEFT), 50.0);

    $r = app(ReconciliacaoXmlEfdService::class)->resumo($user->id);

    expect($r['documentadas'])->toBe(1);
    expect($r['nao_declaradas'])->toBe(1);
});

it('usa o total da linha fiscal do EFD (não a gêmea PIS/COFINS) na reconciliação', function () {
    [$user, $cli] = reconSeedUser();
    $chave = str_pad('A', 44, '0', STR_PAD_LEFT);
    reconXml($user->id, $cli, $chave, 100.0);
    reconEfd($user->id, $cli, $chave, 100.0, 'fiscal');
    reconEfd($user->id, $cli, $chave, 999.0, 'contribuicoes');

    $r = app(ReconciliacaoXmlEfdService::class)->resumo($user->id);

    expect($r['reconciliadas'])->toBe(1);
    expect($r['divergencia_total'])->toBe(0);
});

it('considera reconciliada quando a diferença está dentro da tolerância de 1 centavo', function () {
    [$user, $cli] = reconSeedUser();
    $chave = str_pad('A', 44, '0', STR_PAD_LEFT);
    reconXml($user->id, $cli, $chave, 100.00);
    reconEfd($user->id, $cli, $chave, 100.01);

    $r = app(ReconciliacaoXmlEfdService::class)->resumo($user->id);

    expect($r['reconciliadas'])->toBe(1);
});

it('conta notas EFD sem XML como cobertura (efd_sem_xml), não como não-declaradas', function () {
    [$user, $cli] = reconSeedUser();
    reconEfd($user->id, $cli, str_pad('C', 44, '0', STR_PAD_LEFT), 70.0);

    $r = app(ReconciliacaoXmlEfdService::class)->resumo($user->id);

    expect($r['efd_sem_xml'])->toBe(1);
    expect($r['nao_declaradas'])->toBe(0);
    expect($r['documentadas'])->toBe(0);
});

it('resumoAlertas compõe contagens de divergência, sem-catálogo e não-declaradas com temSinal', function () {
    [$user, $cli] = reconSeedUser();

    // 1 item XML com NCM divergente do catálogo
    $impId = DB::table('efd_importacoes')->insertGetId([
        'user_id' => $user->id, 'cliente_id' => $cli, 'tipo_efd' => 'EFD ICMS/IPI',
        'filename' => 'c.txt', 'status' => 'concluido', 'iniciado_em' => now(), 'created_at' => now(), 'updated_at' => now(),
    ]);
    DB::table('efd_catalogo_itens')->insert([
        'user_id' => $user->id, 'cliente_id' => $cli, 'importacao_id' => $impId, 'cod_item' => 'DIV',
        'descr_item' => 'X', 'tipo_item' => '00', 'cod_ncm' => '11112222', 'aliq_icms' => 18, 'unid_inv' => 'UN',
        'created_at' => now(), 'updated_at' => now(),
    ]);
    $nid = DB::table('xml_notas')->insertGetId([
        'user_id' => $user->id, 'cliente_id' => $cli, 'chave_acesso' => str_pad('D', 44, '0', STR_PAD_LEFT),
        'tipo_documento' => 'NFE', 'numero_documento' => '1', 'serie' => '1', 'data_emissao' => '2024-01-10',
        'tipo_nota' => 1, 'modelo' => '55', 'emit_documento' => '00000000000100', 'dest_documento' => '99999999000191',
        'valor_total' => 10.0, 'created_at' => now(), 'updated_at' => now(),
    ]);
    DB::table('xml_notas_itens')->insert([
        'xml_nota_id' => $nid, 'user_id' => $user->id, 'numero_item' => 1, 'codigo_item' => 'DIV',
        'descricao' => 'item', 'quantidade' => 1, 'valor_total' => 10.0, 'cfop' => 5102, 'aliquota_icms' => 18,
        'ncm' => '99998888', 'created_at' => now(), 'updated_at' => now(),
    ]);
    // 1 nota XML não declarada (sem EFD)
    reconXml($user->id, $cli, str_pad('B', 44, '0', STR_PAD_LEFT), 50.0);

    $r = app(ReconciliacaoXmlEfdService::class)->resumoAlertas($user->id);

    expect($r['ncm_revisar_qtd'])->toBe(1);
    expect($r['sem_catalogo_qtd'])->toBe(0);     // o item 'DIV' tem catálogo
    expect($r['nao_declaradas_qtd'])->toBe(2);   // chave 'D' (XML, EFD ausente) + chave 'B'
    expect($r['temSinal'])->toBeTrue();
});

it('resumoAlertas sem nada retorna tudo zero e temSinal false', function () {
    [$user] = reconSeedUser();

    $r = app(ReconciliacaoXmlEfdService::class)->resumoAlertas($user->id);

    expect($r['ncm_revisar_qtd'])->toBe(0);
    expect($r['sem_catalogo_qtd'])->toBe(0);
    expect($r['nao_declaradas_qtd'])->toBe(0);
    expect($r['temSinal'])->toBeFalse();
});

it('resumoAlertas exclui itens descartados das contagens', function () {
    [$user, $cli] = reconSeedUser();
    $impId = DB::table('efd_importacoes')->insertGetId([
        'user_id' => $user->id, 'cliente_id' => $cli, 'tipo_efd' => 'EFD ICMS/IPI',
        'filename' => 'c.txt', 'status' => 'concluido', 'iniciado_em' => now(), 'created_at' => now(), 'updated_at' => now(),
    ]);
    DB::table('efd_catalogo_itens')->insert([
        'user_id' => $user->id, 'cliente_id' => $cli, 'importacao_id' => $impId, 'cod_item' => 'DIV',
        'descr_item' => 'X', 'tipo_item' => '00', 'cod_ncm' => '11112222', 'aliq_icms' => 18, 'unid_inv' => 'UN',
        'created_at' => now(), 'updated_at' => now(),
    ]);
    $nid = DB::table('xml_notas')->insertGetId([
        'user_id' => $user->id, 'cliente_id' => $cli, 'chave_acesso' => str_pad('D', 44, '0', STR_PAD_LEFT),
        'tipo_documento' => 'NFE', 'numero_documento' => '1', 'serie' => '1', 'data_emissao' => '2024-01-10',
        'tipo_nota' => 1, 'modelo' => '55', 'emit_documento' => '00000000000100', 'dest_documento' => '99999999000191',
        'valor_total' => 20.0, 'created_at' => now(), 'updated_at' => now(),
    ]);
    DB::table('xml_notas_itens')->insert([
        ['xml_nota_id' => $nid, 'user_id' => $user->id, 'numero_item' => 1, 'codigo_item' => 'DIV', 'descricao' => 'i', 'quantidade' => 1, 'valor_total' => 10.0, 'cfop' => 5102, 'aliquota_icms' => 18, 'ncm' => '99998888', 'created_at' => now(), 'updated_at' => now()],
        ['xml_nota_id' => $nid, 'user_id' => $user->id, 'numero_item' => 2, 'codigo_item' => 'FORA', 'descricao' => 'i', 'quantidade' => 1, 'valor_total' => 10.0, 'cfop' => 5102, 'aliquota_icms' => 18, 'ncm' => '12345678', 'created_at' => now(), 'updated_at' => now()],
    ]);

    $svc = app(ReconciliacaoXmlEfdService::class);
    $desc = app(\App\Services\Catalogo\AlertaCatalogoDescarteService::class);

    $antes = $svc->resumoAlertas($user->id);
    expect($antes['ncm_revisar_qtd'])->toBe(1);
    expect($antes['sem_catalogo_qtd'])->toBe(1);

    $desc->descartar($user->id, 'ncm_divergente', 'DIV');
    $desc->descartar($user->id, 'sem_catalogo', 'FORA');

    $depois = $svc->resumoAlertas($user->id);
    expect($depois['ncm_revisar_qtd'])->toBe(0);
    expect($depois['sem_catalogo_qtd'])->toBe(0);
});

it('resumoAlertas conta item XML cujo código não está no catálogo (sem_catalogo_qtd)', function () {
    [$user, $cli] = reconSeedUser();

    // item XML com código FORA do catálogo (nenhum efd_catalogo_itens cadastrado)
    $nid = DB::table('xml_notas')->insertGetId([
        'user_id' => $user->id, 'cliente_id' => $cli, 'chave_acesso' => str_pad('E', 44, '0', STR_PAD_LEFT),
        'tipo_documento' => 'NFE', 'numero_documento' => '1', 'serie' => '1', 'data_emissao' => '2024-01-10',
        'tipo_nota' => 1, 'modelo' => '55', 'emit_documento' => '00000000000100', 'dest_documento' => '99999999000191',
        'valor_total' => 10.0, 'created_at' => now(), 'updated_at' => now(),
    ]);
    DB::table('xml_notas_itens')->insert([
        'xml_nota_id' => $nid, 'user_id' => $user->id, 'numero_item' => 1, 'codigo_item' => 'FORA',
        'descricao' => 'item', 'quantidade' => 1, 'valor_total' => 10.0, 'cfop' => 5102, 'aliquota_icms' => 18,
        'ncm' => '12345678', 'created_at' => now(), 'updated_at' => now(),
    ]);

    $r = app(ReconciliacaoXmlEfdService::class)->resumoAlertas($user->id);

    expect($r['sem_catalogo_qtd'])->toBe(1);
    expect($r['ncm_revisar_qtd'])->toBe(0); // sem catálogo não acusa divergência de NCM
    expect($r['temSinal'])->toBeTrue();
});
