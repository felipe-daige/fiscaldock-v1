<?php

use App\Models\Cliente;
use App\Models\EfdImportacao;
use App\Models\EfdNota;
use App\Models\EfdNotaItem;
use App\Models\Participante;
use App\Models\User;
use App\Models\XmlImportacao;
use App\Models\XmlNota;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Clearance SEFAZ agora roda no Laravel; o foco destes testes é a validação contábil
    // local (popula validacao). Fakeamos o batch p/ não disparar consulta externa.
    Bus::fake();
    Http::fake();
});

function validarEfdMakeUser(): User
{
    return User::factory()->trialAtivo()->create(['credits' => 1000]);
}

function validarEfdClientePropria(User $u): Cliente
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

function validarEfdMakeNota(User $u, array $overrides = []): EfdNota
{
    $cliente = validarEfdClientePropria($u);

    $imp = EfdImportacao::create([
        'user_id' => $u->id,
        'cliente_id' => $cliente->id,
        'tipo_efd' => 'EFD ICMS/IPI',
        'status' => 'concluido',
    ]);

    $part = Participante::firstOrCreate(
        ['user_id' => $u->id, 'documento' => $overrides['part_cnpj'] ?? '13305697000150'],
        ['cliente_id' => $cliente->id, 'razao_social' => 'Fornecedor EFD']
    );

    unset($overrides['part_cnpj']);

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
        'valor_total' => 1000.00,
        'valor_desconto' => 0,
        'origem_arquivo' => 'fiscal',
        'metadados' => [],
    ], $overrides));
}

function validarEfdMakeItem(EfdNota $nota, array $overrides = []): EfdNotaItem
{
    return EfdNotaItem::create(array_merge([
        'efd_nota_id' => $nota->id,
        'user_id' => $nota->user_id,
        'numero_item' => 1,
        'codigo_item' => 'SKU001',
        'descricao' => 'Item teste',
        'valor_total' => 1000.00,
        'cfop' => 5102,
        'cst_icms' => '00',
        'aliquota_icms' => 18.0,
    ], $overrides));
}

it('valida uma EfdNota e persiste resultado com alerta bloqueante de CFOP inconsistente', function () {
    $u = validarEfdMakeUser();
    $nota = validarEfdMakeNota($u, ['tipo_operacao' => 'entrada']);
    validarEfdMakeItem($nota, ['cfop' => 5102]); // CFOP saida em nota de entrada

    $response = actingAs($u)
        ->postJson('/app/clearance/notas/validar', [
            'nota_ids' => [$nota->id],
            'origens' => [$nota->id => 'efd'],
            'tipo' => 'basico',
        ]);

    $response->assertOk();

    $nota->refresh();
    expect($nota->validacao)->not->toBeNull();
    expect($nota->validacao['alertas'])->toBeArray();

    $codigos = collect($nota->validacao['alertas'])->pluck('codigo')->all();
    expect($codigos)->toContain('CFOP_TIPO_INCONSISTENTE');
    expect($nota->validacao['classificacao'])->not->toBe('conforme');
});

it('aceita seleção misturada XML + EFD e persiste validacao nas duas tabelas', function () {
    $u = validarEfdMakeUser();
    $cliente = validarEfdClientePropria($u);

    $efd = validarEfdMakeNota($u, [
        'chave_acesso' => str_repeat('1', 44),
        'tipo_operacao' => 'entrada',
    ]);
    validarEfdMakeItem($efd, ['cfop' => 1102]); // consistente

    // Avanca sequence de xml_notas pra evitar colisao de id com efd_notas (id colidido
    // nao consegue ser representado no mapa origens).
    DB::statement("SELECT setval(pg_get_serial_sequence('xml_notas','id'), GREATEST(nextval(pg_get_serial_sequence('xml_notas','id')), (SELECT COALESCE(MAX(id),0) FROM efd_notas) + 1))");

    $xmlImp = XmlImportacao::create([
        'user_id' => $u->id,
        'cliente_id' => $cliente->id,
        'status' => 'concluido',
        'tipo_documento' => 'NFE',
    ]);

    $xml = XmlNota::create([
        'user_id' => $u->id,
        'importacao_xml_id' => $xmlImp->id,
        'cliente_id' => $cliente->id,
        'chave_acesso' => str_repeat('2', 44),
        'tipo_documento' => 'NFE',
        'numero_documento' => 222,
        'serie' => 1,
        'data_emissao' => '2026-01-20 10:00:00',
        'valor_total' => 500.00,
        'tipo_nota' => XmlNota::TIPO_SAIDA,
        'emit_documento' => '00000000000191',
        'emit_razao_social' => 'Empresa Propria',
        'dest_documento' => '13305697000150',
        'dest_razao_social' => 'Cliente Saida',
        'payload' => ['emit' => ['CRT' => 3], 'det' => [], 'total' => ['ICMSTot' => []]],
    ]);

    $response = actingAs($u)
        ->postJson('/app/clearance/notas/validar', [
            'nota_ids' => [$efd->id, $xml->id],
            'origens' => [$efd->id => 'efd', $xml->id => 'xml'],
            'tipo' => 'basico',
        ]);

    $response->assertOk();

    $efd->refresh();
    $xml->refresh();
    expect($efd->validacao)->not->toBeNull();
    expect($xml->validacao)->not->toBeNull();
});
