<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;

uses(RefreshDatabase::class);

test('tela de redefinir senha responde 200', function () {
    $response = $this->get('/redefinir-senha/qualquer-token?email=x@example.com');

    $response->assertOk();
});

test('redefinir senha com token válido muda a senha e permite login com a nova', function () {
    $user = User::factory()->create([
        'email' => 'usuario@example.com',
        'password' => 'senhaAntiga123',
    ]);

    $token = Password::broker()->createToken($user);

    $response = $this->post('/redefinir-senha', [
        'token' => $token,
        'email' => 'usuario@example.com',
        'password' => 'novaSenha456',
        'password_confirmation' => 'novaSenha456',
    ]);

    $response->assertRedirect(route('login'));
    expect(Auth::attempt(['email' => 'usuario@example.com', 'password' => 'novaSenha456']))->toBeTrue();
    Auth::logout();
    expect(Auth::attempt(['email' => 'usuario@example.com', 'password' => 'senhaAntiga123']))->toBeFalse();
});

test('token inválido não redefine a senha', function () {
    User::factory()->create(['email' => 'usuario2@example.com', 'password' => 'senhaOriginal1']);

    $response = $this->post('/redefinir-senha', [
        'token' => 'token-invalido',
        'email' => 'usuario2@example.com',
        'password' => 'novaSenha456',
        'password_confirmation' => 'novaSenha456',
    ]);

    $response->assertSessionHasErrors();
    expect(Auth::attempt(['email' => 'usuario2@example.com', 'password' => 'novaSenha456']))->toBeFalse();
});

test('token é de uso único', function () {
    $user = User::factory()->create(['email' => 'usuario3@example.com']);
    $token = Password::broker()->createToken($user);

    $payload = [
        'token' => $token,
        'email' => 'usuario3@example.com',
        'password' => 'novaSenha456',
        'password_confirmation' => 'novaSenha456',
    ];

    $this->post('/redefinir-senha', $payload)->assertRedirect(route('login'));

    $payload['password'] = 'outraSenha789';
    $payload['password_confirmation'] = 'outraSenha789';
    $response = $this->post('/redefinir-senha', $payload);

    $response->assertSessionHasErrors();
});

test('senha fraca é rejeitada mesmo com token válido', function () {
    $user = User::factory()->create(['email' => 'usuario4@example.com']);
    $token = Password::broker()->createToken($user);

    $response = $this->post('/redefinir-senha', [
        'token' => $token,
        'email' => 'usuario4@example.com',
        'password' => 'abcdefgh',
        'password_confirmation' => 'abcdefgh',
    ]);

    $response->assertSessionHasErrors('password');
});
