<?php

use App\Models\User;
use App\Models\XmlNota;
use App\Services\Clearance\Comparacao\ComparacaoNotaService;
use App\Services\Clearance\Comparacao\ComparacaoSourceResolver;
use App\Services\Clearance\Comparacao\DivergenciaSnapshotService;
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

function snapshotCriarUser(array &$ids): User
{
    $user = User::factory()->create();
    $ids[] = $user->id;

    return $user;
}

function snapshotService(): DivergenciaSnapshotService
{
    return new DivergenciaSnapshotService(
        new ComparacaoSourceResolver,
        new ComparacaoNotaService,
    );
}

it('persiste OK quando declarado bate com SEFAZ', function () use (&$testUserIds) {
    $user = snapshotCriarUser($testUserIds);
    $chave = '35202404123456789012555000001234567890123450';

    $nota = XmlNota::create([
        'user_id' => $user->id,
        'nfe_id' => $chave,
        'origem' => 'xml_upload',
        'tipo_documento' => 'NFE',
        'numero_nota' => 1,
        'serie' => 1,
        'data_emissao' => '2026-04-12 10:00:00',
        'valor_total' => 100.00,
        'tipo_nota' => XmlNota::TIPO_SAIDA,
        'emit_cnpj' => '12345678000190',
        'dest_cnpj' => '98765432000110',
        'situacao_sefaz' => 'AUTORIZADA',
        'verificado_sefaz_em' => '2026-04-12 14:00:00',
    ]);

    snapshotService()->sincronizar($nota->fresh());

    $reloaded = $nota->fresh();
    expect($reloaded->divergencia_severidade)->toBe(XmlNota::DIVERGENCIA_OK);
    expect($reloaded->divergencia_count)->toBe(0);
    expect($reloaded->comparado_em)->not->toBeNull();
});

it('observer dispara quando situacao_sefaz muda em xml_notas existente', function () use (&$testUserIds) {
    $user = snapshotCriarUser($testUserIds);
    $chave = '35202404123456789012555000001234567890123451';

    actingAs($user);

    $nota = XmlNota::create([
        'user_id' => $user->id,
        'nfe_id' => $chave,
        'origem' => 'xml_upload',
        'tipo_documento' => 'NFE',
        'numero_nota' => 2,
        'serie' => 1,
        'data_emissao' => '2026-04-12 10:00:00',
        'valor_total' => 250.00,
        'tipo_nota' => XmlNota::TIPO_SAIDA,
        'emit_cnpj' => '12345678000190',
        'dest_cnpj' => '98765432000110',
    ]);

    expect($nota->fresh()->divergencia_severidade)->toBeNull();

    $nota->situacao_sefaz = 'AUTORIZADA';
    $nota->verificado_sefaz_em = '2026-04-12 14:00:00';
    $nota->save();

    $reloaded = $nota->fresh();
    expect($reloaded->divergencia_severidade)->not->toBeNull();
    expect($reloaded->comparado_em)->not->toBeNull();
});

it('observer ignora updates que nao tocam situacao_sefaz', function () use (&$testUserIds) {
    $user = snapshotCriarUser($testUserIds);
    $chave = '35202404123456789012555000001234567890123452';

    $nota = XmlNota::create([
        'user_id' => $user->id,
        'nfe_id' => $chave,
        'origem' => 'xml_upload',
        'tipo_documento' => 'NFE',
        'numero_nota' => 3,
        'serie' => 1,
        'data_emissao' => '2026-04-12 10:00:00',
        'valor_total' => 50.00,
        'tipo_nota' => XmlNota::TIPO_SAIDA,
        'emit_cnpj' => '12345678000190',
        'dest_cnpj' => '98765432000110',
    ]);

    expect($nota->fresh()->divergencia_severidade)->toBeNull();

    // Mudar campo nao-snapshot nao deve disparar comparacao
    $nota->numero_nota = 99;
    $nota->save();

    expect($nota->fresh()->divergencia_severidade)->toBeNull();
    expect($nota->fresh()->comparado_em)->toBeNull();
});

it('marca CRITICA quando SEFAZ esta CANCELADA mas declarado tem valor', function () use (&$testUserIds) {
    $user = snapshotCriarUser($testUserIds);
    $chave = '35202404123456789012555000001234567890123453';

    $nota = XmlNota::create([
        'user_id' => $user->id,
        'nfe_id' => $chave,
        'origem' => 'xml_upload',
        'tipo_documento' => 'NFE',
        'numero_nota' => 4,
        'serie' => 1,
        'data_emissao' => '2026-04-12 10:00:00',
        'valor_total' => 5000.00,
        'tipo_nota' => XmlNota::TIPO_SAIDA,
        'emit_cnpj' => '12345678000190',
        'dest_cnpj' => '98765432000110',
        'situacao_sefaz' => 'CANCELADA',
        'verificado_sefaz_em' => '2026-04-12 14:00:00',
    ]);

    snapshotService()->sincronizar($nota->fresh());

    expect($nota->fresh()->divergencia_severidade)->toBe(XmlNota::DIVERGENCIA_CRITICA);
});

it('comando backfill processa apenas notas com snapshot SEFAZ', function () use (&$testUserIds) {
    $user = snapshotCriarUser($testUserIds);

    XmlNota::create([
        'user_id' => $user->id,
        'nfe_id' => '35202404123456789012555000001234567890123454',
        'origem' => 'xml_upload',
        'tipo_documento' => 'NFE',
        'numero_nota' => 5, 'serie' => 1,
        'data_emissao' => '2026-04-12 10:00:00',
        'valor_total' => 100.00, 'tipo_nota' => XmlNota::TIPO_SAIDA,
        'emit_cnpj' => '12345678000190', 'dest_cnpj' => '98765432000110',
        'situacao_sefaz' => 'AUTORIZADA',
        'verificado_sefaz_em' => '2026-04-12 14:00:00',
    ]);

    XmlNota::create([
        'user_id' => $user->id,
        'nfe_id' => '35202404123456789012555000001234567890123455',
        'origem' => 'xml_upload',
        'tipo_documento' => 'NFE',
        'numero_nota' => 6, 'serie' => 1,
        'data_emissao' => '2026-04-12 10:00:00',
        'valor_total' => 200.00, 'tipo_nota' => XmlNota::TIPO_SAIDA,
        'emit_cnpj' => '12345678000190', 'dest_cnpj' => '98765432000110',
        // sem situacao_sefaz
    ]);

    // limpa as colunas de divergencia que o observer ja deve ter populado no save
    XmlNota::where('user_id', $user->id)->update([
        'divergencia_severidade' => null,
        'divergencia_count' => null,
        'divergencia_resumo' => null,
        'comparado_em' => null,
    ]);

    $exit = \Illuminate\Support\Facades\Artisan::call('clearance:backfill-divergencias', [
        '--user' => $user->id,
    ]);

    expect($exit)->toBe(0);

    $comSefaz = XmlNota::where('user_id', $user->id)
        ->whereNotNull('situacao_sefaz')->first();
    $semSefaz = XmlNota::where('user_id', $user->id)
        ->whereNull('situacao_sefaz')->first();

    expect($comSefaz->divergencia_severidade)->not->toBeNull();
    expect($semSefaz->divergencia_severidade)->toBeNull();
});
