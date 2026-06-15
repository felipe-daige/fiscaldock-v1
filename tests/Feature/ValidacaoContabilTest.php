<?php

use App\Models\XmlNota;
use App\Models\Participante;
use App\Models\User;
use App\Services\ValidacaoContabilService;

beforeEach(function () {
    $this->user = User::factory()->create(['credits' => 100]);
    $this->actingAs($this->user);

    $this->participante = Participante::create([
        'user_id' => $this->user->id,
        'cnpj' => '12345678000199',
        'razao_social' => 'Empresa Teste Ltda',
        'uf' => 'SP',
        'crt' => 1, // Simples Nacional
        'situacao_cadastral' => 'ATIVA',
        'origem_tipo' => 'MANUAL',
    ]);

    $this->nota = XmlNota::create([
        'user_id' => $this->user->id,
        'chave_acesso' => str_repeat('1', 44),
        'tipo_documento' => 'NFE',
        'numero_documento' => 1234,
        'serie' => 1,
        'data_emissao' => now(),
        'natureza_operacao' => 'VENDA',
        'valor_total' => 1000.00,
        'tipo_nota' => 1, // Saida
        'finalidade' => 1, // Normal
        'emit_documento' => '12345678000199',
        'emit_razao_social' => 'Empresa Teste Ltda',
        'emit_uf' => 'SP',
        'emit_participante_id' => $this->participante->id,
        'dest_documento' => '98765432000188',
        'dest_razao_social' => 'Cliente Teste SA',
        'dest_uf' => 'SP',
        'icms_valor' => 180.00,
        'pis_valor' => 16.50,
        'cofins_valor' => 76.00,
        'ipi_valor' => 0,
        'tributos_total' => 272.50,
        'payload' => [
            'emit' => ['CRT' => 1, 'CNPJ' => '12345678000199'],
            'det' => [
                ['prod' => ['CFOP' => '5102', 'NCM' => '84713012']],
            ],
            'total' => ['ICMSTot' => ['vNF' => 1000.00, 'vTotTrib' => 272.50]],
        ],
    ]);
});

test('service valida nota corretamente', function () {
    $service = new ValidacaoContabilService();
    $resultado = $service->validarNota($this->nota);

    expect($resultado)
        ->toHaveKey('score_total')
        ->toHaveKey('classificacao')
        ->toHaveKey('scores')
        ->toHaveKey('alertas')
        ->toHaveKey('validado_em');

    expect($resultado['scores'])
        ->toHaveKey('cadastral')
        ->toHaveKey('tributacao')
        ->toHaveKey('cfop_cst')
        ->toHaveKey('integridade')
        ->toHaveKey('ncm')
        ->toHaveKey('operacoes');
});

test('classificacao conforme para nota sem problemas', function () {
    // Criar nota sem problemas
    $notaOk = XmlNota::create([
        'user_id' => $this->user->id,
        'chave_acesso' => str_repeat('2', 44),
        'tipo_documento' => 'NFE',
        'numero_documento' => 5678,
        'serie' => 1,
        'data_emissao' => now(),
        'natureza_operacao' => 'VENDA',
        'valor_total' => 1000.00,
        'tipo_nota' => 1,
        'finalidade' => 1,
        'emit_documento' => '12345678000199',
        'emit_uf' => 'SP',
        'emit_participante_id' => $this->participante->id,
        'dest_documento' => '98765432000188',
        'dest_uf' => 'SP',
        'icms_valor' => 0,
        'pis_valor' => 0,
        'cofins_valor' => 0,
        'ipi_valor' => 0,
        'tributos_total' => 0,
        'payload' => [
            'emit' => ['CRT' => 1],
            'det' => [
                ['prod' => ['CFOP' => '5102', 'NCM' => '84713012']],
            ],
            'total' => ['ICMSTot' => ['vNF' => 1000.00, 'vTotTrib' => 0]],
        ],
    ]);

    $service = new ValidacaoContabilService();
    $resultado = $service->validarNota($notaOk, incluirOperacoes: false);

    expect($resultado['classificacao'])->toBe('conforme');
    expect($resultado['score_total'])->toBeLessThanOrEqual(10);
});

