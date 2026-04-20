<?php

use App\Models\Cliente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

it('renderiza a tela de busca de clearance para requisicao normal', function () {
    $user = User::factory()->create();
    Cliente::create([
        'user_id' => $user->id,
        'tipo_pessoa' => 'PJ',
        'documento' => '00000000000191',
        'razao_social' => 'Empresa Propria',
        'is_empresa_propria' => true,
    ]);

    actingAs($user)
        ->get('/app/clearance/buscar')
        ->assertOk()
        ->assertSee('buscar-nfe-container', false)
        ->assertSee('Cliente associado')
        ->assertSee('Empresa própria')
        ->assertSee('Resumo Operacional')
        ->assertSee('Custo por consulta')
        ->assertSee('Saldo atual')
        ->assertSee('Falhas do provedor estornam os créditos automaticamente.')
        ->assertDontSee('Não associar a cliente agora')
        ->assertDontSee('Custo:')
        ->assertDontSee('Saldo:');
});

it('renderiza a view parcial de busca de clearance para requisicao ajax', function () {
    $user = User::factory()->create();
    Cliente::create([
        'user_id' => $user->id,
        'tipo_pessoa' => 'PJ',
        'documento' => '00000000000191',
        'razao_social' => 'Empresa Propria',
        'is_empresa_propria' => true,
    ]);

    actingAs($user)
        ->withHeader('X-Requested-With', 'XMLHttpRequest')
        ->get('/app/clearance/buscar')
        ->assertOk()
        ->assertSee('buscar-nfe-container', false)
        ->assertSee('Empresa própria')
        ->assertSee('Resumo Operacional');
});

it('bloqueia a busca quando a conta nao possui cliente disponivel', function () {
    $user = User::factory()->create();

    actingAs($user)
        ->get('/app/clearance/buscar')
        ->assertOk()
        ->assertSee('Cliente obrigatório para consultar')
        ->assertSee('/app/cliente/novo', false);
});
