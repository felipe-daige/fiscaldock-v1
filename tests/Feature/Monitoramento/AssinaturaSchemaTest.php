<?php

use App\Models\Cliente;
use App\Models\MonitoramentoAssinatura;
use App\Models\MonitoramentoPlano;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\assertDatabaseHas;

uses(RefreshDatabase::class);

it('cria assinatura com alvo cliente (participante_id nulo)', function () {
    $user = User::factory()->create();
    $cliente = Cliente::create([
        'user_id' => $user->id, 'documento' => '11444777000161', 'tipo_pessoa' => 'PJ',
        'razao_social' => 'Cliente Teste', 'ativo' => true, 'is_empresa_propria' => false,
    ]);
    $plano = MonitoramentoPlano::porCodigo('licitacao');

    $a = MonitoramentoAssinatura::create([
        'user_id' => $user->id,
        'cliente_id' => $cliente->id,
        'participante_id' => null,
        'plano_id' => $plano->id,
        'status' => 'ativo',
        'frequencia_dias' => 30,
        'proxima_execucao_em' => now(),
    ]);

    expect($a->cliente_id)->toBe($cliente->id)
        ->and($a->participante_id)->toBeNull();
    assertDatabaseHas('monitoramento_assinaturas', ['id' => $a->id, 'cliente_id' => $cliente->id]);
});
