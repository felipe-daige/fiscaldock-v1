<?php

use App\Models\Cliente;
use App\Models\MonitoramentoAssinatura;
use App\Models\MonitoramentoPlano;
use App\Models\User;
use Database\Seeders\MonitoramentoPlanoSeeder;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->seed(MonitoramentoPlanoSeeder::class);
    $this->user = User::factory()->create();
    $this->cliente = Cliente::create([
        'user_id' => $this->user->id,
        'documento' => '12345678000190',
        'razao_social' => 'C1',
    ]);
});

it('modal mostra apenas os planos da config monitoramento.planos_assinatura', function () {
    config(['monitoramento.planos_assinatura' => ['validacao', 'licitacao']]);

    $r = $this->actingAs($this->user)->get('/app/monitoramento');

    $r->assertOk()
        ->assertSee('Validação (5 créditos')
        ->assertSee('Licitação (10 créditos')
        ->assertDontSee('Compliance (18 créditos')
        ->assertDontSee('Due Diligence (35 créditos');
});

it('criarAssinatura rejeita plano fora da config', function () {
    config(['monitoramento.planos_assinatura' => ['validacao', 'licitacao']]);
    $compliance = MonitoramentoPlano::where('codigo', 'compliance')->first();

    $r = $this->actingAs($this->user)->postJson('/app/monitoramento/assinatura', [
        'cliente_id' => $this->cliente->id,
        'plano_id' => $compliance->id,
        'frequencia' => 'mensal',
    ]);

    $r->assertStatus(400);
    expect(MonitoramentoAssinatura::count())->toBe(0);
});

it('criarAssinatura aceita plano que está na config', function () {
    config(['monitoramento.planos_assinatura' => ['validacao', 'licitacao']]);
    $validacao = MonitoramentoPlano::where('codigo', 'validacao')->first();

    $r = $this->actingAs($this->user)->postJson('/app/monitoramento/assinatura', [
        'cliente_id' => $this->cliente->id,
        'plano_id' => $validacao->id,
        'frequencia' => 'mensal',
    ]);

    expect(MonitoramentoAssinatura::count())->toBe(1);
});
