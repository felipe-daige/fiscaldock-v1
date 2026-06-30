<?php

use App\Models\EfdImportacao;
use App\Models\EfdNota;
use App\Models\User;
use App\Services\BiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('getDevolucoes inclui devoluções do EFD (C190 CFOP devolução), não só XML', function () {
    $user = User::factory()->create();
    $cli = DB::table('clientes')->insertGetId([
        'user_id' => $user->id, 'documento' => '00000000000191', 'razao_social' => 'Empresa',
        'is_empresa_propria' => true, 'created_at' => now(), 'updated_at' => now(),
    ]);
    $imp = EfdImportacao::create([
        'user_id' => $user->id, 'cliente_id' => $cli, 'tipo_efd' => 'EFD ICMS/IPI',
        'filename' => 'x.txt', 'status' => 'concluido', 'iniciado_em' => now(),
    ]);
    $mkNota = fn (array $a) => EfdNota::create(array_merge([
        'user_id' => $user->id, 'cliente_id' => $cli, 'importacao_id' => $imp->id,
        'numero' => random_int(1, 99999), 'serie' => '1', 'modelo' => '55', 'valor_total' => 0, 'valor_desconto' => 0,
        'cancelada' => false, 'origem_arquivo' => 'fiscal', 'tipo_operacao' => 'entrada',
        'data_emissao' => '2026-03-15',
    ], $a));
    $c190 = fn (EfdNota $n, int $cfop, float $val) => DB::table('efd_notas_consolidados')->insert([
        'efd_nota_id' => $n->id, 'user_id' => $user->id, 'cfop' => $cfop, 'cst_icms' => '00', 'aliquota_icms' => 18,
        'valor_operacao' => $val, 'valor_bc_icms' => 0, 'valor_icms' => 0, 'valor_bc_icms_st' => 0,
        'valor_icms_st' => 0, 'valor_reducao_bc' => 0, 'valor_ipi' => 0, 'created_at' => now(), 'updated_at' => now(),
    ]);

    // devolução (CFOP 1411) → entra
    $c190($mkNota(['chave_acesso' => str_pad('A', 44, '0')]), 1411, 1000.00);
    // venda normal (CFOP 5102) → NÃO entra
    $c190($mkNota(['chave_acesso' => str_pad('B', 44, '0'), 'tipo_operacao' => 'saida']), 5102, 9999.00);
    // devolução cancelada → NÃO entra
    $c190($mkNota(['chave_acesso' => str_pad('C', 44, '0'), 'cancelada' => true]), 2202, 5000.00);

    $dev = app(BiService::class)->getDevolucoes($user->id);

    $mar = collect($dev)->firstWhere('mes_formatado', '03/2026');
    expect($mar)->not->toBeNull()
        ->and($mar['valor_devolucoes'])->toBe(1000.00)
        ->and($mar['qtd_devolucoes'])->toBe(1);
});
