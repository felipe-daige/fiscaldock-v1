<?php

use App\Models\Cliente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

it('mostra o card de monitoramento contínuo na edição do cliente', function () {
    $user = User::factory()->create();
    $cliente = Cliente::create([
        'user_id' => $user->id, 'documento' => '11444777000161', 'tipo_pessoa' => 'PJ',
        'razao_social' => 'Cli', 'ativo' => true, 'is_empresa_propria' => false,
    ]);

    $resp = actingAs($user)->get("/app/cliente/{$cliente->id}/editar", [
        'X-Requested-With' => 'XMLHttpRequest',
    ])->assertOk();

    $resp->assertSee('Monitoramento contínuo')
        ->assertSee('Criar assinatura')
        ->assertSee('btn-criar-assinatura-cliente', false);
});
