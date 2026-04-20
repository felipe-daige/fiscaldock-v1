<?php

use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

afterEach(function () {
    $this->user->forceDelete();
});

it('redireciona para login quando não autenticado', function () {
    auth()->logout();
    $response = $this->get('/app/bi/dashboard');
    $response->assertRedirect('/login');
});

it('exibe a página BI para usuário autenticado', function () {
    $response = $this->get('/app/bi/dashboard');
    $response->assertStatus(200);
    $response->assertViewHas('periodoAtivo');
    $response->assertViewHas('filtros');
});

it('resolve periodo mes_atual', function () {
    $response = $this->get('/app/bi/dashboard?periodo=mes_atual');
    $response->assertViewHas('periodoAtivo', 'mes_atual');
    $filtros = $response->viewData('filtros');
    expect($filtros['data_inicio'])->toBe(now()->startOfMonth()->format('d/m/Y'));
});

it('resolve periodo mes_anterior', function () {
    $response = $this->get('/app/bi/dashboard?periodo=mes_anterior');
    $response->assertViewHas('periodoAtivo', 'mes_anterior');
    $filtros = $response->viewData('filtros');
    expect($filtros['data_inicio'])->toBe(now()->subMonth()->startOfMonth()->format('d/m/Y'));
});

it('resolve periodo personalizado com datas', function () {
    $response = $this->get('/app/bi/dashboard?periodo=personalizado&data_inicio=2026-01-01&data_fim=2026-01-31');
    $response->assertViewHas('periodoAtivo', 'personalizado');
    $filtros = $response->viewData('filtros');
    expect($filtros['data_inicio'])->toBe('01/01/2026');
    expect($filtros['data_fim'])->toBe('31/01/2026');
});

it('resolve periodo ano_atual', function () {
    $response = $this->get('/app/bi/dashboard?periodo=ano_atual');
    $response->assertViewHas('periodoAtivo', 'ano_atual');
    $filtros = $response->viewData('filtros');
    expect($filtros['data_inicio'])->toBe(now()->startOfYear()->format('d/m/Y'));
});

it('resolve periodo trimestre_atual', function () {
    $response = $this->get('/app/bi/dashboard?periodo=trimestre_atual');
    $response->assertViewHas('periodoAtivo', 'trimestre_atual');
    $filtros = $response->viewData('filtros');
    expect($filtros['data_inicio'])->toBe(now()->firstOfQuarter()->format('d/m/Y'));
    expect($filtros['data_fim'])->toBe(now()->lastOfQuarter()->format('d/m/Y'));
});

it('resolve periodo semestre_atual com início e fim corretos', function () {
    $response = $this->get('/app/bi/dashboard?periodo=semestre_atual');
    $response->assertViewHas('periodoAtivo', 'semestre_atual');
    $filtros = $response->viewData('filtros');
    $mes = (int) now()->format('n');
    $expectedInicio = $mes <= 6
        ? now()->startOfYear()->format('d/m/Y')
        : now()->month(7)->startOfMonth()->format('d/m/Y');
    expect($filtros['data_inicio'])->toBe($expectedInicio);
});
