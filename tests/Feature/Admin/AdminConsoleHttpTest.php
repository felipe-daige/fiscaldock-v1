<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

it('bloqueia visitante no admin (redirect login)', function () {
    $this->get('/app/admin')->assertRedirect(route('login'));
});

it('bloqueia usuário não-admin com 403', function () {
    $u = User::factory()->create(['is_admin' => false]);
    actingAs($u)->get('/app/admin')->assertStatus(403);
});

it('admin vê o dashboard de analytics', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $html = actingAs($admin)->get('/app/admin')->assertOk()->getContent();
    expect($html)->toContain('Visão Geral');
    expect($html)->toContain('Receita');
    expect($html)->toContain('Usuários');
});
