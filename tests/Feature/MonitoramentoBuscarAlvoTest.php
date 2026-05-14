<?php

use App\Models\Cliente;
use App\Models\Participante;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('retorna clientes do usuário filtrados por razão social', function () {
    $user = User::factory()->create();
    $cliente = Cliente::create([
        'user_id' => $user->id,
        'documento' => '12345678000190',
        'razao_social' => 'ACME Comércio LTDA',
    ]);
    Cliente::create([
        'user_id' => $user->id,
        'documento' => '99999999000100',
        'razao_social' => 'Outra Empresa',
    ]);

    $response = $this->actingAs($user)->getJson('/app/monitoramento/buscar-alvo?tipo=cliente&q=ACME');

    $response->assertOk()
        ->assertJsonCount(1, 'resultados')
        ->assertJsonPath('resultados.0.id', $cliente->id)
        ->assertJsonPath('resultados.0.razao_social', 'ACME Comércio LTDA');
});

it('retorna participantes do usuário filtrados por documento', function () {
    $user = User::factory()->create();
    $participante = Participante::create([
        'user_id' => $user->id,
        'documento' => '11222333000144',
        'razao_social' => 'Fornecedor X',
    ]);

    $response = $this->actingAs($user)->getJson('/app/monitoramento/buscar-alvo?tipo=participante&q=11222');

    $response->assertOk()
        ->assertJsonCount(1, 'resultados')
        ->assertJsonPath('resultados.0.id', $participante->id);
});

it('limita a 10 resultados', function () {
    $user = User::factory()->create();
    foreach (range(1, 15) as $i) {
        Cliente::create([
            'user_id' => $user->id,
            'documento' => str_pad((string) $i, 14, '0', STR_PAD_LEFT),
            'razao_social' => "Cliente {$i}",
        ]);
    }

    $response = $this->actingAs($user)->getJson('/app/monitoramento/buscar-alvo?tipo=cliente&q=Cliente');

    $response->assertOk()->assertJsonCount(10, 'resultados');
});

it('não vaza alvos de outro usuário', function () {
    $user = User::factory()->create();
    $outro = User::factory()->create();
    Cliente::create([
        'user_id' => $outro->id,
        'documento' => '12345678000190',
        'razao_social' => 'ACME do Outro',
    ]);

    $response = $this->actingAs($user)->getJson('/app/monitoramento/buscar-alvo?tipo=cliente&q=ACME');

    $response->assertOk()->assertJsonCount(0, 'resultados');
});

it('rejeita tipo inválido', function () {
    $user = User::factory()->create();
    $response = $this->actingAs($user)->getJson('/app/monitoramento/buscar-alvo?tipo=foo&q=x');
    $response->assertStatus(422);
});

it('exige autenticação', function () {
    $response = $this->getJson('/app/monitoramento/buscar-alvo?tipo=cliente&q=x');
    $response->assertStatus(401);
});
