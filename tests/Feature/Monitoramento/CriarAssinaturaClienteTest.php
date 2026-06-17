<?php

use App\Models\Cliente;
use App\Models\MonitoramentoAssinatura;
use App\Models\MonitoramentoPlano;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

function clienteDe(User $user, string $doc = '11444777000161', string $nome = 'Cli'): Cliente
{
    return Cliente::create([
        'user_id' => $user->id, 'documento' => $doc, 'tipo_pessoa' => 'PJ',
        'razao_social' => $nome, 'ativo' => true, 'is_empresa_propria' => false,
    ]);
}

it('cria assinatura de cliente com ownership check', function () {
    // trial libera os gates de tier (CNPJ/frequência/profundidade da Fase 5/5.1) — este teste é de ownership
    $user = User::factory()->trialAtivo()->create();
    $cliente = clienteDe($user);
    $plano = MonitoramentoPlano::porCodigo('licitacao');

    actingAs($user)->post(route('app.monitoramento.assinatura.criar'), [
        'cliente_id' => $cliente->id, 'plano_id' => $plano->id,
    ])->assertSuccessful();

    expect(
        MonitoramentoAssinatura::where('cliente_id', $cliente->id)->where('user_id', $user->id)->exists()
    )->toBeTrue();
});

it('rejeita cliente de outro usuário com 403 e não cria nada', function () {
    $user = User::factory()->create();
    $outroDono = User::factory()->create();
    $clienteAlheio = clienteDe($outroDono, '11444777000161', 'Alheio');
    $plano = MonitoramentoPlano::porCodigo('licitacao');

    actingAs($user)->post(route('app.monitoramento.assinatura.criar'), [
        'cliente_id' => $clienteAlheio->id, 'plano_id' => $plano->id,
    ])->assertStatus(403);

    expect(MonitoramentoAssinatura::where('cliente_id', $clienteAlheio->id)->exists())->toBeFalse();
});
