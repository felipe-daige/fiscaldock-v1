<?php

use App\Models\Cliente;
use App\Models\EfdCatalogoItem;
use App\Models\EfdImportacao;
use App\Models\User;
use App\Models\XmlNota;
use App\Models\XmlNotaItem;
use App\Services\Catalogo\AlertasCatalogoService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function alertasCliente(User $user): Cliente
{
    return Cliente::firstOrCreate(
        ['user_id' => $user->id, 'documento' => '00000000000191'],
        ['tipo_pessoa' => 'PJ', 'razao_social' => 'Empresa Própria', 'is_empresa_propria' => true]
    );
}

function alertasImp(User $user, Cliente $cliente): EfdImportacao
{
    return EfdImportacao::create([
        'user_id' => $user->id,
        'cliente_id' => $cliente->id,
        'tipo_efd' => 'EFD ICMS/IPI',
        'status' => 'concluido',
    ]);
}

function alertasCatalogo(User $user, Cliente $cliente, EfdImportacao $imp, string $codItem, array $overrides = []): EfdCatalogoItem
{
    return EfdCatalogoItem::create(array_merge([
        'user_id' => $user->id,
        'cliente_id' => $cliente->id,
        'importacao_id' => $imp->id,
        'cod_item' => $codItem,
        'descr_item' => "Catálogo {$codItem}",
        'tipo_item' => '00',
        'cod_ncm' => '84713012',
        'aliq_icms' => 18.00,
        'unid_inv' => 'UN',
    ], $overrides));
}

function alertasXmlNota(User $user, string $chave): XmlNota
{
    return XmlNota::create([
        'user_id' => $user->id,
        'nfe_id' => $chave,
        'tipo_documento' => 'NFE',
        'tipo_nota' => XmlNota::TIPO_ENTRADA,
        'origem' => 'xml_upload',
        'numero_nota' => (int) substr($chave, -8),
        'serie' => 1,
        'data_emissao' => '2026-04-15',
        'valor_total' => 1500.00,
        'emit_cnpj' => '13305697000150',
        'dest_cnpj' => '00000000000191',
        'payload' => [],
    ]);
}

function alertasXmlItem(XmlNota $nota, array $overrides = []): XmlNotaItem
{
    return XmlNotaItem::create(array_merge([
        'xml_nota_id' => $nota->id,
        'user_id' => $nota->user_id,
        'numero_item' => 1,
        'codigo_item' => 'PROD-001',
        'descricao' => 'Item declarado',
        'cfop' => '5102',
        'quantidade' => 1,
        'valor_total' => 100.00,
        'cst_icms' => '00',
        'aliquota_icms' => 18.00,
        'unidade_medida' => 'UN',
        'ncm' => '84713012',
    ], $overrides));
}

beforeEach(function () {
    $this->service = app(AlertasCatalogoService::class);
});

it('dispara sem_catalogo quando item da nota não está no catálogo', function () {
    $user = User::factory()->create();
    alertasCliente($user); // cria pra ter participante, mas não cria catálogo nenhum

    $nota = alertasXmlNota($user, '35240413305697000150550000000404041953940201');
    alertasXmlItem($nota, ['codigo_item' => 'PROD-FANTASMA']);

    $alertas = $this->service->gerar($user->id);

    expect($alertas)->toHaveCount(1);
    expect($alertas->first()['tipo'])->toBe('sem_catalogo')
        ->and($alertas->first()['codigo_item'])->toBe('PROD-FANTASMA');
});

it('NÃO dispara sem_catalogo quando item está no catálogo', function () {
    $user = User::factory()->create();
    $cliente = alertasCliente($user);
    $imp = alertasImp($user, $cliente);
    alertasCatalogo($user, $cliente, $imp, 'PROD-CAD');

    $nota = alertasXmlNota($user, '35240413305697000150550000000404041953940202');
    alertasXmlItem($nota, ['codigo_item' => 'PROD-CAD']);

    $alertas = $this->service->gerar($user->id);

    expect($alertas->where('tipo', 'sem_catalogo'))->toBeEmpty();
});

it('dispara ncm_divergente quando NCM declarado difere do cadastrado', function () {
    $user = User::factory()->create();
    $cliente = alertasCliente($user);
    $imp = alertasImp($user, $cliente);
    alertasCatalogo($user, $cliente, $imp, 'PROD-NCM', ['cod_ncm' => '84713012']);

    $nota = alertasXmlNota($user, '35240413305697000150550000000404041953940203');
    alertasXmlItem($nota, ['codigo_item' => 'PROD-NCM', 'ncm' => '84713019']); // diferente

    $alertas = $this->service->gerar($user->id);

    $ncm = $alertas->firstWhere('tipo', 'ncm_divergente');
    expect($ncm)->not->toBeNull()
        ->and($ncm['codigo_item'])->toBe('PROD-NCM')
        ->and($ncm['cadastro']['ncm'])->toBe('84713012');
});

it('NÃO dispara ncm_divergente quando NCM bate', function () {
    $user = User::factory()->create();
    $cliente = alertasCliente($user);
    $imp = alertasImp($user, $cliente);
    alertasCatalogo($user, $cliente, $imp, 'PROD-NCM-OK', ['cod_ncm' => '84713012']);

    $nota = alertasXmlNota($user, '35240413305697000150550000000404041953940204');
    alertasXmlItem($nota, ['codigo_item' => 'PROD-NCM-OK', 'ncm' => '84713012']);

    expect($this->service->gerar($user->id)->where('tipo', 'ncm_divergente'))->toBeEmpty();
});

