<?php

use App\Support\SystemCriticalError;

it('gera erro publico com whatsapp sem expor detalhes internos', function () {
    $error = app(SystemCriticalError::class)->forAsyncFailure(
        'CND Federal (undefined / undefined): Parâmetros obrigatórios não foram enviados.',
        'INFOSIMPLES_PARAMETROS_VAZIOS',
        [
            'context' => 'consulta-lote',
            'url' => '/app/consulta/lote/9',
            'reference' => 'Lote #9',
        ]
    );

    expect($error['title'])->toBe('Falha no processamento');
    expect($error['message'])->toContain('instabilidade interna');
    expect($error['message'])->not->toContain('INFOSIMPLES');
    expect($error['message'])->not->toContain('Parâmetros obrigatórios');
    expect($error['action_url'])->toContain('wa.me/5567999844366');
    expect(urldecode($error['action_url']))->toContain('Lote #9');
    expect(urldecode($error['action_url']))->not->toContain('INFOSIMPLES');
});
