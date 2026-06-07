<?php

use App\Models\NfeConsulta;
use App\Models\User;
use App\Services\Clearance\Sefaz\ContextoPersistencia;
use App\Services\Clearance\Sefaz\DocumentoSnapshot;
use App\Services\Clearance\Sefaz\SnapshotPersister;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

function snap(string $status = 'AUTORIZADA', float $valor = 100.0): DocumentoSnapshot
{
    return new DocumentoSnapshot('NFE', str_repeat('5', 44), $status,
        ['status' => $status, 'valor_total' => $valor, 'emit_cnpj' => '11111111111111', 'eventos' => [], 'totais' => [], 'produtos' => []],
        ['nfe_clearance' => ['status' => $status]], true, false, true);
}

it('insere snapshot NF-e com contexto', function () {
    $user = User::factory()->create();
    $lote = \App\Models\ConsultaLote::create([
        'user_id' => $user->id, 'plano_id' => null, 'status' => \App\Models\ConsultaLote::STATUS_PROCESSANDO,
        'total_participantes' => 1, 'creditos_cobrados' => 3, 'tab_id' => 'tab-x',
    ]);
    app(SnapshotPersister::class)->upsert(snap(), new ContextoPersistencia(userId: $user->id, consultaLoteId: $lote->id, custo: 2.0));

    $row = NfeConsulta::where('user_id', $user->id)->where('chave_acesso', str_repeat('5', 44))->firstOrFail();
    expect($row->status)->toBe('AUTORIZADA');
    expect((float) $row->valor_total)->toBe(100.0);
    expect($row->consulta_lote_id)->toBe($lote->id);
    expect((float) $row->custo)->toBe(2.0);
    expect($row->consultado_em)->not->toBeNull();
    expect($row->payload['nfe_clearance']['status'])->toBe('AUTORIZADA');
});

it('UPSERT por (user_id, chave_acesso): segunda consulta sobrescreve, não duplica', function () {
    $user = User::factory()->create();
    $p = app(SnapshotPersister::class);
    $p->upsert(snap('AUTORIZADA', 100.0), new ContextoPersistencia(userId: $user->id));
    $p->upsert(snap('CANCELADA', 100.0), new ContextoPersistencia(userId: $user->id));

    expect(NfeConsulta::where('user_id', $user->id)->count())->toBe(1);
    expect(NfeConsulta::where('user_id', $user->id)->first()->status)->toBe('CANCELADA');
});

it('isola por user_id (mesma chave, usuários distintos = 2 linhas)', function () {
    $a = User::factory()->create();
    $b = User::factory()->create();
    $p = app(SnapshotPersister::class);
    $p->upsert(snap(), new ContextoPersistencia(userId: $a->id));
    $p->upsert(snap(), new ContextoPersistencia(userId: $b->id));

    expect(NfeConsulta::where('chave_acesso', str_repeat('5', 44))->count())->toBe(2);
});
