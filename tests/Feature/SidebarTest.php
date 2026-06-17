<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

/**
 * Sidebar: garante que a navegação de Monitoramento existe e que os marcadores
 * "Novo" + a ocultação da busca avulsa desabilitada não regridam.
 */
it('sidebar expõe a navegação de Monitoramento e pílulas Novo', function () {
    $user = User::factory()->trialAtivo()->create();

    $html = actingAs($user)->get('/app/dashboard')->assertOk()->getContent();

    expect($html)->toContain('Monitoramento')
        ->toContain('/app/monitoramento/clientes')
        ->toContain('/app/monitoramento/grupos')
        ->toContain('Novo'); // pílula de item recém-lançado
});

it('sidebar esconde Buscar Notas quando a busca avulsa está desabilitada', function () {
    config()->set('clearance.busca_avulsa.habilitada', false);
    $user = User::factory()->trialAtivo()->create();

    $html = actingAs($user)->get('/app/dashboard')->assertOk()->getContent();

    expect($html)->not->toContain('/app/clearance/buscar');
});

it('sidebar mostra Buscar Notas quando a busca avulsa está habilitada', function () {
    config()->set('clearance.busca_avulsa.habilitada', true);
    $user = User::factory()->trialAtivo()->create();

    $html = actingAs($user)->get('/app/dashboard')->assertOk()->getContent();

    expect($html)->toContain('/app/clearance/buscar');
});
