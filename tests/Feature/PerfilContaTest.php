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
