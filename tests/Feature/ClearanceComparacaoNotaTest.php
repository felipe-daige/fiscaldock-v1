<?php

use App\Models\User;
use App\Models\XmlNota;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;

$testUserIds = [];

beforeEach(function () use (&$testUserIds) {
    $testUserIds = [];
    config([
        'database.default' => 'pgsql',
        'database.connections.pgsql.host' => env('DB_HOST', 'postgres'),
        'database.connections.pgsql.port' => env('DB_PORT', 5432),
        'database.connections.pgsql.database' => 'fiscaldock_test',
        'database.connections.pgsql.username' => env('DB_USERNAME', 'postgres'),
        'database.connections.pgsql.password' => env('DB_PASSWORD', 'fdpCjI5U7KvpBdWjVLzzAEs2q5NOeGRu'),
        'database.connections.pgsql.schema' => 'public',
    ]);
    DB::purge('pgsql');
    DB::reconnect('pgsql');
});

afterEach(function () use (&$testUserIds) {
    if (! empty($testUserIds)) {
        User::whereIn('id', $testUserIds)->delete();
    }
});

function compararCriarUser(array &$ids): User
{
    $user = User::factory()->create();
    $ids[] = $user->id;

    return $user;
}

it('rota app.clearance.nota.comparar exige autenticacao', function () {
    $r = $this->get('/app/clearance/nota/35202404123456789012555000001234567890123456/comparar');
    expect($r->status())->toBeIn([302, 401]);
});

it('retorna 404 quando nao existe nota nem snapshot', function () use (&$testUserIds) {
    $user = compararCriarUser($testUserIds);
    actingAs($user);

    $r = $this->get('/app/clearance/nota/35202404123456789012555000001234567890123456/comparar');

    $r->assertStatus(404);
});

it('retorna 404 quando chave tem modelo nao suportado', function () use (&$testUserIds) {
    $user = compararCriarUser($testUserIds);
    actingAs($user);

    $r = $this->get('/app/clearance/nota/35202404123456789012885000001234567890123456/comparar');

    $r->assertStatus(404);
});

it('rota rejeita chave com formato invalido por route constraint', function () use (&$testUserIds) {
    $user = compararCriarUser($testUserIds);
    actingAs($user);

    $r = $this->get('/app/clearance/nota/abc/comparar');

    $r->assertStatus(404);
});

it('renderiza view com declarado XML + sefaz vindos da MESMA linha xml_notas', function () use (&$testUserIds) {
    $user = compararCriarUser($testUserIds);
    $chave = '35202404123456789012555000001234567890123456';

    XmlNota::create([
        'user_id' => $user->id, 'nfe_id' => $chave, 'origem' => 'xml_upload',
        'tipo_documento' => 'NFE', 'numero_nota' => 1234, 'serie' => 1,
        'data_emissao' => '2026-04-12 10:00:00', 'valor_total' => 1000.00,
        'tipo_nota' => XmlNota::TIPO_SAIDA,
        'emit_cnpj' => '12345678000190', 'emit_razao_social' => 'ACME',
        'dest_cnpj' => '98765432000110', 'dest_razao_social' => 'XYZ',
        'situacao_sefaz' => 'AUTORIZADA',
        'payload' => [
            'det' => [['nItem' => '1', 'prod' => ['cProd' => 'A', 'vProd' => '1000']]],
            'ide' => ['mod' => '55', 'natOp' => 'Venda'],
            'total' => ['ICMSTot' => ['vBC' => '1000']],
            'nfe_clearance' => [
                'status' => 'AUTORIZADA', 'numero' => '1234', 'modelo' => '55',
                'totais' => ['normalizado_valor_nfe' => 1000],
            ],
        ],
    ]);

    actingAs($user);
    $r = $this->get("/app/clearance/nota/{$chave}/comparar");

    $r->assertStatus(200);
    $r->assertSee('Comparar declarado vs SEFAZ');
    $r->assertSee($chave);
});

it('renderiza view só com declarado quando nao ha snapshot SEFAZ', function () use (&$testUserIds) {
    $user = compararCriarUser($testUserIds);
    $chave = '35202404123456789012555000001234567890123457';

    XmlNota::create([
        'user_id' => $user->id, 'nfe_id' => $chave, 'origem' => 'xml_upload',
        'tipo_documento' => 'NFE', 'numero_nota' => 99, 'serie' => 1,
        'data_emissao' => '2026-04-12 10:00:00', 'valor_total' => 50.00,
        'tipo_nota' => XmlNota::TIPO_SAIDA,
        'emit_cnpj' => '12345678000190', 'dest_cnpj' => '98765432000110',
    ]);

    actingAs($user);
    $r = $this->get("/app/clearance/nota/{$chave}/comparar");

    $r->assertStatus(200);
});
