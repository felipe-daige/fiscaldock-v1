<?php

use App\Models\User;
use App\Services\BiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function semearContextoCob(): array
{
    $user = User::factory()->create();
    $cli = DB::table('clientes')->insertGetId([
        'user_id' => $user->id, 'documento' => '00000000000191', 'razao_social' => 'Empresa Teste',
        'created_at' => now(), 'updated_at' => now(),
    ]);
    $imp = DB::table('efd_importacoes')->insertGetId([
        'user_id' => $user->id, 'cliente_id' => $cli, 'tipo_efd' => 'EFD ICMS/IPI', 'status' => 'concluido',
        'created_at' => now(), 'updated_at' => now(),
    ]);

    return [$user->id, $cli, $imp];
}

function semearNotaCob(int $userId, int $cli, int $imp, string $origem, string $data, ?int $outroCliente = null): void
{
    DB::table('efd_notas')->insert([
        'user_id' => $userId, 'cliente_id' => $outroCliente ?? $cli, 'importacao_id' => $imp,
        'origem_arquivo' => $origem, 'modelo' => '55', 'tipo_operacao' => 'saida', 'cancelada' => false,
        'valor_total' => 100, 'data_emissao' => $data, 'numero' => random_int(1, 999999),
        'created_at' => now(), 'updated_at' => now(),
    ]);
}

it('cenario assimetrico: fiscal so no mes 1, contrib nos meses 1-3 → sem_fiscal = [mes2, mes3]', function () {
    [$uid, $cli, $imp] = semearContextoCob();
    semearNotaCob($uid, $cli, $imp, 'fiscal', '2025-01-10');
    semearNotaCob($uid, $cli, $imp, 'contribuicoes', '2025-01-10');
    semearNotaCob($uid, $cli, $imp, 'contribuicoes', '2025-02-10');
    semearNotaCob($uid, $cli, $imp, 'contribuicoes', '2025-03-10');

    $cob = app(BiService::class)->getCoberturaResumo($uid, null, null, null);

    expect($cob['meses_total'])->toBe(3);
    expect(array_column($cob['meses_sem_fiscal'], 'mes'))->toBe(['2025-02', '2025-03']);
    expect($cob['meses_sem_contrib'])->toBe([]);
    expect($cob['parcial'])->toBeTrue();
});

it('respeita clienteId: notas de outro cliente nao afetam a cobertura', function () {
    [$uid, $cli, $imp] = semearContextoCob();
    $outro = DB::table('clientes')->insertGetId([
        'user_id' => $uid, 'documento' => '00000000000272', 'razao_social' => 'Outra',
        'created_at' => now(), 'updated_at' => now(),
    ]);
    // cliente alvo: fiscal+contrib em jan
    semearNotaCob($uid, $cli, $imp, 'fiscal', '2025-01-10');
    semearNotaCob($uid, $cli, $imp, 'contribuicoes', '2025-01-10');
    // outro cliente: contrib em fev (não deve aparecer no filtro do alvo)
    semearNotaCob($uid, $cli, $imp, 'contribuicoes', '2025-02-10', $outro);

    $cob = app(BiService::class)->getCoberturaResumo($uid, null, null, $cli);

    expect($cob['meses_total'])->toBe(1);
    expect($cob['parcial'])->toBeFalse();
});

it('respeita janela dataInicio/dataFim', function () {
    [$uid, $cli, $imp] = semearContextoCob();
    semearNotaCob($uid, $cli, $imp, 'fiscal', '2025-01-10');
    semearNotaCob($uid, $cli, $imp, 'contribuicoes', '2025-01-10');
    semearNotaCob($uid, $cli, $imp, 'contribuicoes', '2025-02-10');

    $cob = app(BiService::class)->getCoberturaResumo($uid, '2025-02-01', '2025-02-28', null);

    expect($cob['meses_total'])->toBe(1);
    expect(array_column($cob['meses_sem_fiscal'], 'mes'))->toBe(['2025-02']);
});

it('cobertura completa: fiscal e contrib em todos os meses → parcial false', function () {
    [$uid, $cli, $imp] = semearContextoCob();
    semearNotaCob($uid, $cli, $imp, 'fiscal', '2025-01-10');
    semearNotaCob($uid, $cli, $imp, 'contribuicoes', '2025-01-10');

    $cob = app(BiService::class)->getCoberturaResumo($uid, null, null, null);

    expect($cob['parcial'])->toBeFalse();
    expect($cob['meses_sem_fiscal'])->toBe([]);
    expect($cob['meses_sem_contrib'])->toBe([]);
});

it('sem notas: retorno vazio', function () {
    [$uid] = semearContextoCob();

    $cob = app(BiService::class)->getCoberturaResumo($uid, null, null, null);

    expect($cob['meses_total'])->toBe(0);
    expect($cob['parcial'])->toBeFalse();
});

it('gap total fica em meses_gap_total e fora de sem_fiscal/sem_contrib', function () {
    [$uid, $cli, $imp] = semearContextoCob();
    // jan completo; fev sem nenhuma nota (gap total no meio do range)
    semearNotaCob($uid, $cli, $imp, 'fiscal', '2025-01-10');
    semearNotaCob($uid, $cli, $imp, 'contribuicoes', '2025-01-10');
    semearNotaCob($uid, $cli, $imp, 'fiscal', '2025-03-10');
    semearNotaCob($uid, $cli, $imp, 'contribuicoes', '2025-03-10');

    $cob = app(BiService::class)->getCoberturaResumo($uid, null, null, null);

    expect(array_column($cob['meses_gap_total'], 'mes'))->toBe(['2025-02']);
    expect($cob['meses_sem_fiscal'])->toBe([]);
    expect($cob['meses_sem_contrib'])->toBe([]);
});

it('GET /app/bi/resumo inclui cobertura no payload', function () {
    [$uid, $cli, $imp] = semearContextoCob();
    semearNotaCob($uid, $cli, $imp, 'fiscal', '2025-01-10');
    semearNotaCob($uid, $cli, $imp, 'contribuicoes', '2025-01-10');
    semearNotaCob($uid, $cli, $imp, 'contribuicoes', '2025-02-10');

    $resp = $this->actingAs(User::find($uid))
        ->getJson('/app/bi/resumo');

    $resp->assertOk()
        ->assertJsonPath('cobertura.parcial', true)
        ->assertJsonPath('cobertura.meses_sem_fiscal.0.mes', '2025-02');
});
