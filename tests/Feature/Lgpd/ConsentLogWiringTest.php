<?php

use App\Models\ConsentLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

function signupPayload(array $overrides = []): array
{
    return array_merge([
        'nome' => 'Joao',
        'sobrenome' => 'Silva',
        'email' => 'novo-titular@example.com',
        'telefone' => '67911112222',
        'senha' => 'Xk9382mZqp01',
        'senha_confirmation' => 'Xk9382mZqp01',
        'empresa' => 'Empresa Nova',
        'cargo' => 'Contador',
        'documento' => '11144477735',
        'faturamento' => 'ate-1m',
        'desafio_principal' => 'compliance',
        'terms_aceitos' => true,
    ], $overrides);
}

it('grava trilha de consentimento de termos e privacidade no signup', function () {
    $response = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
        ->postJson('/criar-conta', signupPayload());

    $response->assertOk();
    $user = User::where('email', 'novo-titular@example.com')->firstOrFail();

    $this->assertDatabaseHas('consent_logs', [
        'user_id' => $user->id,
        'tipo' => 'termos',
        'acao' => 'aceite',
        'versao' => config('legal.terms_version'),
    ]);
    $this->assertDatabaseHas('consent_logs', [
        'user_id' => $user->id,
        'tipo' => 'privacidade',
        'acao' => 'aceite',
        'versao' => config('legal.privacy_version'),
    ]);
});

it('grava consentimento de marketing no signup somente quando opta', function () {
    $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
        ->postJson('/criar-conta', signupPayload(['marketing_opt_in' => true]))
        ->assertOk();

    $user = User::where('email', 'novo-titular@example.com')->firstOrFail();

    $log = ConsentLog::where('user_id', $user->id)->where('tipo', 'marketing')->first();
    expect($log)->not->toBeNull();
    expect($log->acao)->toBe('aceite');
    expect($log->valor)->toBeTrue();
});

it('não grava marketing no signup quando o titular não opta', function () {
    $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
        ->postJson('/criar-conta', signupPayload())
        ->assertOk();

    $user = User::where('email', 'novo-titular@example.com')->firstOrFail();

    expect(ConsentLog::where('user_id', $user->id)->where('tipo', 'marketing')->exists())->toBeFalse();
});

it('registra revogação de marketing no centro de privacidade', function () {
    $user = User::factory()->create(['marketing_opt_in' => true]);

    actingAs($user)->post('/app/privacidade/marketing/revogar')->assertRedirect();

    $this->assertDatabaseHas('consent_logs', [
        'user_id' => $user->id,
        'tipo' => 'marketing',
        'acao' => 'revogacao',
        'valor' => false,
    ]);
});

it('registra pedido de exclusão (uma vez, idempotente)', function () {
    $user = User::factory()->create();

    actingAs($user)->post('/app/privacidade/exclusao')->assertRedirect();
    actingAs($user)->post('/app/privacidade/exclusao')->assertRedirect();

    expect(ConsentLog::where('user_id', $user->id)
        ->where('tipo', 'exclusao')->where('acao', 'solicitacao')->count())->toBe(1);
});

it('registra cancelamento do pedido de exclusão', function () {
    $user = User::factory()->create(['deletion_requested_at' => now()]);

    actingAs($user)->post('/app/privacidade/exclusao/cancelar')->assertRedirect();

    $this->assertDatabaseHas('consent_logs', [
        'user_id' => $user->id,
        'tipo' => 'exclusao',
        'acao' => 'cancelamento',
    ]);
});
