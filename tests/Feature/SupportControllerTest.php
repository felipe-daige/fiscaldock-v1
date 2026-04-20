<?php

use App\Mail\SupportTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create([
        'name' => 'Maria',
        'sobrenome' => 'Silva',
        'email' => 'maria@example.com',
    ]);
});

test('suporte exige autenticacao', function () {
    $this->get('/app/suporte')
        ->assertRedirect('/login');
});

test('renderiza tela de suporte autenticada', function () {
    $this->actingAs($this->user)
        ->get('/app/suporte')
        ->assertOk()
        ->assertSee('Suporte')
        ->assertSee('Abrir Atendimento');
});

test('aceita querystring e preenche formulario com contexto sanitizado', function () {
    $response = $this->actingAs($this->user)
        ->get('/app/suporte?contexto=<b>validar</b>&url=%2Fapp%2Fvalidacao&mensagem=<script>alert(1)</script>Erro%20ao%20calcular');

    $response->assertOk()
        ->assertSee('Erro em /app/validacao')
        ->assertSee('alert(1)Erro ao calcular')
        ->assertDontSee('<script>', false);
});

test('envia email de suporte com payload valido', function () {
    Mail::fake();

    $this->actingAs($this->user)
        ->post('/app/suporte', [
            'categoria' => 'problema_tecnico',
            'assunto' => 'Erro ao validar XML',
            'mensagem' => 'A validacao falhou ao confirmar o custo.',
            'contexto' => 'validacao',
            'url_origem' => '/app/validacao',
            'mensagem_erro' => 'Erro ao calcular custo.',
        ])
        ->assertRedirect('/app/suporte');

    Mail::assertSent(SupportTicket::class, function (SupportTicket $mail) {
        return $mail->user->is($this->user)
            && $mail->payload['categoria'] === 'problema_tecnico'
            && $mail->payload['assunto'] === 'Erro ao validar XML'
            && $mail->payload['url_origem'] === '/app/validacao';
    });
});

test('valida campos obrigatorios no envio de suporte', function () {
    Mail::fake();

    $this->actingAs($this->user)
        ->from('/app/suporte')
        ->post('/app/suporte', [
            'categoria' => 'invalida',
            'assunto' => '',
            'mensagem' => '',
        ])
        ->assertSessionHasErrors(['categoria', 'assunto', 'mensagem']);

    Mail::assertNothingSent();
});
