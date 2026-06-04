<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

test('login aplica rate limit apos 5 tentativas', function () {
    User::factory()->create([
        'email' => 'vitima@example.com',
        'password' => Hash::make('senhaCorreta123'),
    ]);

    // 5 tentativas falhas permitidas
    for ($i = 0; $i < 5; $i++) {
        $this->post('/login', [
            'email' => 'vitima@example.com',
            'password' => 'senhaErrada123',
        ]);
    }

    // 6ª tentativa é bloqueada pelo throttle (429), antes de tocar credenciais
    $response = $this->post('/login', [
        'email' => 'vitima@example.com',
        'password' => 'senhaErrada123',
    ]);

    $response->assertStatus(429);
});

test('login regenera a sessao apos autenticar (anti session-fixation)', function () {
    User::factory()->create([
        'email' => 'user@example.com',
        'password' => Hash::make('senhaCorreta123'),
    ]);

    $this->startSession();
    $idAntes = session()->getId();

    $this->post('/login', [
        'email' => 'user@example.com',
        'password' => 'senhaCorreta123',
    ]);

    expect(session()->getId())->not->toBe($idAntes);
    $this->assertAuthenticated();
});

test('401 de API nao vaza prefixo nem tamanho do token esperado', function () {
    $response = $this->postJson('/api/importacao/efd/progresso', [
        'importacao_id' => 1,
        'status' => 'processando',
    ]);

    $response->assertStatus(401);
    $response->assertJsonMissing(['debug' => true]);

    $json = $response->json();
    expect($json)->not->toHaveKey('debug');
});

test('health endpoint nao expoe token, php_version nem ambiente', function () {
    $response = $this->getJson('/api/health');

    $response->assertOk();
    $json = $response->json();

    expect($json)->not->toHaveKey('token_prefix');
    expect($json)->not->toHaveKey('token_length');
    expect($json)->not->toHaveKey('raw_length');
    expect($json)->not->toHaveKey('php_version');
    expect($json)->not->toHaveKey('laravel_env');
});

test('signup rejeita senha fraca sem letras+numeros', function () {
    $response = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])->postJson('/criar-conta', [
        'nome' => 'Joao',
        'sobrenome' => 'Silva',
        'email' => 'joao@example.com',
        'telefone' => '67999990000',
        'senha' => 'abcdefgh',
        'senha_confirmation' => 'abcdefgh',
        'empresa' => 'Empresa X',
        'cargo' => 'Contador',
        'documento' => '11144477735',
        'faturamento' => 'ate-1m',
        'desafio_principal' => 'compliance',
        'terms_aceitos' => true,
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('senha');
});

test('conflito de signup usa mensagem generica (anti-enumeracao)', function () {
    User::factory()->create([
        'email' => 'existe@example.com',
        'telefone' => '67988887777',
        'cnpj' => '11222333000181',
    ]);

    $response = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])->postJson('/criar-conta', [
        'nome' => 'Maria',
        'sobrenome' => 'Souza',
        'email' => 'existe@example.com',
        'telefone' => '67911112222',
        'senha' => 'Xk9382mZqp01',
        'senha_confirmation' => 'Xk9382mZqp01',
        'empresa' => 'Outra Empresa',
        'cargo' => 'Diretora',
        'documento' => '11144477735',
        'faturamento' => 'ate-1m',
        'desafio_principal' => 'compliance',
        'terms_aceitos' => true,
    ]);

    $response->assertStatus(422);
    $mensagem = $response->json('errors.email.0');

    // Não pode revelar QUAL campo (e-mail/telefone/CPF) colidiu.
    expect($mensagem)->not->toContain('e-mail');
    expect($mensagem)->not->toContain('telefone');
    expect($mensagem)->not->toContain('CPF');
});

test('csrf mismatch em AJAX retorna 419 JSON com token novo (auto-recuperacao)', function () {
    Route::middleware('web')->post('/__test_csrf_mismatch', function () {
        throw new TokenMismatchException('CSRF token mismatch.');
    });

    $response = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
        ->postJson('/__test_csrf_mismatch');

    $response->assertStatus(419);
    $response->assertJson(['success' => false]);

    $token = $response->json('csrf_token');
    expect($token)->toBeString()->not->toBeEmpty();
});

test('csrf mismatch em requisicao web normal redireciona para login', function () {
    Route::middleware('web')->post('/__test_csrf_mismatch_web', function () {
        throw new TokenMismatchException('CSRF token mismatch.');
    });

    $response = $this->post('/__test_csrf_mismatch_web');

    $response->assertStatus(302);
    $response->assertRedirect('/login');
});

test('respostas trazem cabecalhos de seguranca', function () {
    $response = $this->get('/login');

    $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    $response->assertHeader('X-Content-Type-Options', 'nosniff');
    $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
});
