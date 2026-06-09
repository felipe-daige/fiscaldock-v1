<?php

use App\Services\MercadoPago\MercadoPagoClient;
use Illuminate\Support\Facades\Http;

beforeEach(fn () => config([
    'services.mercadopago.access_token' => 'TEST-token',
    'services.mercadopago.base_url' => 'https://api.mercadopago.com',
]));

it('busca customer por email', function () {
    Http::fake(['api.mercadopago.com/v1/customers/search*' => Http::response(['results' => [['id' => 'CUS-1']]], 200)]);
    $r = (new MercadoPagoClient)->buscarCustomerPorEmail('a@b.com');
    expect($r['results'][0]['id'])->toBe('CUS-1');
    Http::assertSent(fn ($req) => str_contains($req->url(), '/v1/customers/search?email=a%40b.com'));
});

it('cria customer', function () {
    Http::fake(['api.mercadopago.com/v1/customers' => Http::response(['id' => 'CUS-2'], 201)]);
    $r = (new MercadoPagoClient)->criarCustomer('a@b.com');
    expect($r['id'])->toBe('CUS-2');
    Http::assertSent(fn ($req) => $req->method() === 'POST' && $req['email'] === 'a@b.com');
});

it('salva cartão no customer', function () {
    Http::fake(['api.mercadopago.com/v1/customers/CUS-2/cards' => Http::response(['id' => 'CARD-9'], 201)]);
    $r = (new MercadoPagoClient)->salvarCartao('CUS-2', 'tok-abc');
    expect($r['id'])->toBe('CARD-9');
    Http::assertSent(fn ($req) => $req->method() === 'POST' && $req['token'] === 'tok-abc');
});

it('gera token de cartão salvo sem CVV', function () {
    Http::fake(['api.mercadopago.com/v1/card_tokens' => Http::response(['id' => 'NEWTOK'], 201)]);
    $r = (new MercadoPagoClient)->tokenDeCartaoSalvo('CARD-9');
    expect($r['id'])->toBe('NEWTOK');
    Http::assertSent(fn ($req) => $req->method() === 'POST' && $req['card_id'] === 'CARD-9');
});
