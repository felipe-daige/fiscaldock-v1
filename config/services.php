<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'webhook' => [
        // Webhooks Importação EFD (extração completa por tipo)
        'importacao_efd_contribuicoes_url' => env('WEBHOOK_IMPORTACAO_EFD_CONTRIBUICOES_URL'),
        'importacao_efd_fiscal_url' => env('WEBHOOK_IMPORTACAO_EFD_FISCAL_URL'),
        // Webhook Importação de XMLs (NF-e, NFS-e, CT-e)
        'importacao_xml_url' => env('WEBHOOK_IMPORTACAO_XML_URL'),
        // Webhook Consultas de CNPJ - endpoint unificado (avulsa e lote)
        'consultas_cnpj_url' => env('WEBHOOK_CONSULTAS_CNPJ_URL'),
        // Webhook Consultas de Notas Fiscais — clearance em lote (snapshot de verificação SEFAZ sobre acervo)
        'consultas_notas_url' => env('WEBHOOK_CONSULTAS_NOTAS_URL'),
        // Webhook Busca avulsa de Notas Fiscais — aquisição no acervo (xml_notas com origem='busca_avulsa')
        'busca_nota_url' => env('WEBHOOK_BUSCA_NOTA_URL'),
    ],

    'api' => [
        'token' => env('API_TOKEN', ''),
    ],

    'receitaws' => [
        'url' => env('RECEITAWS_API_URL', 'https://www.receitaws.com.br/v1'),
    ],

    'viacep' => [
        'url' => env('VIACEP_API_URL', 'https://viacep.com.br/ws'),
    ],

];
