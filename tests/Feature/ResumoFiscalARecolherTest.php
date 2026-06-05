<?php

use App\Services\ResumoFiscalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    [$this->userId, $this->clienteId] = montarMassaFechamento();
    $this->svc = app(ResumoFiscalService::class);
});

it('consolida tributos a recolher (E116/E250 + PIS/COFINS) com total', function () {
    $r = $this->svc->getARecolherData($this->userId, $this->clienteId, '2024-01');

    $linhas = collect($r['linhas'])->keyBy('tributo');
    expect($linhas['ICMS apuração']['valor'])->toBe(80.0);   // guia 310 do E116
    expect($linhas['PIS']['valor'])->toBe(28.0);             // total_recolher (líquido)
    expect($linhas['COFINS']['valor'])->toBe(88.0);
    expect($r['total'])->toBe(196.0);
});

it('vencimento de ICMS vem do E116 (real) e PIS/COFINS é estimado 25/mm+1', function () {
    $r = $this->svc->getARecolherData($this->userId, $this->clienteId, '2024-01');
    $por = collect($r['linhas'])->keyBy('tributo');

    expect($por['ICMS apuração']['vencimento'])->toBe('2024-02-16');
    expect($por['ICMS apuração']['vencimento_estimado'])->toBeFalse();

    expect($por['PIS']['vencimento'])->toBe('2024-02-25');
    expect($por['PIS']['vencimento_estimado'])->toBeTrue();
});

it('inclui débito especial (350) e ICMS-ST (333) como guias separadas', function () {
    // Acrescenta guia 350 (especial) e uma obrigação ST (E250) ao mês.
    DB::table('efd_apuracoes_icms')->where('cliente_id', $this->clienteId)->update([
        'icms_obrigacoes' => json_encode(['items' => [
            ['ICMS_COD_RECEITA' => '310', 'ICMS_VALOR_OBRIGACAO' => 80, 'ICMS_DATA_VENCIMENTO' => '16022024'],
            ['ICMS_COD_RECEITA' => '350', 'ICMS_VALOR_OBRIGACAO' => 41146.64, 'ICMS_DATA_VENCIMENTO' => '19022024'],
        ]]),
        'st_obrigacoes' => json_encode(['items' => [
            ['ST_COD_RECEITA' => '333', 'ST_VALOR_OBRIGACAO' => 40926.56, 'ST_DATA_VENCIMENTO' => '19022024'],
        ]]),
    ]);

    $r = $this->svc->getARecolherData($this->userId, $this->clienteId, '2024-01');
    $por = collect($r['linhas'])->keyBy('tributo');

    expect($por['ICMS débito especial']['valor'])->toBe(41146.64);
    expect($por->has('ICMS-ST (receita 333)'))->toBeTrue();
    // total = 80 + 41146,64 + 40926,56 (ST) + 28 + 88
    expect($r['total'])->toBe(82269.2);
});

it('omite a linha cujo EFD não foi importado', function () {
    DB::table('efd_apuracoes_contribuicoes')->where('cliente_id', $this->clienteId)->delete();
    $r = $this->svc->getARecolherData($this->userId, $this->clienteId, '2024-01');

    expect(collect($r['linhas'])->pluck('tributo'))->not->toContain('PIS');
    expect($r['total'])->toBe(80.0);
});

it('endpoint a-recolher responde JSON autenticado', function () {
    $user = \App\Models\User::find($this->userId);
    $resp = $this->actingAs($user)->getJson("/app/resumo-fiscal/a-recolher?cliente_id={$this->clienteId}&competencia=2024-01");

    $resp->assertOk()->assertJsonStructure(['linhas', 'total', 'tem_icms', 'tem_contribuicoes']);
    expect((float) $resp->json('total'))->toBe(196.0);
});
