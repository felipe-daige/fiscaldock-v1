<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

it('mostra banner de trial no dashboard e widget na sidebar para usuário em trial', function () {
    $user = User::factory()->trialAtivo(40)->create();

    $html = actingAs($user)->get('/app/dashboard')->assertOk()->getContent();

    // banner do dashboard
    expect($html)->toContain('data-trial-banner');
    expect($html)->toContain('restantes');
    // widget da sidebar
    expect($html)->toContain('data-trial-widget');
    expect($html)->toContain('Trial ativo');
});

it('não mostra banner nem widget de trial para usuário fora do trial', function () {
    $user = User::factory()->create(['trial_used' => false]);

    $html = actingAs($user)->get('/app/dashboard')->assertOk()->getContent();

    expect($html)->not->toContain('data-trial-banner');
    expect($html)->not->toContain('data-trial-widget');
});

it('o widget de trial aparece em qualquer página autenticada (sidebar global)', function () {
    $user = User::factory()->trialAtivo(40)->create();

    $html = actingAs($user)->get('/app/clientes')->assertOk()->getContent();

    expect($html)->toContain('data-trial-widget');
});
