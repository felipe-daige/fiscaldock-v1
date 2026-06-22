<?php

use App\Models\Alerta;
use App\Services\GuiaAlertaService;

function guiaDe(array $attrs): array
{
    return app(GuiaAlertaService::class)->para(new Alerta($attrs));
}

it('nunca_consultado agregado (sem participante) aponta pra listagem de participantes', function () {
    $g = guiaDe(['tipo' => 'nunca_consultado', 'participante_id' => null]);

    expect($g['cta_url'])->toBe('/app/participantes');
    expect($g['cta_text'])->not->toBe('');
    expect($g['texto_acao'])->not->toBe('');
});

it('consulta_vencida com participante aponta pra ficha do participante', function () {
    $g = guiaDe(['tipo' => 'consulta_vencida', 'participante_id' => 42]);

    expect($g['cta_url'])->toBe('/app/participante/42');
});

it('gap_importacao aponta pra importação EFD', function () {
    $g = guiaDe(['tipo' => 'gap_importacao', 'participante_id' => null]);

    expect($g['cta_url'])->toBe('/app/importacao/efd');
});

it('situacao_irregular com participante linka pra ficha (antes não tinha ação)', function () {
    $g = guiaDe(['tipo' => 'situacao_irregular', 'participante_id' => 7]);

    expect($g['cta_url'])->toBe('/app/participante/7');
});

it('tipo desconhecido cai no default sem cta_url', function () {
    $g = guiaDe(['tipo' => 'algo_novo', 'participante_id' => null]);

    expect($g['cta_url'])->toBeNull();
    expect($g['cta_text'])->toBe('Marcar como Resolvido');
    expect($g)->toHaveKeys(['titulo_o_que_e', 'texto_o_que_e', 'titulo_acao', 'texto_acao', 'cta_text', 'cta_url']);
});
