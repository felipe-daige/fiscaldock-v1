<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Parâmetros da Reforma Tributária (IBS/CBS) — Score de Crédito
    |--------------------------------------------------------------------------
    | Ver docs/score-fiscal/credito-reforma.md. Todos parametrizáveis por env
    | para acompanhar a transição (2026 teste → 2033 fim ICMS/ISS).
    |
    | Alíquotas de referência são fixadas/revisadas anualmente por Resolução do
    | Senado com base em cálculo do TCU — os valores abaixo são ESTIMATIVAS
    | (Min. Fazenda 2024-2025; faixa oficial do IVA pleno: 26,5%–28%).
    */

    // Alíquota total IBS+CBS no estado PLENO (2033+). Estimativa ~28,5% (faixa oficial 26,5%–28%).
    'aliquota_referencia' => (float) env('REFORMA_ALIQUOTA_REF', 0.285),

    // Alíquota total IBS+CBS efetiva por ANO da transição (estimativas):
    //   2026: teste (CBS 0,9% + IBS 0,1%)
    //   2027-2028: CBS plena (~9,3%) + IBS ~0,1%
    //   2029-2032: CBS + IBS rampando 10%/20%/30%/40% do IBS pleno (~18%)
    //   2033+: estado pleno (= aliquota_referencia)
    // Anos ausentes < 2026 = 0 (sem IBS/CBS); > 2032 = pleno.
    'aliquotas_por_fase' => [
        2026 => (float) env('REFORMA_ALIQUOTA_2026', 0.010),
        2027 => (float) env('REFORMA_ALIQUOTA_2027', 0.093),
        2028 => (float) env('REFORMA_ALIQUOTA_2028', 0.093),
        2029 => (float) env('REFORMA_ALIQUOTA_2029', 0.111),
        2030 => (float) env('REFORMA_ALIQUOTA_2030', 0.129),
        2031 => (float) env('REFORMA_ALIQUOTA_2031', 0.147),
        2032 => (float) env('REFORMA_ALIQUOTA_2032', 0.165),
    ],

    // Fração de crédito que o Simples Nacional SEM opção pelo regime regular transfere ao
    // comprador. Estimativa parametrizável (art. 41 §3, LC 214/2025). Revisar com a regulamentação.
    'fator_simples_sem_opcao' => (float) env('REFORMA_FATOR_SIMPLES', 0.30),
];
