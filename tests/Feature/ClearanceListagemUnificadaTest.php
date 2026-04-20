<?php

use App\Models\Cliente;
use App\Models\EfdImportacao;
use App\Models\EfdNota;
use App\Models\Participante;
use App\Models\User;
use App\Models\XmlImportacao;
use App\Models\XmlNota;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

function clearanceMakeUser(): User
{
    return User::factory()->create();
}

function clearanceClientePropria(User $u): Cliente
{
    return Cliente::firstOrCreate(
        ['user_id' => $u->id, 'is_empresa_propria' => true],
        [
            'tipo_pessoa' => 'PJ',
            'documento' => '00000000000191',
            'razao_social' => 'Empresa Propria',
        ]
    );
}

function clearanceMakeEfdNota(User $u, array $overrides = []): EfdNota
{
    $cliente = clearanceClientePropria($u);

    $imp = EfdImportacao::create([
        'user_id' => $u->id,
        'cliente_id' => $cliente->id,
        'tipo_efd' => 'EFD ICMS/IPI',
        'status' => 'concluido',
    ]);

    $part = Participante::firstOrCreate(
        [
            'user_id' => $u->id,
            'documento' => $overrides['part_cnpj'] ?? '13305697000150',
        ],
        [
            'cliente_id' => $cliente->id,
            'razao_social' => $overrides['part_razao'] ?? 'Participante SPED',
        ]
    );

    unset($overrides['part_cnpj'], $overrides['part_razao']);

    return EfdNota::create(array_merge([
        'user_id' => $u->id,
        'cliente_id' => $cliente->id,
        'participante_id' => $part->id,
        'importacao_id' => $imp->id,
        'chave_acesso' => '35240413305697000150550000000404041953940992',
        'modelo' => '55',
        'numero' => 40404,
        'serie' => '0',
        'data_emissao' => '2026-01-15',
        'tipo_operacao' => 'entrada',
        'valor_total' => 161.00,
        'valor_desconto' => 0,
        'origem_arquivo' => 'fiscal',
        'metadados' => [],
    ], $overrides));
}

function clearanceMakeXmlNota(User $u, array $overrides = []): XmlNota
{
    $cliente = clearanceClientePropria($u);

    $imp = XmlImportacao::create([
        'user_id' => $u->id,
        'cliente_id' => $cliente->id,
        'status' => 'concluido',
        'tipo_documento' => 'NFE',
    ]);

    return XmlNota::create(array_merge([
        'user_id' => $u->id,
        'importacao_xml_id' => $imp->id,
        'cliente_id' => $cliente->id,
        'nfe_id' => '35240413305697000150550000000404041953940993',
        'tipo_documento' => 'NFE',
        'numero_nota' => 12345,
        'serie' => 1,
        'data_emissao' => '2026-01-10 10:00:00',
        'valor_total' => 999.99,
        'tipo_nota' => XmlNota::TIPO_SAIDA,
        'emit_cnpj' => '00000000000191',
        'emit_razao_social' => 'Empresa Propria',
        'dest_cnpj' => '13305697000150',
        'dest_razao_social' => 'Destinatario XYZ',
    ], $overrides));
}

it('retorna grade vazia com mensagem de escopo quando o usuario nao tem notas', function () {
    $u = clearanceMakeUser();

    actingAs($u)
        ->get('/app/validacao/notas')
        ->assertOk()
        ->assertSee('Nenhuma nota');
});

it('lista notas EFD quando o usuario nao possui XML', function () {
    $u = clearanceMakeUser();
    clearanceMakeEfdNota($u, ['numero' => 40404]);

    actingAs($u)
        ->get('/app/validacao/notas')
        ->assertOk()
        ->assertSee('40404')
        ->assertSee('EFD');
});

it('deduplica linhas pela chave de acesso preferindo XML sobre EFD', function () {
    $u = clearanceMakeUser();
    $chave = '35240413305697000150550000000404041953940992';

    clearanceMakeEfdNota($u, ['chave_acesso' => $chave, 'numero' => 40404]);
    clearanceMakeXmlNota($u, ['nfe_id' => $chave, 'numero_nota' => 77777]);

    $response = actingAs($u)->get('/app/validacao/notas')->assertOk();

    $response->assertSee('77777');
    $response->assertDontSee('40404');
});

it('filtra por periodo em linhas XML e EFD', function () {
    $u = clearanceMakeUser();
    clearanceMakeEfdNota($u, ['data_emissao' => '2026-01-15', 'numero' => 1111, 'chave_acesso' => str_repeat('1', 44)]);
    clearanceMakeEfdNota($u, ['data_emissao' => '2026-03-15', 'numero' => 2222, 'chave_acesso' => str_repeat('2', 44)]);
    clearanceMakeXmlNota($u, ['data_emissao' => '2026-01-20 12:00:00', 'numero_nota' => 3333, 'nfe_id' => str_repeat('3', 44)]);
    clearanceMakeXmlNota($u, ['data_emissao' => '2026-03-20 12:00:00', 'numero_nota' => 4444, 'nfe_id' => str_repeat('4', 44)]);

    $response = actingAs($u)
        ->get('/app/validacao/notas?periodo_de=2026-01-01&periodo_ate=2026-01-31')
        ->assertOk();

    $response->assertSee('1111');
    $response->assertSee('3333');
    $response->assertDontSee('2222');
    $response->assertDontSee('4444');
});

