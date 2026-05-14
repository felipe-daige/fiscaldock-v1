<?php

use App\Models\Alerta;
use App\Models\Cliente;
use App\Models\Participante;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->cliente = Cliente::create(['user_id' => $this->user->id, 'documento' => '12345678000190', 'razao_social' => 'C1']);
    $this->part = Participante::create(['user_id' => $this->user->id, 'documento' => '11222333000144', 'razao_social' => 'P1']);
});

it('lista apenas alertas categoria monitoramento do usuário', function () {
    Alerta::create([
        'user_id' => $this->user->id, 'cliente_id' => $this->cliente->id,
        'categoria' => 'monitoramento', 'tipo' => 'monitoramento_situacao_piorou',
        'severidade' => 'critico', 'titulo' => 'Cliente piorou', 'descricao' => 'situação',
        'hash' => 'h1',
    ]);
    Alerta::create([
        'user_id' => $this->user->id,
        'categoria' => 'outra', 'tipo' => 'foo',
        'severidade' => 'info', 'titulo' => 'Outro', 'descricao' => '',
        'hash' => 'h2',
    ]);

    $r = $this->actingAs($this->user)->get('/app/monitoramento/alertas');

    $r->assertOk()->assertSee('Cliente piorou')->assertDontSee('Outro');
});

it('filtra alertas por tipo de alvo', function () {
    Alerta::create([
        'user_id' => $this->user->id, 'cliente_id' => $this->cliente->id,
        'categoria' => 'monitoramento', 'tipo' => 'x', 'severidade' => 'info',
        'titulo' => 'Alerta cliente', 'descricao' => '', 'hash' => 'h1',
    ]);
    Alerta::create([
        'user_id' => $this->user->id, 'participante_id' => $this->part->id,
        'categoria' => 'monitoramento', 'tipo' => 'x', 'severidade' => 'info',
        'titulo' => 'Alerta participante', 'descricao' => '', 'hash' => 'h2',
    ]);

    $r = $this->actingAs($this->user)->get('/app/monitoramento/alertas?tipo=cliente');
    $r->assertOk()->assertSee('Alerta cliente')->assertDontSee('Alerta participante');

    $r = $this->actingAs($this->user)->get('/app/monitoramento/alertas?tipo=participante');
    $r->assertOk()->assertSee('Alerta participante')->assertDontSee('Alerta cliente');
});

it('renderiza KPI de críticos', function () {
    Alerta::create([
        'user_id' => $this->user->id, 'cliente_id' => $this->cliente->id,
        'categoria' => 'monitoramento', 'tipo' => 'x', 'severidade' => 'critico',
        'titulo' => 'A', 'descricao' => '', 'hash' => 'h1',
    ]);
    Alerta::create([
        'user_id' => $this->user->id, 'cliente_id' => $this->cliente->id,
        'categoria' => 'monitoramento', 'tipo' => 'x', 'severidade' => 'info',
        'titulo' => 'B', 'descricao' => '', 'hash' => 'h2',
    ]);

    $r = $this->actingAs($this->user)->get('/app/monitoramento/alertas');
    expect($r->viewData('kpiCriticos'))->toBe(1);
});

it('não vaza alertas de outro usuário', function () {
    $outro = User::factory()->create();
    Alerta::create([
        'user_id' => $outro->id, 'categoria' => 'monitoramento', 'tipo' => 'x',
        'severidade' => 'critico', 'titulo' => 'Vazado', 'descricao' => '', 'hash' => 'h1',
    ]);

    $r = $this->actingAs($this->user)->get('/app/monitoramento/alertas');
    $r->assertOk()->assertDontSee('Vazado');
});

it('empty state quando sem alertas', function () {
    $r = $this->actingAs($this->user)->get('/app/monitoramento/alertas');
    $r->assertOk()->assertSee('Nenhum alerta', false);
});
