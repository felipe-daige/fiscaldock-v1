<?php

use App\Support\Cfop;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('resolve descricao de cfop comum pelo mapa', function () {
    expect(Cfop::descricao('5102'))->toContain('Venda de mercadoria adquirida');
});

it('cai no fallback de familia para cfop nao mapeado', function () {
    // 5999 não está no mapa top-usados → família "Saída estadual"
    expect(Cfop::descricao('5999'))->toContain('Saída');
});

it('classifica entrada x saida pelo primeiro digito', function () {
    expect(Cfop::tipoOperacao('1102'))->toBe('entrada');
    expect(Cfop::tipoOperacao('6108'))->toBe('saida');
});

it('soma valor e icms do C190 por cfop (nao dos itens fiscais)', function () {
    $user = \App\Models\User::factory()->create();
    $userId = $user->id;
    $clienteId = \DB::table('clientes')->insertGetId([
        'user_id' => $userId, 'documento' => substr(str_replace('.', '', microtime(true)) . $userId, 0, 14), 'razao_social' => 'Empresa Teste',
        'created_at' => now(), 'updated_at' => now(),
    ]);
    $importacaoId = \DB::table('efd_importacoes')->insertGetId([
        'user_id' => $userId, 'tipo_efd' => 'EFD ICMS/IPI', 'status' => 'concluido',
        'created_at' => now(), 'updated_at' => now(),
    ]);
    $nota = \DB::table('efd_notas')->insertGetId(['user_id' => $userId, 'cliente_id' => $clienteId, 'importacao_id' => $importacaoId, 'origem_arquivo' => 'fiscal', 'modelo' => '55', 'tipo_operacao' => 'saida', 'cancelada' => false, 'valor_total' => 1000, 'data_emissao' => '2024-03-10', 'numero' => 1, 'created_at' => now(), 'updated_at' => now()]);
    \DB::table('efd_notas_consolidados')->insert(['efd_nota_id' => $nota, 'user_id' => $userId, 'cst_icms' => '000', 'cfop' => '5102', 'valor_operacao' => 1000, 'valor_icms' => 120, 'created_at' => now(), 'updated_at' => now()]);

    $rows = collect(app(\App\Services\EfdAgregadorService::class)->cfopRanking($userId, null, null, null));
    $r = $rows->firstWhere('cfop', '5102');

    expect((float) $r['valor'])->toBe(1000.0);
    expect((float) $r['icms'])->toBe(120.0);
    expect($r['tipo'])->toBe('saida');
});

it('monta cfop analitico com descricao e tendencia top N', function () {
    $user = \App\Models\User::factory()->create();
    $userId = $user->id;
    $clienteId = \DB::table('clientes')->insertGetId([
        'user_id' => $userId, 'documento' => '00000000000191', 'razao_social' => 'Empresa Teste',
        'created_at' => now(), 'updated_at' => now(),
    ]);
    $impId = \DB::table('efd_importacoes')->insertGetId([
        'user_id' => $userId, 'cliente_id' => $clienteId, 'tipo_efd' => 'EFD ICMS/IPI', 'status' => 'concluido',
        'created_at' => now(), 'updated_at' => now(),
    ]);

    foreach (['2024-03-10' => 1000, '2024-04-10' => 2000] as $data => $val) {
        $nota = \DB::table('efd_notas')->insertGetId(['user_id' => $userId, 'cliente_id' => $clienteId, 'importacao_id' => $impId, 'origem_arquivo' => 'fiscal', 'modelo' => '55', 'tipo_operacao' => 'saida', 'cancelada' => false, 'valor_total' => $val, 'data_emissao' => $data, 'numero' => 1, 'created_at' => now(), 'updated_at' => now()]);
        \DB::table('efd_notas_consolidados')->insert(['efd_nota_id' => $nota, 'user_id' => $userId, 'cst_icms' => '000', 'cfop' => '5102', 'valor_operacao' => $val, 'valor_icms' => 0, 'created_at' => now(), 'updated_at' => now()]);
    }

    $svc = app(\App\Services\BiService::class);
    $analitico = $svc->getCfopAnalitico($userId, null, null, null);
    $top = collect($analitico['ranking'])->firstWhere('cfop', '5102');
    expect($top['descricao'])->toContain('Venda de mercadoria adquirida');
    expect($top['valor'])->toBe(3000.0);

    $tend = $svc->getCfopTendencia($userId, null, null, null, 5);
    expect($tend['series'][0]['name'])->toContain('5102');
    expect(count($tend['categorias']))->toBeGreaterThanOrEqual(2);
});

it('endpoint cfop responde json autenticado', function () {
    $user = \App\Models\User::factory()->create();
    $res = $this->actingAs($user)->getJson('/app/bi/cfop');
    $res->assertOk()->assertJsonStructure(['ranking', 'tendencia' => ['categorias', 'series']]);
});
