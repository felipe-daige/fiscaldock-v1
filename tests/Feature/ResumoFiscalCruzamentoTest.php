<?php

use App\Models\EfdImportacao;
use App\Models\EfdNota;
use App\Models\User;
use App\Services\ResumoFiscalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

/**
 * F2 — o cruzamento "declarado × notas" do Resumo Fiscal não pode acusar
 * divergência FALSA por ler tributo da fonte errada:
 *  - ICMS lido dos itens fiscais (≈0 no perfil B, P2) → falso 100% vermelho.
 *  - PIS/COFINS somado de TODOS os itens (entrada+saída, 2 origens) e comparado
 *    com o "a recolher" (líquido de retenção) → falsa divergência.
 * Correto: ICMS do C190 (gross débito/crédito), PIS/COFINS do débito-saída
 * (itens contribuicoes) vs declarado GROSS devido (M200/M600 = nao_cum+cum).
 */
beforeEach(function () {
    $this->user = User::factory()->create();
    $this->cliente = DB::table('clientes')->insertGetId([
        'user_id' => $this->user->id, 'razao_social' => 'EMPRESA TESTE',
        'documento' => '00000000000100', 'is_empresa_propria' => true,
        'created_at' => now(), 'updated_at' => now(),
    ]);
    $this->impFiscal = EfdImportacao::create([
        'user_id' => $this->user->id, 'cliente_id' => $this->cliente,
        'tipo_efd' => 'EFD ICMS/IPI', 'filename' => 'icms.txt', 'status' => 'concluido', 'iniciado_em' => now(),
    ]);
    $this->impContrib = EfdImportacao::create([
        'user_id' => $this->user->id, 'cliente_id' => $this->cliente,
        'tipo_efd' => 'EFD PIS/COFINS', 'filename' => 'pc.txt', 'status' => 'concluido', 'iniciado_em' => now(),
    ]);

    $mk = fn (array $a) => EfdNota::create(array_merge([
        'user_id' => $this->user->id, 'cliente_id' => $this->cliente,
        'numero' => random_int(1, 99999), 'serie' => '1', 'data_emissao' => '2024-01-15',
        'valor_total' => 1000, 'valor_desconto' => 0, 'cancelada' => false,
    ], $a));

    // ICMS: débito vive no C190 (saída) e crédito no C190 (entrada); itens fiscais lixo.
    $fSaida = $mk(['importacao_id' => $this->impFiscal->id, 'chave_acesso' => str_pad('A', 44, '0', STR_PAD_LEFT), 'modelo' => '55', 'tipo_operacao' => 'saida', 'origem_arquivo' => 'fiscal']);
    $fEntrada = $mk(['importacao_id' => $this->impFiscal->id, 'chave_acesso' => str_pad('C', 44, '0', STR_PAD_LEFT), 'modelo' => '55', 'tipo_operacao' => 'entrada', 'origem_arquivo' => 'fiscal']);
    $cSaida = $mk(['importacao_id' => $this->impContrib->id, 'chave_acesso' => str_pad('A', 44, '0', STR_PAD_LEFT), 'modelo' => '55', 'tipo_operacao' => 'saida', 'origem_arquivo' => 'contribuicoes']);

    $cons = fn (EfdNota $n, float $icms, int $cfop) => DB::table('efd_notas_consolidados')->insert([
        'efd_nota_id' => $n->id, 'user_id' => $this->user->id, 'cfop' => $cfop, 'cst_icms' => '00', 'aliquota_icms' => 18,
        'valor_operacao' => 1000, 'valor_bc_icms' => 1000, 'valor_icms' => $icms, 'valor_bc_icms_st' => 0,
        'valor_icms_st' => 0, 'valor_reducao_bc' => 0, 'valor_ipi' => 0, 'created_at' => now(), 'updated_at' => now(),
    ]);
    $cons($fSaida, 100, 5102);   // débito declarado = 100
    $cons($fEntrada, 20, 1102);  // crédito declarado = 20

    $item = fn (EfdNota $n, array $v) => DB::table('efd_notas_itens')->insert(array_merge([
        'efd_nota_id' => $n->id, 'user_id' => $this->user->id, 'numero_item' => 1, 'codigo_item' => 'X',
        'quantidade' => 1, 'valor_total' => 1000, 'cfop' => 5102, 'valor_icms' => 0, 'valor_pis' => 0,
        'valor_cofins' => 0, 'created_at' => now(), 'updated_at' => now(),
    ], $v));
    $item($fSaida, ['valor_icms' => 0.01, 'valor_pis' => 999, 'valor_cofins' => 999]); // lixo: não pode ser lido
    $item($cSaida, ['valor_pis' => 30, 'valor_cofins' => 90]);                          // débito-saída real

    DB::table('efd_apuracoes_icms')->insert([
        'importacao_id' => $this->impFiscal->id, 'user_id' => $this->user->id, 'cliente_id' => $this->cliente,
        'periodo_inicio' => '2024-01-01', 'periodo_fim' => '2024-01-31',
        'icms_tot_debitos' => 100, 'icms_tot_creditos' => 20, 'icms_a_recolher' => 80, 'st_icms_recolher' => 0,
        'created_at' => now(), 'updated_at' => now(),
    ]);
    DB::table('efd_apuracoes_contribuicoes')->insert([
        'importacao_id' => $this->impContrib->id, 'user_id' => $this->user->id, 'cliente_id' => $this->cliente,
        'pis_nao_cumulativo' => 0, 'pis_cumulativo' => 30, 'pis_total_recolher' => 28, // recolher é líquido de retenção 2
        'pis_retencao_cum' => 2, 'cofins_retencao_cum' => 2,
        'cofins_nao_cumulativo' => 0, 'cofins_cumulativo' => 90, 'cofins_total_recolher' => 88,
        'created_at' => now(), 'updated_at' => now(),
    ]);

    // F600: as MESMAS retenções já deduzidas na apuração (total 4 = pis 2 + cofins 2).
    DB::table('efd_retencoes_fonte')->insert([
        'importacao_id' => $this->impContrib->id, 'user_id' => $this->user->id, 'cliente_id' => $this->cliente,
        'natureza' => '01', 'natureza_receita' => '01', 'data_retencao' => '2024-01-15',
        'base_calculo' => 100, 'cod_receita' => '5952',
        'valor_total' => 4, 'valor_pis' => 2, 'valor_cofins' => 2, 'cnpj' => '00000000000191',
        'created_at' => now(), 'updated_at' => now(),
    ]);

    $this->svc = app(ResumoFiscalService::class);
});

