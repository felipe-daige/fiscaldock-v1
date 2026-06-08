<?php

use App\Models\MercadoPagoPayment;
use App\Models\User;
use Illuminate\Support\Facades\Http;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\postJson;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    config([
        'services.mercadopago.access_token' => 'TEST-token',
        'services.mercadopago.public_key' => 'TEST-pk',
        'services.mercadopago.webhook_secret' => 'webhook-secret-xyz',
        'services.mercadopago.base_url' => 'https://api.mercadopago.com',
    ]);
});

/**
 * Monta um header x-signature válido para o webhook, espelhando o manifest do MP:
 * id:<data.id>;request-id:<x-request-id>;ts:<ts>;
 */
function assinaturaValida(string $dataId, string $requestId, string $secret): string
{
    $ts = (string) time();
    $manifest = 'id:'.strtolower($dataId).';request-id:'.$requestId.';ts:'.$ts.';';
    $v1 = hash_hmac('sha256', $manifest, $secret);

    return "ts={$ts},v1={$v1}";
}

it('cria pagamento pending com valor/créditos do catálogo backend (não do front)', function () {
    Http::fake([
        'api.mercadopago.com/v1/payments' => Http::response([
            'id' => 9001,
            'status' => 'pending',
            'status_detail' => 'pending_waiting_transfer',
            'payment_method_id' => 'pix',
        ], 201),
    ]);

    $user = User::factory()->create();
    actingAs($user);

    // Front tenta adulterar o amount; backend ignora e usa o catálogo (business = R$200 / 1000 cr).
    $resp = postJson(route('app.pagamento.mercadopago.criar'), [
        'pacote' => 'business',
        'amount' => 1.00,
        'payment_data' => ['payment_method_id' => 'pix'],
    ])->assertOk();

    $resp->assertJsonPath('status', 'pending')
        ->assertJsonPath('creditos', 1000)
        ->assertJsonPath('valor', '200.00');

    $payment = MercadoPagoPayment::first();
    expect($payment->valor)->toBe('200.00');
    expect($payment->creditos)->toBe(1000);
    expect($payment->mp_payment_id)->toBe('9001');
    expect($payment->credited_at)->toBeNull();

    // O valor enviado ao MP é o do catálogo, não o do front.
    Http::assertSent(fn ($req) => $req['transaction_amount'] === 200.0);
});

it('erro do MP (sem id) vira rejected com a causa, sem gravar o HTTP code como status', function () {
    Http::fake([
        'api.mercadopago.com/v1/payments' => Http::response([
            'error' => 'bad_request',
            'status' => 400, // HTTP code ecoado no corpo — NÃO é status de pagamento
            'message' => 'Invalid users involved',
            'cause' => [['code' => 2034, 'description' => 'Invalid users involved']],
        ], 400),
    ]);

    $user = User::factory()->create();
    actingAs($user);

    $resp = postJson(route('app.pagamento.mercadopago.criar'), [
        'pacote' => 'business',
        'payment_data' => ['payment_method_id' => 'pix'],
    ])->assertOk();

    $resp->assertJsonPath('status', 'rejected');

    $payment = MercadoPagoPayment::first();
    expect($payment->status)->toBe('rejected');
    expect($payment->mp_payment_id)->toBeNull();
    expect($payment->status_detail)->toBe('Invalid users involved');
    expect($payment->credited_at)->toBeNull();
});

it('recusa pacote inválido', function () {
    $user = User::factory()->create();
    actingAs($user);

    postJson(route('app.pagamento.mercadopago.criar'), [
        'pacote' => 'inexistente',
    ])->assertStatus(422);

    expect(MercadoPagoPayment::count())->toBe(0);
});

