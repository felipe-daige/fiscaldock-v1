<?php

use App\Models\AdminPendencia;
use App\Models\User;
use Database\Seeders\AdminPendenciaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('cria o lembrete do híbrido idempotente', function () {
    User::factory()->create(['is_admin' => true]); // garante um user p/ criado_por
    (new AdminPendenciaSeeder())->run();
    (new AdminPendenciaSeeder())->run(); // 2x não duplica

    $itens = AdminPendencia::where('titulo', 'like', '%Simples híbrido%')->get();
    expect($itens)->toHaveCount(1);
    expect($itens->first()->lembrar_em->format('Y-m-d'))->toBe('2026-11-03');
    expect($itens->first()->status)->toBe('aberta');
});
