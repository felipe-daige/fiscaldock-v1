<?php

use App\Models\EfdImportacao;
use App\Models\EfdNota;
use App\Models\User;
use App\Services\Consultas\ParticipanteFiscalResumoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function pfrSetup(): array
{
    $user = User::factory()->create();
    $mkCli = fn (string $nome, string $doc, bool $propria) => DB::table('clientes')->insertGetId([
        'user_id' => $user->id, 'razao_social' => $nome, 'documento' => $doc,
        'is_empresa_propria' => $propria, 'created_at' => now(), 'updated_at' => now(),
    ]);
    $empresaA = $mkCli('EMPRESA A', '00000000000100', true);
    $empresaB = $mkCli('EMPRESA B', '00000000000200', false);

    $mkPart = fn (string $doc) => DB::table('participantes')->insertGetId([
        'user_id' => $user->id, 'cliente_id' => $empresaA, 'razao_social' => "P {$doc}",
        'documento' => $doc, 'origem_tipo' => 'MANUAL', 'created_at' => now(), 'updated_at' => now(),
    ]);
    $forn = $mkPart('11111111000111');   // só entrada → fornecedor
    $cli = $mkPart('22222222000122');    // só saida → cliente
    $ambos = $mkPart('33333333000133');  // entrada + saida, 2 empresas → ambos

    $impF = EfdImportacao::create(['user_id' => $user->id, 'cliente_id' => $empresaA, 'tipo_efd' => 'EFD ICMS/IPI', 'filename' => 'f.txt', 'status' => 'concluido', 'iniciado_em' => now()]);
    $impC = EfdImportacao::create(['user_id' => $user->id, 'cliente_id' => $empresaA, 'tipo_efd' => 'EFD PIS/COFINS', 'filename' => 'c.txt', 'status' => 'concluido', 'iniciado_em' => now()]);
    $n = 0;
    $mk = function (array $a) use ($user, &$n) {
        $n++;
        return EfdNota::create(array_merge([
            'user_id' => $user->id, 'numero' => $n, 'serie' => '1', 'data_emissao' => '2024-03-10',
            'valor_desconto' => 0, 'cancelada' => false, 'modelo' => '55', 'origem_arquivo' => 'fiscal',
        ], $a));
    };

    // fornecedor: 2 entradas na empresa A
    $mk(['importacao_id' => $impF->id, 'cliente_id' => $empresaA, 'participante_id' => $forn, 'tipo_operacao' => 'entrada', 'valor_total' => 1000, 'data_emissao' => '2024-01-05']);
    $mk(['importacao_id' => $impF->id, 'cliente_id' => $empresaA, 'participante_id' => $forn, 'tipo_operacao' => 'entrada', 'valor_total' => 500, 'data_emissao' => '2024-04-20']);
    // cliente: 1 saida na empresa A
    $mk(['importacao_id' => $impF->id, 'cliente_id' => $empresaA, 'participante_id' => $cli, 'tipo_operacao' => 'saida', 'valor_total' => 300]);
    // ambos: entrada na A + saida na B
    $mk(['importacao_id' => $impF->id, 'cliente_id' => $empresaA, 'participante_id' => $ambos, 'tipo_operacao' => 'entrada', 'valor_total' => 200]);
    $mk(['importacao_id' => $impF->id, 'cliente_id' => $empresaB, 'participante_id' => $ambos, 'tipo_operacao' => 'saida', 'valor_total' => 700]);
    // ruído: contribuicoes (ignorado) + cancelada (ignorada) sob fornecedor
    $mk(['importacao_id' => $impC->id, 'cliente_id' => $empresaA, 'participante_id' => $forn, 'tipo_operacao' => 'entrada', 'valor_total' => 9999, 'origem_arquivo' => 'contribuicoes']);
    $mk(['importacao_id' => $impF->id, 'cliente_id' => $empresaA, 'participante_id' => $forn, 'tipo_operacao' => 'entrada', 'valor_total' => 8888, 'cancelada' => true]);

    return compact('user', 'forn', 'cli', 'ambos', 'empresaA', 'empresaB');
}

it('classifica fornecedor (só entrada), com valor e período corretos', function () {
    $d = pfrSetup();
    $r = app(ParticipanteFiscalResumoService::class)->paraParticipantes($d['user']->id, [$d['forn']]);

    expect($r)->toHaveKey($d['forn']);
    $f = $r[$d['forn']];
    expect($f['papel'])->toBe('fornecedor');
    expect($f['total_comprado'])->toEqual(1500.0);  // 1000+500, sem contrib/cancelada
    expect($f['total_vendido'])->toEqual(0.0);
    expect($f['qtd_notas'])->toBe(2);
    expect($f['primeira_nota'])->toBe('2024-01-05');
    expect($f['ultima_nota'])->toBe('2024-04-20');
    expect($f['empresas_count'])->toBe(1);
});

it('classifica cliente (só saída)', function () {
    $d = pfrSetup();
    $r = app(ParticipanteFiscalResumoService::class)->paraParticipantes($d['user']->id, [$d['cli']]);
    expect($r[$d['cli']]['papel'])->toBe('cliente');
    expect($r[$d['cli']]['total_vendido'])->toEqual(300.0);
    expect($r[$d['cli']]['total_comprado'])->toEqual(0.0);
});

it('classifica ambos e separa relacionamento por empresa', function () {
    $d = pfrSetup();
    $r = app(ParticipanteFiscalResumoService::class)->paraParticipantes($d['user']->id, [$d['ambos']]);
    $a = $r[$d['ambos']];
    expect($a['papel'])->toBe('ambos');
    expect($a['empresas_count'])->toBe(2);
    $porEmpresa = collect($a['relacionamentos'])->keyBy('empresa_id');
    expect($porEmpresa[$d['empresaA']]['papel'])->toBe('fornecedor');
    expect($porEmpresa[$d['empresaA']]['valor_entrada'])->toEqual(200.0);
    expect($porEmpresa[$d['empresaB']]['papel'])->toBe('cliente');
    expect($porEmpresa[$d['empresaB']]['valor_saida'])->toEqual(700.0);
    expect($porEmpresa[$d['empresaA']]['empresa_nome'])->toBe('EMPRESA A');
    expect($porEmpresa[$d['empresaA']]['is_empresa_propria'])->toBeTrue();
});

it('não vaza entre usuários e omite participante sem notas', function () {
    $d = pfrSetup();
    $outro = User::factory()->create();
    $r = app(ParticipanteFiscalResumoService::class)->paraParticipantes($outro->id, [$d['forn'], $d['cli']]);
    expect($r)->toBe([]);
});
