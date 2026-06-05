<?php

use App\Services\BiService;
use App\Services\Efd\CruzamentoApuracaoService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('BI e resumo-fiscal classificam a MESMA divergencia com o MESMO flag', function () {
    [$userId, $clienteId] = montarMassaFechamento();

    $bi = app(BiService::class)->getApuracaoVsNotas($userId, '2024-01-01', '2024-01-31', $clienteId);
    $rf = app(CruzamentoApuracaoService::class)->paraCompetencia($userId, $clienteId, '2024-01');

    // Jan/2024: ICMS débito declarado=100, notas=100 → 'verde'; PIS 30/30 → 'verde'
    expect($bi['mensal'][0]['icms']['flag'])->toBe($rf['icms']['status_debito']);
    expect($bi['mensal'][0]['pis']['flag'])->toBe($rf['pis_cofins']['pis_status']);
    expect($bi['mensal'][0]['cofins']['flag'])->toBe($rf['pis_cofins']['cofins_status']);
});
