<?php

use App\Mail\RecargaAutomaticaPausada;
use App\Models\CreditTransaction;
use App\Models\MercadoPagoPayment;
use App\Models\RecargaAutomatica;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(fn () => config([
    'services.mercadopago.access_token' => 'TEST-token',
    'services.mercadopago.base_url' => 'https://api.mercadopago.com',
    'services.mercadopago.webhook_secret' => 'webhook-secret-xyz',
]));

function sigTopup(string $dataId, string $reqId, string $secret): string
{
    $ts = (string) time();
    $manifest = 'id:'.strtolower($dataId).';request-id:'.$reqId.';ts:'.$ts.';';

    return "ts={$ts},v1=".hash_hmac('sha256', $manifest, $secret);
}

it('webhook payment approved de auto_topup credita 1x e marca recarga ativa', function () {
    $user = User::factory()->create(['credits' => 0]);
    $recarga = RecargaAutomatica::create([
        'user_id' => $user->id, 'gatilho' => 'saldo', 'limite_creditos' => 50,
        'pacote' => 'business', 'creditos' => 1000, 'valor' => 200, 'status' => 'ativa',
        'mp_customer_id' => 'CUS-1', 'mp_card_id' => 'CARD-1', 'cobranca_em_andamento' => true,
    ]);
    $pay = MercadoPagoPayment::create([
        'user_id' => $user->id, 'tipo' => 'auto_topup', 'pacote' => 'business',
        'status' => 'pending', 'valor' => 200, 'creditos' => 1000,
        'idempotency_key' => 'k1', 'mp_payment_id' => 'PAY-1',
    ]);

    Http::fake(['api.mercadopago.com/v1/payments/PAY-1' => Http::response([
        'id' => 'PAY-1', 'status' => 'approved', 'external_reference' => (string) $pay->id,
    ], 200)]);

    $enviar = fn () => $this->withHeaders(['x-signature' => sigTopup('PAY-1', 'r1', 'webhook-secret-xyz'), 'x-request-id' => 'r1'])
        ->postJson('/api/mercado-pago/webhook?type=payment&data_id=PAY-1', ['type' => 'payment', 'data' => ['id' => 'PAY-1']]);

    $enviar()->assertOk();
    $enviar()->assertOk(); // reentrega

    expect($user->fresh()->credits)->toBe(1000);
    expect(CreditTransaction::where('user_id', $user->id)->where('type', 'purchase')->count())->toBe(1);
    $recarga->refresh();
    expect($recarga->status)->toBe('ativa');
    expect($recarga->cobranca_em_andamento)->toBeFalse();
    expect($recarga->ultima_cobranca_em)->not->toBeNull();
});

it('webhook payment rejected de auto_topup marca inadimplente e notifica', function () {
    Mail::fake();
    $user = User::factory()->create(['credits' => 0]);
    $recarga = RecargaAutomatica::create([
        'user_id' => $user->id, 'gatilho' => 'saldo', 'limite_creditos' => 50,
        'pacote' => 'business', 'creditos' => 1000, 'valor' => 200, 'status' => 'ativa',
        'mp_customer_id' => 'CUS-1', 'mp_card_id' => 'CARD-1', 'cobranca_em_andamento' => true,
    ]);
    $pay = MercadoPagoPayment::create([
        'user_id' => $user->id, 'tipo' => 'auto_topup', 'pacote' => 'business',
        'status' => 'pending', 'valor' => 200, 'creditos' => 1000,
        'idempotency_key' => 'k2', 'mp_payment_id' => 'PAY-2',
    ]);

    Http::fake(['api.mercadopago.com/v1/payments/PAY-2' => Http::response([
        'id' => 'PAY-2', 'status' => 'rejected', 'external_reference' => (string) $pay->id,
    ], 200)]);

    $this->withHeaders(['x-signature' => sigTopup('PAY-2', 'r2', 'webhook-secret-xyz'), 'x-request-id' => 'r2'])
        ->postJson('/api/mercado-pago/webhook?type=payment&data_id=PAY-2', ['type' => 'payment', 'data' => ['id' => 'PAY-2']])
        ->assertOk();

    expect($user->fresh()->credits)->toBe(0);
    expect($recarga->fresh()->status)->toBe('inadimplente');
    expect($recarga->fresh()->cobranca_em_andamento)->toBeFalse();
    Mail::assertQueued(RecargaAutomaticaPausada::class);
});
