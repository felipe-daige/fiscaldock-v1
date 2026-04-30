<?php

use App\Models\Cliente;
use App\Models\Participante;
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
    $r->assertSee('Sem snapshot SEFAZ');
    $r->assertSee('Incluir em lote de clearance');
});

it('D3 — busca avulsa órfã renderiza com declaradoAusente', function () use (&$testUserIds) {
    $user = compararCriarUser($testUserIds);
    $chave = '35202404123456789012555000001234567890123470';

    XmlNota::create([
        'user_id' => $user->id, 'nfe_id' => $chave, 'origem' => 'busca_avulsa',
        'tipo_documento' => 'NFE', 'numero_nota' => 1234, 'serie' => 1,
        'data_emissao' => '2026-04-12 10:00:00', 'valor_total' => 1000.00,
        'tipo_nota' => XmlNota::TIPO_SAIDA,
        'situacao_sefaz' => 'AUTORIZADA', 'verificado_sefaz_em' => '2026-04-12 14:32:00',
        'emit_cnpj' => '12345678000190', 'emit_razao_social' => 'ACME',
        'dest_cnpj' => '98765432000110',
        'payload' => [
            'nfe_clearance' => [
                'status' => 'AUTORIZADA', 'numero' => '1234', 'modelo' => '55',
                'totais' => ['normalizado_valor_nfe' => 1000],
            ],
        ],
    ]);

    actingAs($user);
    $r = $this->get("/app/clearance/nota/{$chave}/comparar");

    $r->assertStatus(200);
    $r->assertSee('Sem nota declarada');
    $r->assertSee('Importar XML desta chave');
});

it('D4 — só EFD declarado funciona como fallback', function () use (&$testUserIds) {
    $user = compararCriarUser($testUserIds);
    $chave = '35202404123456789012555000001234567890123471';

    $cliente = Cliente::create(['user_id' => $user->id, 'tipo_pessoa' => 'PJ', 'documento' => '12345678000190', 'razao_social' => 'ACME']);
    $part = Participante::create(['user_id' => $user->id, 'documento' => '98765432000110', 'tipo_documento' => 'CNPJ', 'razao_social' => 'XYZ']);
    $impId = DB::table('efd_importacoes')->insertGetId([
        'user_id' => $user->id, 'tipo_efd' => 'EFD ICMS/IPI', 'filename' => 't.txt',
        'status' => 'ok', 'created_at' => now(), 'updated_at' => now(),
    ]);
    DB::table('efd_notas')->insert([
        'user_id' => $user->id, 'cliente_id' => $cliente->id, 'participante_id' => $part->id,
        'importacao_id' => $impId, 'chave_acesso' => $chave,
        'modelo' => '55', 'numero' => 1234, 'serie' => '1',
        'data_emissao' => '2026-04-12', 'tipo_operacao' => 'saida',
        'origem_arquivo' => 'fiscal', 'valor_total' => 1000,
        'created_at' => now(), 'updated_at' => now(),
    ]);
    XmlNota::create([
        'user_id' => $user->id, 'nfe_id' => $chave, 'origem' => 'busca_avulsa',
        'tipo_documento' => 'NFE', 'numero_nota' => 1234, 'serie' => 1,
        'data_emissao' => '2026-04-12 10:00:00', 'valor_total' => 1000.00,
        'tipo_nota' => XmlNota::TIPO_SAIDA,
        'situacao_sefaz' => 'AUTORIZADA',
        'emit_cnpj' => '12345678000190', 'dest_cnpj' => '98765432000110',
        'payload' => [
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
    $r->assertSee('EFD');
});

it('CT-e renderiza componentes do frete', function () use (&$testUserIds) {
    $user = compararCriarUser($testUserIds);
    $chave = '35202404123456789012575000001234567890123456';

    XmlNota::create([
        'user_id' => $user->id, 'nfe_id' => $chave, 'origem' => 'busca_avulsa',
        'tipo_documento' => 'CTE', 'numero_nota' => 5678, 'serie' => 1,
        'data_emissao' => '2026-04-12 10:00:00', 'valor_total' => 500.00,
        'tipo_nota' => XmlNota::TIPO_SAIDA,
        'situacao_sefaz' => 'AUTORIZADA',
        'emit_cnpj' => '11111111000111', 'dest_cnpj' => '22222222000122',
        'payload' => [
            'cte_clearance' => [
                'status' => 'AUTORIZADA', 'numero' => '5678', 'modelo' => '57',
                'valor_prestacao' => 500.00,
                'componentes' => [
                    ['nome' => 'FRETE PESO', 'valor' => '400'],
                    ['nome' => 'PEDAGIO', 'valor' => '100'],
                ],
                'emitente' => ['cnpj' => '11111111000111', 'nome' => 'TRANSP'],
                'tomador' => ['cnpj' => '22222222000122', 'nome' => 'TOMADOR'],
            ],
        ],
    ]);

    actingAs($user);
    $r = $this->get("/app/clearance/nota/{$chave}/comparar");

    $r->assertStatus(200);
    $r->assertSee('Componentes do frete');
    $r->assertSee('FRETE PESO');
    $r->assertSee('PEDAGIO');
});
