<?php

use App\Models\EfdImportacao;
use App\Models\EfdNota;
use App\Models\User;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('view do cliente lista produtos e CFOPs', function () {
    $this->seed(SubscriptionPlanSeeder::class);

    $user = User::factory()->create();

    // cliente não-própria
    $clienteId = DB::table('clientes')->insertGetId([
        'user_id' => $user->id,
        'razao_social' => 'Empresa Teste SA',
        'documento' => '12345678000195',
        'tipo_pessoa' => 'PJ',
        'is_empresa_propria' => false,
        'ativo' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $part = DB::table('participantes')->insertGetId([
        'user_id' => $user->id,
        'cliente_id' => $clienteId,
        'razao_social' => 'Fornecedor X',
        'documento' => '98765432000111',
        'origem_tipo' => 'MANUAL',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $imp = EfdImportacao::create([
        'user_id' => $user->id,
        'cliente_id' => $clienteId,
        'tipo_efd' => 'EFD ICMS/IPI',
        'filename' => 'teste.txt',
        'status' => 'concluido',
        'iniciado_em' => now(),
    ]);

    DB::table('efd_catalogo_itens')->insert([
        'user_id' => $user->id,
        'cliente_id' => $clienteId,
        'importacao_id' => $imp->id,
        'cod_item' => 'PROD01',
        'descr_item' => 'PRODUTO PRINCIPAL',
        'tipo_item' => '00',
        'cod_ncm' => '12345678',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $notaId = EfdNota::create([
        'user_id' => $user->id,
        'cliente_id' => $clienteId,
        'participante_id' => $part,
        'importacao_id' => $imp->id,
        'numero' => '1001',
        'serie' => '1',
        'modelo' => '55',
        'origem_arquivo' => 'fiscal',
        'tipo_operacao' => 'entrada',
        'valor_total' => 1500.0,
        'valor_desconto' => 0,
        'cancelada' => false,
        'data_emissao' => '2024-05-01',
    ])->id;

    DB::table('efd_notas_itens')->insert([
        'efd_nota_id' => $notaId,
        'user_id' => $user->id,
        'numero_item' => 1,
        'codigo_item' => 'PROD01',
        'descricao' => 'PRODUTO PRINCIPAL',
        'quantidade' => 1,
        'unidade_medida' => 'UN',
        'valor_unitario' => 1500.0,
        'valor_total' => 1500.0,
        'cfop' => 1102,
        'cst_icms' => '00',
        'aliquota_icms' => 12,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('efd_notas_consolidados')->insert([
        'efd_nota_id' => $notaId,
        'user_id' => $user->id,
        'cfop' => 1102,
        'cst_icms' => '00',
        'aliquota_icms' => 12,
        'valor_operacao' => 1500.0,
        'valor_bc_icms' => 0,
        'valor_icms' => 0,
        'valor_bc_icms_st' => 0,
        'valor_icms_st' => 0,
        'valor_reducao_bc' => 0,
        'valor_ipi' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $response = $this->actingAs($user)
        ->withHeader('X-Requested-With', 'XMLHttpRequest')
        ->get('/app/cliente/'.$clienteId);

    $response->assertOk()
        ->assertSee('Principais produtos')
        ->assertSee('PRODUTO PRINCIPAL')
        ->assertSee('1102');
});
