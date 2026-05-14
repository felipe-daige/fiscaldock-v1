<?php

use App\Models\MonitoramentoAssinatura;
use App\Models\MonitoramentoConsulta;
use App\Models\MonitoramentoPlano;
use App\Models\Participante;
use App\Models\User;
use Database\Seeders\MonitoramentoPlanoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    Http::fake();
    $this->seed(MonitoramentoPlanoSeeder::class);
    $this->plano = MonitoramentoPlano::query()->first();
    $this->user = User::factory()->create();
    $this->participante = Participante::create([
        'user_id' => $this->user->id, 'documento' => '11222333000181',
        'tipo_documento' => 'PJ', 'razao_social' => 'Fornecedor X',
        'latitude' => -23.5, 'longitude' => -46.6,
    ]);
});

it('mostra o painel da assinatura com custo mensal e total consumido (excluindo erro)', function () {
    MonitoramentoAssinatura::create([
        'user_id' => $this->user->id, 'participante_id' => $this->participante->id,
        'plano_id' => $this->plano->id, 'status' => 'ativo', 'frequencia_dias' => 30,
    ]);
    foreach (['sucesso', 'sucesso', 'erro'] as $status) {
        MonitoramentoConsulta::create([
            'user_id' => $this->user->id, 'participante_id' => $this->participante->id,
            'plano_id' => $this->plano->id, 'tipo' => 'assinatura', 'status' => $status,
            'creditos_cobrados' => 7, 'executado_em' => now(),
        ]);
    }

    $response = $this->actingAs($this->user)->get("/app/participante/{$this->participante->id}");

    $response->assertOk();
    $response->assertSee('Custo Mensal Estimado');
    $response->assertSee('Total Já Consumido');
    $response->assertSee('14 creditos'); // 2 sucesso x 7; erro excluido
    $response->assertDontSee('21 creditos'); // somaria o erro se nao excluisse
});

it('não renderiza o painel quando não há assinatura', function () {
    $response = $this->actingAs($this->user)->get("/app/participante/{$this->participante->id}");

    $response->assertOk();
    $response->assertDontSee('Custo Mensal Estimado');
});
