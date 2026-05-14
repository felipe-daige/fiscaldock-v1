<?php

use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('sidebar mostra os 4 itens novos do grupo Monitoramento', function () {
    $user = User::factory()->create();
    $r = $this->actingAs($user)->get('/app/monitoramento');
    $r->assertOk()
        ->assertSee('href="/app/monitoramento"', false)
        ->assertSee('Painel', false)
        ->assertSee('href="/app/monitoramento/historico"', false)
        ->assertSee('href="/app/monitoramento/alertas"', false)
        ->assertSee('href="/app/monitoramento/grupos"', false);
});

it('sidebar não mostra mais o item legado Clientes', function () {
    $user = User::factory()->create();
    $r = $this->actingAs($user)->get('/app/monitoramento');
    $r->assertDontSee('href="/app/monitoramento/clientes"', false);
});
