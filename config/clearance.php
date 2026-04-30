<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Clearance — parâmetros gerais e da comparação declarado vs SEFAZ
    |--------------------------------------------------------------------------
    | Centraliza tolerâncias e limiares usados no `ComparacaoNotaService`.
    | Alíquotas fiscais e parâmetros de exposição (multa, Selic, decadência)
    | serão adicionados em fase posterior pela spec 2026-04-24.
    */

    'comparacao' => [
        'tolerancia_monetaria' => 0.01,
        'tolerancia_quantidade_decimais' => 4,
        'limiar_critico_valor_pct' => 10.0,
        'limiar_critico_valor_abs' => 100.0,
    ],

    /*
    | Busca avulsa de DF-e por chave (NF-e/CT-e via InfoSimples).
    | Mantida desabilitada enquanto o pipeline n8n + persistência em xml_notas
    | não estiver operacional ponta a ponta. Habilita por env CLEARANCE_BUSCA_AVULSA_HABILITADA=true.
    */
    'busca_avulsa' => [
        'habilitada' => env('CLEARANCE_BUSCA_AVULSA_HABILITADA', false),
    ],
];
