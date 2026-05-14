<?php

use App\Models\Cliente;
use App\Models\MonitoramentoAssinatura;
use App\Models\MonitoramentoPlano;
use App\Models\Participante;
use App\Models\User;
use Database\Seeders\MonitoramentoPlanoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(MonitoramentoPlanoSeeder::class);
    $this->plano = MonitoramentoPlano::query()->first();
});

it('resolve alvo participante', function () {
    $user = User::factory()->create();
    $participante = Participante::create([
        'user_id' => $user->id, 'documento' => '11222333000181',
        'tipo_documento' => 'PJ', 'razao_social' => 'Fornecedor X',
    ]);
    $assinatura = MonitoramentoAssinatura::create([
        'user_id' => $user->id, 'participante_id' => $participante->id,
        'plano_id' => $this->plano->id, 'status' => 'ativo', 'frequencia_dias' => 30,
    ]);

    expect($assinatura->alvoTipo())->toBe('participante');
    expect($assinatura->alvo()->is($participante))->toBeTrue();
});

it('resolve alvo cliente', function () {
    $user = User::factory()->create();
    $cliente = Cliente::create([
        'user_id' => $user->id, 'documento' => '44555666000199', 'razao_social' => 'Cliente Y',
    ]);
    $assinatura = MonitoramentoAssinatura::create([
        'user_id' => $user->id, 'cliente_id' => $cliente->id,
        'plano_id' => $this->plano->id, 'status' => 'ativo', 'frequencia_dias' => 30,
    ]);

    expect($assinatura->alvoTipo())->toBe('cliente');
    expect($assinatura->alvo()->is($cliente))->toBeTrue();
});
