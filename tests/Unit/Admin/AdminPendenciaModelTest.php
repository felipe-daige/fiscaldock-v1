<?php

use App\Models\AdminPendencia;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function pendencia(array $attrs = []): AdminPendencia
{
    $u = User::factory()->create(['is_admin' => true]);

    return AdminPendencia::create(array_merge([
        'titulo' => 'Teste', 'status' => 'aberta', 'criado_por' => $u->id,
    ], $attrs));
}

it('marca como vencida quando aberta e lembrar_em passou', function () {
    expect(pendencia(['lembrar_em' => now()->subDay()])->esta_vencida)->toBeTrue();
});

it('não é vencida quando lembrar_em é futura ou nula', function () {
    expect(pendencia(['lembrar_em' => now()->addDay()])->esta_vencida)->toBeFalse();
    expect(pendencia(['lembrar_em' => null])->esta_vencida)->toBeFalse();
});

it('não é vencida quando já resolvida mesmo com data passada', function () {
    expect(pendencia(['lembrar_em' => now()->subDay(), 'status' => 'resolvida'])->esta_vencida)->toBeFalse();
});

it('scope vencidas só pega abertas com data <= hoje', function () {
    pendencia(['lembrar_em' => now()->subDay()]);                          // vencida
    pendencia(['lembrar_em' => now()->addDay()]);                          // futura
    pendencia(['lembrar_em' => now()->subDay(), 'status' => 'resolvida']); // resolvida
    expect(AdminPendencia::vencidas()->count())->toBe(1);
});

it('scope abertas e resolvidas separam por status', function () {
    pendencia();
    pendencia(['status' => 'resolvida']);
    expect(AdminPendencia::abertas()->count())->toBe(1);
    expect(AdminPendencia::resolvidas()->count())->toBe(1);
});
