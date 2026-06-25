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

it('renderiza detalhamento do score (PDF) com categorias e barra', function () {
    $det = [
        'cadastral' => ['label' => 'Situação Cadastral', 'peso_pct' => 15, 'score' => 0, 'avaliado' => true, 'hex' => '#047857'],
        'cnd_federal' => ['label' => 'CND Federal', 'peso_pct' => 20, 'score' => 70, 'avaliado' => true, 'hex' => '#ea580c'],
        'fgts' => ['label' => 'FGTS/CRF', 'peso_pct' => 10, 'score' => null, 'avaliado' => false, 'hex' => '#9ca3af'],
    ];
    $html = view('reports.partials._score-detalhamento', ['detalhamento' => $det])->render();

    expect($html)->toContain('Situação Cadastral')
        ->and($html)->toContain('CND Federal')
        ->and($html)->toContain('15%')
        ->and($html)->toContain('background-color:#ea580c')
        ->and($html)->toContain('Não avaliado'); // categoria fgts null
});

it('detalhamento PDF vazio (tudo não avaliado) mostra aviso', function () {
    $det = [
        'cadastral' => ['label' => 'Situação Cadastral', 'peso_pct' => 15, 'score' => null, 'avaliado' => false, 'hex' => '#9ca3af'],
    ];
    $html = view('reports.partials._score-detalhamento', ['detalhamento' => $det])->render();
    expect($html)->toContain('Score não avaliado');
});
