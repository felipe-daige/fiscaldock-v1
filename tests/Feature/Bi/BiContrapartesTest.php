<?php

use App\Models\EfdImportacao;
use App\Models\EfdNota;
use App\Models\User;
use App\Services\BiExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('contrapartes no portfólio trazem score + top CFOPs e vêm antes do cfop', function () {
    $user = User::factory()->create();
    $cli = DB::table('clientes')->insertGetId([
        'user_id' => $user->id, 'documento' => '00000000000191', 'razao_social' => 'Empresa',
        'is_empresa_propria' => true, 'created_at' => now(), 'updated_at' => now(),
    ]);
    $part = DB::table('participantes')->insertGetId([
        'user_id' => $user->id, 'cliente_id' => $cli, 'razao_social' => 'ACME LTDA',
        'documento' => '11111111000111', 'origem_tipo' => 'MANUAL', 'situacao_cadastral' => '02',
        'created_at' => now(), 'updated_at' => now(),
    ]);
    DB::table('participante_scores')->insert([
        'user_id' => $user->id, 'cliente_id' => $cli, 'participante_id' => $part,
        'score_total' => 80, 'classificacao' => 'baixo', 'created_at' => now(), 'updated_at' => now(),
    ]);
    $imp = EfdImportacao::create([
        'user_id' => $user->id, 'cliente_id' => $cli, 'tipo_efd' => 'EFD ICMS/IPI',
        'filename' => 'x.txt', 'status' => 'concluido', 'iniciado_em' => now(),
    ]);
    $nota = EfdNota::create([
        'user_id' => $user->id, 'cliente_id' => $cli, 'participante_id' => $part, 'importacao_id' => $imp->id,
        'numero' => 1, 'serie' => '1', 'modelo' => '55', 'valor_desconto' => 0, 'cancelada' => false,
        'origem_arquivo' => 'fiscal', 'tipo_operacao' => 'saida', 'data_emissao' => '2026-03-10',
        'chave_acesso' => str_pad('1', 44, '0'), 'valor_total' => 1000,
    ]);
    DB::table('efd_notas_consolidados')->insert([
        'efd_nota_id' => $nota->id, 'user_id' => $user->id, 'cfop' => 5102, 'cst_icms' => '00', 'aliquota_icms' => 18,
        'valor_operacao' => 1000, 'valor_bc_icms' => 0, 'valor_icms' => 180, 'valor_bc_icms_st' => 0,
        'valor_icms_st' => 0, 'valor_reducao_bc' => 0, 'valor_ipi' => 0, 'created_at' => now(), 'updated_at' => now(),
    ]);

    $rel = app(BiExportService::class)->relatorioCompleto($user->id, null, null, null);

    expect($rel['secoes'])->toHaveKey('contrapartes')
        ->and($rel['secoes'])->not->toHaveKey('participantes');

    $sec = $rel['secoes']['contrapartes'];
    expect($sec['modo'])->toBe('portfolio')
        ->and($sec['itens'])->not->toBeEmpty();

    $item = collect($sec['itens'])->firstWhere('cnpj', '11111111000111');
    expect($item)->not->toBeNull()
        ->and($item['score_total'])->toBe(80)
        ->and($item['classificacao'])->toBe('baixo')
        ->and($item['cfops'])->toContain('5102');

    // ordem: contrapartes antes de cfop
    $ordem = $rel['ordem_secoes'];
    expect(array_search('contrapartes', $ordem))->toBeLessThan(array_search('cfop', $ordem));
});

it('contrapartes no modo cliente resolvem score por CNPJ best-effort', function () {
    $user = User::factory()->create();
    $cli = DB::table('clientes')->insertGetId([
        'user_id' => $user->id, 'documento' => '00000000000191', 'razao_social' => 'Empresa',
        'is_empresa_propria' => true, 'created_at' => now(), 'updated_at' => now(),
    ]);
    $part = DB::table('participantes')->insertGetId([
        'user_id' => $user->id, 'cliente_id' => $cli, 'razao_social' => 'FORN LTDA',
        'documento' => '33333333000133', 'origem_tipo' => 'MANUAL', 'created_at' => now(), 'updated_at' => now(),
    ]);
    DB::table('participante_scores')->insert([
        'user_id' => $user->id, 'cliente_id' => $cli, 'participante_id' => $part,
        'score_total' => 40, 'classificacao' => 'alto', 'created_at' => now(), 'updated_at' => now(),
    ]);
    $imp = EfdImportacao::create([
        'user_id' => $user->id, 'cliente_id' => $cli, 'tipo_efd' => 'EFD ICMS/IPI',
        'filename' => 'x.txt', 'status' => 'concluido', 'iniciado_em' => now(),
    ]);
    EfdNota::create([
        'user_id' => $user->id, 'cliente_id' => $cli, 'participante_id' => $part, 'importacao_id' => $imp->id,
        'numero' => 2, 'serie' => '1', 'modelo' => '55', 'valor_desconto' => 0, 'cancelada' => false,
        'origem_arquivo' => 'fiscal', 'tipo_operacao' => 'entrada', 'data_emissao' => '2026-03-10',
        'chave_acesso' => str_pad('2', 44, '0'), 'valor_total' => 500,
    ]);

    $rel = app(BiExportService::class)->relatorioCompleto($user->id, null, null, $cli);
    $sec = $rel['secoes']['contrapartes'];

    expect($sec['modo'])->toBe('cliente');
    $item = collect($sec['itens'])->firstWhere('cnpj', '33333333000133');
    expect($item)->not->toBeNull()
        ->and($item['papel'])->toBe('Fornecedor')
        ->and($item['score_total'])->toBe(40)
        ->and($item['classificacao'])->toBe('alto');
});
