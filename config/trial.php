<?php

return [
    // Bônus de boas-vindas concedido no signup.
    'creditos' => (int) env('TRIAL_CREDITOS', 100),
    'validade_dias' => (int) env('TRIAL_VALIDADE_DIAS', 30),

    // Teto GLOBAL de consultas (CNPJs) liberadas ANTES da 1ª compra — pool único
    // somado entre TODOS os planos pagos abaixo. Esgotou o pool, só liberando com depósito.
    'limite_consultas_sem_compra' => (int) env('TRIAL_LIMITE_CONSULTAS_SEM_COMPRA', 5),

    // Planos pagos sujeitos ao teto (Gratuito fica de fora). O pool é compartilhado entre eles.
    'planos_com_teto' => ['validacao', 'licitacao', 'compliance', 'due_diligence'],
];
