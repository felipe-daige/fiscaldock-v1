<?php

use App\Models\Cliente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('dry-run lista os pedidos de exclusão sem alterar nada', function () {
    $user = User::factory()->create([
        'email' => 'quer-sair@example.com',
        'deletion_requested_at' => now()->subDays(40),
    ]);

    $this->artisan('lgpd:processar-exclusoes')
        ->expectsOutputToContain('quer-sair@example.com')
        ->assertExitCode(0);

    $fresh = $user->fresh();
    expect($fresh->anonimizado_em)->toBeNull();
    expect($fresh->email)->toBe('quer-sair@example.com');
});

it('--force anonimiza a PII do titular e marca anonimizado_em', function () {
    $user = User::factory()->create([
        'name' => 'Fulano',
        'email' => 'quer-sair@example.com',
        'telefone' => '67999990000',
        'cnpj' => '11222333000181',
        'deletion_requested_at' => now()->subDays(40),
    ]);

    $this->artisan('lgpd:processar-exclusoes --force')->assertExitCode(0);

    $fresh = $user->fresh();
    expect($fresh->anonimizado_em)->not->toBeNull();
    expect($fresh->email)->not->toBe('quer-sair@example.com');
    expect($fresh->name)->not->toBe('Fulano');
    expect($fresh->telefone)->not->toBe('67999990000');
    expect($fresh->cnpj)->toBeNull();
});

it('--force preserva os dados fiscais (clientes) do titular', function () {
    $user = User::factory()->create([
        'deletion_requested_at' => now()->subDays(40),
    ]);
    Cliente::create([
        'user_id' => $user->id,
        'tipo_pessoa' => 'PJ',
        'documento' => '11222333000181',
        'nome' => 'Cliente Fiscal',
        'razao_social' => 'Cliente Fiscal LTDA',
        'is_empresa_propria' => false,
    ]);

    $this->artisan('lgpd:processar-exclusoes --force')->assertExitCode(0);

    expect(Cliente::where('user_id', $user->id)->count())->toBe(1);
});

it('respeita --apos-dias e ignora pedidos recentes', function () {
    $user = User::factory()->create([
        'deletion_requested_at' => now()->subDays(2),
    ]);

    $this->artisan('lgpd:processar-exclusoes --force --apos-dias=30')->assertExitCode(0);

    expect($user->fresh()->anonimizado_em)->toBeNull();
});

it('não reprocessa quem já foi anonimizado', function () {
    $user = User::factory()->create([
        'email' => 'anon-99@anonimizado.invalid',
        'deletion_requested_at' => now()->subDays(40),
        'anonimizado_em' => now()->subDay(),
    ]);

    $this->artisan('lgpd:processar-exclusoes --force')->assertExitCode(0);

    // anonimizado_em não muda (não reprocessa)
    expect($user->fresh()->email)->toBe('anon-99@anonimizado.invalid');
});

it('ignora quem não pediu exclusão', function () {
    $user = User::factory()->create(['deletion_requested_at' => null]);

    $this->artisan('lgpd:processar-exclusoes --force')->assertExitCode(0);

    expect($user->fresh()->anonimizado_em)->toBeNull();
});