test('detecta ncm generico', function () {
    $notaComNcmGenerico = XmlNota::create([
        'user_id' => $this->user->id,
        'chave_acesso' => str_repeat('3', 44),
        'tipo_documento' => 'NFE',
        'numero_documento' => 9999,
        'serie' => 1,
        'data_emissao' => now(),
        'natureza_operacao' => 'VENDA',
        'valor_total' => 1000.00,
        'tipo_nota' => 1,
        'finalidade' => 1,
        'emit_documento' => '12345678000199',
        'emit_uf' => 'SP',
        'dest_documento' => '98765432000188',
        'dest_uf' => 'SP',
        'icms_valor' => 0,
        'tributos_total' => 0,
        'payload' => [
            'emit' => ['CRT' => 1],
            'det' => [
                ['prod' => ['CFOP' => '5102', 'NCM' => '99999999']], // NCM generico
            ],
            'total' => ['ICMSTot' => ['vNF' => 1000.00]],
        ],
    ]);

    $service = new ValidacaoContabilService();
    $resultado = $service->validarNota($notaComNcmGenerico, incluirOperacoes: false);

    $alertasNcm = collect($resultado['alertas'])->where('categoria', 'ncm');
    expect($alertasNcm)->not->toBeEmpty();
    expect($alertasNcm->first()['codigo'])->toBe('NCM_GENERICO');
});

test('detecta cfop inconsistente com tipo nota', function () {
    // Nota de saida (tipo_nota=1) com CFOP de entrada (1xxx)
    $notaInconsistente = XmlNota::create([
        'user_id' => $this->user->id,
        'chave_acesso' => str_repeat('4', 44),
        'tipo_documento' => 'NFE',
        'numero_documento' => 8888,
        'serie' => 1,
        'data_emissao' => now(),
        'natureza_operacao' => 'VENDA',
        'valor_total' => 1000.00,
        'tipo_nota' => 1, // Saida
        'finalidade' => 1,
        'emit_documento' => '12345678000199',
        'emit_uf' => 'SP',
        'dest_documento' => '98765432000188',
        'dest_uf' => 'SP',
        'icms_valor' => 0,
        'tributos_total' => 0,
        'payload' => [
            'emit' => ['CRT' => 1],
            'det' => [
                ['prod' => ['CFOP' => '1102', 'NCM' => '84713012']], // CFOP de entrada em nota de saida
            ],
            'total' => ['ICMSTot' => ['vNF' => 1000.00]],
        ],
    ]);

    $service = new ValidacaoContabilService();
    $resultado = $service->validarNota($notaInconsistente, incluirOperacoes: false);

    $alertasCfop = collect($resultado['alertas'])->where('categoria', 'cfop_cst');
    expect($alertasCfop)->not->toBeEmpty();
    expect($alertasCfop->first()['codigo'])->toBe('CFOP_TIPO_INCONSISTENTE');
    expect($alertasCfop->first()['nivel'])->toBe('bloqueante');
});

test('detecta emitente baixado', function () {
    // Atualizar participante para situacao baixada
    $this->participante->update(['situacao_cadastral' => 'BAIXADA']);

    $service = new ValidacaoContabilService();
    $resultado = $service->validarNota($this->nota, incluirOperacoes: false);

    $alertasCadastrais = collect($resultado['alertas'])->where('categoria', 'cadastral');
    expect($alertasCadastrais)->not->toBeEmpty();
    expect($alertasCadastrais->first()['codigo'])->toBe('EMIT_BAIXADA');
    expect($alertasCadastrais->first()['nivel'])->toBe('bloqueante');
});

test('estatisticas retorna dados corretos', function () {
    $service = new ValidacaoContabilService();
    $estatisticas = $service->getEstatisticas($this->user->id);

    expect($estatisticas)
        ->toHaveKey('total_notas')
        ->toHaveKey('total_validadas')
        ->toHaveKey('conforme')
        ->toHaveKey('atencao')
        ->toHaveKey('irregular')
        ->toHaveKey('critico')
        ->toHaveKey('media_score')
        ->toHaveKey('percentual_validado');

    expect($estatisticas['total_notas'])->toBe(1);
    expect($estatisticas['total_validadas'])->toBe(0);
});