it('dispara unidade_divergente quando unidade declarada (PC) ≠ catalogada (UN)', function () {
    $user = User::factory()->create();
    $cliente = alertasCliente($user);
    $imp = alertasImp($user, $cliente);
    alertasCatalogo($user, $cliente, $imp, 'PROD-U', ['unid_inv' => 'UN']);

    $nota = alertasXmlNota($user, '35240413305697000150550000000404041953940205');
    alertasXmlItem($nota, ['codigo_item' => 'PROD-U', 'unidade_medida' => 'PC']);

    $u = $this->service->gerar($user->id)->firstWhere('tipo', 'unidade_divergente');
    expect($u)->not->toBeNull()
        ->and($u['cadastro']['unidade'])->toBe('UN');
});

it('aliquota: tolerância 0,5pp NÃO dispara (Δ 0,3pp)', function () {
    $user = User::factory()->create();
    $cliente = alertasCliente($user);
    $imp = alertasImp($user, $cliente);
    alertasCatalogo($user, $cliente, $imp, 'PROD-AL1', ['aliq_icms' => 18.00]);

    $nota = alertasXmlNota($user, '35240413305697000150550000000404041953940206');
    alertasXmlItem($nota, ['codigo_item' => 'PROD-AL1', 'aliquota_icms' => 18.30]);

    expect($this->service->gerar($user->id)->where('tipo', 'aliquota_incompativel'))->toBeEmpty();
});

it('aliquota: divergência de 0,8pp DISPARA', function () {
    $user = User::factory()->create();
    $cliente = alertasCliente($user);
    $imp = alertasImp($user, $cliente);
    alertasCatalogo($user, $cliente, $imp, 'PROD-AL2', ['aliq_icms' => 18.00]);

    $nota = alertasXmlNota($user, '35240413305697000150550000000404041953940207');
    alertasXmlItem($nota, ['codigo_item' => 'PROD-AL2', 'aliquota_icms' => 18.80]);

    $a = $this->service->gerar($user->id)->firstWhere('tipo', 'aliquota_incompativel');
    expect($a)->not->toBeNull()
        ->and((float) $a['cadastro']['aliquota'])->toBe(18.0);
});

it('aliquota: usa média ponderada (caso real divergente)', function () {
    $user = User::factory()->create();
    $cliente = alertasCliente($user);
    $imp = alertasImp($user, $cliente);
    alertasCatalogo($user, $cliente, $imp, 'PROD-AVG', ['aliq_icms' => 18.00]);

    $nota = alertasXmlNota($user, '35240413305697000150550000000404041953940208');
    // ponderada = (4*100 + 4*100 + 18*200)/400 = (400+400+3600)/400 = 11.0
    // |18.0 - 11.0| = 7.0 > 0.5 → dispara
    alertasXmlItem($nota, ['numero_item' => 1, 'codigo_item' => 'PROD-AVG', 'aliquota_icms' => 4.00, 'valor_total' => 100.00]);
    alertasXmlItem($nota, ['numero_item' => 2, 'codigo_item' => 'PROD-AVG', 'aliquota_icms' => 4.00, 'valor_total' => 100.00]);
    alertasXmlItem($nota, ['numero_item' => 3, 'codigo_item' => 'PROD-AVG', 'aliquota_icms' => 18.00, 'valor_total' => 200.00]);

    expect($this->service->gerar($user->id)->where('tipo', 'aliquota_incompativel'))->toHaveCount(1);
});

it('aliquota: catálogo sem aliq_icms cadastrada NÃO gera alerta', function () {
    $user = User::factory()->create();
    $cliente = alertasCliente($user);
    $imp = alertasImp($user, $cliente);
    alertasCatalogo($user, $cliente, $imp, 'PROD-NULL', ['aliq_icms' => null]);

    $nota = alertasXmlNota($user, '35240413305697000150550000000404041953940209');
    alertasXmlItem($nota, ['codigo_item' => 'PROD-NULL', 'aliquota_icms' => 4.00]);

    expect($this->service->gerar($user->id)->where('tipo', 'aliquota_incompativel'))->toBeEmpty();
});

it('isolamento por user_id (não vaza alerta de outro usuário)', function () {
    $alice = User::factory()->create();
    $bob = User::factory()->create();

    $notaAlice = alertasXmlNota($alice, '35240413305697000150550000000404041953940210');
    alertasXmlItem($notaAlice, ['codigo_item' => 'ALICE-X']);

    expect($this->service->gerar($bob->id))->toBeEmpty();
    expect($this->service->gerar($alice->id))->toHaveCount(1);
});

it('resumo retorna contagem por tipo', function () {
    $user = User::factory()->create();
    $cliente = alertasCliente($user);
    $imp = alertasImp($user, $cliente);

    // Item A: tem catálogo, NCM divergente
    alertasCatalogo($user, $cliente, $imp, 'A', ['cod_ncm' => '84713012']);
    $notaA = alertasXmlNota($user, '35240413305697000150550000000404041953940211');
    alertasXmlItem($notaA, ['codigo_item' => 'A', 'ncm' => '94054210']);

    // Item B: sem catálogo
    $notaB = alertasXmlNota($user, '35240413305697000150550000000404041953940212');
    alertasXmlItem($notaB, ['codigo_item' => 'B']);

    $resumo = $this->service->resumo($user->id);

    expect($resumo['sem_catalogo'])->toBe(1)
        ->and($resumo['ncm_divergente'])->toBe(1)
        ->and($resumo['unidade_divergente'])->toBe(0)
        ->and($resumo['aliquota_incompativel'])->toBe(0);
});
