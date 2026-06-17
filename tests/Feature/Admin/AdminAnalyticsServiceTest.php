<?php

use App\Models\User;
use App\Services\Admin\AdminAnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('agrega crescimento, receita, créditos e uso no período', function () {
    $u1 = User::factory()->create(['created_at' => now()->subDays(2)]);
    User::factory()->create(['created_at' => now()->subDays(60)]);

    DB::table('mercado_pago_payments')->insert([
        'user_id' => $u1->id, 'pacote' => 'licitacao', 'status' => 'approved', 'valor' => 100.00,
        'creditos' => 500, 'idempotency_key' => 'test-idem-1',
        'created_at' => now()->subDay(), 'updated_at' => now(),
    ]);
    DB::table('credit_transactions')->insert([
        ['user_id' => $u1->id, 'amount' => 500, 'balance_after' => 500, 'type' => 'purchase', 'created_at' => now(), 'updated_at' => now()],
        ['user_id' => $u1->id, 'amount' => -10, 'balance_after' => 490, 'type' => 'consulta_lote', 'created_at' => now(), 'updated_at' => now()],
    ]);
    DB::table('consulta_lotes')->insert([
        'user_id' => $u1->id, 'status' => 'finalizado', 'total_participantes' => 1, 'creditos_cobrados' => 10,
        'tab_id' => 't1', 'created_at' => now(), 'updated_at' => now(),
    ]);

    $r = app(AdminAnalyticsService::class)->resumo(['periodo' => '30']);

    expect($r['crescimento']['total_usuarios'])->toBe(2);
    expect($r['crescimento']['novos'])->toBe(1);
    expect($r['receita']['aprovada_total'])->toBe(100.0);
    expect($r['creditos']['vendidos'])->toBe(500.0);
    expect($r['creditos']['consumidos'])->toBe(10.0);
    expect($r['uso']['consultas'])->toBe(1);
});

it('conta conversão de trial (trial_used + compra)', function () {
    $conv = User::factory()->create(['trial_used' => true]);
    DB::table('credit_transactions')->insert(['user_id' => $conv->id, 'amount' => 100, 'balance_after' => 100, 'type' => 'purchase', 'created_at' => now(), 'updated_at' => now()]);
    User::factory()->create(['trial_used' => true]);

    $r = app(AdminAnalyticsService::class)->resumo();

    expect($r['trial']['convertidos'])->toBe(1);
});

it('periodo "tudo" não filtra por data', function () {
    User::factory()->create(['created_at' => now()->subDays(400)]);

    $r = app(AdminAnalyticsService::class)->resumo(['periodo' => 'tudo']);

    expect($r['crescimento']['novos'])->toBe($r['crescimento']['total_usuarios']);
});
