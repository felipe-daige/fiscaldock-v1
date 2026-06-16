<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Versões dos documentos legais
    |--------------------------------------------------------------------------
    |
    | Fonte única da versão vigente dos Termos de Uso e da Política de
    | Privacidade. Cada evento de consentimento (signup, opt-in) carimba a
    | versão vigente em `consent_logs.versao` — prova de qual texto o titular
    | aceitou. Subir uma versão aqui passa a carimbar a nova nos próximos
    | aceites. O re-aceite forçado quando a versão muda é fase 2.2.
    |
    */

    'terms_version' => env('LEGAL_TERMS_VERSION', '1.0'),

    'privacy_version' => env('LEGAL_PRIVACY_VERSION', '1.0'),
];
