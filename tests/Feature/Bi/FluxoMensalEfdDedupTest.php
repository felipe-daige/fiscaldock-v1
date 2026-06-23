<?php

use App\Models\EfdImportacao;
use App\Models\EfdNota;
use App\Models\User;
use App\Services\BiService;
use App\Services\EfdAgregadorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

/**
 * Regressão (feedback Marcio, 2026-06-23): o gráfico "Fluxo Mensal Entradas vs
 * Saídas (EFD)" usava SUM bruto de efd_notas — dobrava a mesma NF-e escriturada
 * em fiscal E contribuicoes (P1) e somava canceladas (P4). Resultado: o gráfico
 * mostrava ~6% a mais que os KPIs Faturamento/Aquisições (base dedup).
 *
 * Contrato: getFluxoMensalEfd DEVE bater, por construção, com faturamentoMensal
 * (mesma base dedup dos KPIs).
 */
beforeEach(function () {
    $this->user = User::factory()->create();
    $this->cliente = DB::table('clientes')->insertGetId([
        'user_id' => $this->user->id,
        'razao_social' => 'EMPRESA TESTE',
        'documento' => '00000000000100',
        'is_empresa_propria' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $impFiscal = EfdImportacao::create([
        'user_id' => $this->user->id, 'cliente_id' => $this->cliente,
        'tipo_efd' => 'EFD ICMS/IPI', 'filename' => 'icms.txt',
        'status' => 'concluido', 'iniciado_em' => now()->subMinutes(2),
    ]);
    $impContrib = EfdImportacao::create([
        'user_id' => $this->user->id, 'cliente_id' => $this->cliente,
        'tipo_efd' => 'EFD PIS/COFINS', 'filename' => 'pc.txt',
        'status' => 'concluido', 'iniciado_em' => now()->subMinutes(2),
    ]);

    $chaveA = str_pad('A', 44, '0', STR_PAD_LEFT); // NF-e nas 2 origens (P1)
    $chaveC = str_pad('C', 44, '0', STR_PAD_LEFT); // entrada nas 2 origens
    $chaveE = str_pad('E', 44, '0', STR_PAD_LEFT); // cancelada (P4)

    $mk = function (array $attrs): EfdNota {
        return EfdNota::create(array_merge([
            'user_id' => $this->user->id, 'cliente_id' => $this->cliente,
            'numero' => random_int(1, 99999), 'serie' => '1', 'modelo' => '55',
            'data_emissao' => '2024-01-15', 'valor_desconto' => 0, 'cancelada' => false,
        ], $attrs));
    };

    // Saída NF-e A em fiscal (1000) + gêmea contribuicoes (1000) — deve contar 1000, não 2000.
    $mk(['importacao_id' => $impFiscal->id, 'chave_acesso' => $chaveA, 'tipo_operacao' => 'saida', 'origem_arquivo' => 'fiscal', 'valor_total' => 1000]);
    $mk(['importacao_id' => $impContrib->id, 'chave_acesso' => $chaveA, 'tipo_operacao' => 'saida', 'origem_arquivo' => 'contribuicoes', 'valor_total' => 1000]);
    // Saída cancelada (9999) — não soma.
    $mk(['importacao_id' => $impFiscal->id, 'chave_acesso' => $chaveE, 'tipo_operacao' => 'saida', 'origem_arquivo' => 'fiscal', 'valor_total' => 9999, 'cancelada' => true]);
    // Entrada NF-e C em fiscal (700) + gêmea contribuicoes (700) — deve contar 700, não 1400.
    $mk(['importacao_id' => $impFiscal->id, 'chave_acesso' => $chaveC, 'tipo_operacao' => 'entrada', 'origem_arquivo' => 'fiscal', 'valor_total' => 700]);
    $mk(['importacao_id' => $impContrib->id, 'chave_acesso' => $chaveC, 'tipo_operacao' => 'entrada', 'origem_arquivo' => 'contribuicoes', 'valor_total' => 700]);

    $this->bi = app(BiService::class);
    $this->efd = app(EfdAgregadorService::class);
});

it('getFluxoMensalEfd usa base dedup: não dobra NF-e nas 2 origens nem soma cancelada', function () {
    $fluxo = $this->bi->getFluxoMensalEfd($this->user->id, null, null);

    $saidas = array_sum(array_column($fluxo, 'saidas'));
    $entradas = array_sum(array_column($fluxo, 'entradas'));

    expect($saidas)->toBe(1000.0); // não 12799 (bruto) nem 2000
    expect($entradas)->toBe(700.0); // não 1400 (bruto)
});

it('getFluxoMensalEfd bate com faturamentoMensal (mesma base dos KPIs)', function () {
    $fluxo = $this->bi->getFluxoMensalEfd($this->user->id, null, null);
    $saidasChart = array_sum(array_column($fluxo, 'saidas'));
    $entradasChart = array_sum(array_column($fluxo, 'entradas'));

    $saidasKpi = array_sum(array_column($this->efd->faturamentoMensal($this->user->id, 'saida', null, null), 'valor'));
    $entradasKpi = array_sum(array_column($this->efd->faturamentoMensal($this->user->id, 'entrada', null, null), 'valor'));

    expect($saidasChart)->toBe($saidasKpi);
    expect($entradasChart)->toBe($entradasKpi);
});
