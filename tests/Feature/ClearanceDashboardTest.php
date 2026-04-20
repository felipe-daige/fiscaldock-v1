<?php

use App\Models\Cliente;
use App\Models\EfdImportacao;
use App\Models\EfdNota;
use App\Models\Participante;
use App\Models\User;
use App\Models\XmlImportacao;
use App\Models\XmlNota;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

function dashUser(): User
{
    return User::factory()->create();
}

function dashClientePropria(User $u): Cliente
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

function dashMakeXmlNota(User $u, array $overrides = []): XmlNota
{
    $cliente = dashClientePropria($u);

    $imp = XmlImportacao::firstOrCreate(
        ['user_id' => $u->id, 'cliente_id' => $cliente->id, 'tipo_documento' => 'NFE', 'status' => 'concluido']
    );

    return XmlNota::create(array_merge([
        'user_id' => $u->id,
        'importacao_xml_id' => $imp->id,
        'cliente_id' => $cliente->id,
        'nfe_id' => str_repeat('1', 44),
        'tipo_documento' => 'NFE',
        'numero_nota' => 1234,
        'serie' => 1,
        'data_emissao' => '2026-01-10 10:00:00',
        'valor_total' => 100.00,
        'tipo_nota' => XmlNota::TIPO_SAIDA,
        'emit_cnpj' => '00000000000191',
        'emit_razao_social' => 'Empresa Propria',
        'dest_cnpj' => '13305697000150',
        'dest_razao_social' => 'Destinatario Teste',
    ], $overrides));
}

function dashMakeEfdNota(User $u, array $overrides = []): EfdNota
{
    $cliente = dashClientePropria($u);

    $imp = EfdImportacao::create([
        'user_id' => $u->id,
        'cliente_id' => $cliente->id,
        'tipo_efd' => 'EFD ICMS/IPI',
        'status' => 'concluido',
    ]);

    $part = Participante::firstOrCreate(
        ['user_id' => $u->id, 'documento' => '13305697000150'],
        ['cliente_id' => $cliente->id, 'razao_social' => 'Fornecedor EFD']
    );

    return EfdNota::create(array_merge([
        'user_id' => $u->id,
        'cliente_id' => $cliente->id,
        'participante_id' => $part->id,
        'importacao_id' => $imp->id,
        'chave_acesso' => str_repeat('2', 44),
        'modelo' => '55',
        'numero' => 4040,
        'serie' => '0',
        'data_emissao' => '2026-01-15',
        'tipo_operacao' => 'entrada',
        'valor_total' => 200.00,
        'valor_desconto' => 0,
        'origem_arquivo' => 'fiscal',
        'metadados' => [],
    ], $overrides));
}

it('mostra zerado para usuario sem notas', function () {
    $u = dashUser();

    $response = actingAs($u)->get('/app/validacao')->assertOk();

    $response->assertSee('Total DF-e');
    $response->assertSee('Nenhuma verificação ainda');
});

it('agrega XML+EFD deduplicando por chave de acesso', function () {
    $u = dashUser();
    $chave = str_repeat('1', 44);

    dashMakeXmlNota($u, [
        'nfe_id' => $chave,
        'validacao' => ['situacao' => 'AUTORIZADA', 'consultado_em' => '2026-04-17T10:00:00Z', 'fonte' => 'infosimples/receita-federal/nfe'],
    ]);
    dashMakeEfdNota($u, ['chave_acesso' => $chave]);

    $service = app(\App\Services\ValidacaoContabilService::class);
    $kpis = $service->getKpisStatusReceita($u->id);

    expect($kpis['total'])->toBe(1);
    expect($kpis['autorizadas'])->toBe(1);
    expect($kpis['nao_verificadas'])->toBe(0);
});

