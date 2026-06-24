<?php

use App\Models\EfdImportacao;
use App\Models\EfdNota;
use App\Models\User;
use App\Services\Consultas\Fiscal\TopMovimentacaoQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function tmqSetup(): array
{
    $user = User::factory()->create();
    $cliente = DB::table('clientes')->insertGetId([
        'user_id' => $user->id, 'razao_social' => 'EMPRESA A', 'documento' => '00000000000100',
        'is_empresa_propria' => true, 'created_at' => now(), 'updated_at' => now(),
    ]);
    $part = DB::table('participantes')->insertGetId([
        'user_id' => $user->id, 'cliente_id' => $cliente, 'razao_social' => 'FORN X',
        'documento' => '11111111000111', 'origem_tipo' => 'MANUAL', 'created_at' => now(), 'updated_at' => now(),
    ]);
    $imp = EfdImportacao::create(['user_id' => $user->id, 'cliente_id' => $cliente, 'tipo_efd' => 'EFD ICMS/IPI', 'filename' => 'f.txt', 'status' => 'concluido', 'iniciado_em' => now()]);

    // catálogo: AGUA tem NCM, PARAFUSO não está no catálogo (testa fallback)
    DB::table('efd_catalogo_itens')->insert([
        'user_id' => $user->id, 'cliente_id' => $cliente, 'importacao_id' => $imp->id,
        'cod_item' => 'AGUA', 'descr_item' => 'AGUA MINERAL 500ML', 'tipo_item' => '00',
        'cod_ncm' => '22011000', 'created_at' => now(), 'updated_at' => now(),
    ]);

    $mkNota = fn (int $pid, string $op, float $v) => EfdNota::create([
        'user_id' => $user->id, 'cliente_id' => $cliente, 'participante_id' => $pid,
        'importacao_id' => $imp->id, 'numero' => random_int(1, 1_000_000), 'serie' => '1',
        'modelo' => '55', 'origem_arquivo' => 'fiscal', 'tipo_operacao' => $op,
        'valor_total' => $v, 'valor_desconto' => 0, 'cancelada' => false, 'data_emissao' => '2024-05-01',
    ])->id;

    $ni = 0;
    $mkItem = function (int $notaId, string $cod, float $valor, int $cfop) use ($user, &$ni) {
        $ni++;
        DB::table('efd_notas_itens')->insert([
            'efd_nota_id' => $notaId, 'user_id' => $user->id, 'numero_item' => $ni,
            'codigo_item' => $cod, 'descricao' => $cod === 'PARAF' ? 'PARAFUSO 8MM' : 'desc qualquer',
            'quantidade' => 1, 'unidade_medida' => 'UN', 'valor_unitario' => $valor, 'valor_total' => $valor,
            'cfop' => $cfop, 'cst_icms' => '00', 'aliquota_icms' => 18,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    };

    $consol = function (int $notaId, int $cfop, int $seq) use ($user) {
        DB::table('efd_notas_consolidados')->insert([
            'efd_nota_id' => $notaId, 'user_id' => $user->id, 'cfop' => $cfop, 'cst_icms' => '00',
            'aliquota_icms' => $seq, 'valor_operacao' => 100, 'valor_bc_icms' => 0, 'valor_icms' => 0,
            'valor_bc_icms_st' => 0, 'valor_icms_st' => 0, 'valor_reducao_bc' => 0, 'valor_ipi' => 0,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    };

    // AGUA: 2 itens somando 800; PARAF: 1 item de 500 (sem catálogo)
    $n1 = $mkNota($part, 'entrada', 1300);
    $mkItem($n1, 'AGUA', 500, 5102);
    $mkItem($n1, 'AGUA', 300, 5102);
    $mkItem($n1, 'PARAF', 500, 6108);
    $consol($n1, 5102, 1);
    $consol($n1, 5102, 2);
    $consol($n1, 6108, 3);

    return compact('user', 'cliente', 'part');
}

it('produtos: top por valor com NCM do catálogo e fallback de descrição', function () {
    $d = tmqSetup();
    $r = app(TopMovimentacaoQuery::class)->produtos($d['user']->id, 'cliente_id', [$d['cliente']]);

    expect($r)->toHaveKey($d['cliente']);
    $prods = $r[$d['cliente']];
    expect($prods[0]['cod_item'])->toBe('AGUA');            // 800 > 500
    expect($prods[0]['valor'])->toEqual(800.0);
    expect($prods[0]['qtd'])->toBe(2);                       // 2 linhas de item
    expect($prods[0]['descricao'])->toBe('AGUA MINERAL 500ML'); // descr do catálogo
    expect($prods[0]['ncm'])->toBe('22011000');
    expect($prods[1]['cod_item'])->toBe('PARAF');
    expect($prods[1]['descricao'])->toBe('PARAFUSO 8MM');    // fallback: itens.descricao
    expect($prods[1]['ncm'])->toBeNull();                    // sem catálogo
});

it('produtos: respeita o limite', function () {
    $d = tmqSetup();
    $r = app(TopMovimentacaoQuery::class)->produtos($d['user']->id, 'cliente_id', [$d['cliente']], 1);
    expect($r[$d['cliente']])->toHaveCount(1);
    expect($r[$d['cliente']][0]['cod_item'])->toBe('AGUA');
});

it('cfops: top por valor no escopo, com descrição e valor', function () {
    $d = tmqSetup();
    $r = app(TopMovimentacaoQuery::class)->cfops($d['user']->id, 'cliente_id', [$d['cliente']]);
    $cfops = $r[$d['cliente']];
    expect($cfops[0]['cfop'])->toBe(5102);              // 2 ocorrências × valor_operacao 100 = 200 (maior)
    expect($cfops[0]['qtd'])->toBe(2);
    expect($cfops[0]['valor'])->toEqual(200.0);         // SUM(valor_operacao)
    expect($cfops[0]['descricao'])->toContain('5102');  // descrição CONFAZ (App\Support\Cfop)
    expect(collect($cfops)->pluck('cfop')->all())->toContain(6108);
});

it('produtos por participante usa a coluna participante_id', function () {
    $d = tmqSetup();
    $r = app(TopMovimentacaoQuery::class)->produtos($d['user']->id, 'participante_id', [$d['part']]);
    expect($r[$d['part']][0]['cod_item'])->toBe('AGUA');
});

it('coluna inválida lança exceção', function () {
    app(TopMovimentacaoQuery::class)->produtos(1, 'razao_social', [1]);
})->throws(InvalidArgumentException::class);

it('ids vazios retornam array vazio', function () {
    expect(app(TopMovimentacaoQuery::class)->produtos(1, 'cliente_id', []))->toBe([]);
    expect(app(TopMovimentacaoQuery::class)->cfops(1, 'cliente_id', []))->toBe([]);
});

it('não vaza produtos nem cfops entre usuários', function () {
    $d = tmqSetup();
    $outro = App\Models\User::factory()->create();
    expect(app(TopMovimentacaoQuery::class)->produtos($outro->id, 'cliente_id', [$d['cliente']]))->toBe([]);
    expect(app(TopMovimentacaoQuery::class)->cfops($outro->id, 'cliente_id', [$d['cliente']]))->toBe([]);
});