it('webhook approved com assinatura válida credita 1× (idempotente em reentrega)', function () {
    $user = User::factory()->create(['credits' => 10]);

    $payment = MercadoPagoPayment::create([
        'user_id' => $user->id,
        'pacote' => 'business',
        'mp_payment_id' => '7777',
        'status' => 'pending',
        'valor' => 200.00,
        'creditos' => 1000,
        'idempotency_key' => 'idem-1',
    ]);

    Http::fake([
        'api.mercadopago.com/v1/payments/7777' => Http::response([
            'id' => 7777,
            'status' => 'approved',
            'status_detail' => 'accredited',
            'payment_method_id' => 'pix',
            'external_reference' => (string) $payment->id,
        ], 200),
    ]);

    $sig = assinaturaValida('7777', 'req-1', 'webhook-secret-xyz');

    $enviar = fn () => $this->withHeaders([
        'x-signature' => $sig,
        'x-request-id' => 'req-1',
    ])->postJson('/api/mercado-pago/webhook?type=payment&data_id=7777', [
        'type' => 'payment',
        'data' => ['id' => '7777'],
    ]);

    $enviar()->assertOk();
    $enviar()->assertOk(); // reentrega

    $user->refresh();
    expect($user->credits)->toBe(1010); // 10 + 1000, só uma vez
    expect($payment->fresh()->status)->toBe('approved');
    expect($payment->fresh()->credited_at)->not->toBeNull();

    expect(\App\Models\CreditTransaction::where('user_id', $user->id)->where('type', 'purchase')->count())
        ->toBe(1);
});

it('webhook com assinatura inválida retorna 401 e não credita', function () {
    $user = User::factory()->create(['credits' => 10]);

    MercadoPagoPayment::create([
        'user_id' => $user->id,
        'pacote' => 'business',
        'mp_payment_id' => '8888',
        'status' => 'pending',
        'valor' => 200.00,
        'creditos' => 1000,
        'idempotency_key' => 'idem-2',
    ]);

    Http::fake(); // não deve nem chamar o MP

    $this->withHeaders([
        'x-signature' => 'ts=123,v1=deadbeef',
        'x-request-id' => 'req-x',
    ])->postJson('/api/mercado-pago/webhook?type=payment&data_id=8888', [
        'type' => 'payment',
        'data' => ['id' => '8888'],
    ])->assertStatus(401);

    expect($user->fresh()->credits)->toBe(10);
    Http::assertNothingSent();
});

it('checkout renderiza o Payment Brick real (sem simulação) e injeta a public key', function () {
    config(['services.mercadopago.public_key' => 'TEST-public-key-render']);

    $user = User::factory()->create();
    actingAs($user);

    $resp = get('/app/checkout/business')->assertOk();
    $html = $resp->getContent();

    expect($html)->toContain('paymentBrick_container');         // container do Brick
    expect($html)->toContain('sdk.mercadopago.com/js/v2');       // SDK do MP
    expect($html)->toContain('TEST-public-key-render');          // public key injetada
    expect($html)->toContain('data-mp-endpoint');                // endpoint pro Brick

    // A simulação antiga não pode mais existir.
    expect($html)->not->toContain('_ckProcessPayment');
    expect($html)->not->toContain('Simulacao de Gateway');
});

it('webhook rejected não credita', function () {
    $user = User::factory()->create(['credits' => 10]);

    $payment = MercadoPagoPayment::create([
        'user_id' => $user->id,
        'pacote' => 'business',
        'mp_payment_id' => '5555',
        'status' => 'pending',
        'valor' => 200.00,
        'creditos' => 1000,
        'idempotency_key' => 'idem-3',
    ]);

    Http::fake([
        'api.mercadopago.com/v1/payments/5555' => Http::response([
            'id' => 5555,
            'status' => 'rejected',
            'status_detail' => 'cc_rejected_insufficient_amount',
            'external_reference' => (string) $payment->id,
        ], 200),
    ]);

    $sig = assinaturaValida('5555', 'req-3', 'webhook-secret-xyz');

    $this->withHeaders([
        'x-signature' => $sig,
        'x-request-id' => 'req-3',
    ])->postJson('/api/mercado-pago/webhook?type=payment&data_id=5555', [
        'type' => 'payment',
        'data' => ['id' => '5555'],
    ])->assertOk();

    expect($user->fresh()->credits)->toBe(10);
    expect($payment->fresh()->status)->toBe('rejected');
    expect($payment->fresh()->credited_at)->toBeNull();
});
