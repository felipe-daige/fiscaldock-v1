<?php

use App\Actions\Monitoramento\FinalizarCicloMonitoramento;
use App\Models\ConsultaLote;
use App\Models\ConsultaResultado;
use App\Models\MonitoramentoConsulta;
use App\Models\MonitoramentoPlano;
use App\Models\Participante;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

it('marca a consulta sucesso e deriva situação irregular do resultado do lote', function () {
    $user = User::factory()->create();
    $plano = MonitoramentoPlano::porCodigo('licitacao');
    $p = Participante::create(['user_id' => $user->id, 'documento' => '11222333000181', 'razao_social' => 'ACME']);

    $lote = ConsultaLote::create([
        'user_id' => $user->id, 'plano_id' => $plano->id, 'status' => ConsultaLote::STATUS_CONCLUIDO,
        'total_participantes' => 1, 'creditos_cobrados' => 10, 'tab_id' => (string) Str::uuid(),
    ]);
    ConsultaResultado::create([
        'consulta_lote_id' => $lote->id, 'participante_id' => $p->id,
        'status' => ConsultaResultado::STATUS_SUCESSO,
        'resultado_dados' => ['situacao_cadastral' => 'ATIVA', 'cgu_cnc' => ['possui_sancao' => true]],
    ]);
    $consulta = MonitoramentoConsulta::create([
        'user_id' => $user->id, 'plano_id' => $plano->id, 'participante_id' => $p->id,
        'tipo' => 'assinatura', 'status' => 'pendente', 'consulta_lote_id' => $lote->id, 'creditos_cobrados' => 10,
    ]);

    app(FinalizarCicloMonitoramento::class)->execute($consulta->id);

    $consulta->refresh();
    expect($consulta->status)->toBe('sucesso')
        ->and($consulta->situacao_geral)->toBe('irregular')
        ->and($consulta->tem_pendencias)->toBeTrue();
});

it('marca erro quando o lote não tem resultado de sucesso', function () {
    $user = User::factory()->create();
    $plano = MonitoramentoPlano::porCodigo('licitacao');
    $p = Participante::create(['user_id' => $user->id, 'documento' => '11222333000182', 'razao_social' => 'BAD']);

    $lote = ConsultaLote::create([
        'user_id' => $user->id, 'plano_id' => $plano->id, 'status' => ConsultaLote::STATUS_ERRO,
        'total_participantes' => 1, 'creditos_cobrados' => 10, 'tab_id' => (string) Str::uuid(),
    ]);
    $consulta = MonitoramentoConsulta::create([
        'user_id' => $user->id, 'plano_id' => $plano->id, 'participante_id' => $p->id,
        'tipo' => 'assinatura', 'status' => 'pendente', 'consulta_lote_id' => $lote->id, 'creditos_cobrados' => 10,
    ]);

    app(FinalizarCicloMonitoramento::class)->execute($consulta->id);

    expect($consulta->fresh()->status)->toBe('erro');
});
