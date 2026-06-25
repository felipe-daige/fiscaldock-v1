<?php

it('renderiza o bloco Crédito tributário com headline, R$ em risco e metodologia', function () {
    $fiscal = [
        'papel' => 'fornecedor', 'total_comprado' => 100000.0, 'total_vendido' => 0.0,
        'qtd_entrada' => 3, 'qtd_saida' => 0, 'primeira_nota' => null, 'ultima_nota' => null,
        'top_produtos' => [], 'relacionamentos' => [], 'top_cfops' => [],
        'credito_reforma' => [
            'fornecedor' => [
                'volume' => 100000.0, 'aliquota' => 0.285, 'credito_potencial' => 28500.0,
                'credito_em_risco' => 19950.0, 'fator' => 0.3, 'score' => 30,
                'gera_credito' => 'Gera crédito parcial', 'flag' => 'amarelo',
            ],
            'legado' => ['destacado' => 18500.0],
        ],
    ];

    $html = view('autenticado.consulta.partials.relacionamento-fiscal', compact('fiscal'))->render();

    expect($html)->toContain('Crédito tributário');
    expect($html)->toContain('19.950,00');           // em risco IBS/CBS
    expect($html)->toContain('Gera crédito parcial');
    expect($html)->toContain('18.500,00');            // legado destacado
    expect($html)->toContain('Como calculamos');
    expect($html)->toContain('Art. 47');              // base legal
});

it('regime não identificado (cinza) esconde o R$ em risco e mostra o gancho de consulta', function () {
    $fiscal = [
        'papel' => 'fornecedor', 'total_comprado' => 50000.0, 'total_vendido' => 0.0,
        'qtd_entrada' => 1, 'qtd_saida' => 0, 'top_produtos' => [], 'relacionamentos' => [], 'top_cfops' => [],
        'credito_reforma' => [
            'fornecedor' => [
                'volume' => 50000.0, 'aliquota' => 0.285, 'credito_potencial' => 14250.0,
                'credito_em_risco' => null, 'fator' => null, 'score' => null,
                'gera_credito' => 'Regime não identificado', 'flag' => 'cinza',
            ],
        ],
    ];

    $html = view('autenticado.consulta.partials.relacionamento-fiscal', compact('fiscal'))->render();

    expect($html)->toContain('Regime do fornecedor não consultado');
    expect($html)->toContain('Como calculamos');
    expect($html)->toContain('Art. 47');
    expect($html)->not->toContain('</strong> em risco');
});
