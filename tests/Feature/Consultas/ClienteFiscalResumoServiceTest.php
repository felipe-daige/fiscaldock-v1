<?php

use App\Models\EfdImportacao;
use App\Models\EfdNota;
use App\Models\User;
use App\Services\Consultas\ClienteFiscalResumoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function cfrSetup(): array
{
    $user = User::factory()->create();
    $cliente = DB::table('clientes')->insertGetId([
        'user_id' => $user->id, 'razao_social' => 'HIDRATOP LTDA', 'documento' => '00000000000100',
        'is_empresa_propria' => false, 'created_at' => now(), 'updated_at' => now(),
    ]);
    $fornecedor = DB::table('participantes')->insertGetId([
        'user_id' => $user->id, 'cliente_id' => $cliente, 'razao_social' => 'DISTRIBUIDORA X',
        'documento' => '11111111000111', 'origem_tipo' => 'MANUAL', 'created_at' => now(), 'updated_at' => now(),
    ]);
    $comprador = DB::table('participantes')->insertGetId([
        'user_id' => $user->id, 'cliente_id' => $cliente, 'razao_social' => 'MERCADO Y',
        'documento' => '22222222000122', 'origem_tipo' => 'MANUAL', 'created_at' => now(), 'updated_at' => now(),
    ]);
    $imp = EfdImportacao::create(['user_id' => $user->id, 'cliente_id' => $cliente, 'tipo_efd' => 'EFD ICMS/IPI', 'filename' => 'f.txt', 'status' => 'concluido', 'iniciado_em' => now()]);

    DB::table('efd_catalogo_itens')->insert([
        'user_id' => $user->id, 'cliente_id' => $cliente, 'importacao_id' => $imp->id,
        'cod_item' => 'AGUA', 'descr_item' => 'AGUA MINERAL 500ML', 'tipo_item' => '00',
        'cod_ncm' => '22011000', 'created_at' => now(), 'updated_at' => now(),
    ]);

    $mkNota = fn (int $pid, string $op, float $v, string $dt) => EfdNota::create([
        'user_id' => $user->id, 'cliente_id' => $cliente, 'participante_id' => $pid,
        'importacao_id' => $imp->id, 'numero' => random_int(1, 1_000_000), 'serie' => '1',
        'modelo' => '55', 'origem_arquivo' => 'fiscal', 'tipo_operacao' => $op,
        'valor_total' => $v, 'valor_desconto' => 0, 'cancelada' => false, 'data_emissao' => $dt,
    ])->id;

    $ni = 0;
    $mkItem = function (int $notaId, string $cod, float $valor) use ($user, &$ni) {
        $ni++;
        DB::table('efd_notas_itens')->insert([
            'efd_nota_id' => $notaId, 'user_id' => $user->id, 'numero_item' => $ni,
            'codigo_item' => $cod, 'descricao' => 'item', 'quantidade' => 1,
            'unidade_medida' => 'UN', 'valor_unitario' => $valor, 'valor_total' => $valor,
            'cfop' => 1102, 'cst_icms' => '00', 'aliquota_icms' => 18, 'created_at' => now(), 'updated_at' => now(),
        ]);
    };

    // 2 entradas (compras) do fornecedor: 1000 + 200; 1 saida (venda) ao comprador: 700
    $e1 = $mkNota($fornecedor, 'entrada', 1000, '2024-01-05');
    $e2 = $mkNota($fornecedor, 'entrada', 200, '2024-03-10');
    $s1 = $mkNota($comprador, 'saida', 700, '2024-06-20');
    $mkItem($e1, 'AGUA', 1000);
    $mkItem($e2, 'AGUA', 200);
    $mkItem($s1, 'AGUA', 700);

    // ruído ignorado: contribuicoes + cancelada
    $mkNota($fornecedor, 'entrada', 9999, '2024-02-02'); // será marcada contrib abaixo
    DB::table('efd_notas')->where('valor_total', 9999)->update(['origem_arquivo' => 'contribuicoes']);

    return compact('user', 'cliente', 'fornecedor', 'comprador');
}

it('agrega volume do ledger próprio do cliente (entradas/saídas, período)', function () {
    $d = cfrSetup();
    $r = app(ClienteFiscalResumoService::class)->paraClientes($d['user']->id, [$d['cliente']]);

    expect($r)->toHaveKey($d['cliente']);
    $f = $r[$d['cliente']];
    expect($f['perspectiva'])->toBe('cliente');
    expect($f['papel'])->toBeNull();
    expect($f['total_comprado'])->toEqual(1200.0);  // 1000+200, sem contrib
    expect($f['total_vendido'])->toEqual(700.0);
    expect($f['qtd_entrada'])->toBe(2);
    expect($f['qtd_saida'])->toBe(1);
    expect($f['qtd_notas'])->toBe(3);
    expect($f['primeira_nota'])->toBe('2024-01-05');
    expect($f['ultima_nota'])->toBe('2024-06-20');
    expect($f['relacionamentos_titulo'])->toBe('Principais contrapartes');
});

it('top_produtos do cliente vem do catálogo, ordenado por valor', function () {
    $d = cfrSetup();
    $f = app(ClienteFiscalResumoService::class)->paraClientes($d['user']->id, [$d['cliente']])[$d['cliente']];
    expect($f['top_produtos'][0]['cod_item'])->toBe('AGUA');
    expect($f['top_produtos'][0]['valor'])->toEqual(1900.0);  // 1000+200+700
    expect($f['top_produtos'][0]['ncm'])->toBe('22011000');
});

it('relacionamentos = contrapartes (participantes) com papel', function () {
    $d = cfrSetup();
    $f = app(ClienteFiscalResumoService::class)->paraClientes($d['user']->id, [$d['cliente']])[$d['cliente']];
    $porNome = collect($f['relacionamentos'])->keyBy('nome');
    expect($f['empresas_count'])->toBe(2);
    expect($porNome['DISTRIBUIDORA X']['papel'])->toBe('fornecedor');  // só entrada
    expect($porNome['DISTRIBUIDORA X']['valor_entrada'])->toEqual(1200.0);
    expect($porNome['DISTRIBUIDORA X']['is_propria'])->toBeFalse();
    expect($porNome['MERCADO Y']['papel'])->toBe('cliente');           // só saida
    expect($porNome['MERCADO Y']['valor_saida'])->toEqual(700.0);
});

it('cliente sem nota fica ausente; não vaza entre usuários', function () {
    $d = cfrSetup();
    $vazio = DB::table('clientes')->insertGetId([
        'user_id' => $d['user']->id, 'razao_social' => 'SEM NOTA', 'documento' => '00000000000999',
        'is_empresa_propria' => false, 'created_at' => now(), 'updated_at' => now(),
    ]);
    $r = app(ClienteFiscalResumoService::class)->paraClientes($d['user']->id, [$vazio]);
    expect($r)->toBe([]);

    $outro = User::factory()->create();
    expect(app(ClienteFiscalResumoService::class)->paraClientes($outro->id, [$d['cliente']]))->toBe([]);
});

it('config panorama_fiscal.maximo limita o nº de contrapartes', function () {
    config(['consultas.panorama_fiscal.maximo' => 1]);  // cfrSetup tem 2 contrapartes
    $d = cfrSetup();
    $f = app(ClienteFiscalResumoService::class)->paraClientes($d['user']->id, [$d['cliente']])[$d['cliente']];
    expect($f['relacionamentos'])->toHaveCount(1);
    expect($f['empresas_count'])->toBe(2);  // contagem real preservada (só a lista é capada)
});
