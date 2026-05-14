<?php

use App\Models\Cliente;
use App\Models\MonitoramentoAssinatura;
use App\Models\MonitoramentoConsulta;
use App\Models\MonitoramentoPlano;
use App\Models\Participante;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\MonitoramentoPlanoSeeder;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->seed(MonitoramentoPlanoSeeder::class);
    // plano 'validacao' tem custo_creditos = 5
    $this->plano = MonitoramentoPlano::where('codigo', 'validacao')->first();
});

it('renderiza painel com KPIs e tabela de assinaturas', function () {
    $cliente = Cliente::create(['user_id' => $this->user->id, 'documento' => '12345678000190', 'razao_social' => 'C1']);
    $part = Participante::create(['user_id' => $this->user->id, 'documento' => '11222333000144', 'razao_social' => 'P1']);

    MonitoramentoAssinatura::create([
        'user_id' => $this->user->id,
        'cliente_id' => $cliente->id,
        'plano_id' => $this->plano->id,
        'status' => 'ativo',
        'frequencia_dias' => 30,
        'proxima_execucao_em' => now()->addDays(5),
    ]);
    MonitoramentoAssinatura::create([
        'user_id' => $this->user->id,
        'participante_id' => $part->id,
        'plano_id' => $this->plano->id,
        'status' => 'pausado',
        'frequencia_dias' => 30,
    ]);

    $response = $this->actingAs($this->user)->get('/app/monitoramento');

    $response->assertOk()
        ->assertSee('Painel de Monitoramento', false)
        ->assertSee('C1')
        ->assertSee('P1')
        ->assertSee('Nova assinatura', false);
});

it('KPIs refletem a sub-aba de tipo', function () {
    $cliente = Cliente::create(['user_id' => $this->user->id, 'documento' => '12345678000190', 'razao_social' => 'C1']);
    $part = Participante::create(['user_id' => $this->user->id, 'documento' => '11222333000144', 'razao_social' => 'P1']);

    // plano 'licitacao' (custo 10) para segunda assinatura do cliente evitar unique constraint
    $planoLicitacao = MonitoramentoPlano::where('codigo', 'licitacao')->first();

    MonitoramentoAssinatura::create(['user_id' => $this->user->id, 'cliente_id' => $cliente->id, 'plano_id' => $this->plano->id, 'status' => 'ativo', 'frequencia_dias' => 30]);
    MonitoramentoAssinatura::create(['user_id' => $this->user->id, 'cliente_id' => $cliente->id, 'plano_id' => $planoLicitacao->id, 'status' => 'pausado', 'frequencia_dias' => 30]);
    MonitoramentoAssinatura::create(['user_id' => $this->user->id, 'participante_id' => $part->id, 'plano_id' => $this->plano->id, 'status' => 'ativo', 'frequencia_dias' => 30]);

    $r = $this->actingAs($this->user)->get('/app/monitoramento');
    expect($r->viewData('kpiAtivas'))->toBe(2)->and($r->viewData('kpiPausadas'))->toBe(1);

    $r = $this->actingAs($this->user)->get('/app/monitoramento?tipo=cliente');
    expect($r->viewData('kpiAtivas'))->toBe(1)->and($r->viewData('kpiPausadas'))->toBe(1);

    $r = $this->actingAs($this->user)->get('/app/monitoramento?tipo=participante');
    expect($r->viewData('kpiAtivas'))->toBe(1)->and($r->viewData('kpiPausadas'))->toBe(0);
});

it('KPI créditos consumidos no mês ignora erros', function () {
    $cliente = Cliente::create(['user_id' => $this->user->id, 'documento' => '12345678000190', 'razao_social' => 'C1']);
    $assinatura = MonitoramentoAssinatura::create(['user_id' => $this->user->id, 'cliente_id' => $cliente->id, 'plano_id' => $this->plano->id, 'status' => 'ativo', 'frequencia_dias' => 30]);

    MonitoramentoConsulta::create([
        'user_id' => $this->user->id, 'cliente_id' => $cliente->id, 'assinatura_id' => $assinatura->id,
        'plano_id' => $this->plano->id, 'tipo' => 'assinatura', 'status' => 'sucesso',
        'creditos_cobrados' => 5, 'executado_em' => now(),
    ]);
    MonitoramentoConsulta::create([
        'user_id' => $this->user->id, 'cliente_id' => $cliente->id, 'assinatura_id' => $assinatura->id,
        'plano_id' => $this->plano->id, 'tipo' => 'assinatura', 'status' => 'erro',
        'creditos_cobrados' => 5, 'executado_em' => now(),
    ]);
    MonitoramentoConsulta::create([
        'user_id' => $this->user->id, 'cliente_id' => $cliente->id, 'assinatura_id' => $assinatura->id,
        'plano_id' => $this->plano->id, 'tipo' => 'assinatura', 'status' => 'sucesso',
        'creditos_cobrados' => 5, 'executado_em' => now()->subMonth(),
    ]);

    $r = $this->actingAs($this->user)->get('/app/monitoramento');
    expect($r->viewData('kpiCreditosMes'))->toBe(5);
});

it('KPI previsão próximo ciclo soma custo_creditos das ativas', function () {
    $cliente = Cliente::create(['user_id' => $this->user->id, 'documento' => '12345678000190', 'razao_social' => 'C1']);
    $part = Participante::create(['user_id' => $this->user->id, 'documento' => '11222333000144', 'razao_social' => 'P1']);

    // dois planos diferentes (5 + 5 = 10) para contornar unique (participante_id, plano_id)
    $planoLicitacao = MonitoramentoPlano::where('codigo', 'licitacao')->first(); // custo 10, mas usamos validacao p/ ativo

    MonitoramentoAssinatura::create(['user_id' => $this->user->id, 'cliente_id' => $cliente->id, 'plano_id' => $this->plano->id, 'status' => 'ativo', 'frequencia_dias' => 30]);
    MonitoramentoAssinatura::create(['user_id' => $this->user->id, 'participante_id' => $part->id, 'plano_id' => $this->plano->id, 'status' => 'ativo', 'frequencia_dias' => 30]);
    MonitoramentoAssinatura::create(['user_id' => $this->user->id, 'participante_id' => $part->id, 'plano_id' => $planoLicitacao->id, 'status' => 'pausado', 'frequencia_dias' => 30]);

    $r = $this->actingAs($this->user)->get('/app/monitoramento');
    expect($r->viewData('kpiPrevisaoCiclo'))->toBe(10);
});

it('renderiza empty state quando não há assinaturas', function () {
    $r = $this->actingAs($this->user)->get('/app/monitoramento');
    $r->assertOk()->assertSee('Nenhuma assinatura', false);
});

it('redireciona para login quando não autenticado', function () {
    $this->get('/app/monitoramento')->assertRedirect();
});
