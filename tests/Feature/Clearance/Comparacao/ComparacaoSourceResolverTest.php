<?php

use App\Models\Cliente;
use App\Models\EfdNota;
use App\Models\Participante;
use App\Models\User;
use App\Models\XmlNota;
use App\Services\Clearance\Comparacao\Adapters\EfdNotaDeclaradoAdapter;
use App\Services\Clearance\Comparacao\Adapters\XmlNotaDeclaradoAdapter;
use App\Services\Clearance\Comparacao\Adapters\XmlNotaSefazCteAdapter;
use App\Services\Clearance\Comparacao\Adapters\XmlNotaSefazNfeAdapter;
use App\Services\Clearance\Comparacao\ComparacaoSourceResolver;
use Illuminate\Support\Facades\DB;

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

function resolverCriarUser(array &$ids): User
{
    $user = User::factory()->create();
    $ids[] = $user->id;

    return $user;
}

it('detecta NFE pelo modelo 55 da chave', function () use (&$testUserIds) {
    $user = resolverCriarUser($testUserIds);
    $resolver = new ComparacaoSourceResolver;

    $r = $resolver->resolver($user->id, '35202404123456789012555000001234567890123456');

    expect($r->tipoDocumento)->toBe('NFE');
});

it('detecta CTE pelo modelo 57', function () use (&$testUserIds) {
    $user = resolverCriarUser($testUserIds);
    $resolver = new ComparacaoSourceResolver;

    $r = $resolver->resolver($user->id, '35202404123456789012575000001234567890123456');

    expect($r->tipoDocumento)->toBe('CTE');
});

it('detecta NFCE como NFE pelo modelo 65', function () use (&$testUserIds) {
    $user = resolverCriarUser($testUserIds);
    $resolver = new ComparacaoSourceResolver;

    $r = $resolver->resolver($user->id, '35202404123456789012655000001234567890123456');

    expect($r->tipoDocumento)->toBe('NFE');
});

it('lança InvalidArgumentException pra chave com tamanho errado', function () use (&$testUserIds) {
    $user = resolverCriarUser($testUserIds);
    $resolver = new ComparacaoSourceResolver;

    $resolver->resolver($user->id, '12345');
})->throws(InvalidArgumentException::class);

it('lança InvalidArgumentException pra modelo desconhecido', function () use (&$testUserIds) {
    $user = resolverCriarUser($testUserIds);
    $resolver = new ComparacaoSourceResolver;

    $resolver->resolver($user->id, '35202404123456789012885000001234567890123456');
})->throws(InvalidArgumentException::class);

it('prioriza XML upload sobre EFD no lado declarado', function () use (&$testUserIds) {
    $user = resolverCriarUser($testUserIds);
    $chave = '35202404123456789012555000001234567890123456';

    XmlNota::create([
        'user_id' => $user->id, 'nfe_id' => $chave, 'origem' => 'xml_upload',
        'tipo_documento' => 'NFE', 'numero_nota' => 1234, 'serie' => 1,
        'data_emissao' => '2026-04-12 10:00:00', 'valor_total' => 1000,
        'tipo_nota' => XmlNota::TIPO_SAIDA,
        'emit_cnpj' => '12345678000190', 'dest_cnpj' => '98765432000110',
    ]);
    $cliente = Cliente::create(['user_id' => $user->id, 'tipo_pessoa' => 'PJ', 'documento' => '12345678000190', 'razao_social' => 'X']);
    $impId = DB::table('efd_importacoes')->insertGetId([
        'user_id' => $user->id, 'tipo_efd' => 'EFD ICMS/IPI', 'filename' => 't.txt',
        'status' => 'ok', 'created_at' => now(), 'updated_at' => now(),
    ]);
    DB::table('efd_notas')->insert([
        'user_id' => $user->id, 'cliente_id' => $cliente->id, 'importacao_id' => $impId,
        'chave_acesso' => $chave, 'modelo' => '55', 'numero' => 1234, 'serie' => '1',
        'data_emissao' => '2026-04-12', 'tipo_operacao' => 'saida',
        'origem_arquivo' => 'fiscal', 'valor_total' => 1000,
        'created_at' => now(), 'updated_at' => now(),
    ]);

    $r = (new ComparacaoSourceResolver)->resolver($user->id, $chave);

    expect($r->declarado)->toBeInstanceOf(XmlNotaDeclaradoAdapter::class);
});

it('usa EFD quando não há XML upload', function () use (&$testUserIds) {
    $user = resolverCriarUser($testUserIds);
    $chave = '35202404123456789012555000001234567890123457';

    $cliente = Cliente::create(['user_id' => $user->id, 'tipo_pessoa' => 'PJ', 'documento' => '12345678000190', 'razao_social' => 'X']);
    $part = Participante::create(['user_id' => $user->id, 'documento' => '98765432000110', 'tipo_documento' => 'CNPJ', 'razao_social' => 'Y']);
    $impId = DB::table('efd_importacoes')->insertGetId([
        'user_id' => $user->id, 'tipo_efd' => 'EFD ICMS/IPI', 'filename' => 't.txt',
        'status' => 'ok', 'created_at' => now(), 'updated_at' => now(),
    ]);
    EfdNota::create([
        'user_id' => $user->id, 'importacao_id' => $impId,
        'cliente_id' => $cliente->id, 'participante_id' => $part->id,
        'chave_acesso' => $chave, 'modelo' => '55', 'numero' => 1234, 'serie' => '1',
        'data_emissao' => '2026-04-12', 'tipo_operacao' => 'saida',
        'origem_arquivo' => 'fiscal', 'valor_total' => 1000,
    ]);

    $r = (new ComparacaoSourceResolver)->resolver($user->id, $chave);

    expect($r->declarado)->toBeInstanceOf(EfdNotaDeclaradoAdapter::class);
});

