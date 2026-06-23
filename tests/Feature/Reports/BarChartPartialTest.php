<?php

it('renderiza barras com largura pct e cor inline', function () {
    $html = view('reports.partials._bar-chart', ['itens' => [
        ['label' => 'Entrada', 'valor' => 'R$ 100', 'pct' => 100, 'hex' => '#047857'],
        ['label' => 'Saída', 'valor' => 'R$ 50', 'pct' => 50, 'hex' => '#dc2626'],
    ]])->render();

    expect($html)->toContain('width:100%')
        ->and($html)->toContain('width:50%')
        ->and($html)->toContain('background-color:#047857')
        ->and($html)->toContain('Entrada')
        ->and($html)->toContain('R$ 100');
});

it('barra vazia nao quebra com lista vazia', function () {
    $html = view('reports.partials._bar-chart', ['itens' => []])->render();
    expect($html)->toBeString();
});
