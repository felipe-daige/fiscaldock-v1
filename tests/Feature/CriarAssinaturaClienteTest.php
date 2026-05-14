<?php

use App\Models\Cliente;
use App\Models\MonitoramentoAssinatura;
use App\Models\MonitoramentoPlano;
use App\Models\User;
use Database\Seeders\MonitoramentoPlanoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(MonitoramentoPlanoSeeder::class);
    $this->plano = MonitoramentoPlano::query()->where('is_active', true)->first();
});

it('cria assinatura para um cliente do próprio usuário', function () {
    $user = User::factory()->create();
    $cliente = Cliente::create([
        'user_id' => $user->id, 'documento' => '44555666000199', 'razao_social' => 'Cliente Y',
    ]);

    $this->actingAs($user)
        ->withHeader('X-Requested-With', 'XMLHttpRequest')
        ->postJson('/app/monitoramento/assinatura', [
            'cliente_id' => $cliente->id,
            'plano_id' => $this->plano->id,
            'frequencia' => 'mensal',
        ])
        ->assertOk();

    $assinatura = MonitoramentoAssinatura::where('cliente_id', $cliente->id)->first();
    expect($assinatura)->not->toBeNull();
    expect($assinatura->participante_id)->toBeNull();
    expect($assinatura->status)->toBe('ativo');
});

it('rejeita cliente de outro usuário', function () {
    $dono = User::factory()->create();
    $intruso = User::factory()->create();
    $cliente = Cliente::create([
        'user_id' => $dono->id, 'documento' => '44555666000199', 'razao_social' => 'Cliente Y',
    ]);

    $this->actingAs($intruso)
        ->withHeader('X-Requested-With', 'XMLHttpRequest')
        ->postJson('/app/monitoramento/assinatura', [
            'cliente_id' => $cliente->id,
            'plano_id' => $this->plano->id,
            'frequencia' => 'mensal',
        ]);

    expect(MonitoramentoAssinatura::count())->toBe(0);
});
