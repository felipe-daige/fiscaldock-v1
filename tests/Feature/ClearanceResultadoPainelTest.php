<?php

use App\Models\Cliente;
use App\Models\ConsultaLote;
use App\Models\EfdImportacao;
use App\Models\EfdNota;
use App\Models\User;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;

// listarConsultasDfePorLote usa NULL::varchar (sintaxe PostgreSQL exclusiva).
// O phpunit.xml padroniza sqlite em memória; sobrescrevemos para pgsql (fiscaldock_test)
// em beforeEach — antes de qualquer acesso ao banco dentro dos testes.
// afterEach apaga o usuário de teste (cascade elimina todos os registros filhos).
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

function painelCriarUser(array &$ids): User
{
    $user = User::factory()->create();
    $ids[] = $user->id;

    return $user;
}

function criarSnapshotNfeBaseline(array $attrs): void
{
    DB::table('nfe_consultas')->insert(array_merge([
        'tipo_documento' => 'NFE',
        'modelo' => '55',
        'serie' => 1,
        'status' => 'AUTORIZADA',
        'consultado_em' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ], $attrs));
}

function criarClienteBaseline(int $userId): Cliente
{
    return Cliente::create([
        'user_id' => $userId,
        'tipo_pessoa' => 'PJ',
        'documento' => str_pad((string) random_int(1, 99_999_999_999_999), 14, '0', STR_PAD_LEFT),
        'razao_social' => 'Empresa Teste '.$userId,
        'is_empresa_propria' => true,
    ]);
}

it('renderiza painel Declarado vs SEFAZ com veredito crítico', function () use (&$testUserIds) {
    $user = painelCriarUser($testUserIds);
    $cliente = criarClienteBaseline($user->id);

    $lote = ConsultaLote::create([
        'user_id' => $user->id,
        'status' => 'finalizado',
        'total_participantes' => 2,
        'creditos_cobrados' => 20,
        'tab_id' => 'test-tab',
        'processado_em' => now(),
    ]);
    $importacao = EfdImportacao::create([
        'user_id' => $user->id,
        'cliente_id' => $cliente->id,
        'tipo_efd' => 'EFD ICMS/IPI',
        'status' => 'finalizada',
        'periodo_inicial' => '2024-02-01',
        'periodo_final' => '2024-02-29',
    ]);

    $chaveCritica = '50240246088921000159550010000017471100017471';
    $chaveMatch = '50240243648971004576550010001117201983139706';

    EfdNota::create([
        'user_id' => $user->id, 'cliente_id' => $cliente->id, 'importacao_id' => $importacao->id,
        'chave_acesso' => $chaveCritica, 'modelo' => '55', 'numero' => 1747, 'serie' => '1',
        'data_emissao' => '2024-02-29', 'tipo_operacao' => 'saida',
        'origem_arquivo' => 'fiscal', 'valor_total' => 250.00,
    ]);
    EfdNota::create([
        'user_id' => $user->id, 'cliente_id' => $cliente->id, 'importacao_id' => $importacao->id,
        'chave_acesso' => $chaveMatch, 'modelo' => '55', 'numero' => 111720, 'serie' => '1',
        'data_emissao' => '2024-02-29', 'tipo_operacao' => 'saida',
        'origem_arquivo' => 'fiscal', 'valor_total' => 4957.52,
    ]);

    criarSnapshotNfeBaseline([
        'user_id' => $user->id, 'consulta_lote_id' => $lote->id,
        'chave_acesso' => $chaveCritica, 'numero' => '1747',
        'valor_total' => 1135.00, 'emit_nome' => 'SOTRACTOR', 'emit_cnpj' => '46088921000159',
    ]);
    criarSnapshotNfeBaseline([
        'user_id' => $user->id, 'consulta_lote_id' => $lote->id,
        'chave_acesso' => $chaveMatch, 'numero' => '111720',
        'valor_total' => 4957.52, 'emit_nome' => 'WURTH', 'emit_cnpj' => '43648971004576',
    ]);

    actingAs($user)
        ->get("/app/clearance/notas/resultado/{$lote->id}")
        ->assertOk()
        ->assertSee('Veredito do lote')
        ->assertSee('Atenção crítica')
        ->assertSee('Indicadores operacionais')
        ->assertSee('Categorias de divergência')
        ->assertSee('Divergências a investigar')
        ->assertSee('Sem divergência')
        ->assertSee('SOTRACTOR')
        ->assertSee('WURTH')
        ->assertSee('Dados confrontados com a Receita Federal via InfoSimples');
});

it('renderiza veredito "tudo certo" quando nao ha divergencia', function () use (&$testUserIds) {
    $user = painelCriarUser($testUserIds);
    $cliente = criarClienteBaseline($user->id);

    $lote = ConsultaLote::create([
        'user_id' => $user->id,
        'status' => 'finalizado',
        'total_participantes' => 1,
        'creditos_cobrados' => 10,
        'tab_id' => 'test-tab-ok',
        'processado_em' => now(),
    ]);
    $importacao = EfdImportacao::create([
        'user_id' => $user->id,
        'cliente_id' => $cliente->id,
        'tipo_efd' => 'EFD ICMS/IPI',
        'status' => 'finalizada',
        'periodo_inicial' => '2024-02-01',
        'periodo_final' => '2024-02-29',
    ]);
    $chave = '35240246970030000202570010000432381000772218';

    EfdNota::create([
        'user_id' => $user->id, 'cliente_id' => $cliente->id, 'importacao_id' => $importacao->id,
        'chave_acesso' => $chave, 'modelo' => '57', 'numero' => 43238, 'serie' => '1',
        'data_emissao' => '2024-02-29', 'tipo_operacao' => 'saida',
        'origem_arquivo' => 'fiscal', 'valor_total' => 114.98,
    ]);

    DB::table('cte_consultas')->insert([
        'user_id' => $user->id,
        'consulta_lote_id' => $lote->id,
        'chave_acesso' => $chave,
        'tipo_documento' => 'CTE',
        'modelo' => '57',
        'numero' => '43238',
        'serie' => 1,
        'status' => 'AUTORIZADA',
        'valor_prestacao' => 114.98,
        'emit_nome' => 'PANTANAL',
        'emit_cnpj' => '46970030000202',
        'consultado_em' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    actingAs($user)
        ->get("/app/clearance/notas/resultado/{$lote->id}")
        ->assertOk()
        ->assertSee('Tudo certo')
        ->assertSee('Nenhuma divergência acima da tolerância');
});
