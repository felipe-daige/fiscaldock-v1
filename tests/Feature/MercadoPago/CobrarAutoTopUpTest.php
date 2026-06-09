<?php

use App\Actions\MercadoPago\CobrarAutoTopUp;
use App\Models\MercadoPagoPayment;
use App\Models\RecargaAutomatica;
use App\Models\User;
use Illuminate\Support\Facades\Http;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(fn () => config([
    'services.mercadopago.access_token' => 'TEST-token',
    'services.mercadopago.base_url' => 'https://api.mercadopago.com',
]));

function recargaSaldoVault(User $user): RecargaAutomatica
{
    return RecargaAutomatica::create([
        'user_id' => $user->id, 'gatilho' => 'saldo', 'limite_creditos' => 50,
        'pacote' => 'business', 'creditos' => 1000, 'valor' => 200, 'status' => 'ativa',
        'mp_customer_id' => 'CUS-1', 'mp_card_id' => 'CARD-1',
    ]);
}

it('gera token do cartão salvo e cobra /v1/payments criando payment auto_topup', function () {
    Http::fake([
        'api.mercadopago.com/v1/card_tokens' => Http::response(['id' => 'NEWTOK'], 201),
        'api.mercadopago.com/v1/payments' => Http::response(['id' => 'PAY-1', 'status' => 'approved'], 201),
    ]);
    $user = User::factory()->create();
    $recarga = recargaSaldoVault($user);

    $pay = (new CobrarAutoTopUp)->execute($recarga);

    expect($pay->tipo)->toBe('auto_topup');
    expect($pay->mp_payment_id)->toBe('PAY-1');
    expect($pay->creditos)->toBe(1000);
    expect((float) $pay->valor)->toBe(200.0);
    Http::assertSent(fn ($req) => $req->url() === 'https://api.mercadopago.com/v1/card_tokens' && $req['card_id'] === 'CARD-1');
    Http::assertSent(fn ($req) => $req->url() === 'https://api.mercadopago.com/v1/payments'
        && $req['token'] === 'NEWTOK'
        && $req['transaction_amount'] === 200.0
        && $req['payer']['id'] === 'CUS-1');
});

it('marca rejected quando o MP não cria o pagamento', function () {
    Http::fake([
        'api.mercadopago.com/v1/card_tokens' => Http::response(['id' => 'NEWTOK'], 201),
        'api.mercadopago.com/v1/payments' => Http::response(['message' => 'bad', 'cause' => [['description' => 'cc_rejected']]], 400),
    ]);
    $user = User::factory()->create();
    $recarga = recargaSaldoVault($user);

    $pay = (new CobrarAutoTopUp)->execute($recarga);
    expect($pay->status)->toBe('rejected');
    expect($pay->mp_payment_id)->toBeNull();
});

it('marca rejected quando não tokeniza o cartão salvo', function () {
    Http::fake([
        'api.mercadopago.com/v1/card_tokens' => Http::response(['message' => 'erro'], 400),
    ]);
    $user = User::factory()->create();
    $recarga = recargaSaldoVault($user);

    $pay = (new CobrarAutoTopUp)->execute($recarga);
    expect($pay->status)->toBe('rejected');
    Http::assertNotSent(fn ($req) => str_contains($req->url(), '/v1/payments'));
});
