<?php

use App\Services\ResumoFiscalService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * F2 — o cruzamento "declarado × notas" do Resumo Fiscal não pode acusar
 * divergência FALSA por ler tributo da fonte errada (ICMS dos itens fiscais ≈0;
 * PIS/COFINS de todos os itens vs líquido). Pós-refactor, o cálculo é delegado
 * ao CruzamentoApuracaoService (mesma fonte do BI). Massa em montarMassaFechamento().
 */
beforeEach(function () {
    [$this->userId, $this->cliente] = montarMassaFechamento();
    $this->svc = app(ResumoFiscalService::class);
});

it('ICMS cruzamento usa C190 (não itens fiscais lixo) e não acusa divergência falsa', function () {
    $c = $this->svc->getCruzamentosData($this->userId, $this->cliente, '2024-01');

    expect($c['icms']['notas_debito'])->toBe(100.0);           // C190, não 0,01 dos itens
    expect($c['icms']['divergencia_debito_pct'])->toBe(0.0);
    expect($c['icms']['status_debito'])->toBe('verde');
    expect($c['icms']['notas_credito'])->toBe(20.0);
    expect($c['icms']['status_credito'])->toBe('verde');
});

it('PIS/COFINS cruzamento compara débito-saída (itens contrib) com devido GROSS', function () {
    $c = $this->svc->getCruzamentosData($this->userId, $this->cliente, '2024-01');

    expect($c['pis_cofins']['pis_notas'])->toBe(30.0);         // débito-saída, não 999+30
    expect($c['pis_cofins']['pis_declarado'])->toBe(30.0);     // gross devido (nc 0 + cum 30), não 28 líquido
    expect($c['pis_cofins']['pis_divergencia_pct'])->toBe(0.0);
    expect($c['pis_cofins']['pis_status'])->toBe('verde');
    expect($c['pis_cofins']['cofins_notas'])->toBe(90.0);
    expect($c['pis_cofins']['cofins_declarado'])->toBe(90.0);
    expect($c['pis_cofins']['cofins_status'])->toBe('verde');
});

it('cruzamento de retenções soma F600 vs deduzido na apuração', function () {
    $c = $this->svc->getCruzamentosData($this->userId, $this->cliente, '2024-01');

    expect($c['retencoes']['total_retido'])->toBe(4.0);
    expect($c['retencoes']['deduzido_apuracao'])->toBe(4.0);
    expect($c['retencoes']['nao_compensado'])->toBe(0.0);
    expect($c['retencoes']['status'])->toBe('verde');
});

it('não gera alertas de divergência quando declarado bate com as notas', function () {
    $a = $this->svc->getAlertasFiscaisData($this->userId, $this->cliente, '2024-01');

    // cruzamento verde → zero alertas de divergência (Obrigações vencidas são à parte)
    $divergencia = collect($a['alertas'])->whereIn('categoria', ['ICMS', 'PIS/COFINS', 'Retenções']);
    expect($divergencia)->toHaveCount(0);
});

it('A4: gera alerta de obrigação ICMS vencida lendo as chaves reais do E116', function () {
    // chave real (ICMS_DATA_VENCIMENTO) + formato DDMMYYYY + data no passado
    \Illuminate\Support\Facades\DB::table('efd_apuracoes_icms')->where('cliente_id', $this->cliente)->update([
        'icms_obrigacoes' => json_encode(['items' => [
            ['ICMS_COD_RECEITA' => '310', 'ICMS_VALOR_OBRIGACAO' => 1234.56, 'ICMS_DATA_VENCIMENTO' => '10012020'],
        ]]),
    ]);

    $a = $this->svc->getAlertasFiscaisData($this->userId, $this->cliente, '2024-01');
    $venc = collect($a['alertas'])->firstWhere('titulo', 'Obrigação ICMS vencida');

    expect($venc)->not->toBeNull();
    expect($venc['valor'])->toBe(1234.56);
    expect($venc['descricao'])->toContain('10/01/2020');
});

it('saldo_liquido não subtrai a retenção em dobro (a_recolher já é líquido)', function () {
    // ICMS guia 80 + PIS 28 + COFINS 88 = 196.
    $re = $this->svc->getResumoExecutivo($this->userId, $this->cliente, '2024-01');

    expect($re['kpis']['retencoes_compensaveis']['valor'])->toBe(4.0);
    expect($re['kpis']['saldo_liquido']['valor'])->toBe(196.0);
    expect($re['kpis']['icms_a_recolher']['valor'])->toBe(80.0);  // família ICMS (guia 310)
    expect($re['kpis']['pis_a_recolher']['valor'])->toBe(28.0);
});

it('saldo_liquido NÃO trata PIS/COFINS ausente como zero (completude)', function () {
    \Illuminate\Support\Facades\DB::table('efd_apuracoes_contribuicoes')->delete();

    $re = $this->svc->getResumoExecutivo($this->userId, $this->cliente, '2024-01');

    expect($re['tem_icms'])->toBeTrue();
    expect($re['tem_contribuicoes'])->toBeFalse();
    expect($re['kpis']['saldo_liquido']['valor'])->toBe(80.0);     // só ICMS (não finge PIS/COFINS=0)
    expect($re['kpis']['saldo_liquido']['parcial'])->toBeTrue();
});

it('saldo_liquido inclui débito especial + ICMS-ST (não só guia 310)', function () {
    \Illuminate\Support\Facades\DB::table('efd_apuracoes_icms')->where('cliente_id', $this->cliente)->update([
        'icms_obrigacoes' => json_encode(['items' => [
            ['ICMS_COD_RECEITA' => '310', 'ICMS_VALOR_OBRIGACAO' => 80, 'ICMS_DATA_VENCIMENTO' => '16022024'],
            ['ICMS_COD_RECEITA' => '350', 'ICMS_VALOR_OBRIGACAO' => 20, 'ICMS_DATA_VENCIMENTO' => '19022024'],
        ]]),
        'st_obrigacoes' => json_encode(['items' => [
            ['ST_COD_RECEITA' => '333', 'ST_VALOR_OBRIGACAO' => 50, 'ST_DATA_VENCIMENTO' => '19022024'],
        ]]),
    ]);

    $re = $this->svc->getResumoExecutivo($this->userId, $this->cliente, '2024-01');

    expect($re['kpis']['icms_a_recolher']['valor'])->toBe(150.0); // 80+20+50
    expect($re['kpis']['saldo_liquido']['valor'])->toBe(266.0);   // 150 + 28 + 88
});
