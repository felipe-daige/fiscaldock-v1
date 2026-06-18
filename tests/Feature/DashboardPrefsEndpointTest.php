<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('salva prefs validas e devolve o shape mesclado', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson('/app/dashboard/prefs', [
            'cards' => ['atividade' => ['visivel' => true]],
            'atalhos_fixos' => ['consulta_nova', 'verificar_notas'],
        ])
        ->assertOk()
        ->assertJsonPath('prefs.cards.atividade.visivel', true);

    expect($user->fresh()->dashboard_prefs['cards']['atividade']['visivel'])->toBeTrue();
});

it('rejeita card fora da whitelist', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson('/app/dashboard/prefs', ['cards' => ['hackerman' => ['visivel' => true]]])
        ->assertStatus(422);
});

it('rejeita atalho fora do catalogo', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson('/app/dashboard/prefs', ['atalhos_fixos' => ['rm_-rf']])
        ->assertStatus(422);
});
