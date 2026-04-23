<?php

use App\Models\ConsultaResultado;
use App\Models\Participante;

it('usa o regime textual do participante antes do fallback generico do crt', function () {
    $resultado = new ConsultaResultado([
        'resultado_dados' => [],
    ]);

    $resultado->setRelation('participante', new Participante([
        'regime_tributario' => 'Lucro Presumido',
        'crt' => '3',
    ]));

    expect($resultado->getRegimeTributarioLabel())->toBe('Lucro Presumido');
});

it('retorna regime normal quando apenas o crt 3 estiver disponivel', function () {
    $resultado = new ConsultaResultado([
        'resultado_dados' => [
            'crt' => 3,
        ],
    ]);

    expect($resultado->getRegimeTributarioLabel())->toBe('Regime Normal');
});

it('retorna a mensagem raiz do resultado para exibicao', function () {
    $resultado = new ConsultaResultado([
        'status' => ConsultaResultado::STATUS_SUCESSO,
        'resultado_dados' => [
            'mensagem' => 'Participante localizado a partir do EFD.',
        ],
    ]);

    expect($resultado->getMensagemExibivel())->toBe('Participante localizado a partir do EFD.');
});

it('faz fallback para mensagem aninhada em blocos conhecidos', function () {
    $resultado = new ConsultaResultado([
        'status' => ConsultaResultado::STATUS_SUCESSO,
        'resultado_dados' => [
            'cnd_federal' => [
                'status' => 'INDETERMINADO',
                'mensagem' => 'Receita sem dados suficientes para emitir a certidao.',
            ],
        ],
    ]);

    expect($resultado->getMensagemExibivel())->toBe('Receita sem dados suficientes para emitir a certidao.');
});

it('prioriza a mensagem publica de erro em resultados com falha', function () {
    $resultado = new ConsultaResultado([
        'status' => ConsultaResultado::STATUS_ERRO,
        'error_message' => 'Parametros obrigatorios nao foram enviados.',
        'resultado_dados' => [
            'mensagem' => 'Mensagem de sucesso que nao deve aparecer.',
        ],
    ]);

    expect($resultado->getMensagemExibivel())->toBe('Parametros obrigatorios nao foram enviados.');
});
