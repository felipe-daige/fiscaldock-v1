<?php

use App\Models\User;
use App\Notifications\ResetPasswordQueued;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

test('fluxo completo: solicitar link, extrair token real do e-mail, redefinir e logar', function () {
    Notification::fake();

    $user = User::factory()->create([
        'email' => 'fluxo@example.com',
        'password' => 'senhaAntiga123',
    ]);

    // 1. Usuário pede o link.
    $this->post('/esqueci-senha', ['email' => 'fluxo@example.com'])
        ->assertRedirect();

    // 2. Captura o token real gerado pela notification (equivalente a "clicar no e-mail").
    $tokenCapturado = null;
    Notification::assertSentTo($user, ResetPasswordQueued::class, function (ResetPasswordQueued $notification) use (&$tokenCapturado) {
        $tokenCapturado = $notification->token;

        return true;
    });
    expect($tokenCapturado)->not->toBeNull();

    // 3. A tela de reset carrega normalmente com esse token.
    $this->get("/redefinir-senha/{$tokenCapturado}?email=fluxo@example.com")
        ->assertOk();

    // 4. Usuário envia a nova senha.
    $response = $this->post('/redefinir-senha', [
        'token' => $tokenCapturado,
        'email' => 'fluxo@example.com',
        'password' => 'senhaNova789',
        'password_confirmation' => 'senhaNova789',
    ]);
    $response->assertRedirect(route('login'));

    // 5. Login só funciona com a senha nova.
    expect(Auth::attempt(['email' => 'fluxo@example.com', 'password' => 'senhaNova789']))->toBeTrue();
});
