<?php

use App\Models\Cliente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('exige autenticacao', function () {
    $this->getJson('/app/dashboard/dados')->assertStatus(401);
});

it('retorna o shape do cockpit', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->getJson('/app/dashboard/dados?periodo=12')
        ->assertOk()
        ->assertJsonStructure([
            'kpis' => ['volume', 'saude', 'creditos'],
            'triagem',
            'tendencia' => ['meses', 'saida_valor', 'saida_qtd', 'entrada_valor', 'entrada_qtd'],
            'top_fornecedores',
            'risco_distribuicao',
            'meta',
        ])
        ->assertJsonPath('meta.periodo', 12);
});

it('ignora cliente de outro usuario (vira carteira toda)', function () {
    $user = User::factory()->create();
    $outro = User::factory()->create();
    $alheio = Cliente::create(['user_id' => $outro->id, 'documento' => '11111111000111', 'razao_social' => 'ALHEIO']);

    $this->actingAs($user)
        ->getJson("/app/dashboard/dados?cliente={$alheio->id}")
        ->assertOk()
        ->assertJsonPath('meta.cliente', null);
});
