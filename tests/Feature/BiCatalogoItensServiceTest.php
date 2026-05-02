<?php

use App\Models\Cliente;
use App\Models\EfdCatalogoItem;
use App\Models\EfdImportacao;
use App\Models\EfdNota;
use App\Models\EfdNotaItem;
use App\Models\User;
use App\Models\XmlNota;
use App\Models\XmlNotaItem;
use App\Services\Catalogo\BiCatalogoItensService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function biCliente(User $user, string $documento = '00000000000191'): Cliente
{
    return Cliente::firstOrCreate(
        ['user_id' => $user->id, 'documento' => $documento],
        ['tipo_pessoa' => 'PJ', 'razao_social' => 'Empresa', 'is_empresa_propria' => true]
    );
}

function biImp(User $user, Cliente $cliente): EfdImportacao
{
    return EfdImportacao::create([
        'user_id' => $user->id,
        'cliente_id' => $cliente->id,
        'tipo_efd' => 'EFD ICMS/IPI',
        'status' => 'concluido',
    ]);
}

function biEfdNota(User $user, Cliente $cliente, EfdImportacao $imp, string $chave, array $overrides = []): EfdNota
{
    return EfdNota::create(array_merge([
        'user_id' => $user->id,
        'cliente_id' => $cliente->id,
        'importacao_id' => $imp->id,
        'chave_acesso' => $chave,
        'modelo' => '55',
        'numero' => (int) substr($chave, -8),
        'serie' => '1',
        'data_emissao' => '2026-04-15',
        'tipo_operacao' => 'entrada',
        'valor_total' => 1000,
        'valor_desconto' => 0,
        'metadados' => [],
    ], $overrides));
}

function biXmlNota(User $user, string $chave, array $overrides = []): XmlNota
{
    return XmlNota::create(array_merge([
        'user_id' => $user->id,
        'nfe_id' => $chave,
        'tipo_documento' => 'NFE',
        'tipo_nota' => 0, // entrada
        'origem' => 'xml_upload',
        'numero_nota' => (int) substr($chave, -8),
        'serie' => 1,
        'data_emissao' => '2026-04-15',
        'valor_total' => 500,
        'emit_cnpj' => '13305697000150',
        'dest_cnpj' => '00000000000191',
        'payload' => [],
    ], $overrides));
}

beforeEach(function () {
    $this->service = app(BiCatalogoItensService::class);
});

it('topNcms ordena por valor descendente', function () {
    $user = User::factory()->create();
    $nota = biXmlNota($user, '35240413305697000150550000000404041953940501');

    XmlNotaItem::create([
        'xml_nota_id' => $nota->id, 'user_id' => $user->id,
        'numero_item' => 1, 'codigo_item' => 'A', 'descricao' => 'A', 'cfop' => '5102',
        'quantidade' => 1, 'valor_total' => 100, 'ncm' => '11111111',
    ]);
    XmlNotaItem::create([
        'xml_nota_id' => $nota->id, 'user_id' => $user->id,
        'numero_item' => 2, 'codigo_item' => 'B', 'descricao' => 'B', 'cfop' => '5102',
        'quantidade' => 1, 'valor_total' => 500, 'ncm' => '22222222',
    ]);
    XmlNotaItem::create([
        'xml_nota_id' => $nota->id, 'user_id' => $user->id,
        'numero_item' => 3, 'codigo_item' => 'C', 'descricao' => 'C', 'cfop' => '5102',
        'quantidade' => 1, 'valor_total' => 300, 'ncm' => '33333333',
    ]);

    $top = $this->service->topNcms($user->id);

    expect($top)->toHaveCount(3)
        ->and($top[0]['ncm'])->toBe('22222222')
        ->and($top[1]['ncm'])->toBe('33333333')
        ->and($top[2]['ncm'])->toBe('11111111')
        ->and($top[0]['percentual'])->toBe(55.56); // 500/900
});

