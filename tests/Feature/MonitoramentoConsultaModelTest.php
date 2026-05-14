<?php

use App\Models\MonitoramentoConsulta;
use App\Models\MonitoramentoPlano;
use App\Models\Participante;
use App\Models\User;
use Database\Seeders\MonitoramentoPlanoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function novaConsultaErro(int $userId, int $participanteId, int $planoId, ?int $parentId = null): MonitoramentoConsulta
{
    return MonitoramentoConsulta::create([
        'user_id' => $userId, 'participante_id' => $participanteId, 'plano_id' => $planoId,
        'tipo' => 'assinatura', 'status' => 'erro', 'creditos_cobrados' => 5,
        'parent_consulta_id' => $parentId, 'executado_em' => now(),
    ]);
}

it('conta retries andando a cadeia de pais', function () {
    $this->seed(MonitoramentoPlanoSeeder::class);
    $plano = MonitoramentoPlano::query()->first();
    $user = User::factory()->create();
    $participante = Participante::create([
        'user_id' => $user->id, 'documento' => '11222333000181',
        'tipo_documento' => 'PJ', 'razao_social' => 'Fornecedor X',
    ]);

    $original = novaConsultaErro($user->id, $participante->id, $plano->id);
    $retry1 = novaConsultaErro($user->id, $participante->id, $plano->id, $original->id);
    $retry2 = novaConsultaErro($user->id, $participante->id, $plano->id, $retry1->id);

    expect($original->retryCount())->toBe(0);
    expect($retry1->retryCount())->toBe(1);
    expect($retry2->retryCount())->toBe(2);
    expect($retry2->parent->is($retry1))->toBeTrue();
    expect($original->retries->first()->is($retry1))->toBeTrue();
});
