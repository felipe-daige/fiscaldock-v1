<?php

use App\Models\ConsentLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

it('mostra o histórico de consentimentos no centro de privacidade', function () {
    $user = User::factory()->create();
    ConsentLog::create([
        'user_id' => $user->id,
        'tipo' => 'marketing',
        'acao' => 'revogacao',
        'valor' => false,
        'created_at' => now(),
    ]);

    actingAs($user)->get('/app/privacidade')
        ->assertOk()
        ->assertSee('Histórico de consentimentos')
        ->assertSee('Revogação');
});

it('não mostra a seção de histórico quando não há eventos', function () {
    $user = User::factory()->create();

    actingAs($user)->get('/app/privacidade')
        ->assertOk()
        ->assertDontSee('Histórico de consentimentos');
});

it('inclui a trilha de consentimento no export DSAR', function () {
    $user = User::factory()->create();
    ConsentLog::create([
        'user_id' => $user->id,
        'tipo' => 'termos',
        'acao' => 'aceite',
        'versao' => '1.0',
        'created_at' => now(),
    ]);

    $response = actingAs($user)->get('/app/privacidade/exportar')->assertOk();
    $payload = json_decode($response->getContent(), true);

    expect($payload)->toHaveKey('trilha_consentimento');
    expect(collect($payload['trilha_consentimento'])->pluck('tipo'))->toContain('termos');
});
