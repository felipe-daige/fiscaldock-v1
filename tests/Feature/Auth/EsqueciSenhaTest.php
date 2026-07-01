<?php

use App\Models\User;
use App\Notifications\ResetPasswordQueued;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

test('tela de esqueci senha responde 200', function () {
    $this->get('/esqueci-senha')->assertOk();
});

test('solicitar redefinição envia notificação quando o e-mail existe', function () {
    Notification::fake();

    $user = User::factory()->create(['email' => 'existe@example.com']);

    $response = $this->post('/esqueci-senha', ['email' => 'existe@example.com']);

    $response->assertRedirect();
    Notification::assertSentTo($user, ResetPasswordQueued::class);
});

test('mensagem de resposta é idêntica exista ou não o e-mail (anti-enumeração)', function () {
    Notification::fake();

    User::factory()->create(['email' => 'existe2@example.com']);

    $respostaExistente = $this->post('/esqueci-senha', ['email' => 'existe2@example.com']);
    $respostaInexistente = $this->post('/esqueci-senha', ['email' => 'ninguem@example.com']);

    expect($respostaExistente->getSession()->get('status'))
        ->not->toBeNull()
        ->toBe($respostaInexistente->getSession()->get('status'));
});

test('solicitar redefinição aplica rate limit por IP', function () {
    for ($i = 0; $i < 5; $i++) {
        $this->post('/esqueci-senha', ['email' => "teste{$i}@example.com"]);
    }

    $response = $this->post('/esqueci-senha', ['email' => 'mais-um@example.com']);

    $response->assertStatus(429);
});

test('tela de esqueci senha tem o formulário esperado', function () {
    $response = $this->get('/esqueci-senha');

    $response->assertOk();
    $response->assertSee('esqueci-senha-form', false);
    $response->assertSee('name="email"', false);
});
