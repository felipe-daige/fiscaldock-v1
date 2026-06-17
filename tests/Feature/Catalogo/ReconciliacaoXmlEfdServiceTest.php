<?php

use App\Models\EfdImportacao;
use App\Models\EfdNota;
use App\Models\User;
use App\Services\Catalogo\ReconciliacaoXmlEfdService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function reconSeedUser(): array
{
    $user = User::factory()->create();
    $clienteId = DB::table('clientes')->insertGetId([
        'user_id' => $user->id, 'razao_social' => 'EMPRESA', 'documento' => '00000000000100',
        'is_empresa_propria' => true, 'created_at' => now(), 'updated_at' => now(),
    ]);

    return [$user, (int) $clienteId];
}

function reconXml(int $userId, int $clienteId, string $chave, float $valor, string $data = '2024-01-10'): void
{
    DB::table('xml_notas')->insert([
        'user_id' => $userId, 'cliente_id' => $clienteId, 'chave_acesso' => $chave, 'tipo_documento' => 'NFE',
        'numero_documento' => '1', 'serie' => '1', 'data_emissao' => $data, 'tipo_nota' => 1, 'modelo' => '55',
        'emit_documento' => '00000000000100', 'dest_documento' => '99999999000191', 'valor_total' => $valor,
        'created_at' => now(), 'updated_at' => now(),
    ]);
}

function reconEfd(int $userId, int $clienteId, string $chave, float $valor, string $origem = 'fiscal', string $data = '2024-01-10'): void
{
    $imp = EfdImportacao::create(['user_id' => $userId, 'cliente_id' => $clienteId, 'tipo_efd' => 'EFD ICMS/IPI', 'filename' => 'i.txt', 'status' => 'concluido', 'iniciado_em' => now()]);
    EfdNota::create([
        'user_id' => $userId, 'cliente_id' => $clienteId, 'importacao_id' => $imp->id, 'numero' => 1, 'serie' => '1',
        'data_emissao' => $data, 'valor_desconto' => 0, 'cancelada' => false, 'chave_acesso' => $chave,
        'modelo' => '55', 'tipo_operacao' => 'saida', 'origem_arquivo' => $origem, 'valor_total' => $valor,
    ]);
}

it('conta chave em ambos com totais batendo como reconciliada', function () {
    [$user, $cli] = reconSeedUser();
    $chave = str_pad('A', 44, '0', STR_PAD_LEFT);
    reconXml($user->id, $cli, $chave, 100.0);
    reconEfd($user->id, $cli, $chave, 100.0);

    $r = app(ReconciliacaoXmlEfdService::class)->resumo($user->id);

    expect($r['documentadas'])->toBe(1);
    expect($r['reconciliadas'])->toBe(1);
    expect($r['divergencia_total'])->toBe(0);
    expect($r['nao_declaradas'])->toBe(0);
});

it('marca divergência de total quando XML e EFD diferem além da tolerância', function () {
    [$user, $cli] = reconSeedUser();
    $chave = str_pad('A', 44, '0', STR_PAD_LEFT);
    reconXml($user->id, $cli, $chave, 100.0);
    reconEfd($user->id, $cli, $chave, 90.0);

    $r = app(ReconciliacaoXmlEfdService::class)->resumo($user->id);

    expect($r['divergencia_total'])->toBe(1);
    expect($r['reconciliadas'])->toBe(0);
});

it('marca nota XML sem contraparte no EFD como não declarada', function () {
    [$user, $cli] = reconSeedUser();
    reconXml($user->id, $cli, str_pad('B', 44, '0', STR_PAD_LEFT), 50.0);

    $r = app(ReconciliacaoXmlEfdService::class)->resumo($user->id);

    expect($r['documentadas'])->toBe(1);
    expect($r['nao_declaradas'])->toBe(1);
});

it('usa o total da linha fiscal do EFD (não a gêmea PIS/COFINS) na reconciliação', function () {
    [$user, $cli] = reconSeedUser();
    $chave = str_pad('A', 44, '0', STR_PAD_LEFT);
    reconXml($user->id, $cli, $chave, 100.0);
    reconEfd($user->id, $cli, $chave, 100.0, 'fiscal');
    reconEfd($user->id, $cli, $chave, 999.0, 'contribuicoes');

    $r = app(ReconciliacaoXmlEfdService::class)->resumo($user->id);

    expect($r['reconciliadas'])->toBe(1);
    expect($r['divergencia_total'])->toBe(0);
});

it('considera reconciliada quando a diferença está dentro da tolerância de 1 centavo', function () {
    [$user, $cli] = reconSeedUser();
    $chave = str_pad('A', 44, '0', STR_PAD_LEFT);
    reconXml($user->id, $cli, $chave, 100.00);
    reconEfd($user->id, $cli, $chave, 100.01);

    $r = app(ReconciliacaoXmlEfdService::class)->resumo($user->id);

    expect($r['reconciliadas'])->toBe(1);
});

it('conta notas EFD sem XML como cobertura (efd_sem_xml), não como não-declaradas', function () {
    [$user, $cli] = reconSeedUser();
    reconEfd($user->id, $cli, str_pad('C', 44, '0', STR_PAD_LEFT), 70.0);

    $r = app(ReconciliacaoXmlEfdService::class)->resumo($user->id);

    expect($r['efd_sem_xml'])->toBe(1);
    expect($r['nao_declaradas'])->toBe(0);
    expect($r['documentadas'])->toBe(0);
});