it('topNcms aplica dedup XML×EFD (mesma chave não infla NCM)', function () {
    $user = User::factory()->create();
    $cliente = biCliente($user);
    $imp = biImp($user, $cliente);
    $chave = '35240413305697000150550000000404041953940502';

    EfdCatalogoItem::create([
        'user_id' => $user->id, 'cliente_id' => $cliente->id, 'importacao_id' => $imp->id,
        'cod_item' => 'PROD', 'descr_item' => 'P', 'tipo_item' => '00',
        'cod_ncm' => '84713012',
    ]);

    $efd = biEfdNota($user, $cliente, $imp, $chave);
    EfdNotaItem::create([
        'efd_nota_id' => $efd->id, 'user_id' => $user->id,
        'numero_item' => 1, 'codigo_item' => 'PROD', 'descricao' => 'P',
        'quantidade' => 1, 'valor_total' => 1000, 'cfop' => 5102,
    ]);

    $xml = biXmlNota($user, $chave);
    XmlNotaItem::create([
        'xml_nota_id' => $xml->id, 'user_id' => $user->id,
        'numero_item' => 1, 'codigo_item' => 'PROD', 'descricao' => 'P', 'cfop' => '5102',
        'quantidade' => 1, 'valor_total' => 1000, 'ncm' => '84713012',
    ]);

    $top = $this->service->topNcms($user->id);

    expect($top)->toHaveCount(1)
        ->and((float) $top[0]['valor_total'])->toBe(1000.0); // não 2000
});

it('cfopsPorNcm separa entrada e saída', function () {
    $user = User::factory()->create();

    // Entrada
    $nIn = biXmlNota($user, '35240413305697000150550000000404041953940503', ['tipo_nota' => 0]);
    XmlNotaItem::create([
        'xml_nota_id' => $nIn->id, 'user_id' => $user->id,
        'numero_item' => 1, 'codigo_item' => 'P', 'descricao' => 'P', 'cfop' => '1102',
        'quantidade' => 1, 'valor_total' => 100, 'ncm' => '84713012',
    ]);

    // Saída
    $nOut = biXmlNota($user, '35240413305697000150550000000404041953940504', ['tipo_nota' => 1]);
    XmlNotaItem::create([
        'xml_nota_id' => $nOut->id, 'user_id' => $user->id,
        'numero_item' => 1, 'codigo_item' => 'P', 'descricao' => 'P', 'cfop' => '5102',
        'quantidade' => 1, 'valor_total' => 200, 'ncm' => '84713012',
    ]);

    $resultado = $this->service->cfopsPorNcm($user->id);

    expect($resultado)->toHaveCount(1);
    $ncm = $resultado[0];
    expect($ncm['ncm'])->toBe('84713012')
        ->and($ncm['entradas'])->toHaveCount(1)
        ->and($ncm['entradas'][0]['cfop'])->toBe('1102')
        ->and($ncm['saidas'])->toHaveCount(1)
        ->and($ncm['saidas'][0]['cfop'])->toBe('5102');
});

it('dispersaoAliquota ignora itens com só uma alíquota', function () {
    $user = User::factory()->create();
    $nota = biXmlNota($user, '35240413305697000150550000000404041953940505');

    // Item A com alíquota constante
    XmlNotaItem::create([
        'xml_nota_id' => $nota->id, 'user_id' => $user->id,
        'numero_item' => 1, 'codigo_item' => 'CONSTANTE', 'descricao' => 'C', 'cfop' => '5102',
        'quantidade' => 1, 'valor_total' => 100, 'aliquota_icms' => 18.0,
    ]);
    XmlNotaItem::create([
        'xml_nota_id' => $nota->id, 'user_id' => $user->id,
        'numero_item' => 2, 'codigo_item' => 'CONSTANTE', 'descricao' => 'C', 'cfop' => '5102',
        'quantidade' => 1, 'valor_total' => 100, 'aliquota_icms' => 18.0,
    ]);

    // Item B com 2 alíquotas
    XmlNotaItem::create([
        'xml_nota_id' => $nota->id, 'user_id' => $user->id,
        'numero_item' => 3, 'codigo_item' => 'VARIAVEL', 'descricao' => 'V', 'cfop' => '5102',
        'quantidade' => 1, 'valor_total' => 100, 'aliquota_icms' => 4.0,
    ]);
    XmlNotaItem::create([
        'xml_nota_id' => $nota->id, 'user_id' => $user->id,
        'numero_item' => 4, 'codigo_item' => 'VARIAVEL', 'descricao' => 'V', 'cfop' => '5102',
        'quantidade' => 1, 'valor_total' => 100, 'aliquota_icms' => 18.0,
    ]);

    $disp = $this->service->dispersaoAliquota($user->id);

    expect($disp)->toHaveCount(1)
        ->and($disp[0]['codigo_item'])->toBe('VARIAVEL')
        ->and((float) $disp[0]['aliq_min'])->toBe(4.0)
        ->and((float) $disp[0]['aliq_max'])->toBe(18.0)
        ->and((float) $disp[0]['dispersao'])->toBe(14.0);
});