it('conta separadamente quando chaves diferem', function () {
    $u = dashUser();

    dashMakeXmlNota($u, [
        'nfe_id' => str_repeat('1', 44),
        'validacao' => ['situacao' => 'CANCELADA', 'consultado_em' => '2026-04-17T10:00:00Z'],
    ]);
    dashMakeEfdNota($u, [
        'chave_acesso' => str_repeat('2', 44),
        'validacao' => ['situacao' => 'AUTORIZADA', 'consultado_em' => '2026-04-17T11:00:00Z'],
    ]);

    $service = app(\App\Services\ValidacaoContabilService::class);
    $kpis = $service->getKpisStatusReceita($u->id);

    expect($kpis['total'])->toBe(2);
    expect($kpis['canceladas'])->toBe(1);
    expect($kpis['autorizadas'])->toBe(1);
});

it('classifica como nao_verificadas notas sem situacao', function () {
    $u = dashUser();

    dashMakeXmlNota($u, ['nfe_id' => str_repeat('1', 44), 'validacao' => null]);
    dashMakeXmlNota($u, [
        'nfe_id' => str_repeat('2', 44),
        'validacao' => ['classificacao' => 'conforme', 'score_total' => 95, 'alertas' => []],
    ]);

    $service = app(\App\Services\ValidacaoContabilService::class);
    $kpis = $service->getKpisStatusReceita($u->id);

    expect($kpis['total'])->toBe(2);
    expect($kpis['nao_verificadas'])->toBe(2);
    expect($kpis['verificadas'])->toBe(0);
});

it('lista notas bloqueantes ordenadas por consultado_em desc', function () {
    $u = dashUser();

    dashMakeXmlNota($u, [
        'nfe_id' => str_repeat('1', 44),
        'numero_nota' => 1111,
        'validacao' => ['situacao' => 'CANCELADA', 'consultado_em' => '2026-04-15T10:00:00Z'],
    ]);
    dashMakeXmlNota($u, [
        'nfe_id' => str_repeat('2', 44),
        'numero_nota' => 2222,
        'validacao' => ['situacao' => 'DENEGADA', 'consultado_em' => '2026-04-17T10:00:00Z'],
    ]);
    dashMakeXmlNota($u, [
        'nfe_id' => str_repeat('3', 44),
        'numero_nota' => 3333,
        'validacao' => ['situacao' => 'AUTORIZADA', 'consultado_em' => '2026-04-16T10:00:00Z'],
    ]);

    $service = app(\App\Services\ValidacaoContabilService::class);
    $bloqueantes = $service->getNotasComSituacaoBloqueante($u->id, 5);

    expect($bloqueantes)->toHaveCount(2);
    expect($bloqueantes[0]['numero'])->toBe('2222'); // mais recente
    expect($bloqueantes[1]['numero'])->toBe('1111');
});

it('filtra listagem por situacao_receita', function () {
    $u = dashUser();

    dashMakeXmlNota($u, [
        'nfe_id' => str_repeat('1', 44),
        'numero_nota' => 1111,
        'validacao' => ['situacao' => 'CANCELADA', 'consultado_em' => '2026-04-15T10:00:00Z'],
    ]);
    dashMakeXmlNota($u, [
        'nfe_id' => str_repeat('2', 44),
        'numero_nota' => 2222,
        'validacao' => ['situacao' => 'AUTORIZADA', 'consultado_em' => '2026-04-16T10:00:00Z'],
    ]);

    $response = actingAs($u)->get('/app/validacao/notas?situacao_receita=CANCELADA')->assertOk();

    $response->assertSee('1111');
    $response->assertDontSee('2222');
});

it('filtra listagem por status_validacao=sem_situacao_receita', function () {
    $u = dashUser();

    dashMakeXmlNota($u, ['nfe_id' => str_repeat('1', 44), 'numero_nota' => 1111, 'validacao' => null]);
    dashMakeXmlNota($u, [
        'nfe_id' => str_repeat('2', 44),
        'numero_nota' => 2222,
        'validacao' => ['situacao' => 'AUTORIZADA', 'consultado_em' => '2026-04-16T10:00:00Z'],
    ]);

    $response = actingAs($u)->get('/app/validacao/notas?status_validacao=sem_situacao_receita')->assertOk();

    $response->assertSee('1111');
    $response->assertDontSee('2222');
});
