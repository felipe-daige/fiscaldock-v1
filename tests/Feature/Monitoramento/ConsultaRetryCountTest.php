<?php

use App\Models\MonitoramentoConsulta;
use App\Models\MonitoramentoPlano;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('conta a cadeia de retries via parent_consulta_id', function () {
    $user = User::factory()->create();
    $plano = MonitoramentoPlano::porCodigo('licitacao');

    $c1 = MonitoramentoConsulta::create(['user_id' => $user->id, 'plano_id' => $plano->id, 'tipo' => 'assinatura', 'status' => 'erro', 'creditos_cobrados' => 0]);
    $c2 = MonitoramentoConsulta::create(['user_id' => $user->id, 'plano_id' => $plano->id, 'tipo' => 'assinatura', 'status' => 'erro', 'creditos_cobrados' => 0, 'parent_consulta_id' => $c1->id]);
    $c3 = MonitoramentoConsulta::create(['user_id' => $user->id, 'plano_id' => $plano->id, 'tipo' => 'assinatura', 'status' => 'erro', 'creditos_cobrados' => 0, 'parent_consulta_id' => $c2->id]);

    expect($c1->retryCount())->toBe(0)
        ->and($c2->retryCount())->toBe(1)
        ->and($c3->retryCount())->toBe(2);
});