it('SEFAZ NFE vem de xml_notas com situacao_sefaz preenchido', function () use (&$testUserIds) {
    $user = resolverCriarUser($testUserIds);
    $chave = '35202404123456789012555000001234567890123458';

    XmlNota::create([
        'user_id' => $user->id, 'nfe_id' => $chave, 'origem' => 'busca_avulsa',
        'tipo_documento' => 'NFE', 'numero_nota' => 1, 'serie' => 1,
        'data_emissao' => '2026-04-15 09:00', 'valor_total' => 100,
        'tipo_nota' => XmlNota::TIPO_SAIDA,
        'emit_cnpj' => '12345678000190', 'dest_cnpj' => '98765432000110',
        'situacao_sefaz' => 'AUTORIZADA',
        'payload' => ['nfe_clearance' => ['status' => 'AUTORIZADA']],
    ]);

    $r = (new ComparacaoSourceResolver)->resolver($user->id, $chave);

    expect($r->sefaz)->toBeInstanceOf(XmlNotaSefazNfeAdapter::class);
});

it('SEFAZ CTE usa adapter CT-e por modelo da chave', function () use (&$testUserIds) {
    $user = resolverCriarUser($testUserIds);
    $chave = '35202404123456789012575000001234567890123456';

    XmlNota::create([
        'user_id' => $user->id, 'nfe_id' => $chave, 'origem' => 'busca_avulsa',
        'tipo_documento' => 'CTE', 'numero_nota' => 1, 'serie' => 1,
        'data_emissao' => '2026-04-15 09:00', 'valor_total' => 100,
        'tipo_nota' => XmlNota::TIPO_SAIDA,
        'emit_cnpj' => '12345678000190', 'dest_cnpj' => '98765432000110',
        'situacao_sefaz' => 'AUTORIZADA',
        'payload' => ['cte_clearance' => ['status' => 'AUTORIZADA']],
    ]);

    $r = (new ComparacaoSourceResolver)->resolver($user->id, $chave);

    expect($r->sefaz)->toBeInstanceOf(XmlNotaSefazCteAdapter::class);
});

it('a MESMA linha xml_notas pode ser declarado E sefaz (xml_upload + clearance posterior)', function () use (&$testUserIds) {
    $user = resolverCriarUser($testUserIds);
    $chave = '35202404123456789012555000001234567890123459';

    XmlNota::create([
        'user_id' => $user->id, 'nfe_id' => $chave, 'origem' => 'xml_upload',
        'tipo_documento' => 'NFE', 'numero_nota' => 1, 'serie' => 1,
        'data_emissao' => '2026-04-15 09:00', 'valor_total' => 1000,
        'tipo_nota' => XmlNota::TIPO_SAIDA,
        'emit_cnpj' => '12345678000190', 'dest_cnpj' => '98765432000110',
        'situacao_sefaz' => 'AUTORIZADA',
        'payload' => [
            'det' => [['nItem' => '1', 'prod' => ['cProd' => 'A', 'vProd' => '1000']]],
            'ide' => ['mod' => '55'],
            'total' => ['ICMSTot' => ['vBC' => '1000']],
            'nfe_clearance' => ['status' => 'AUTORIZADA', 'numero' => '1', 'totais' => ['normalizado_valor_nfe' => 1000]],
        ],
    ]);

    $r = (new ComparacaoSourceResolver)->resolver($user->id, $chave);

    expect($r->declarado)->toBeInstanceOf(XmlNotaDeclaradoAdapter::class);
    expect($r->sefaz)->toBeInstanceOf(XmlNotaSefazNfeAdapter::class);
});

it('escopo por usuário — A não vê nota do B', function () use (&$testUserIds) {
    $userA = resolverCriarUser($testUserIds);
    $userB = resolverCriarUser($testUserIds);
    $chave = '35202404123456789012555000001234567890123460';

    XmlNota::create([
        'user_id' => $userB->id, 'nfe_id' => $chave, 'origem' => 'xml_upload',
        'tipo_documento' => 'NFE', 'numero_nota' => 1, 'serie' => 1,
        'data_emissao' => '2026-04-15 09:00', 'valor_total' => 100,
        'tipo_nota' => XmlNota::TIPO_SAIDA,
        'emit_cnpj' => '12345678000190', 'dest_cnpj' => '98765432000110',
        'situacao_sefaz' => 'AUTORIZADA',
        'payload' => ['nfe_clearance' => ['status' => 'AUTORIZADA']],
    ]);

    $r = (new ComparacaoSourceResolver)->resolver($userA->id, $chave);

    expect($r->declarado)->toBeNull();
    expect($r->sefaz)->toBeNull();
});

it('retorna ambos null quando nada existe', function () use (&$testUserIds) {
    $user = resolverCriarUser($testUserIds);
    $chave = '35202404123456789012555000001234567890123461';

    $r = (new ComparacaoSourceResolver)->resolver($user->id, $chave);

    expect($r->declarado)->toBeNull();
    expect($r->sefaz)->toBeNull();
});
