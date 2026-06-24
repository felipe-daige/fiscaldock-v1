<?php

use App\Models\Participante;
use App\Services\RiskScoreService;

// Sobe o app (container) — fatorCreditoRegime lê config('reforma.*') para o fator do Simples.
uses(Tests\TestCase::class);

beforeEach(function () {
    $this->svc = new RiskScoreService;
});

// ---------- fatorCreditoRegime: regime do fornecedor -> fração de crédito IBS/CBS (0..1) ----------
// 1.0 = gera crédito integral | reduzido = Simples | 0.0 = MEI | null = regime não identificado

it('Regime Normal (crt 3) gera crédito integral (1.0)', function () {
    expect($this->svc->fatorCreditoRegime(null, ['crt' => 3]))->toBe(1.0);
});

it('Regime Normal via regime_tributario textual (Lucro Real) gera 1.0', function () {
    expect($this->svc->fatorCreditoRegime(null, ['regime_tributario' => 'Lucro Real']))->toBe(1.0);
});

it('Simples Nacional gera crédito reduzido (= fator da config)', function () {
    expect($this->svc->fatorCreditoRegime(null, ['simples_nacional' => true]))
        ->toBe(config('reforma.fator_simples_sem_opcao'));
});

it('Simples via crt 1 gera crédito reduzido', function () {
    expect($this->svc->fatorCreditoRegime(null, ['crt' => 1]))
        ->toBe(config('reforma.fator_simples_sem_opcao'));
});

it('fator do Simples vem da config (parametrizável)', function () {
    config(['reforma.fator_simples_sem_opcao' => 0.5]);
    expect($this->svc->fatorCreditoRegime(null, ['simples_nacional' => true]))->toBe(0.5);
});

it('MEI não gera crédito (0.0)', function () {
    expect($this->svc->fatorCreditoRegime(null, ['mei' => true]))->toBe(0.0);
});

it('MEI vence Simples quando regime textual é "Simples Nacional - MEI"', function () {
    expect($this->svc->fatorCreditoRegime(null, ['regime_tributario' => 'Simples Nacional - MEI']))->toBe(0.0);
});

it('regime não identificado retorna null (não chuta)', function () {
    expect($this->svc->fatorCreditoRegime(null, []))->toBeNull();
});

it('cai no crt do Participante quando dados não trazem regime', function () {
    $participante = new Participante(['crt' => 3]);
    expect($this->svc->fatorCreditoRegime($participante, []))->toBe(1.0);
});

// ---------- scoreCreditoReforma: 0-100 (0 = gera crédito cheio, 100 = não gera, null = ?) ----------

it('scoreCreditoReforma: Regime Normal => 0 (gera crédito cheio)', function () {
    expect($this->svc->scoreCreditoReforma(['crt' => 3]))->toBe(0);
});

it('scoreCreditoReforma: MEI => 100 (não gera)', function () {
    expect($this->svc->scoreCreditoReforma(['mei' => true]))->toBe(100);
});

it('scoreCreditoReforma: Simples => round((1 - fator) * 100)', function () {
    $esperado = (int) round((1 - config('reforma.fator_simples_sem_opcao')) * 100);
    expect($this->svc->scoreCreditoReforma(['simples_nacional' => true]))->toBe($esperado);
});

it('scoreCreditoReforma: regime desconhecido => null', function () {
    expect($this->svc->scoreCreditoReforma([]))->toBeNull();
});
