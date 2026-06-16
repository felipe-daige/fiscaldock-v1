<?php

namespace App\Services\Lgpd;

use App\Models\ConsentLog;

/**
 * LGPD fase 2.1 — ponto único de gravação da trilha de consentimento.
 *
 * Toda mudança de consentimento (aceite no signup, opt-in/revogação de marketing,
 * pedido/cancelamento de exclusão) passa por aqui — garante carimbo consistente de
 * versão/IP/UA e mantém a tabela `consent_logs` como prova auditável (append-only).
 */
class ConsentLogService
{
    private const USER_AGENT_MAX = 500;

    public function registrar(
        int $userId,
        string $tipo,
        string $acao,
        ?bool $valor = null,
        ?string $versao = null,
        ?string $ip = null,
        ?string $userAgent = null,
    ): ConsentLog {
        return ConsentLog::create([
            'user_id' => $userId,
            'tipo' => $tipo,
            'acao' => $acao,
            'valor' => $valor,
            'versao' => $versao,
            'ip' => $ip,
            'user_agent' => $userAgent !== null ? mb_substr($userAgent, 0, self::USER_AGENT_MAX) : null,
            'created_at' => now(),
        ]);
    }
}
