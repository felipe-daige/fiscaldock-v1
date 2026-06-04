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
