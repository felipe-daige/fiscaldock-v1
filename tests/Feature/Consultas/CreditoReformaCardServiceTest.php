<?php

use App\Models\Cliente;
use App\Models\Participante;
use App\Services\Reforma\CreditoReformaCardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['reforma.aliquota_referencia' => 0.285]);
    $this->svc = app(CreditoReformaCardService::class);
});

it('lente fornecedor: usa total_comprado × alíquota pleno e o regime do participante', function () {
    $user = User::factory()->create();
    // MEI => não gera crédito => tudo em risco
    $part = Participante::create(['user_id' => $user->id, 'documento' => '11111111000111',
        'razao_social' => 'F', 'origem_tipo' => 'MANUAL', 'regime_tributario' => 'MEI']);

    $resumo = ['qtd_entrada' => 3, 'qtd_saida' => 0, 'total_comprado' => 100000.0, 'relacionamentos' => []];
    $r = $this->svc->montar($user->id, $part, $resumo);

    expect($r['fornecedor']['credito_potencial'])->toBe(28500.0); // 100000 * 0.285
    expect($r['fornecedor']['credito_em_risco'])->toBe(28500.0);  // fator 0
    expect($r['fornecedor']['flag'])->toBe('vermelho');
    expect($r)->not->toHaveKey('cliente_b2b');
});

it('lente cliente B2B: crédito que MINHA empresa transfere ao comprador (regime da minha empresa)', function () {
    $user = User::factory()->create();
    $minha = Cliente::create(['user_id' => $user->id, 'documento' => '00000000000100',
        'razao_social' => 'MINHA', 'is_empresa_propria' => true, 'crt' => 3]); // Regime Normal => integral
    $part = Participante::create(['user_id' => $user->id, 'documento' => '22222222000122',
        'razao_social' => 'COMPRADOR', 'origem_tipo' => 'MANUAL']);

    $resumo = ['qtd_entrada' => 0, 'qtd_saida' => 2, 'total_comprado' => 0.0, 'total_vendido' => 50000.0,
        'relacionamentos' => [['empresa_id' => $minha->id, 'valor_saida' => 50000.0]]];
    $r = $this->svc->montar($user->id, $part, $resumo);

    expect($r['cliente_b2b']['credito_potencial'])->toBe(14250.0);    // 50000 * 0.285
    expect($r['cliente_b2b']['credito_transferido'])->toBe(14250.0);  // fator 1 (Regime Normal)
    expect($r['cliente_b2b']['flag'])->toBe('verde');
    expect($r)->not->toHaveKey('fornecedor');
});

it('sem movimentação retorna null', function () {
    $user = User::factory()->create();
    $part = Participante::create(['user_id' => $user->id, 'documento' => '33333333000133',
        'razao_social' => 'Z', 'origem_tipo' => 'MANUAL']);
    $resumo = ['qtd_entrada' => 0, 'qtd_saida' => 0, 'total_comprado' => 0.0, 'relacionamentos' => []];

    expect($this->svc->montar($user->id, $part, $resumo))->toBeNull();
});
