<?php

it('renderiza detalhamento web com headline, categorias, peso e legenda', function () {
    $det = [
        'cadastral' => ['label' => 'Situação Cadastral', 'peso_pct' => 15, 'score' => 0, 'avaliado' => true, 'hex' => '#047857'],
        'cnd_federal' => ['label' => 'CND Federal', 'peso_pct' => 20, 'score' => 70, 'avaliado' => true, 'hex' => '#ea580c'],
        'fgts' => ['label' => 'FGTS/CRF', 'peso_pct' => 10, 'score' => null, 'avaliado' => false, 'hex' => '#9ca3af'],
    ];
    $html = view('autenticado.partials._score-detalhamento', [
        'detalhamento' => $det, 'scoreTotal' => 14, 'classificacao' => 'baixo', 'comHeadline' => true,
    ])->render();

    expect($html)->toContain('14')                       // headline
        ->and($html)->toContain('CND Federal')
        ->and($html)->toContain('Peso: 20%')
        ->and($html)->toContain('background-color: #ea580c')
        ->and($html)->toContain('Não avaliado')          // fgts null
        ->and($html)->toContain('Categorias em breve')
        ->and($html)->toContain('Legenda');
});

it('sem headline (risk/show) não exibe o número grande do total', function () {
    $det = ['cadastral' => ['label' => 'Situação Cadastral', 'peso_pct' => 15, 'score' => 0, 'avaliado' => true, 'hex' => '#047857']];
    $html = view('autenticado.partials._score-detalhamento', [
        'detalhamento' => $det, 'scoreTotal' => 0, 'classificacao' => 'baixo', 'comHeadline' => false,
    ])->render();
    expect($html)->not->toContain('score-headline-total');
});

it('empty-state quando não há nada avaliado e total null', function () {
    $html = view('autenticado.partials._score-detalhamento', [
        'detalhamento' => [], 'scoreTotal' => null, 'classificacao' => 'nao_avaliado', 'comHeadline' => true,
    ])->render();
    expect($html)->toContain('Score não calculado');
});
