<?php

return [
    'providers' => [
        'minhareceita' => [
            'base_url' => env('MINHARECEITA_BASE_URL', 'https://minhareceita.org'),
            'timeout' => (int) env('MINHARECEITA_TIMEOUT', 20),
            'tries' => (int) env('MINHARECEITA_TRIES', 2),
        ],
        'infosimples' => [
            'base_url' => env('INFOSIMPLES_BASE_URL', 'https://api.infosimples.com/api/v2/consultas'),
            'token' => env('INFOSIMPLES_TOKEN'),
            'timeout' => (int) env('INFOSIMPLES_TIMEOUT', 120),
            'tries' => (int) env('INFOSIMPLES_TRIES', 3),
            'rate_limit_por_segundo' => (float) env('INFOSIMPLES_RATE_LIMIT', 1),
        ],
    ],

    // Gate de cutover: enquanto false, fontes InfoSimples NÃO roteiam pro Laravel
    // (planos pagos seguem no n8n). Ligar só após pagar/validar o InfoSimples e
    // confirmar o estorno preciso por fonte. ENV: CONSULTAS_INFOSIMPLES_ATIVO.
    'infosimples_ativo' => (bool) env('CONSULTAS_INFOSIMPLES_ATIVO', false),

    // Grupos de código InfoSimples → status canônico (fonte: docs/infosimples/endpoints-catalog.md)
    'codigos' => [
        'sucesso' => [200, 201],
        'nao_encontrado' => [612],
        // 611 = a fonte oficial não conseguiu emitir pela internet (dados insuficientes).
        // NÃO é irregularidade — vira INDETERMINADO, preservando a mensagem. Não estorna.
        'indeterminado' => [611],
        'erro_participante' => [608, 619, 620],
        'retry' => [600, 605, 609, 610, 613, 614, 615, 618],
        'fatal' => [601, 602, 603, 604, 606, 607, 617, 621, 622],
    ],

    // Custo em créditos por fonte paga (usado no estorno preciso). 1 crédito = R$ 0,20.
    'fontes' => [
        'cnd_federal' => (int) env('CONSULTA_CREDITOS_CND_FEDERAL', 2),
        'cndt' => (int) env('CONSULTA_CREDITOS_CNDT', 2),
        'crf_fgts' => (int) env('CONSULTA_CREDITOS_CRF_FGTS', 2),
    ],
];
