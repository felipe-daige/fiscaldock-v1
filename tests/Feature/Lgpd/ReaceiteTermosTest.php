<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

it('redireciona usuário com versão defasada para o re-aceite (full page)', function () {
    config(['legal.terms_version' => '2.0', 'legal.privacy_version' => '2.0']);
    $user = User::factory()->create(['terms_version' => '1.0', 'privacy_version' => '1.0']);

    actingAs($user)->get('/app/privacidade')
        ->assertRedirect(route('app.reaceite.show'));
});

it('deixa passar usuário com versões em dia', function () {
    config(['legal.terms_version' => '1.0', 'legal.privacy_version' => '1.0']);
    $user = User::factory()->create(['terms_version' => '1.0', 'privacy_version' => '1.0']);

    actingAs($user)->get('/app/privacidade')->assertOk();
});

it('não intercepta requisição AJAX do SPA mesmo com versão defasada', function () {
    config(['legal.terms_version' => '2.0', 'legal.privacy_version' => '2.0']);
    $user = User::factory()->create(['terms_version' => '1.0', 'privacy_version' => '1.0']);

    actingAs($user)->get('/app/privacidade', ['X-Requested-With' => 'XMLHttpRequest'])
        ->assertOk();
});

it('mostra o interstitial sem entrar em loop de redirect', function () {
    config(['legal.terms_version' => '2.0', 'legal.privacy_version' => '2.0']);
    $user = User::factory()->create(['terms_version' => '1.0', 'privacy_version' => '1.0']);

    actingAs($user)->get(route('app.reaceite.show'))
        ->assertOk()
        ->assertSee('Atualizamos');
});

it('aceitar atualiza versões, registra na trilha e libera o app', function () {
    config(['legal.terms_version' => '2.0', 'legal.privacy_version' => '2.0']);
    $user = User::factory()->create(['terms_version' => '1.0', 'privacy_version' => '1.0']);

    actingAs($user)->post(route('app.reaceite.aceitar'), ['aceito' => '1'])
        ->assertRedirect('/app/dashboard');

    $user->refresh();
    expect($user->terms_version)->toBe('2.0');
    expect($user->privacy_version)->toBe('2.0');
    $this->assertDatabaseHas('consent_logs', [
        'user_id' => $user->id, 'tipo' => 'termos', 'acao' => 'aceite', 'versao' => '2.0',
    ]);
    $this->assertDatabaseHas('consent_logs', [
        'user_id' => $user->id, 'tipo' => 'privacidade', 'acao' => 'aceite', 'versao' => '2.0',
    ]);
});

it('aceitar exige marcar o checkbox', function () {
    config(['legal.terms_version' => '2.0', 'legal.privacy_version' => '2.0']);
    $user = User::factory()->create(['terms_version' => '1.0', 'privacy_version' => '1.0']);

    actingAs($user)->post(route('app.reaceite.aceitar'), [])
        ->assertSessionHasErrors('aceito');
});

it('quem já está em dia é mandado pro dashboard se cair no interstitial', function () {
    config(['legal.terms_version' => '1.0', 'legal.privacy_version' => '1.0']);
    $user = User::factory()->create(['terms_version' => '1.0', 'privacy_version' => '1.0']);

    actingAs($user)->get(route('app.reaceite.show'))->assertRedirect('/app/dashboard');
});
