<?php

it('renderiza badge com cor inline', function () {
    $html = view('reports.partials._badge', ['label' => 'REGULAR', 'hex' => '#047857'])->render();
    expect($html)->toContain('background-color:#047857')
        ->and($html)->toContain('REGULAR');
});

it('renderiza faixa de KPIs', function () {
    $html = view('reports.partials._kpi-strip', ['itens' => [
        ['label' => 'Total', 'valor' => 12],
        ['label' => 'Score Médio', 'valor' => '78,5'],
    ]])->render();
    expect($html)->toContain('Total')->and($html)->toContain('12')
        ->and($html)->toContain('Score Médio')->and($html)->toContain('78,5');
});
