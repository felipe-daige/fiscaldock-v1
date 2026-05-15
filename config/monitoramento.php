<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Planos disponíveis para Compliance Automático (modal "Nova assinatura")
    |--------------------------------------------------------------------------
    |
    | Lista de códigos de MonitoramentoPlano que aparecem como opção ao criar
    | uma assinatura recorrente. Planos fora dessa lista não são oferecidos
    | nem aceitos pelo backend, mesmo que estejam com is_active=true no banco.
    |
    | Compliance e Due Diligence ficam de fora enquanto as integrações
    | InfoSimples necessárias (CND Estadual/Municipal, sanções, CNJ) não
    | estiverem operacionais ponta a ponta.
    |
    */
    'planos_assinatura' => [
        'gratuito',
        'validacao',
        'licitacao',
    ],
];
