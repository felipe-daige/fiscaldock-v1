<?php

use App\Models\Cliente;
use App\Models\MonitoramentoAssinatura;
use App\Models\MonitoramentoConsulta;
use App\Models\MonitoramentoPlano;
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
    $this->cliente = Cliente::create([
        'user_id' => $this->user->id, 'documento' => '44555666000199', 'razao_social' => 'Cliente Y',
    ]);
});

it('mostra painel da assinatura e histórico na tela do cliente', function () {
    $assinatura = MonitoramentoAssinatura::create([
        'user_id' => $this->user->id, 'cliente_id' => $this->cliente->id,
        'plano_id' => $this->plano->id, 'status' => 'ativo', 'frequencia_dias' => 30,
    ]);
    foreach (['sucesso', 'sucesso', 'erro'] as $status) {
        MonitoramentoConsulta::create([
            'user_id' => $this->user->id, 'cliente_id' => $this->cliente->id,
            'plano_id' => $this->plano->id, 'tipo' => 'assinatura', 'status' => $status,
            'creditos_cobrados' => 7, 'executado_em' => now(),
        ]);
    }

    $response = $this->actingAs($this->user)->get("/app/cliente/{$this->cliente->id}");

    $response->assertOk();
    $response->assertSee('Assinatura Ativa');
    $response->assertSee('Custo Mensal Estimado');
    $response->assertSee('14 creditos');
    $response->assertDontSee('21 creditos');
    $response->assertSee('Histórico de Execuções do Monitoramento');
    $response->assertSee('data-assinatura-id="'.$assinatura->id.'"', false);
});

it('não renderiza o painel quando o cliente não tem assinatura', function () {
    $response = $this->actingAs($this->user)->get("/app/cliente/{$this->cliente->id}");

    $response->assertOk();
    $response->assertDontSee('Custo Mensal Estimado');
});
