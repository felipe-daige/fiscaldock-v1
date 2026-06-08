<?php

use App\Models\AccountSubscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\PricingCatalogService;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(SubscriptionPlanSeeder::class);
    $this->pricing = app(PricingCatalogService::class);
});

it('assinante Profissional ganha faixa X mesmo sem histórico pago', function () {
    $user = User::factory()->create();
    $plano = SubscriptionPlan::where('codigo', 'profissional')->first();
    AccountSubscription::create([
        'user_id' => $user->id, 'subscription_plan_id' => $plano->id,
        'status' => 'ativa', 'ciclo' => 'mensal',
    ]);

    expect($this->pricing->getTierForUser($user)['slug'])->toBe('x');
});

it('Free sem histórico continua na Base', function () {
    $user = User::factory()->create();
    expect($this->pricing->getTierForUser($user)['slug'])->toBe('base');
});

it('histórico pago alto vence a faixa do plano (não regride)', function () {
    $user = User::factory()->create();
    // Essencial compra faixa base; mas histórico pago de 6000 créditos = faixa Y.
    $plano = SubscriptionPlan::where('codigo', 'essencial')->first();
    AccountSubscription::create([
        'user_id' => $user->id, 'subscription_plan_id' => $plano->id,
        'status' => 'ativa', 'ciclo' => 'mensal',
    ]);
    \App\Models\CreditTransaction::create([
        'user_id' => $user->id, 'amount' => 6000, 'balance_after' => 6000,
        'type' => 'purchase', 'description' => 'teste',
    ]);

    expect($this->pricing->getTierForUser($user)['slug'])->toBe('y');
});
