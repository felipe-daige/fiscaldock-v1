<?php

use App\Models\User;
use App\Services\EfdAgregadorService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('soma declarado bruto por mes a partir das apuracoes', function () {
    $user = User::factory()->create();
    $userId = $user->id;

    $clienteId = \DB::table('clientes')->insertGetId([
        'user_id' => $userId,
        'documento' => '00000000000191',
        'razao_social' => 'Empresa Teste',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // ICMS jan/2024: debito bruto 1000
    \DB::table('efd_importacoes')->insert(['id' => 10, 'user_id' => $userId, 'cliente_id' => $clienteId, 'tipo_efd' => 'EFD ICMS/IPI', 'status' => 'concluido', 'created_at' => now(), 'updated_at' => now()]);
    \DB::table('efd_apuracoes_icms')->insert(['id' => 1, 'importacao_id' => 10, 'user_id' => $userId, 'cliente_id' => $clienteId, 'periodo_inicio' => '2024-01-01', 'periodo_fim' => '2024-01-31', 'icms_tot_debitos' => 1000, 'created_at' => now(), 'updated_at' => now()]);

    // PIS/COFINS jan/2024: bruto pis = 60 (40 nc + 20 cum), cofins = 277
    \DB::table('efd_importacoes')->insert(['id' => 11, 'user_id' => $userId, 'cliente_id' => $clienteId, 'tipo_efd' => 'EFD PIS/COFINS', 'status' => 'concluido', 'created_at' => now(), 'updated_at' => now()]);
    \DB::table('efd_apuracoes_contribuicoes')->insert(['id' => 1, 'importacao_id' => 11, 'user_id' => $userId, 'cliente_id' => $clienteId, 'pis_nao_cumulativo' => 40, 'pis_cumulativo' => 20, 'cofins_nao_cumulativo' => 277, 'cofins_cumulativo' => 0, 'created_at' => now(), 'updated_at' => now()]);
    \DB::table('efd_notas')->insert(['user_id' => $userId, 'cliente_id' => $clienteId, 'importacao_id' => 11, 'origem_arquivo' => 'contribuicoes', 'modelo' => '55', 'tipo_operacao' => 'saida', 'cancelada' => false, 'valor_total' => 100, 'data_emissao' => '2024-01-15', 'numero' => 1, 'created_at' => now(), 'updated_at' => now()]);

    $svc = app(EfdAgregadorService::class);
    $rows = collect($svc->cargaDeclaradaBrutaMensal($userId))->keyBy('mes');

    $jan = $rows->first();
    expect((float) $jan['icms'])->toBe(1000.0);
    expect((float) $jan['pis'])->toBe(60.0);
    expect((float) $jan['cofins'])->toBe(277.0);
});

it('cruza declarado x computado por mes com delta e flag', function () {
    $user = \App\Models\User::factory()->create();
    $userId = $user->id;
    $clienteId = \DB::table('clientes')->insertGetId([
        'user_id' => $userId, 'documento' => '00000000000191', 'razao_social' => 'Empresa Teste',
        'created_at' => now(), 'updated_at' => now(),
    ]);

    \DB::table('efd_importacoes')->insert(['id' => 10, 'user_id' => $userId, 'cliente_id' => $clienteId, 'tipo_efd' => 'EFD ICMS/IPI', 'status' => 'concluido', 'created_at' => now(), 'updated_at' => now()]);
    \DB::table('efd_apuracoes_icms')->insert(['id' => 1, 'importacao_id' => 10, 'user_id' => $userId, 'cliente_id' => $clienteId, 'periodo_inicio' => '2024-01-01', 'periodo_fim' => '2024-01-31', 'icms_tot_debitos' => 1000, 'created_at' => now(), 'updated_at' => now()]);

    // computado: 1 nota saída fiscal com C190 icms 950 (Δ -5% => amarelo)
    $notaId = \DB::table('efd_notas')->insertGetId(['user_id' => $userId, 'cliente_id' => $clienteId, 'importacao_id' => 10, 'origem_arquivo' => 'fiscal', 'modelo' => '55', 'tipo_operacao' => 'saida', 'cancelada' => false, 'valor_total' => 5000, 'data_emissao' => '2024-01-15', 'numero' => 1, 'created_at' => now(), 'updated_at' => now()]);
    \DB::table('efd_notas_consolidados')->insert(['efd_nota_id' => $notaId, 'user_id' => $userId, 'cst_icms' => '000', 'cfop' => '5102', 'valor_operacao' => 5000, 'valor_icms' => 950, 'created_at' => now(), 'updated_at' => now()]);

    $data = app(\App\Services\BiService::class)->getApuracaoVsNotas($userId, null, null, null);

    $jan = collect($data['mensal'])->firstWhere('mes', '2024-01');
    expect($jan['icms']['declarado'])->toBe(1000.0);
    expect($jan['icms']['computado'])->toBe(950.0);
    expect($jan['icms']['delta_pct'])->toBe(-5.0);
    expect($jan['icms']['flag'])->toBe('amarelo');
    expect($data['totais']['icms']['declarado'])->toBe(1000.0);
});

it('endpoint apuracao-notas responde json autenticado', function () {
    $user = \App\Models\User::factory()->create();
    $res = $this->actingAs($user)->getJson('/app/bi/apuracao-notas');
    $res->assertOk()->assertJsonStructure(['mensal', 'totais' => ['icms', 'pis', 'cofins']]);
});

it('marca PIS/COFINS como sem_dado no mes sem apuracao de contribuicoes', function () {
    $user = \App\Models\User::factory()->create();
    $userId = $user->id;
    $clienteId = \DB::table('clientes')->insertGetId([
        'user_id' => $userId, 'documento' => '00000000000191', 'razao_social' => 'Empresa Teste',
        'created_at' => now(), 'updated_at' => now(),
    ]);
    // abril com ICMS completo (apuração + nota/C190), mas SEM arquivo PIS/COFINS
    $imp = \DB::table('efd_importacoes')->insertGetId(['user_id' => $userId, 'cliente_id' => $clienteId, 'tipo_efd' => 'EFD ICMS/IPI', 'status' => 'concluido', 'created_at' => now(), 'updated_at' => now()]);
    \DB::table('efd_apuracoes_icms')->insert(['importacao_id' => $imp, 'user_id' => $userId, 'cliente_id' => $clienteId, 'periodo_inicio' => '2024-04-01', 'periodo_fim' => '2024-04-30', 'icms_tot_debitos' => 1000, 'created_at' => now(), 'updated_at' => now()]);
    $nota = \DB::table('efd_notas')->insertGetId(['user_id' => $userId, 'cliente_id' => $clienteId, 'importacao_id' => $imp, 'origem_arquivo' => 'fiscal', 'modelo' => '55', 'tipo_operacao' => 'saida', 'cancelada' => false, 'valor_total' => 5000, 'data_emissao' => '2024-04-10', 'numero' => 1, 'created_at' => now(), 'updated_at' => now()]);
    \DB::table('efd_notas_consolidados')->insert(['efd_nota_id' => $nota, 'user_id' => $userId, 'cst_icms' => '000', 'cfop' => '5102', 'valor_operacao' => 5000, 'valor_icms' => 1000, 'created_at' => now(), 'updated_at' => now()]);

    $abr = collect(app(\App\Services\BiService::class)->getApuracaoVsNotas($userId, null, null, null)['mensal'])->firstWhere('mes', '2024-04');

    expect($abr['icms']['flag'])->toBe('verde');     // tem fonte ICMS
    expect($abr['pis']['flag'])->toBe('sem_dado');   // sem apuração de contribuições
    expect($abr['cofins']['flag'])->toBe('sem_dado');
});