it('filtra por CNPJ de participante em linhas XML (emit/dest) e EFD (join)', function () {
    $u = clearanceMakeUser();

    clearanceMakeEfdNota($u, [
        'chave_acesso' => str_repeat('1', 44),
        'numero' => 1111,
        'part_cnpj' => '11111111000191',
        'part_razao' => 'Fornecedor A',
    ]);
    clearanceMakeEfdNota($u, [
        'chave_acesso' => str_repeat('2', 44),
        'numero' => 2222,
        'part_cnpj' => '22222222000192',
        'part_razao' => 'Fornecedor B',
    ]);
    clearanceMakeXmlNota($u, [
        'nfe_id' => str_repeat('3', 44),
        'numero_nota' => 3333,
        'emit_cnpj' => '11111111000191',
    ]);

    $response = actingAs($u)
        ->get('/app/validacao/notas?participante_cnpj=11111111000191')
        ->assertOk();

    $response->assertSee('1111');
    $response->assertSee('3333');
    $response->assertDontSee('2222');
});

it('filtra por tipo_nota mapeando corretamente entre XML e EFD', function () {
    $u = clearanceMakeUser();

    clearanceMakeEfdNota($u, ['chave_acesso' => str_repeat('1', 44), 'numero' => 1111, 'tipo_operacao' => 'entrada']);
    clearanceMakeEfdNota($u, ['chave_acesso' => str_repeat('2', 44), 'numero' => 2222, 'tipo_operacao' => 'saida']);
    clearanceMakeXmlNota($u, ['nfe_id' => str_repeat('3', 44), 'numero_nota' => 3333, 'tipo_nota' => XmlNota::TIPO_ENTRADA]);
    clearanceMakeXmlNota($u, ['nfe_id' => str_repeat('4', 44), 'numero_nota' => 4444, 'tipo_nota' => XmlNota::TIPO_SAIDA]);

    $response = actingAs($u)
        ->get('/app/validacao/notas?tipo_nota=entrada')
        ->assertOk();

    $response->assertSee('1111');
    $response->assertSee('3333');
    $response->assertDontSee('2222');
    $response->assertDontSee('4444');
});

it('status=validadas retorna apenas XML com validacao preenchida', function () {
    $u = clearanceMakeUser();

    clearanceMakeEfdNota($u, ['chave_acesso' => str_repeat('1', 44), 'numero' => 1111]);
    clearanceMakeXmlNota($u, [
        'nfe_id' => str_repeat('3', 44),
        'numero_nota' => 3333,
        'validacao' => ['classificacao' => 'conforme', 'alertas' => []],
    ]);
    clearanceMakeXmlNota($u, ['nfe_id' => str_repeat('4', 44), 'numero_nota' => 4444, 'validacao' => null]);

    $response = actingAs($u)
        ->get('/app/validacao/notas?status_validacao=validadas')
        ->assertOk();

    $response->assertSee('3333');
    $response->assertDontSee('1111');
    $response->assertDontSee('4444');
});

it('status=nao_validadas inclui todas as EFD e XML sem validacao', function () {
    $u = clearanceMakeUser();

    clearanceMakeEfdNota($u, ['chave_acesso' => str_repeat('1', 44), 'numero' => 1111]);
    clearanceMakeXmlNota($u, [
        'nfe_id' => str_repeat('3', 44),
        'numero_nota' => 3333,
        'validacao' => ['classificacao' => 'conforme', 'alertas' => []],
    ]);
    clearanceMakeXmlNota($u, ['nfe_id' => str_repeat('4', 44), 'numero_nota' => 4444, 'validacao' => null]);

    $response = actingAs($u)
        ->get('/app/validacao/notas?status_validacao=nao_validadas')
        ->assertOk();

    $response->assertSee('1111');
    $response->assertSee('4444');
    $response->assertDontSee('3333');
});

it('todosIds retorna ids totais com mapa de origens respeitando dedupe por chave', function () {
    $u = clearanceMakeUser();

    // Avanca o sequence de xml_notas para evitar colisao de id com efd_notas (id colidido
    // nao consegue ser representado no mapa origens, limitacao conhecida do contrato).
    $cliente = clearanceClientePropria($u);
    $impSeed = XmlImportacao::create([
        'user_id' => $u->id,
        'cliente_id' => $cliente->id,
        'status' => 'concluido',
        'tipo_documento' => 'NFE',
    ]);
    DB::table('xml_notas')->insert([
        'user_id' => $u->id,
        'importacao_xml_id' => $impSeed->id,
        'nfe_id' => str_repeat('9', 44),
        'tipo_documento' => 'NFE',
        'numero_nota' => 0,
        'serie' => 1,
        'data_emissao' => '2026-01-01 00:00:00',
        'valor_total' => 0,
        'tipo_nota' => XmlNota::TIPO_SAIDA,
        'emit_cnpj' => '00000000000191',
        'dest_cnpj' => '00000000000000',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    DB::table('xml_notas')->where('nfe_id', str_repeat('9', 44))->delete();

    $efdUnico = clearanceMakeEfdNota($u, ['chave_acesso' => str_repeat('1', 44), 'numero' => 1111]);
    $efdDup = clearanceMakeEfdNota($u, ['chave_acesso' => str_repeat('2', 44), 'numero' => 2222]);
    $xml = clearanceMakeXmlNota($u, ['nfe_id' => str_repeat('3', 44), 'numero_nota' => 3333]);
    $xmlDup = clearanceMakeXmlNota($u, ['nfe_id' => str_repeat('2', 44), 'numero_nota' => 4444]);

    $response = actingAs($u)
        ->get('/app/validacao/notas/todos-ids')
        ->assertOk()
        ->assertJson(['success' => true]);

    $payload = $response->json();

    expect($payload['ids'])->toEqualCanonicalizing([$xml->id, $xmlDup->id, $efdUnico->id]);
    expect($payload['total'])->toBe(3);

    $origens = (array) $payload['origens'];
    expect($origens[$xml->id])->toBe('xml');
    expect($origens[$xmlDup->id])->toBe('xml');
    expect($origens[$efdUnico->id])->toBe('efd');
});
