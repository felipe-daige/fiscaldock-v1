<?php

return [
    // Busca avulsa por chave (NF-e/CT-e) — segue DESLIGADA por padrão (comportamento atual).
    'busca_avulsa' => [
        'habilitada' => (bool) env('CLEARANCE_BUSCA_AVULSA_HABILITADA', false),
    ],

    // Clearance Full (tributos + comparação item-a-item) — exige certificado digital A1/A3.
    // Enquanto false, a UI mostra placeholders "em breve" e o tier 'full' é coagido a 'basico'.
    // Spec: docs/superpowers/specs/2026-06-08-clearance-full-certificado-design.md
    'full' => [
        'habilitado' => (bool) env('CLEARANCE_FULL_HABILITADO', false),
    ],
];
