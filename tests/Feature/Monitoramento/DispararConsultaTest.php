<?php

use App\Actions\Monitoramento\DispararConsultaMonitoramento;
use App\Models\MonitoramentoAssinatura;
use App\Models\MonitoramentoPlano;
use App\Models\Participante;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use function Pest\Laravel\assertDatabaseHas;

uses(RefreshDatabase::class);

it('cria consulta + lote e despacha o batch da pipeline', function () {
    Bus::fake();
    $user = User::factory()->create(['credits' => 100]);
    $plano = MonitoramentoPlano::porCodigo('licitacao');
    $p = Participante::create(['user_id' => $user->id, 'documento' => '11222333000181', 'uf' => 'SP', 'razao_social' => 'ACME']);
    $a = MonitoramentoAssinatura::create([
        'user_id' => $user->id, 'participante_id' => $p->id, 'plano_id' => $plano->id,
        'status' => 'ativo', 'frequencia_dias' => 30, 'proxima_execucao_em' => now(),
    ]);

    $consulta = app(DispararConsultaMonitoramento::class)->execute($a);

    expect($consulta->status)->toBe('pendente')
        ->and($consulta->consulta_lote_id)->not->toBeNull();
    assertDatabaseHas('consulta_lotes', ['id' => $consulta->consulta_lote_id, 'user_id' => $user->id]);
    Bus::assertBatchCount(1);
});

it('pausa o ciclo (erro) e não cria lote quando falta saldo', function () {
    Bus::fake();
    $user = User::factory()->create(['credits' => 0]);
    $plano = MonitoramentoPlano::porCodigo('licitacao');
    $p = Participante::create(['user_id' => $user->id, 'documento' => '11222333000181', 'uf' => 'SP', 'razao_social' => 'ACME']);
    $a = MonitoramentoAssinatura::create([
        'user_id' => $user->id, 'participante_id' => $p->id, 'plano_id' => $plano->id,
        'status' => 'ativo', 'frequencia_dias' => 30, 'proxima_execucao_em' => now(),
    ]);

    $consulta = app(DispararConsultaMonitoramento::class)->execute($a);

    expect($consulta->status)->toBe('erro')
        ->and($consulta->consulta_lote_id)->toBeNull();
    Bus::assertNothingBatched();
});
