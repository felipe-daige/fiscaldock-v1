<?php

use App\Models\EfdImportacao;
use App\Models\EfdNota;
use App\Models\User;
use App\Services\Dashboard\DashboardDataService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

/**
 * F8 — Dashboard inicial (`DashboardDataService::getKpis`). A manchete da home
 * (volume de notas e valor) somava `efd_notas` cru → a MESMA NF-e nas 2 origens
 * dobrava (P1) e cancelada entrava (P4). Medido: 10.118/53,2mi vs 7.488/42,6mi.
 */
beforeEach(function () {
    $this->user = User::factory()->create();
    $this->cliente = DB::table('clientes')->insertGetId([
        'user_id' => $this->user->id, 'razao_social' => 'EMPRESA', 'documento' => '00000000000100',
        'is_empresa_propria' => true, 'created_at' => now(), 'updated_at' => now(),
    ]);
    $impF = EfdImportacao::create(['user_id' => $this->user->id, 'cliente_id' => $this->cliente, 'tipo_efd' => 'EFD ICMS/IPI', 'filename' => 'i.txt', 'status' => 'concluido', 'iniciado_em' => now()]);
    $impC = EfdImportacao::create(['user_id' => $this->user->id, 'cliente_id' => $this->cliente, 'tipo_efd' => 'EFD PIS/COFINS', 'filename' => 'p.txt', 'status' => 'concluido', 'iniciado_em' => now()]);

    $mk = fn (array $a) => EfdNota::create(array_merge([
        'user_id' => $this->user->id, 'cliente_id' => $this->cliente, 'numero' => random_int(1, 99999),
        'serie' => '1', 'data_emissao' => '2024-01-15', 'valor_desconto' => 0, 'cancelada' => false,
    ], $a));

    $a = str_pad('A', 44, '0', STR_PAD_LEFT);
    $c = str_pad('C', 44, '0', STR_PAD_LEFT);
    $mk(['importacao_id' => $impF->id, 'chave_acesso' => $a, 'modelo' => '55', 'tipo_operacao' => 'saida', 'origem_arquivo' => 'fiscal', 'valor_total' => 1000]);
    $mk(['importacao_id' => $impC->id, 'chave_acesso' => $a, 'modelo' => '55', 'tipo_operacao' => 'saida', 'origem_arquivo' => 'contribuicoes', 'valor_total' => 1000]); // dup
    $mk(['importacao_id' => $impC->id, 'chave_acesso' => null, 'modelo' => '00', 'tipo_operacao' => 'saida', 'origem_arquivo' => 'contribuicoes', 'valor_total' => 500]); // NFS-e
    $mk(['importacao_id' => $impF->id, 'chave_acesso' => $c, 'modelo' => '55', 'tipo_operacao' => 'entrada', 'origem_arquivo' => 'fiscal', 'valor_total' => 700]);
    $mk(['importacao_id' => $impC->id, 'chave_acesso' => $c, 'modelo' => '55', 'tipo_operacao' => 'entrada', 'origem_arquivo' => 'contribuicoes', 'valor_total' => 700]); // dup
    $mk(['importacao_id' => $impF->id, 'chave_acesso' => str_pad('E', 44, '0', STR_PAD_LEFT), 'modelo' => '55', 'tipo_operacao' => 'saida', 'origem_arquivo' => 'fiscal', 'valor_total' => 9999, 'cancelada' => true]);
});

it('volume da home não dobra origem nem conta cancelada (P1/P4)', function () {
    $kpis = app(DashboardDataService::class)->getKpis($this->user->id, $this->user);

    // A (dedup) + NFS-e + C (dedup) = 3 documentos; cancelada E fora.
    expect($kpis['volume_total_notas'])->toBe(3);
    // 1000 + 500 + 700 = 2200 (todas as operações, sem dobra, sem cancelada).
    expect($kpis['volume_valor_total'])->toBe(2200.0);
});
