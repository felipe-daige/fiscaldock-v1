<?php

use App\Models\Alerta;
use App\Models\MonitoramentoAssinatura;
use App\Models\MonitoramentoConsulta;
use App\Models\MonitoramentoPlano;
use App\Models\Participante;
use App\Models\User;
use Database\Seeders\MonitoramentoPlanoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(MonitoramentoPlanoSeeder::class);
    $this->plano = MonitoramentoPlano::query()->first();
    $this->token = config('services.api.token');
    $this->user = User::factory()->create(['credits' => 50]);
    $this->participante = Participante::create([
        'user_id' => $this->user->id, 'documento' => '11222333000181',
        'tipo_documento' => 'PJ', 'razao_social' => 'Fornecedor X',
    ]);
    $this->assinatura = MonitoramentoAssinatura::create([
        'user_id' => $this->user->id, 'participante_id' => $this->participante->id,
        'plano_id' => $this->plano->id, 'status' => 'ativo', 'frequencia_dias' => 30,
    ]);
});

it('avalia mudança de situação no retorno de sucesso', function () {
    MonitoramentoConsulta::create([
        'user_id' => $this->user->id, 'participante_id' => $this->participante->id,
        'plano_id' => $this->plano->id, 'assinatura_id' => $this->assinatura->id,
        'tipo' => 'assinatura', 'status' => 'sucesso', 'situacao_geral' => 'regular',
        'creditos_cobrados' => 5, 'executado_em' => now()->subMonth(),
    ]);
    $nova = MonitoramentoConsulta::create([
        'user_id' => $this->user->id, 'participante_id' => $this->participante->id,
        'plano_id' => $this->plano->id, 'assinatura_id' => $this->assinatura->id,
        'tipo' => 'assinatura', 'status' => 'pendente', 'creditos_cobrados' => 5,
    ]);

    $this->withHeader('X-API-Token', $this->token)
        ->postJson('/api/monitoramento/consulta/resultado', [
            'consulta_id' => $nova->id,
            'status' => 'sucesso',
            'situacao_geral' => 'irregular',
        ])
        ->assertOk();

    expect(Alerta::where('tipo', 'monitoramento_situacao_piorou')->count())->toBe(1);
});

it('estorna crédito no retorno de erro', function () {
    $consulta = MonitoramentoConsulta::create([
        'user_id' => $this->user->id, 'participante_id' => $this->participante->id,
        'plano_id' => $this->plano->id, 'assinatura_id' => $this->assinatura->id,
        'tipo' => 'assinatura', 'status' => 'pendente', 'creditos_cobrados' => 10,
    ]);

    $this->withHeader('X-API-Token', $this->token)
        ->postJson('/api/monitoramento/consulta/resultado', [
            'consulta_id' => $consulta->id,
            'status' => 'erro',
            'error_code' => 'timeout',
            'error_message' => 'Provedor não respondeu',
        ])
        ->assertOk();

    expect($this->user->fresh()->credits)->toBe(60);
    expect($consulta->fresh()->status)->toBe('erro');
});
