<?php

use App\Models\Participante;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('ficha mostra bloco de movimentacoes e botao de dossie', function () {
    $user = User::factory()->create();
    $p = Participante::create([
        'user_id' => $user->id,
        'documento' => '07863768000138',
        'razao_social' => 'ACME LTDA',
        'uf' => 'SP',
        'crt' => '3',
        'latitude' => -23.5,
        'longitude' => -46.6,
    ]);
    criarNotaEfd($user, $p, 'saida', '2026-01-10', 500);

    $this->actingAs($user)->get("/app/participante/{$p->id}")
        ->assertOk()
        ->assertSee('Movimentações', false)
        ->assertSee("/app/participante/{$p->id}/dossie", false);
});
