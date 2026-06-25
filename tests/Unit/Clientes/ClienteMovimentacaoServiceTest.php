<?php

use App\Models\Cliente;
use App\Models\EfdImportacao;
use App\Models\EfdNota;
use App\Models\User;
use App\Services\Clientes\ClienteMovimentacaoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('agrega kpis/cfop/cst/impostos escopados por cliente_id, excluindo canceladas', function () {
    $user = User::factory()->create();
    $cliente = Cliente::create([
        'user_id' => $user->id, 'razao_social' => 'CLI MOV', 'documento' => '33333333000133',
        'tipo_pessoa' => 'PJ', 'is_empresa_propria' => false,
    ]);
    $outro = Cliente::create([
        'user_id' => $user->id, 'razao_social' => 'OUTRO', 'documento' => '44444444000144',
        'tipo_pessoa' => 'PJ', 'is_empresa_propria' => false,
    ]);
    $imp = EfdImportacao::create(['user_id' => $user->id, 'cliente_id' => $cliente->id, 'tipo_efd' => 'EFD ICMS/IPI', 'filename' => 'f.txt', 'status' => 'concluido', 'iniciado_em' => now()]);

    // nota do cliente (entrada) + item + consolidado
    $nota = EfdNota::create([
        'user_id' => $user->id, 'cliente_id' => $cliente->id, 'importacao_id' => $imp->id,
        'numero' => '1', 'serie' => '1', 'modelo' => '55', 'origem_arquivo' => 'fiscal',
        'tipo_operacao' => 'entrada', 'valor_total' => 1000, 'valor_desconto' => 0,
        'cancelada' => false, 'data_emissao' => '2024-05-01',
    ]);
    DB::table('efd_notas_itens')->insert([
        'efd_nota_id' => $nota->id, 'user_id' => $user->id, 'numero_item' => 1,
        'codigo_item' => 'AGUA', 'descricao' => 'AGUA MINERAL', 'quantidade' => 1,
        'unidade_medida' => 'UN', 'valor_unitario' => 1000, 'valor_total' => 1000,
        'cfop' => 1102, 'cst_icms' => '00', 'aliquota_icms' => 18, 'valor_icms' => 180,
        'created_at' => now(), 'updated_at' => now(),
    ]);
    // nota cancelada do cliente -> deve ser ignorada
    EfdNota::create([
        'user_id' => $user->id, 'cliente_id' => $cliente->id, 'importacao_id' => $imp->id,
        'numero' => '2', 'serie' => '1', 'modelo' => '55', 'origem_arquivo' => 'fiscal',
        'tipo_operacao' => 'entrada', 'valor_total' => 9999, 'valor_desconto' => 0,
        'cancelada' => true, 'data_emissao' => '2024-05-02',
    ]);
    // nota de OUTRO cliente -> fora do escopo
    EfdNota::create([
        'user_id' => $user->id, 'cliente_id' => $outro->id, 'importacao_id' => $imp->id,
        'numero' => '3', 'serie' => '1', 'modelo' => '55', 'origem_arquivo' => 'fiscal',
        'tipo_operacao' => 'saida', 'valor_total' => 7777, 'valor_desconto' => 0,
        'cancelada' => false, 'data_emissao' => '2024-05-03',
    ]);

    $svc = app(ClienteMovimentacaoService::class);

    expect($svc->kpis($cliente)['total_notas'])->toBe(1);
    expect($svc->kpis($cliente)['valor_movimentado'])->toBe(1000.0);
    expect($svc->porCfop($cliente)[0]['cfop'])->toBe('1102');
    expect($svc->porCst($cliente)[0]['cst'])->toBe('00');
    expect($svc->impostos($cliente)['icms'])->toBe(180.0);
    expect($svc->porCompetencia($cliente)[0]['competencia'])->toBe('2024-05');
});
