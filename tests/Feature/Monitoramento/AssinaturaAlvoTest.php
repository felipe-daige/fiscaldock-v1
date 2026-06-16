<?php

use App\Models\Cliente;
use App\Models\MonitoramentoAssinatura;
use App\Models\MonitoramentoPlano;
use App\Models\Participante;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('expõe o alvo genérico (participante ou cliente)', function () {
    $user = User::factory()->create();
    $plano = MonitoramentoPlano::porCodigo('licitacao');

    $p = Participante::create(['user_id' => $user->id, 'documento' => '11222333000181', 'razao_social' => 'ACME']);
    $aP = MonitoramentoAssinatura::create([
        'user_id' => $user->id, 'participante_id' => $p->id, 'plano_id' => $plano->id,
        'status' => 'ativo', 'frequencia_dias' => 30, 'proxima_execucao_em' => now(),
    ]);
    expect($aP->alvoTipo())->toBe('participante')
        ->and($aP->alvo()->is($p))->toBeTrue();

    $c = Cliente::create([
        'user_id' => $user->id, 'documento' => '11444777000161', 'tipo_pessoa' => 'PJ',
        'razao_social' => 'Cli', 'ativo' => true, 'is_empresa_propria' => false,
    ]);
    $aC = MonitoramentoAssinatura::create([
        'user_id' => $user->id, 'cliente_id' => $c->id, 'plano_id' => $plano->id,
        'status' => 'ativo', 'frequencia_dias' => 30, 'proxima_execucao_em' => now(),
    ]);
    expect($aC->alvoTipo())->toBe('cliente')
        ->and($aC->alvo()->is($c))->toBeTrue();
});
