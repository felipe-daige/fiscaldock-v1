<?php

use App\Jobs\ProcessarAutoTopUpJob;
use App\Mail\RecargaAutomaticaPausada;
use App\Models\MercadoPagoPayment;
use App\Models\RecargaAutomatica;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(fn () => config([
    'services.mercadopago.access_token' => 'TEST-token',
    'services.mercadopago.base_url' => 'https://api.mercadopago.com',
    'services.mercadopago.auto_topup.max_por_dia' => 3,
]));

function recargaSaldoAtiva(User $user, array $over = []): RecargaAutomatica
{
    return RecargaAutomatica::create(array_merge([
        'user_id' => $user->id, 'gatilho' => 'saldo', 'limite_creditos' => 50,
        'pacote' => 'business', 'creditos' => 1000, 'valor' => 200, 'status' => 'ativa',
        'mp_customer_id' => 'CUS-1', 'mp_card_id' => 'CARD-1', 'cobranca_em_andamento' => false,
    ], $over));
}

it('cobra quando saldo abaixo do limite e seta a flag/tentativa', function () {
    Http::fake([
        'api.mercadopago.com/v1/card_tokens' => Http::response(['id' => 'NEWTOK'], 201),
        'api.mercadopago.com/v1/payments' => Http::response(['id' => 'PAY-1', 'status' => 'pending'], 201),
    ]);
    $user = User::factory()->create(['credits' => 10]);
    $r = recargaSaldoAtiva($user);

    (new ProcessarAutoTopUpJob($user->id))->handle();

    expect(MercadoPagoPayment::where('tipo', 'auto_topup')->count())->toBe(1);
    $r->refresh();
    expect($r->cobranca_em_andamento)->toBeTrue();
    expect($r->ultima_tentativa_em)->not->toBeNull();
});

it('não cobra se o saldo já voltou acima do limite', function () {
    Http::fake();
    $user = User::factory()->create(['credits' => 999]);
    recargaSaldoAtiva($user);
    (new ProcessarAutoTopUpJob($user->id))->handle();
    expect(MercadoPagoPayment::count())->toBe(0);
});

it('teto diário atingido pausa, notifica e não cobra', function () {
    Http::fake();
    Mail::fake();
    $user = User::factory()->create(['credits' => 10]);
    $r = recargaSaldoAtiva($user);
    foreach (range(1, 3) as $i) {
        MercadoPagoPayment::create([
            'user_id' => $user->id, 'tipo' => 'auto_topup', 'pacote' => 'business',
            'status' => 'approved', 'valor' => 200, 'creditos' => 1000,
            'idempotency_key' => "k{$i}",
        ]);
    }
    (new ProcessarAutoTopUpJob($user->id))->handle();
    expect($r->fresh()->status)->toBe('inadimplente');
    Mail::assertQueued(RecargaAutomaticaPausada::class);
    Http::assertNothingSent();
});
