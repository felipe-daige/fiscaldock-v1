<?php

use App\Models\AdminPendencia;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

function adminUser(): User
{
    return User::factory()->create(['is_admin' => true]);
}

function novaPendencia(User $autor, array $attrs = []): AdminPendencia
{
    return AdminPendencia::create(array_merge([
        'titulo' => 'x', 'status' => 'aberta', 'criado_por' => $autor->id,
    ], $attrs));
}

it('bloqueia não-admin no índice de pendências', function () {
    actingAs(User::factory()->create(['is_admin' => false]))
        ->get('/app/admin/pendencias')->assertStatus(403);
});

it('admin abre o índice', function () {
    actingAs(adminUser())->get('/app/admin/pendencias')->assertOk();
});

it('cria pendência com criado_por do admin logado', function () {
    $a = adminUser();
    actingAs($a)->post('/app/admin/pendencias', [
        'titulo' => 'Revisar X', 'nota' => 'contexto', 'lembrar_em' => '2026-11-03',
    ])->assertRedirect();

    $p = AdminPendencia::first();
    expect($p->titulo)->toBe('Revisar X');
    expect($p->status)->toBe('aberta');
    expect($p->criado_por)->toBe($a->id);
    expect($p->lembrar_em->format('Y-m-d'))->toBe('2026-11-03');
});

it('rejeita pendência sem título', function () {
    actingAs(adminUser())->post('/app/admin/pendencias', ['titulo' => ''])
        ->assertSessionHasErrors('titulo');
});

it('índice ordena vencidas antes de futuras', function () {
    $a = adminUser();
    novaPendencia($a, ['titulo' => 'futura', 'lembrar_em' => now()->addMonth()]);
    novaPendencia($a, ['titulo' => 'vencida', 'lembrar_em' => now()->subMonth()]);
    $html = actingAs($a)->get('/app/admin/pendencias')->getContent();
    expect(strpos($html, 'vencida'))->toBeLessThan(strpos($html, 'futura'));
});

it('renderiza form, badge de vencida e botão resolver', function () {
    $a = adminUser();
    novaPendencia($a, ['titulo' => 'pend vencida', 'lembrar_em' => now()->subDay()]);
    $html = actingAs($a)->get('/app/admin/pendencias')->assertOk()->getContent();
    expect($html)->toContain('pend vencida');
    expect($html)->toContain('vencida em');
    expect($html)->toContain(route('app.admin.pendencias.store'));
});

it('resolver marca status e grava quem resolveu', function () {
    $a = adminUser();
    $p = novaPendencia($a);
    actingAs($a)->post("/app/admin/pendencias/{$p->id}/resolver")->assertRedirect();
    $p->refresh();
    expect($p->status)->toBe('resolvida');
    expect($p->resolvido_por)->toBe($a->id);
    expect($p->resolvido_em)->not->toBeNull();
});

it('reabrir limpa os campos de resolução', function () {
    $a = adminUser();
    $p = novaPendencia($a, ['status' => 'resolvida', 'resolvido_por' => $a->id, 'resolvido_em' => now()]);
    actingAs($a)->post("/app/admin/pendencias/{$p->id}/reabrir")->assertRedirect();
    $p->refresh();
    expect($p->status)->toBe('aberta');
    expect($p->resolvido_por)->toBeNull();
    expect($p->resolvido_em)->toBeNull();
});

it('excluir remove a pendência', function () {
    $a = adminUser();
    $p = novaPendencia($a);
    actingAs($a)->delete("/app/admin/pendencias/{$p->id}")->assertRedirect();
    expect(AdminPendencia::find($p->id))->toBeNull();
});

it('bloqueia não-admin de resolver', function () {
    $p = novaPendencia(adminUser());
    actingAs(User::factory()->create(['is_admin' => false]))
        ->post("/app/admin/pendencias/{$p->id}/resolver")->assertStatus(403);
});

it('nav admin mostra aba pendencias e badge de vencidas', function () {
    $a = adminUser();
    novaPendencia($a, ['titulo' => 'pend no badge', 'lembrar_em' => now()->subDay()]);
    $html = actingAs($a)->get('/app/admin/pendencias')->assertOk()->getContent();
    expect($html)->toContain('/app/admin/pendencias');   // aba presente
    expect($html)->toContain('bg-red-600');              // badge de vencida
});
