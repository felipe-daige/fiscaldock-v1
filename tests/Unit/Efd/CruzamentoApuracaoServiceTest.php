<?php

use App\Services\Efd\CruzamentoApuracaoService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('classifica flag por |Δ%|: verde <=2, amarelo <=10, vermelho >10', function () {
    $s = app(CruzamentoApuracaoService::class);

    expect($s->classificarFlag(100.0, 100.0, true)['flag'])->toBe('verde');
    expect($s->classificarFlag(100.0, 101.0, true)['flag'])->toBe('verde');   // 1%
    expect($s->classificarFlag(100.0, 105.0, true)['flag'])->toBe('amarelo'); // 5%
    expect($s->classificarFlag(100.0, 120.0, true)['flag'])->toBe('vermelho');// 20%
});

it('marca sem_dado quando a fonte de apuração não existe no mês', function () {
    $s = app(CruzamentoApuracaoService::class);

    expect($s->classificarFlag(0.0, 0.0, false)['flag'])->toBe('sem_dado');
    expect($s->classificarFlag(0.0, 0.0, true)['flag'])->toBe('neutro');
});

it('expõe declarado/computado/delta/delta_pct', function () {
    $s = app(CruzamentoApuracaoService::class);
    $r = $s->classificarFlag(100.0, 90.0, true);

    expect($r['declarado'])->toBe(100.0);
    expect($r['computado'])->toBe(90.0);
    expect($r['delta'])->toBe(-10.0);
    expect($r['delta_pct'])->toBe(-10.0);
});

it('paraCompetencia usa os builders do agregador (mesma fonte do BI)', function () {
    [$userId, $clienteId] = montarMassaFechamento();
    $s = app(CruzamentoApuracaoService::class);

    $c = $s->paraCompetencia($userId, $clienteId, '2024-01');

    expect($c['icms']['declarado_debito'])->toBe(100.0);
    expect($c['icms']['notas_debito'])->toBe(100.0);
    expect($c['icms']['status_debito'])->toBe('verde');
    expect($c['icms']['declarado_credito'])->toBe(20.0);
    expect($c['icms']['notas_credito'])->toBe(20.0);
    expect($c['icms']['status_credito'])->toBe('verde');
    expect($c['icms']['tem_dados'])->toBeTrue();

    expect($c['pis_cofins']['pis_declarado'])->toBe(30.0);
    expect($c['pis_cofins']['pis_notas'])->toBe(30.0);
    expect($c['pis_cofins']['pis_status'])->toBe('verde');
    expect($c['pis_cofins']['cofins_declarado'])->toBe(90.0);
    expect($c['pis_cofins']['cofins_notas'])->toBe(90.0);
    expect($c['pis_cofins']['cofins_status'])->toBe('verde');
});

it('paraCompetencia marca sem_dado quando falta a EFD do imposto', function () {
    [$userId, $clienteId] = montarMassaFechamento();
    \Illuminate\Support\Facades\DB::table('efd_apuracoes_icms')->delete();
    $s = app(CruzamentoApuracaoService::class);

    $c = $s->paraCompetencia($userId, $clienteId, '2024-01');

    expect($c['icms']['tem_dados'])->toBeFalse();
    expect($c['icms']['status_debito'])->toBe('sem_dado');
    expect($c['pis_cofins']['pis_status'])->toBe('verde'); // contribuições segue presente
});
