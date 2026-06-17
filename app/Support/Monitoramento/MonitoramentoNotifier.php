<?php

namespace App\Support\Monitoramento;

use App\Models\MonitoramentoAssinatura;
use App\Models\MonitoramentoConsulta;

/**
 * Canal de aviso do monitoramento contínuo. Hoje só in-app; mensageria
 * (e-mail/WhatsApp) futura implementa este mesmo contrato.
 */
interface MonitoramentoNotifier
{
    public function assinaturaPausadaSemSaldo(MonitoramentoAssinatura $assinatura): void;

    public function assinaturaPausadaPorFalhas(MonitoramentoAssinatura $assinatura): void;

    public function assinaturaPausadaPorLimiteConsumo(MonitoramentoAssinatura $assinatura): void;

    public function situacaoPiorou(MonitoramentoConsulta $consulta, ?MonitoramentoConsulta $anterior): void;

    public function situacaoMelhorou(MonitoramentoConsulta $consulta, ?MonitoramentoConsulta $anterior): void;

    public function pendenciasSurgiram(MonitoramentoConsulta $consulta): void;

    public function certidaoVencendo(MonitoramentoConsulta $consulta): void;
}