it('itensSaidaSemCatalogo só considera tipo_operacao saida', function () {
    $user = User::factory()->create();

    // Entrada sem catálogo — NÃO deve aparecer
    $nIn = biXmlNota($user, '35240413305697000150550000000404041953940506', ['tipo_nota' => 0]);
    XmlNotaItem::create([
        'xml_nota_id' => $nIn->id, 'user_id' => $user->id,
        'numero_item' => 1, 'codigo_item' => 'COMPRA-ZERO', 'descricao' => 'Compra', 'cfop' => '1102',
        'quantidade' => 1, 'valor_total' => 100,
    ]);

    // Saída sem catálogo — DEVE aparecer
    $nOut = biXmlNota($user, '35240413305697000150550000000404041953940507', ['tipo_nota' => 1]);
    XmlNotaItem::create([
        'xml_nota_id' => $nOut->id, 'user_id' => $user->id,
        'numero_item' => 1, 'codigo_item' => 'VENDA-ZERO', 'descricao' => 'Venda', 'cfop' => '5102',
        'quantidade' => 1, 'valor_total' => 500,
    ]);

    $resultado = $this->service->itensSaidaSemCatalogo($user->id);

    expect($resultado)->toHaveCount(1)
        ->and($resultado[0]['codigo_item'])->toBe('VENDA-ZERO')
        ->and((float) $resultado[0]['valor_total'])->toBe(500.0);
});

it('itensSaidaSemCatalogo exclui itens que TEM catálogo', function () {
    $user = User::factory()->create();
    $cliente = biCliente($user);
    $imp = biImp($user, $cliente);

    EfdCatalogoItem::create([
        'user_id' => $user->id, 'cliente_id' => $cliente->id, 'importacao_id' => $imp->id,
        'cod_item' => 'COM-CADASTRO', 'descr_item' => 'OK', 'tipo_item' => '00',
    ]);

    $nOut = biXmlNota($user, '35240413305697000150550000000404041953940508', ['tipo_nota' => 1]);
    XmlNotaItem::create([
        'xml_nota_id' => $nOut->id, 'user_id' => $user->id,
        'numero_item' => 1, 'codigo_item' => 'COM-CADASTRO', 'descricao' => 'OK', 'cfop' => '5102',
        'quantidade' => 1, 'valor_total' => 100,
    ]);

    expect($this->service->itensSaidaSemCatalogo($user->id))->toBeEmpty();
});

it('isolamento por user_id em todos os métodos', function () {
    $alice = User::factory()->create();
    $bob = User::factory()->create();

    $nota = biXmlNota($alice, '35240413305697000150550000000404041953940509');
    XmlNotaItem::create([
        'xml_nota_id' => $nota->id, 'user_id' => $alice->id,
        'numero_item' => 1, 'codigo_item' => 'A', 'descricao' => 'A', 'cfop' => '5102',
        'quantidade' => 1, 'valor_total' => 100, 'ncm' => '11111111', 'aliquota_icms' => 18.0,
    ]);

    expect($this->service->topNcms($bob->id))->toBeEmpty();
    expect($this->service->cfopsPorNcm($bob->id))->toBeEmpty();
    expect($this->service->dispersaoAliquota($bob->id))->toBeEmpty();
    expect($this->service->itensSaidaSemCatalogo($bob->id))->toBeEmpty();
});

it('topNcms respeita filtro de período', function () {
    $user = User::factory()->create();

    $nIn = biXmlNota($user, '35240413305697000150550000000404041953940510', ['data_emissao' => '2026-04-10']);
    XmlNotaItem::create([
        'xml_nota_id' => $nIn->id, 'user_id' => $user->id,
        'numero_item' => 1, 'codigo_item' => 'P', 'descricao' => 'P', 'cfop' => '5102',
        'quantidade' => 1, 'valor_total' => 100, 'ncm' => '84713012',
    ]);

    $nOut = biXmlNota($user, '35240413305697000150550000000404041953940511', ['data_emissao' => '2026-05-15']);
    XmlNotaItem::create([
        'xml_nota_id' => $nOut->id, 'user_id' => $user->id,
        'numero_item' => 1, 'codigo_item' => 'P', 'descricao' => 'P', 'cfop' => '5102',
        'quantidade' => 1, 'valor_total' => 9000, 'ncm' => '94054210',
    ]);

    $abril = $this->service->topNcms($user->id, filtros: ['data_inicio' => '2026-04-01', 'data_fim' => '2026-04-30']);

    expect($abril)->toHaveCount(1)
        ->and($abril[0]['ncm'])->toBe('84713012');
});
