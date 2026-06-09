<?php

use Illuminate\Support\Facades\Schema;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

it('recarga_automaticas tem as colunas do gatilho por saldo', function () {
    foreach ([
        'gatilho', 'limite_creditos', 'mp_customer_id', 'mp_card_id',
        'cobranca_em_andamento', 'ultima_tentativa_em',
    ] as $coluna) {
        expect(Schema::hasColumn('recarga_automaticas', $coluna))->toBeTrue("falta {$coluna}");
    }
});

it('gatilho default é tempo e cobranca_em_andamento default false', function () {
    $user = \App\Models\User::factory()->create();
    $r = \App\Models\RecargaAutomatica::create([
        'user_id' => $user->id, 'pacote' => 'business', 'creditos' => 1000, 'valor' => 200,
        'status' => 'pendente',
    ]);
    expect($r->fresh()->gatilho)->toBe('tempo');
    expect((bool) $r->fresh()->cobranca_em_andamento)->toBeFalse();
});

it('config do auto_topup tem cooldown e teto diário', function () {
    expect(config('services.mercadopago.auto_topup.cooldown_minutos'))->toBe(5);
    expect(config('services.mercadopago.auto_topup.max_por_dia'))->toBe(3);
});
