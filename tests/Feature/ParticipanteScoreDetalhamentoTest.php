<?php

use App\Models\ConsultaLote;
use App\Models\ConsultaResultado;
use App\Models\MonitoramentoPlano;
use App\Models\Participante;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function planoScoreParticipante(): MonitoramentoPlano
{
    return MonitoramentoPlano::ativos()->first() ?? MonitoramentoPlano::create([
        'nome' => 'Gratuito', 'codigo' => 'gratuito', 'ativo' => true,
        'creditos_por_consulta' => 0, 'consultas_incluidas' => [], 'etapas' => [],
    ]);
}

it('perfil do participante mostra detalhamento do score quando há consulta', function () {
    $user = User::factory()->create();
    $part = Participante::create([
        'user_id' => $user->id, 'documento' => '11111111000111',
        'razao_social' => 'FORNECEDOR X', 'uf' => 'SP', 'crt' => '3',
    ]);
    $lote = ConsultaLote::create([
        'user_id' => $user->id, 'plano_id' => planoScoreParticipante()->id, 'status' => ConsultaLote::STATUS_FINALIZADO,
        'total_participantes' => 1, 'creditos_cobrados' => 0, 'tab_id' => 'tab-'.uniqid(), 'processado_em' => now(),
    ]);
    $lote->participantes()->attach([$part->id]);
    ConsultaResultado::create([
        'consulta_lote_id' => $lote->id, 'participante_id' => $part->id, 'status' => ConsultaResultado::STATUS_SUCESSO,
        'consultado_em' => now(),
        'resultado_dados' => ['situacao_cadastral' => 'ATIVA', 'cnd_federal' => ['status' => 'Negativa']],
    ]);

    $resp = $this->actingAs($user)->get("/app/participante/{$part->id}");
    $resp->assertOk();
    $resp->assertSee('Detalhamento do Score');
    $resp->assertSee('Situação Cadastral');
    $resp->assertSee('Peso:');
});

it('perfil do participante sem consulta mostra empty-state do score', function () {
    $user = User::factory()->create();
    $part = Participante::create([
        'user_id' => $user->id, 'documento' => '22222222000122', 'razao_social' => 'SEM CONSULTA', 'uf' => 'SP',
    ]);
    $resp = $this->actingAs($user)->get("/app/participante/{$part->id}");
    $resp->assertOk();
    $resp->assertSee('Score não calculado');
});
