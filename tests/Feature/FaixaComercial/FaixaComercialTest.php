<?php

use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

it('a rota /app/faixa-comercial renderiza e a antiga /app/plano não existe', function () {
    $user = User::factory()->create();
    actingAs($user);

    // /app/faixa-comercial agora é a página "Saldo e extrato" (faixas de volume foram eliminadas);
    // a antiga /app/plano não existe.
    get('/app/faixa-comercial')->assertOk();
    get('/app/plano')->assertNotFound();
});

it('a página de saldo mostra saldo e extrato (sem faixa)', function () {
    $user = User::factory()->create();
    actingAs($user);

    $html = get('/app/faixa-comercial')->assertOk()->getContent();

    expect($html)->toContain('Saldo atual');
    expect($html)->not->toContain('Custo por consulta em cada faixa');
    expect($html)->not->toContain('Como funciona a faixa comercial');
});
