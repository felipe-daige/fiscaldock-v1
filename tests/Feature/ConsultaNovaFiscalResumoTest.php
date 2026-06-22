<?php

use App\Models\EfdImportacao;
use App\Models\EfdNota;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

it('getParticipantes traz fiscal_resumo com papel e valor quando há notas', function () {
    $user = User::factory()->create();
    $empresa = DB::table('clientes')->insertGetId([
        'user_id' => $user->id, 'razao_social' => 'MINHA EMPRESA', 'documento' => '00000000000100',
        'is_empresa_propria' => true, 'created_at' => now(), 'updated_at' => now(),
    ]);
    $part = DB::table('participantes')->insertGetId([
        'user_id' => $user->id, 'cliente_id' => $empresa, 'razao_social' => 'FORNECEDOR X',
        'documento' => '11111111000111', 'origem_tipo' => 'MANUAL', 'created_at' => now(), 'updated_at' => now(),
    ]);
    $imp = EfdImportacao::create(['user_id' => $user->id, 'cliente_id' => $empresa, 'tipo_efd' => 'EFD ICMS/IPI', 'filename' => 'f.txt', 'status' => 'concluido', 'iniciado_em' => now()]);
    EfdNota::create(['user_id' => $user->id, 'cliente_id' => $empresa, 'participante_id' => $part, 'importacao_id' => $imp->id, 'numero' => 1, 'serie' => '1', 'modelo' => '55', 'origem_arquivo' => 'fiscal', 'tipo_operacao' => 'entrada', 'valor_total' => 1500, 'valor_desconto' => 0, 'cancelada' => false, 'data_emissao' => '2024-02-02']);

    $resp = actingAs($user)->getJson('/app/consulta/nova/participantes')->assertOk()->json();
    $item = collect($resp['data'])->firstWhere('id', $part);

    expect($item['fiscal_resumo'])->not->toBeNull();
    expect($item['fiscal_resumo']['papel'])->toBe('fornecedor');
    expect($item['fiscal_resumo']['papel_label'])->toBe('Fornecedor');
    expect($item['fiscal_resumo']['empresa_label'])->toBe('MINHA EMPRESA');
    expect($item['fiscal_resumo']['total_formatado'])->toBe('R$ 1.500,00');
});

it('fiscal_resumo é null para participante sem notas', function () {
    $user = User::factory()->create();
    $part = DB::table('participantes')->insertGetId([
        'user_id' => $user->id, 'razao_social' => 'SEM NOTAS', 'documento' => '99999999000199',
        'origem_tipo' => 'MANUAL', 'created_at' => now(), 'updated_at' => now(),
    ]);
    $resp = actingAs($user)->getJson('/app/consulta/nova/participantes')->assertOk()->json();
    $item = collect($resp['data'])->firstWhere('id', $part);
    expect($item['fiscal_resumo'])->toBeNull();
});

it('filtra participantes por relação (inclusivo: fornecedor inclui ambos)', function () {
    $user = User::factory()->create();
    $empA = DB::table('clientes')->insertGetId(['user_id' => $user->id, 'razao_social' => 'EMP A', 'documento' => '00000000000100', 'is_empresa_propria' => true, 'created_at' => now(), 'updated_at' => now()]);
    $empB = DB::table('clientes')->insertGetId(['user_id' => $user->id, 'razao_social' => 'EMP B', 'documento' => '00000000000200', 'is_empresa_propria' => false, 'created_at' => now(), 'updated_at' => now()]);
    $mkPart = fn (string $doc, string $nome) => DB::table('participantes')->insertGetId(['user_id' => $user->id, 'cliente_id' => $empA, 'razao_social' => $nome, 'documento' => $doc, 'origem_tipo' => 'MANUAL', 'created_at' => now(), 'updated_at' => now()]);
    $forn = $mkPart('11111111000111', 'FORN');
    $cli = $mkPart('22222222000122', 'CLI');
    $ambos = $mkPart('33333333000133', 'AMBOS');
    $semNota = $mkPart('44444444000144', 'SEM NOTA');

    $imp = EfdImportacao::create(['user_id' => $user->id, 'cliente_id' => $empA, 'tipo_efd' => 'EFD ICMS/IPI', 'filename' => 'f.txt', 'status' => 'concluido', 'iniciado_em' => now()]);
    $n = 0;
    $mk = function (int $pid, int $emp, string $op) use ($user, $imp, &$n) {
        $n++;
        EfdNota::create(['user_id' => $user->id, 'cliente_id' => $emp, 'participante_id' => $pid, 'importacao_id' => $imp->id, 'numero' => $n, 'serie' => '1', 'modelo' => '55', 'origem_arquivo' => 'fiscal', 'tipo_operacao' => $op, 'valor_total' => 100, 'valor_desconto' => 0, 'cancelada' => false, 'data_emissao' => '2024-03-01']);
    };
    $mk($forn, $empA, 'entrada');
    $mk($cli, $empA, 'saida');
    $mk($ambos, $empA, 'entrada');
    $mk($ambos, $empB, 'saida');

    $idsDe = function (string $relacao) use ($user) {
        $resp = actingAs($user)->getJson('/app/consulta/nova/participantes?relacao='.$relacao)->assertOk()->json();
        return collect($resp['data'])->pluck('id')->all();
    };

    expect($idsDe('fornecedor'))->toEqualCanonicalizing([$forn, $ambos]);
    expect($idsDe('cliente'))->toEqualCanonicalizing([$cli, $ambos]);
    expect($idsDe('ambos'))->toEqualCanonicalizing([$ambos]);
    expect($idsDe('sem_movimentacao'))->toEqualCanonicalizing([$semNota]);
});
