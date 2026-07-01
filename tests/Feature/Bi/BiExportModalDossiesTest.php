<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('o modal de export do BI tem o seletor de dossiês e o wiring', function () {
    $user = User::factory()->trialAtivo()->create();
    $resp = $this->actingAs($user)->get('/app/bi/dashboard')->assertOk();

    $resp->assertSee('export-pdf-dossies', false)   // id do select
        ->assertSee('Sem dossiês', false)           // opção default
        ->assertSee('Todos os participantes', false)
        ->assertSee('dossies', false);            // param injetado pelo download-button
});
