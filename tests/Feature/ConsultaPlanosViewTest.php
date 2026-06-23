<?php

use App\Models\CreditTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

it('lista os planos de consulta com a nota do periodo de teste e cobertura atualizada', function () {
    $user = User::factory()->create();

    $resp = actingAs($user)->get('/app/consulta/planos')->assertOk();

    // planos pagos ativos usáveis (Due Diligence foi desativado na reestruturação)
    $resp->assertSee('Compliance')->assertSee('Usar plano')->assertDontSee('Due Diligence');
    // nota do teto de teste (usuário sem 1ª compra)
    $resp->assertSee('Período de teste');
    // cobertura alinhada (rótulos derivados do catálogo via PlanoConsultaLabels) — Licitação/Compliance
    $resp->assertSee('CND Estadual')->assertSee('CNDT (débitos trabalhistas)');
});

it('nao mostra a nota de teste apos a primeira compra', function () {
    $user = User::factory()->create();
    CreditTransaction::create([
        'user_id' => $user->id, 'amount' => 250, 'balance_after' => 250, 'type' => 'purchase',
    ]);

    actingAs($user)->get('/app/consulta/planos')
        ->assertOk()
        ->assertDontSee('Período de teste');
});
