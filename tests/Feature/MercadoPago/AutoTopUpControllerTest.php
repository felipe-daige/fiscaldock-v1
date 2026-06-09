<?php

use App\Models\RecargaAutomatica;
use App\Models\User;
use Illuminate\Support\Facades\Http;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(fn () => config([
    'services.mercadopago.access_token' => 'TEST-token',
    'services.mercadopago.base_url' => 'https://api.mercadopago.com',
    'services.mercadopago.preapproval_teto_centavos' => 400000,
]));

it('endpoint cria recarga por saldo com limite', function () {
    Http::fake([
        'api.mercadopago.com/v1/customers/search*' => Http::response(['results' => []], 200),
        'api.mercadopago.com/v1/customers' => Http::response(['id' => 'CUS-1'], 201),
        'api.mercadopago.com/v1/customers/CUS-1/cards' => Http::response(['id' => 'CARD-1'], 201),
    ]);
    $user = User::factory()->create();
    actingAs($user);

    postJson(route('app.recarga.criar-saldo'), ['pacote' => 'business', 'token' => 'tok', 'limite_creditos' => 50])
        ->assertOk()->assertJsonPath('status', 'ativa');

    $r = RecargaAutomatica::first();
    expect($r->gatilho)->toBe('saldo');
    expect($r->limite_creditos)->toBe(50);
});

it('endpoint recusa limite ausente', function () {
    $user = User::factory()->create();
    actingAs($user);
    Http::fake();
    postJson(route('app.recarga.criar-saldo'), ['pacote' => 'business', 'token' => 'tok'])->assertStatus(422);
});

it('cancelar recarga por saldo limpa o vault sem chamar preapproval', function () {
    Http::fake();
    $user = User::factory()->create();
    RecargaAutomatica::create([
        'user_id' => $user->id, 'gatilho' => 'saldo', 'limite_creditos' => 50,
        'pacote' => 'business', 'creditos' => 1000, 'valor' => 200, 'status' => 'ativa',
        'mp_customer_id' => 'CUS-1', 'mp_card_id' => 'CARD-1',
    ]);
    actingAs($user);

    postJson(route('app.recarga.cancelar'))->assertOk();

    expect(RecargaAutomatica::first()->status)->toBe('cancelada');
    Http::assertNothingSent(); // sem preapproval a cancelar
});
