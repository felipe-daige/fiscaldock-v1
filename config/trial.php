<?php

return [
    // Bônus de boas-vindas concedido no signup.
    'creditos' => (int) env('TRIAL_CREDITOS', 100),
    'validade_dias' => (int) env('TRIAL_VALIDADE_DIAS', 30),

    // Teto de CNPJs consultáveis por plano premium ANTES da 1ª compra.
    'limite_premium_por_plano' => (int) env('TRIAL_LIMITE_PREMIUM_POR_PLANO', 5),

    // Planos sujeitos ao teto de teste (Gratuito e Validação ficam de fora).
    'planos_premium' => ['licitacao', 'compliance', 'due_diligence'],
];
