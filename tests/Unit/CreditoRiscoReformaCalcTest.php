<?php

use App\Models\Participante;
use App\Services\Reforma\CreditoRiscoReformaService;

// Sobe o app (container) p/ config('reforma.*'); não toca DB.
uses(Tests\TestCase::class);

beforeEach(function () {
    $this->svc = app(CreditoRiscoReformaService::class);
    // Fixa a alíquota de referência destes testes de fórmula (independe do default de prod).
    config(['reforma.aliquota_referencia' => 0.265]);
});

// ---------- aliquotaPara: alíquota total IBS+CBS por ano de vigência ----------

it('aliquotaPara: sem ano = estado pleno; por ano = tabela de fases', function () {
    config([
        'reforma.aliquota_referencia' => 0.285,
        'reforma.aliquotas_por_fase' => [
            2026 => 0.010, 2027 => 0.093, 2032 => 0.165,
        ],
    ]);

    expect($this->svc->aliquotaPara(null))->toBe(0.285); // pleno
    expect($this->svc->aliquotaPara(2026))->toBe(0.010); // teste
    expect($this->svc->aliquotaPara(2027))->toBe(0.093); // CBS plena
    expect($this->svc->aliquotaPara(2032))->toBe(0.165); // fim da transição IBS
    expect($this->svc->aliquotaPara(2033))->toBe(0.285); // após a tabela = pleno
    expect($this->svc->aliquotaPara(2040))->toBe(0.285);
    expect($this->svc->aliquotaPara(2025))->toBe(0.0);   // antes da reforma = sem IBS/CBS
});

it('creditoParticipante aceita o ano e usa a alíquota da fase', function () {
    config(['reforma.aliquotas_por_fase' => [2027 => 0.093]]);

    // MEI (não gera) em 2027: risco = volume × 0,093 × 1
    $r = $this->svc->creditoParticipante(new Participante(['regime_tributario' => 'MEI']), 100000, null, 2027);

    expect($r['credito_potencial'])->toBe(9300.0);  // 100000 * 0.093
    expect($r['credito_em_risco'])->toBe(9300.0);
});

it('crédito do MEI: não gera, tudo em risco', function () {
    $r = $this->svc->creditoParticipante(new Participante(['regime_tributario' => 'MEI']), 100000);

    expect($r['fator'])->toBe(0.0);
    expect($r['credito_potencial'])->toBe(26500.0);   // 100000 * 0.265
    expect($r['credito_em_risco'])->toBe(26500.0);    // * (1 - 0)
    expect($r['flag'])->toBe('vermelho');
    expect($r['gera_credito'])->toBe('Não gera crédito');
});

it('crédito do Regime Normal: gera integral, risco zero', function () {
    $r = $this->svc->creditoParticipante(new Participante(['crt' => 3]), 200000);

    expect($r['credito_em_risco'])->toBe(0.0);        // 200000 * 0.265 * (1 - 1)
    expect($r['flag'])->toBe('verde');
    expect($r['gera_credito'])->toBe('Gera crédito integral');
});

it('regime não identificado: risco indeterminado (null), flag cinza', function () {
    $r = $this->svc->creditoParticipante(new Participante([]), 50000);

    expect($r['credito_em_risco'])->toBeNull();
    expect($r['flag'])->toBe('cinza');
    expect($r['gera_credito'])->toBe('Regime não identificado');
});

// O regime mora no resultado da consulta (persistido em score_credito_reforma), NÃO nas colunas
// do participante (que costumam vir vazias). O score persistido é a fonte de verdade.
it('usa o score_credito_reforma persistido sobre colunas vazias (consistência lista×detalhe)', function () {
    // colunas vazias, mas score persistido = 0 (gera integral)
    $r = $this->svc->creditoParticipante(new Participante([]), 100000, 0);
    expect($r['fator'])->toBe(1.0);
    expect($r['credito_em_risco'])->toBe(0.0);
    expect($r['flag'])->toBe('verde');

    // score persistido = 100 (não gera) -> tudo em risco
    $r2 = $this->svc->creditoParticipante(new Participante([]), 100000, 100);
    expect($r2['credito_em_risco'])->toBe(26500.0);
    expect($r2['flag'])->toBe('vermelho');

    // score persistido = 70 (Simples parcial)
    $r3 = $this->svc->creditoParticipante(new Participante([]), 100000, 70);
    expect($r3['fator'])->toBe(0.3);
    expect($r3['credito_em_risco'])->toBe(18550.0); // 26500 * (1 - 0.3)
    expect($r3['flag'])->toBe('amarelo');
});