it('ICMS cruzamento usa C190 (não itens fiscais lixo) e não acusa divergência falsa', function () {
    $c = $this->svc->getCruzamentosData($this->user->id, $this->cliente, '2024-01');

    expect($c['icms']['notas_debito'])->toBe(100.0);           // C190, não 0,01 dos itens
    expect($c['icms']['divergencia_debito_pct'])->toBe(0.0);
    expect($c['icms']['status_debito'])->toBe('verde');
    expect($c['icms']['notas_credito'])->toBe(20.0);
    expect($c['icms']['status_credito'])->toBe('verde');
});

it('PIS/COFINS cruzamento compara débito-saída (itens contrib) com devido GROSS', function () {
    $c = $this->svc->getCruzamentosData($this->user->id, $this->cliente, '2024-01');

    expect($c['pis_cofins']['pis_notas'])->toBe(30.0);         // débito-saída, não 999+30 dos itens todos
    expect($c['pis_cofins']['pis_declarado'])->toBe(30.0);     // gross devido (nao_cum 0 + cum 30), não 28 líquido
    expect($c['pis_cofins']['pis_divergencia_pct'])->toBe(0.0);
    expect($c['pis_cofins']['pis_status'])->toBe('verde');
    expect($c['pis_cofins']['cofins_notas'])->toBe(90.0);
    expect($c['pis_cofins']['cofins_declarado'])->toBe(90.0);
    expect($c['pis_cofins']['cofins_status'])->toBe('verde');
});

it('não gera alertas fiscais falsos quando declarado bate com as notas', function () {
    $a = $this->svc->getAlertasFiscaisData($this->user->id, $this->cliente, '2024-01');

    expect($a['resumo']['total'])->toBe(0);
});

it('saldo_liquido não subtrai a retenção em dobro (a_recolher já é líquido)', function () {
    // icms_a_recolher 80 + pis_total_recolher 28 + cofins_total_recolher 88 = 196.
    // Os *_recolher JÁ descontam a retenção (pis 30→28, cofins 90→88). Subtrair
    // a retenção F600 (4) de novo daria 192 — subestima o que se deve pagar.
    $re = $this->svc->getResumoExecutivo($this->user->id, $this->cliente, '2024-01');

    expect($re['kpis']['retencoes_compensaveis']['valor'])->toBe(4.0);
    expect($re['kpis']['saldo_liquido']['valor'])->toBe(196.0);
});
