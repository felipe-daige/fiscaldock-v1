<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('atualiza name/sobrenome/telefone do usuario autenticado', function () {
    $user = User::factory()->create([
        'name' => 'Antigo',
        'sobrenome' => 'Nome',
    ]);

    $this->actingAs($user)
        ->patchJson('/app/perfil', [
            'name' => 'Felipe',
            'sobrenome' => 'Daige',
            'telefone' => '(11) 98888-7777',
        ])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('user.name', 'Felipe');

    $fresh = $user->fresh();
    expect($fresh->name)->toBe('Felipe');
    expect($fresh->sobrenome)->toBe('Daige');
    expect($fresh->telefone)->toBe('(11) 98888-7777');
});

it('rejeita name vazio', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patchJson('/app/perfil', ['name' => '', 'sobrenome' => 'x'])
        ->assertStatus(422);
});

it('ignora email enviado no payload (read-only)', function () {
    $user = User::factory()->create(['email' => 'original@fiscaldock.com.br']);

    $this->actingAs($user)
        ->patchJson('/app/perfil', [
            'name' => 'Novo',
            'email' => 'hacker@evil.com',
        ])
        ->assertOk();

    expect($user->fresh()->email)->toBe('original@fiscaldock.com.br');
});

it('exige autenticacao no PATCH perfil', function () {
    $this->patchJson('/app/perfil', ['name' => 'X'])
        ->assertStatus(401);
});

it('troca a senha com current_password correto', function () {
    $user = User::factory()->create(['password' => bcrypt('senha-antiga-123')]);

    $this->actingAs($user)
        ->putJson('/app/perfil/senha', [
            'current_password' => 'senha-antiga-123',
            'password' => 'nova-senha-forte-456',
            'password_confirmation' => 'nova-senha-forte-456',
        ])
        ->assertOk()
        ->assertJsonPath('success', true);

    expect(\Illuminate\Support\Facades\Hash::check('nova-senha-forte-456', $user->fresh()->password))->toBeTrue();
});

it('rejeita current_password errado', function () {
    $user = User::factory()->create(['password' => bcrypt('senha-antiga-123')]);

    $this->actingAs($user)
        ->putJson('/app/perfil/senha', [
            'current_password' => 'errada',
            'password' => 'nova-senha-forte-456',
            'password_confirmation' => 'nova-senha-forte-456',
        ])
        ->assertStatus(422);
});

it('rejeita confirmacao divergente', function () {
    $user = User::factory()->create(['password' => bcrypt('senha-antiga-123')]);

    $this->actingAs($user)
        ->putJson('/app/perfil/senha', [
            'current_password' => 'senha-antiga-123',
            'password' => 'nova-senha-forte-456',
            'password_confirmation' => 'diferente-789',
        ])
        ->assertStatus(422);
});
