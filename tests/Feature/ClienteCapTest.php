<?php

use App\Models\Cliente;
use App\Models\User;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(SubscriptionPlanSeeder::class));

function freeUserComPropria(): User
{
    $user = User::factory()->create();
    Cliente::create([
        'user_id' => $user->id,
        'documento' => '10000000000191',
        'tipo_pessoa' => 'PJ',
        'razao_social' => 'Empresa Propria',
        'is_empresa_propria' => true,
        'ativo' => true,
    ]);

    return $user;
}

function payloadCliente(string $doc): array
{
    return ['tipo_pessoa' => 'PJ', 'documento' => $doc, 'razao_social' => 'Nova Empresa'];
}

it('Free cadastra o +1 cliente além da própria', function () {
    $user = freeUserComPropria();

    $this->actingAs($user)
        ->postJson('/app/cliente/novo', payloadCliente('22222222000191'))
        ->assertStatus(201);

    expect(Cliente::where('user_id', $user->id)->where('is_empresa_propria', false)->count())->toBe(1);
});

it('Free é bloqueado ao cadastrar um 2º cliente (cap = própria + 1)', function () {
    $user = freeUserComPropria();
    Cliente::create([
        'user_id' => $user->id, 'documento' => '22222222000191', 'tipo_pessoa' => 'PJ',
        'razao_social' => 'A', 'is_empresa_propria' => false, 'ativo' => true,
    ]);

    $this->actingAs($user)
        ->postJson('/app/cliente/novo', payloadCliente('33333333000191'))
        ->assertStatus(403);

    expect(Cliente::where('user_id', $user->id)->where('documento', '33333333000191')->exists())->toBeFalse();
});

it('não dá pra burlar o cap forjando is_empresa_propria=true', function () {
    $user = freeUserComPropria();
    // usa o +1 (cap cheio: própria + 1)
    Cliente::create([
        'user_id' => $user->id, 'documento' => '22222222000191', 'tipo_pessoa' => 'PJ',
        'razao_social' => 'A', 'is_empresa_propria' => false, 'ativo' => true,
    ]);

    // tenta um 3º forjando "própria" pra escapar do cap
    $this->actingAs($user)
        ->postJson('/app/cliente/novo', array_merge(payloadCliente('33333333000191'), ['is_empresa_propria' => true]))
        ->assertStatus(403);

    expect(Cliente::where('user_id', $user->id)->where('documento', '33333333000191')->exists())->toBeFalse();
    expect(Cliente::where('user_id', $user->id)->where('is_empresa_propria', true)->count())->toBe(1);
});

it('is_empresa_propria=true vira cliente normal quando já existe própria (cap com folga)', function () {
    $user = freeUserComPropria();

    $this->actingAs($user)
        ->postJson('/app/cliente/novo', array_merge(payloadCliente('22222222000191'), ['is_empresa_propria' => true]))
        ->assertStatus(201);

    expect(Cliente::where('user_id', $user->id)->where('is_empresa_propria', true)->count())->toBe(1);
    expect(Cliente::where('user_id', $user->id)->where('documento', '22222222000191')
        ->where('is_empresa_propria', false)->exists())->toBeTrue();
});

it('trial ativo cadastra clientes sem cap', function () {
    $user = User::factory()->trialAtivo()->create();
    Cliente::create([
        'user_id' => $user->id, 'documento' => '10000000000191', 'tipo_pessoa' => 'PJ',
        'razao_social' => 'Propria', 'is_empresa_propria' => true, 'ativo' => true,
    ]);

    foreach (['22222222000191', '33333333000191', '44444444000191'] as $doc) {
        $this->actingAs($user)->postJson('/app/cliente/novo', payloadCliente($doc))->assertStatus(201);
    }

    expect(Cliente::where('user_id', $user->id)->where('is_empresa_propria', false)->count())->toBe(3);
});
