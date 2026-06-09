<?php

use App\Models\CreditTransaction;
use App\Models\MercadoPagoPayment;
use App\Models\RecargaAutomatica;
use App\Models\User;
use Illuminate\Support\Facades\Http;

use function Pest\Laravel\actingAs;
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

function assinaturaValidaRecarga(string $dataId, string $requestId, string $secret): string
{
    $ts = (string) time();
    $manifest = 'id:'.strtolower($dataId).';request-id:'.$requestId.';ts:'.$ts.';';
    $v1 = hash_hmac('sha256', $manifest, $secret);

    return "ts={$ts},v1={$v1}";
}

it('criar recarga usa valor/créditos do catálogo (não do front) e persiste pendente', function () {
    Http::fake(['api.mercadopago.com/preapproval' => Http::response(['id' => 'PRE-R1', 'status' => 'pending'], 201)]);

    $user = User::factory()->create();
    actingAs($user);

    // business = 1000 cr / R$200; tenta adulterar amount → ignorado.
    $resp = postJson(route('app.recarga.criar'), [
        'pacote' => 'business',
        'token' => 'card-token-xyz',
        'amount' => 1.00,
    ])->assertOk();

    $resp->assertJsonPath('status', 'pendente');

    $recarga = RecargaAutomatica::first();
    expect($recarga->status)->toBe('pendente');
    expect($recarga->creditos)->toBe(1000);
    expect((float) $recarga->valor)->toBe(200.0);
    expect($recarga->mp_preapproval_id)->toBe('PRE-R1');

    Http::assertSent(fn ($req) => $req->url() === 'https://api.mercadopago.com/preapproval'
        && $req['auto_recurring']['transaction_amount'] === 200.0
        && $req['auto_recurring']['frequency'] === 1
        && $req['card_token_id'] === 'card-token-xyz');
});

it('criar recarga recusa pacote inválido', function () {
    $user = User::factory()->create();
    actingAs($user);
    Http::fake();

    postJson(route('app.recarga.criar'), ['pacote' => 'inexistente', 'token' => 't'])->assertStatus(422);
    expect(RecargaAutomatica::count())->toBe(0);
    Http::assertNothingSent();
});

it('webhook preapproval authorized ativa a recarga (fallthrough da assinatura)', function () {
    $user = User::factory()->create();
    $recarga = RecargaAutomatica::create([
        'user_id' => $user->id, 'pacote' => 'business', 'creditos' => 1000, 'valor' => 200,
        'status' => 'pendente', 'mp_preapproval_id' => 'PRE-RA',
    ]);

    Http::fake([
        'api.mercadopago.com/preapproval/PRE-RA' => Http::response(['id' => 'PRE-RA', 'status' => 'authorized'], 200),
    ]);

    $sig = assinaturaValidaRecarga('PRE-RA', 'req-ra', 'webhook-secret-xyz');
    $this->withHeaders(['x-signature' => $sig, 'x-request-id' => 'req-ra'])
        ->postJson('/api/mercado-pago/webhook?type=subscription_preapproval&data_id=PRE-RA', [
            'type' => 'subscription_preapproval', 'data' => ['id' => 'PRE-RA'],
        ])->assertOk();

    expect($recarga->fresh()->status)->toBe('ativa');
});

it('webhook authorized_payment approved credita o pacote 1× (idempotente)', function () {
    $user = User::factory()->create(['credits' => 0]);
    RecargaAutomatica::create([
        'user_id' => $user->id, 'pacote' => 'business', 'creditos' => 1000, 'valor' => 200,
        'status' => 'ativa', 'mp_preapproval_id' => 'PRE-RB',
    ]);

    Http::fake([
        'api.mercadopago.com/authorized_payments/AP-RB' => Http::response([
            'id' => 'AP-RB', 'status' => 'approved', 'preapproval_id' => 'PRE-RB', 'transaction_amount' => 200.0,
        ], 200),
    ]);

    $sig = assinaturaValidaRecarga('AP-RB', 'req-rb', 'webhook-secret-xyz');
    $enviar = fn () => $this->withHeaders(['x-signature' => $sig, 'x-request-id' => 'req-rb'])
        ->postJson('/api/mercado-pago/webhook?type=subscription_authorized_payment&data_id=AP-RB', [
            'type' => 'subscription_authorized_payment', 'data' => ['id' => 'AP-RB'],
        ]);

    $enviar()->assertOk();
    $enviar()->assertOk(); // reentrega

    expect($user->fresh()->credits)->toBe(1000); // creditou 1× só
    expect(CreditTransaction::where('user_id', $user->id)->where('type', 'purchase')->count())->toBe(1);
    expect(MercadoPagoPayment::where('tipo', 'recarga')->count())->toBe(1);
});

it('webhook authorized_payment rejected marca a recarga inadimplente e não credita', function () {
    $user = User::factory()->create(['credits' => 0]);
    RecargaAutomatica::create([
        'user_id' => $user->id, 'pacote' => 'business', 'creditos' => 1000, 'valor' => 200,
        'status' => 'ativa', 'mp_preapproval_id' => 'PRE-RC',
    ]);

    Http::fake([
        'api.mercadopago.com/authorized_payments/AP-RC' => Http::response([
            'id' => 'AP-RC', 'status' => 'rejected', 'preapproval_id' => 'PRE-RC',
        ], 200),
    ]);

    $sig = assinaturaValidaRecarga('AP-RC', 'req-rc', 'webhook-secret-xyz');
    $this->withHeaders(['x-signature' => $sig, 'x-request-id' => 'req-rc'])
        ->postJson('/api/mercado-pago/webhook?type=subscription_authorized_payment&data_id=AP-RC', [
            'type' => 'subscription_authorized_payment', 'data' => ['id' => 'AP-RC'],
        ])->assertOk();

    expect($user->fresh()->credits)->toBe(0);
    expect(RecargaAutomatica::first()->status)->toBe('inadimplente');
});

it('a página /app/creditos renderiza o opt-in de recarga com SDK e public key', function () {
    config(['services.mercadopago.public_key' => 'TEST-PK-RECARGA']);

    $user = User::factory()->create();
    actingAs($user);

    $html = \Pest\Laravel\get('/app/creditos')->assertOk()->getContent();

    expect($html)->toContain('Recarga automática');
    expect($html)->toContain('recarga-ativar');           // botão de opt-in
    expect($html)->toContain('sdk.mercadopago.com/js/v2'); // SDK
    expect($html)->toContain('TEST-PK-RECARGA');           // public key
    expect($html)->toContain('/js/recarga.js');
});

it('cancelar recarga chama o MP e marca cancelada', function () {
    $user = User::factory()->create();
    RecargaAutomatica::create([
        'user_id' => $user->id, 'pacote' => 'business', 'creditos' => 1000, 'valor' => 200,
        'status' => 'ativa', 'mp_preapproval_id' => 'PRE-RD',
    ]);

    Http::fake(['api.mercadopago.com/preapproval/PRE-RD' => Http::response(['id' => 'PRE-RD', 'status' => 'cancelled'], 200)]);

    actingAs($user);
    postJson(route('app.recarga.cancelar'))->assertOk();

    expect(RecargaAutomatica::first()->status)->toBe('cancelada');
    Http::assertSent(fn ($req) => $req->method() === 'PUT' && str_ends_with($req->url(), '/preapproval/PRE-RD'));
});
