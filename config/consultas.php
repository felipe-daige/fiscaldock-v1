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

    // GUARD DE TESTE: se NÃO vazio, só estes CNPJs (14 dígitos, CSV) realmente chamam o
    // InfoSimples — qualquer outro é bloqueado ANTES da chamada (sem cobrança). Use durante os
    // testes pagos p/ não queimar crédito por engano. Vazio = produção normal (todos passam).
    // ENV: CONSULTAS_INFOSIMPLES_TESTE_CNPJS.
    'infosimples_teste_cnpjs' => array_values(array_filter(array_map(
        fn ($c) => preg_replace('/[^0-9]/', '', (string) $c),
        explode(',', (string) env('CONSULTAS_INFOSIMPLES_TESTE_CNPJS', ''))
    ))),

    // Mapa fonte → etapa (grupo) do progresso. Várias fontes compartilham a mesma etapa
    // (ex: cnd_federal/cndt/crf_fgts = certidoes_federais), p/ o strip avançar por grupo e não
    // repetir um "loop" por fonte. As chaves de etapa vêm de PlanoCatalog (resolvedEtapas).
    'fonte_etapa' => [
        'cadastro' => 'cadastrais',
        'cnd_federal' => 'certidoes_federais',
        'cndt' => 'certidoes_federais',
        'crf_fgts' => 'certidoes_federais',
        'cnd_estadual' => 'certidoes_estaduais',
        'cnd_municipal' => 'certidoes_estaduais',
        'sintegra' => 'certidoes_estaduais',
        'cgu_cnc' => 'sancoes',
        'cnj_improbidade' => 'sancoes',
        'protestos' => 'sancoes',
    ],

    // Atributos de consultas_incluidas que NÃO são fontes — renderizados inline a partir dos
    // dados já obtidos (ex: parecer_fiscal é um parecer gerado dos dados cadastrais). Não
    // bloqueiam o roteamento pro Laravel nem geram chamada externa.
    'atributos_inline' => ['parecer_fiscal'],

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

    // Cobertura parcial do InfoSimples para CND Estadual (SEFAZ por UF). Só estas UFs são
    // consultadas; alvo em UF fora da lista é pulado (sem cobrar). Ajustar à cobertura real
    // do plano InfoSimples. ENV: CONSULTA_CND_ESTADUAL_UFS (CSV). CND Municipal terá tabela
    // cidade→slug própria (cobertura por município).
    'cnd_estadual' => [
        // Default = as 27 UFs listadas no catálogo do repo (docs/infosimples/endpoints-catalog.md,
        // `sefaz/{uf}/certidao-debitos`). NÃO foi verificado contra a cobertura real do plano
        // InfoSimples — ao ativar, conferir e TRIMAR via ENV CONSULTA_CND_ESTADUAL_UFS (CSV) para
        // a cobertura efetiva, senão UFs não atendidas serão chamadas (e cobradas) à toa.
        'ufs_cobertas' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env(
                'CONSULTA_CND_ESTADUAL_UFS',
                'AC,AL,AM,AP,BA,CE,DF,ES,GO,MA,MG,MS,MT,PA,PB,PE,PI,PR,RJ,RN,RO,RR,RS,SC,SE,SP,TO'
            ))
        ))),
    ],

    // Cobertura CND Municipal: mapa "{uf}:{cidade-normalizada}" → slug InfoSimples. O slug NÃO
    // é gerável do nome (ex: "Rio de Janeiro" → pref/rj/rio-janeiro/cnd, sem "de"), então é
    // explícito. Cidade do alvo vem do cadastro (minhareceita). Cidade fora do mapa → INDISPONÍVEL.
    // Inicial = cidades com slug /cnd explícito no catálogo do repo; COMPLETAR/validar via InfoSimples.
    'cnd_municipal' => [
        // Mapa completo extraído da doc oficial InfoSimples (docs/infosimples.md). Chave =
        // "{uf}:{cidade-normalizada}". Aliases adicionais cobrem nomes oficiais cujo slug difere
        // (ex: rio-de-janeiro→rio-janeiro), pra casar com o município que a minhareceita retorna.
        'slugs' => [
            'ap:macapa' => 'pref/ap/macapa/cnd',
            'ap:santana' => 'pref/ap/santana/cnd',
            'ba:camacari' => 'pref/ba/camacari/cnd',
            'ba:ilheus' => 'pref/ba/ilheus/cnd',
            'ba:juazeiro' => 'pref/ba/juazeiro/cnd',
            'ba:salvador' => 'pref/ba/salvador/cnd',
            'ce:caucaia' => 'pref/ce/caucaia/cnd',
            'ce:fortaleza' => 'pref/ce/fortaleza/cnd',
            'ce:jaguaretama' => 'pref/ce/jaguaretama/cnd',
            'go:anapolis' => 'pref/go/anapolis/cnd',
            'go:aparecida-goiania' => 'pref/go/aparecida-goiania/cnd',
            'go:aparecida-de-goiania' => 'pref/go/aparecida-goiania/cnd',
            'go:campos-verdes' => 'pref/go/campos-verdes/cnd',
            'go:catalao' => 'pref/go/catalao/cnd',
            'go:firminopolis' => 'pref/go/firminopolis/cnd',
            'go:goiania' => 'pref/go/goiania/cnd',
            'go:itumbiara' => 'pref/go/itumbiara/cnd',
            'go:jatai' => 'pref/go/jatai/cnd',
            'go:morrinhos' => 'pref/go/morrinhos/cnd',
            'go:rio-verde' => 'pref/go/rio-verde/cnd',
            'go:uruacu' => 'pref/go/uruacu/cnd',
            'ma:balsas' => 'pref/ma/balsas/cnd',
            'ma:sao-luis' => 'pref/ma/sao-luis/cnd',
            'mg:araujos' => 'pref/mg/araujos/cnd',
            'mg:belo-horizonte' => 'pref/mg/belo-horizonte/cnd',
            'mg:betim' => 'pref/mg/betim/cnd',
            'mg:contagem' => 'pref/mg/contagem/cnd',
            'mg:divinopolis' => 'pref/mg/divinopolis/cnd',
            'mg:dores-indaia' => 'pref/mg/dores-indaia/cnd',
            'mg:dores-do-indaia' => 'pref/mg/dores-indaia/cnd',
            'mg:itauna' => 'pref/mg/itauna/cnd',
            'mg:janauba' => 'pref/mg/janauba/cnd',
            'mg:juatuba' => 'pref/mg/juatuba/cnd',
            'mg:luz' => 'pref/mg/luz/cnd',
            'mg:martinho-campos' => 'pref/mg/martinho-campos/cnd',
            'mg:montes-claros' => 'pref/mg/montes-claros/cnd',
            'mg:nova-serrana' => 'pref/mg/nova-serrana/cnd',
            'mg:para-minas' => 'pref/mg/para-minas/cnd',
            'mg:para-de-minas' => 'pref/mg/para-minas/cnd',
            'mg:santa-vitoria' => 'pref/mg/santa-vitoria/cnd',
            'mg:uba' => 'pref/mg/uba/cnd',
            'mg:uberaba' => 'pref/mg/uberaba/cnd',
            'mg:uberlandia' => 'pref/mg/uberlandia/cnd',
            'ms:chapadao-do-sul' => 'pref/ms/chapadao-do-sul/cnd',
            'ms:mundo-novo' => 'pref/ms/mundo-novo/cnd',
            'ms:navirai' => 'pref/ms/navirai/cnd',
            'mt:cuiaba' => 'pref/mt/cuiaba/cnd',
            'mt:rondonopolis' => 'pref/mt/rondonopolis/cnd',
            'pa:itaituba' => 'pref/pa/itaituba/cnd',
            'pb:mataraca' => 'pref/pb/mataraca/cnd',
            'pe:recife' => 'pref/pe/recife/cnd',
            'pr:ampere' => 'pref/pr/ampere/cnd',
            'pr:curitiba' => 'pref/pr/curitiba/cnd',
            'pr:francisco-beltrao' => 'pref/pr/francisco-beltrao/cnd',
            'pr:maringa' => 'pref/pr/maringa/cnd',
            'rj:duque-de-caxias' => 'pref/rj/duque-de-caxias/cnd',
            'rj:rio-janeiro' => 'pref/rj/rio-janeiro/cnd',
            'rj:rio-de-janeiro' => 'pref/rj/rio-janeiro/cnd',
            'rn:natal' => 'pref/rn/natal/cnd',
            'rn:touros' => 'pref/rn/touros/cnd',
            'rs:canela' => 'pref/rs/canela/cnd',
            'rs:caxias-do-sul' => 'pref/rs/caxias-do-sul/cnd',
            'rs:montenegro' => 'pref/rs/montenegro/cnd',
            'rs:porto-alegre' => 'pref/rs/porto-alegre/cnd',
            'rs:santa-cruz-do-sul' => 'pref/rs/santa-cruz-do-sul/cnd',
            'rs:tres-coroas' => 'pref/rs/tres-coroas/cnd',
            'sc:balneario' => 'pref/sc/balneario/cnd',
            'sc:balneario-camboriu' => 'pref/sc/balneario/cnd',
            'sc:blumenau' => 'pref/sc/blumenau/cnd',
            'sc:florianopolis' => 'pref/sc/florianopolis/cnd',
            'sc:imbituba' => 'pref/sc/imbituba/cnd',
            'sc:itajai' => 'pref/sc/itajai/cnd',
            'sc:joinville' => 'pref/sc/joinville/cnd',
            'se:laranjeiras' => 'pref/se/laranjeiras/cnd',
            'sp:campinas' => 'pref/sp/campinas/cnd',
            'sp:guarulhos' => 'pref/sp/guarulhos/cnd',
            'sp:hortolandia' => 'pref/sp/hortolandia/cnd',
            'sp:mairipora' => 'pref/sp/mairipora/cnd',
            'sp:ribeirao-preto' => 'pref/sp/ribeirao-preto/cnd',
            'sp:sao-bernardo' => 'pref/sp/sao-bernardo/cnd',
            'sp:sao-bernardo-do-campo' => 'pref/sp/sao-bernardo/cnd',
            'sp:sao-carlos' => 'pref/sp/sao-carlos/cnd',
            'to:colinas' => 'pref/to/colinas/cnd',
            'to:colinas-do-tocantins' => 'pref/to/colinas/cnd',
            'to:palmas' => 'pref/to/palmas/cnd',
        ],
    ],

    // Custo em créditos por fonte paga (usado no estorno preciso). 1 crédito = R$ 0,20.
    'fontes' => [
        'cnd_federal' => (int) env('CONSULTA_CREDITOS_CND_FEDERAL', 2),
        'cndt' => (int) env('CONSULTA_CREDITOS_CNDT', 2),
        'crf_fgts' => (int) env('CONSULTA_CREDITOS_CRF_FGTS', 2),
        'cnd_estadual' => (int) env('CONSULTA_CREDITOS_CND_ESTADUAL', 2),
        'sintegra' => (int) env('CONSULTA_CREDITOS_SINTEGRA', 1),
        'cgu_cnc' => (int) env('CONSULTA_CREDITOS_CGU_CNC', 2),
        'cnj_improbidade' => (int) env('CONSULTA_CREDITOS_CNJ_IMPROBIDADE', 2),
        'cnd_municipal' => (int) env('CONSULTA_CREDITOS_CND_MUNICIPAL', 2),
        'protestos' => (int) env('CONSULTA_CREDITOS_PROTESTOS', 2),
    ],
];
