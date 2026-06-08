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
        // Único uso restante do n8n: extração completa de documentos EFD por tipo.
        // Cutover 2026-06-07: consultas de CNPJ migraram 100% pro Laravel
        // (app/Services/Consultas). Clearance de notas e importação XML foram
        // desligados junto com a remoção dos webhooks n8n correspondentes.
        'importacao_efd_contribuicoes_url' => env('WEBHOOK_IMPORTACAO_EFD_CONTRIBUICOES_URL'),
        'importacao_efd_fiscal_url' => env('WEBHOOK_IMPORTACAO_EFD_FISCAL_URL'),
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

    'mercadopago' => [
        // Conta própria (sem OAuth): Access Token + Public Key da FiscalDock (test → prod).
        'access_token' => env('MERCADO_PAGO_ACCESS_TOKEN'),
        'public_key' => env('MERCADO_PAGO_PUBLIC_KEY'),
        // Secret da assinatura do webhook (gerado no painel MP ao cadastrar a notificação).
        'webhook_secret' => env('MERCADO_PAGO_WEBHOOK_SECRET'),
        'base_url' => env('MERCADO_PAGO_BASE_URL', 'https://api.mercadopago.com'),
    ],

];
