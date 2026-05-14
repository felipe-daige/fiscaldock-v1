<?php

use App\Models\Cliente;
use App\Models\User;
use Database\Seeders\MonitoramentoPlanoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('mostra o botão de criar assinatura na tela do cliente', function () {
    $this->seed(MonitoramentoPlanoSeeder::class);
    $user = User::factory()->create();
    $cliente = Cliente::create([
        'user_id' => $user->id, 'documento' => '44555666000199', 'razao_social' => 'Cliente Y',
    ]);

    $response = $this->actingAs($user)->get("/app/cliente/{$cliente->id}");

    $response->assertOk();
    $response->assertSee('Criar assinatura');
    $response->assertSee('id="modal-criar-assinatura"', false);
});
