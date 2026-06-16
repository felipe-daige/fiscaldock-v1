<?php

use App\Models\AccountSubscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(SubscriptionPlanSeeder::class);
});

function comTrialAtivo(User $user): User
{
    $user->forceFill([
        'trial_used' => true,
        'trial_started_at' => now(),
        'trial_expires_at' => now()->addDays(30),
        'trial_credits_remaining' => 50,
    ])->save();

    return $user;
}

function comPlano(User $user, string $codigo): User
{
    $plano = SubscriptionPlan::where('codigo', $codigo)->first();
    AccountSubscription::create([
        'user_id' => $user->id, 'subscription_plan_id' => $plano->id,
        'status' => 'ativa', 'ciclo' => 'mensal',
    ]);

    return $user;
}

// ---- Clearance em lote (clearance_lote) ----

it('Free puro recebe 403 ao validar clearance em lote', function () {
    actingAs(User::factory()->create())
        ->postJson('/app/clearance/notas/validar', ['ids' => [1]])
        ->assertStatus(403);
});

it('Free com trial ativo NÃO é barrado pelo gate de clearance lote', function () {
    $status = actingAs(comTrialAtivo(User::factory()->create()))
        ->postJson('/app/clearance/notas/validar', ['ids' => [1]])->status();
    expect($status)->not->toBe(403);
});

it('plano Essencial NÃO é barrado pelo gate de clearance lote', function () {
    $status = actingAs(comPlano(User::factory()->create(), 'essencial'))
        ->postJson('/app/clearance/notas/validar', ['ids' => [1]])->status();
    expect($status)->not->toBe(403);
});

// ---- Export (export) ----

it('Free puro recebe 403 ao exportar BI', function () {
    actingAs(User::factory()->create())
        ->get('/app/bi/exportar')
        ->assertStatus(403);
});

it('plano Essencial (export=[csv]) NÃO é barrado no export do BI', function () {
    $status = actingAs(comPlano(User::factory()->create(), 'essencial'))
        ->get('/app/bi/exportar')->status();
    expect($status)->not->toBe(403);
});
