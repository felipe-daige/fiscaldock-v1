<?php

use App\Models\Cliente;
use App\Models\EfdImportacao;
use App\Models\EfdNota;
use App\Models\Participante;
use App\Models\User;
use App\Services\Reforma\CreditoRiscoReformaService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Fixa a alíquota destes testes de fórmula (independe do default de prod).
    config(['reforma.aliquota_referencia' => 0.265]);
});

function cenarioCredito(): array
{
    $user = User::factory()->create();
    $cliente = Cliente::create([
        'user_id' => $user->id, 'documento' => '11111111000111', 'razao_social' => 'EMPRESA X',
    ]);
    $imp = EfdImportacao::create(['user_id' => $user->id, 'tipo_efd' => 'icms']);

    $mei = Participante::create([
        'user_id' => $user->id, 'documento' => '22222222000122', 'razao_social' => 'FORN MEI', 'regime_tributario' => 'MEI',
    ]);
    $normal = Participante::create([
        'user_id' => $user->id, 'documento' => '33333333000133', 'razao_social' => 'FORN NORMAL', 'crt' => 3,
    ]);

    foreach ([[$mei, 100000], [$normal, 200000]] as [$p, $v]) {
        EfdNota::create([
            'user_id' => $user->id, 'cliente_id' => $cliente->id, 'participante_id' => $p->id,
            'importacao_id' => $imp->id, 'modelo' => '55', 'numero' => '1',
            'tipo_operacao' => 'entrada', 'valor_total' => $v, 'cancelada' => false,
        ]);
    }

    return compact('cliente', 'mei', 'normal');
}

it('calcula crédito em risco por fornecedor (MEI vs Regime Normal)', function () {
    ['cliente' => $cliente, 'mei' => $mei] = cenarioCredito();

    $linhas = app(CreditoRiscoReformaService::class)->exposicaoPorFornecedor($cliente->id);

    expect($linhas)->toHaveCount(2);
    // ordenado desc por risco -> MEI primeiro (não gera crédito)
    expect($linhas[0]['participante_id'])->toBe($mei->id);
    expect($linhas[0]['credito_potencial'])->toBe(26500.0);  // 100000 * 0.265
    expect($linhas[0]['credito_em_risco'])->toBe(26500.0);   // * (1 - 0.0)
    expect($linhas[0]['flag'])->toBe('vermelho');
    // Regime Normal -> gera crédito cheio -> risco 0
    expect($linhas[1]['credito_em_risco'])->toBe(0.0);       // 200000 * 0.265 * (1 - 1.0)
    expect($linhas[1]['flag'])->toBe('verde');
});

it('resumo soma o crédito em risco da carteira', function () {
    ['cliente' => $cliente] = cenarioCredito();

    $resumo = app(CreditoRiscoReformaService::class)->resumo($cliente->id);

    expect($resumo['total_em_risco'])->toBe(26500.0);
    expect($resumo['total_potencial'])->toBe(79500.0); // 26500 + 53000
    expect($resumo['fornecedores'])->toBe(2);
});

it('ignora notas de saída e canceladas', function () {
    $user = User::factory()->create();
    $cliente = Cliente::create(['user_id' => $user->id, 'documento' => '44444444000144', 'razao_social' => 'Y']);
    $imp = EfdImportacao::create(['user_id' => $user->id, 'tipo_efd' => 'icms']);
    $mei = Participante::create([
        'user_id' => $user->id, 'documento' => '55555555000155', 'razao_social' => 'MEI', 'regime_tributario' => 'MEI',
    ]);

    // saída (não conta) + entrada cancelada (não conta)
    EfdNota::create(['user_id' => $user->id, 'cliente_id' => $cliente->id, 'participante_id' => $mei->id,
        'importacao_id' => $imp->id, 'modelo' => '55', 'numero' => '1', 'tipo_operacao' => 'saida', 'valor_total' => 999, 'cancelada' => false]);
    EfdNota::create(['user_id' => $user->id, 'cliente_id' => $cliente->id, 'participante_id' => $mei->id,
        'importacao_id' => $imp->id, 'modelo' => '55', 'numero' => '2', 'tipo_operacao' => 'entrada', 'valor_total' => 999, 'cancelada' => true]);

    $resumo = app(CreditoRiscoReformaService::class)->resumo($cliente->id);

    expect($resumo['fornecedores'])->toBe(0);
    expect($resumo['total_em_risco'])->toBe(0.0);
});
