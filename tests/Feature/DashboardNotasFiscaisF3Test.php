<?php

use App\Models\EfdImportacao;
use App\Models\EfdNota;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

/**
 * F3 — Dashboard de Notas Fiscais. As abas de VALOR de nota (Visão Geral,
 * Participantes, Compliance) não podem dobrar a mesma NF-e que existe nas 2
 * origens (P1) nem contar canceladas (P4). Quando o filtro tipo_efd é específico,
 * a origem já restringe → sem dedup.
 */
beforeEach(function () {
    $this->user = User::factory()->create();
    $this->cliente = DB::table('clientes')->insertGetId([
        'user_id' => $this->user->id, 'razao_social' => 'EMPRESA TESTE',
        'documento' => '00000000000100', 'is_empresa_propria' => true,
        'created_at' => now(), 'updated_at' => now(),
    ]);
    $impFiscal = EfdImportacao::create([
        'user_id' => $this->user->id, 'cliente_id' => $this->cliente,
        'tipo_efd' => 'EFD ICMS/IPI', 'filename' => 'i.txt', 'status' => 'concluido', 'iniciado_em' => now(),
    ]);
    $impContrib = EfdImportacao::create([
        'user_id' => $this->user->id, 'cliente_id' => $this->cliente,
        'tipo_efd' => 'EFD PIS/COFINS', 'filename' => 'p.txt', 'status' => 'concluido', 'iniciado_em' => now(),
    ]);

    $mk = fn (array $a) => EfdNota::create(array_merge([
        'user_id' => $this->user->id, 'cliente_id' => $this->cliente, 'numero' => random_int(1, 99999),
        'serie' => '1', 'data_emissao' => '2024-01-15', 'valor_desconto' => 0, 'cancelada' => false,
    ], $a));

    $a = str_pad('A', 44, '0', STR_PAD_LEFT);
    $c = str_pad('C', 44, '0', STR_PAD_LEFT);
    $fSaida = $mk(['importacao_id' => $impFiscal->id, 'chave_acesso' => $a, 'modelo' => '55', 'tipo_operacao' => 'saida', 'origem_arquivo' => 'fiscal', 'valor_total' => 1000]);
    $cSaida = $mk(['importacao_id' => $impContrib->id, 'chave_acesso' => $a, 'modelo' => '55', 'tipo_operacao' => 'saida', 'origem_arquivo' => 'contribuicoes', 'valor_total' => 1000]); // dup
    $nfse = $mk(['importacao_id' => $impContrib->id, 'chave_acesso' => null, 'modelo' => '00', 'tipo_operacao' => 'saida', 'origem_arquivo' => 'contribuicoes', 'valor_total' => 500]); // NFS-e
    $mk(['importacao_id' => $impFiscal->id, 'chave_acesso' => str_pad('E', 44, '0', STR_PAD_LEFT), 'modelo' => '55', 'tipo_operacao' => 'saida', 'origem_arquivo' => 'fiscal', 'valor_total' => 0, 'cancelada' => true]);
    $fEntrada = $mk(['importacao_id' => $impFiscal->id, 'chave_acesso' => $c, 'modelo' => '55', 'tipo_operacao' => 'entrada', 'origem_arquivo' => 'fiscal', 'valor_total' => 700]);
    $cEntrada = $mk(['importacao_id' => $impContrib->id, 'chave_acesso' => $c, 'modelo' => '55', 'tipo_operacao' => 'entrada', 'origem_arquivo' => 'contribuicoes', 'valor_total' => 700]); // dup

    // ICMS verdadeiro no C190 (fiscal); itens fiscais carregam PIS/COFINS irrelevante.
    $cons = fn (EfdNota $n, float $icms) => DB::table('efd_notas_consolidados')->insert([
        'efd_nota_id' => $n->id, 'user_id' => $this->user->id, 'cfop' => 5102, 'cst_icms' => '00', 'aliquota_icms' => 18,
        'valor_operacao' => $n->valor_total, 'valor_bc_icms' => 0, 'valor_icms' => $icms, 'valor_bc_icms_st' => 0,
        'valor_icms_st' => 0, 'valor_reducao_bc' => 0, 'valor_ipi' => 0, 'created_at' => now(), 'updated_at' => now(),
    ]);
    $cons($fSaida, 100);    // ICMS débito
    $cons($fEntrada, 50);   // ICMS crédito
    $item = fn (EfdNota $n, array $v) => DB::table('efd_notas_itens')->insert(array_merge([
        'efd_nota_id' => $n->id, 'user_id' => $this->user->id, 'numero_item' => 1, 'codigo_item' => 'X',
        'quantidade' => 1, 'valor_total' => 100, 'cfop' => 5102, 'valor_icms' => 0, 'valor_pis' => 0,
        'valor_cofins' => 0, 'created_at' => now(), 'updated_at' => now(),
    ], $v));
    $item($fSaida, ['valor_icms' => 0.01]);                        // fiscal lixo
    $item($fEntrada, ['valor_pis' => 99, 'valor_cofins' => 99]);   // fiscal entrada lixo (NÃO é crédito PIS/COFINS)
    $item($cSaida, ['valor_pis' => 20, 'valor_cofins' => 90]);     // débito
    $item($nfse, ['valor_pis' => 3, 'valor_cofins' => 14]);        // débito serviço
    $item($cEntrada, ['valor_pis' => 5, 'valor_cofins' => 7]);     // crédito real
});

it('visão geral não dobra origem nem conta cancelada (tipo_efd=todos)', function () {
    $r = actingAs($this->user)
        ->getJson('/app/notas/dashboard/visao-geral?periodo_inicio=2024-01&periodo_fim=2024-01')
        ->assertOk()
        ->json();

    expect($r['kpis']['total_notas'])->toBe(3);                  // A + NFS-e + C; sem dups, sem cancelada
    expect($r['kpis']['valor_saidas'])->toEqual(1500.0);            // 1000 + 500 (não 2500)
    expect($r['kpis']['valor_entradas'])->toEqual(700.0);          // não 1400

    $m55 = collect($r['por_modelo'])->firstWhere('modelo', '55');
    expect($m55['valor_total'])->toEqual(1700.0);                   // A 1000 + C 700, sem dobra
});

it('visão geral exclui notas de CFOP fora-faturamento (base comercial = BI)', function () {
    // Nota de SAÍDA com CFOP fora-faturamento (5916 = remessa, não compõe
    // faturamento). Deve sair do valor/contagem da visão geral, igual ao BI.
    $imp = EfdImportacao::where('user_id', $this->user->id)->where('tipo_efd', 'EFD ICMS/IPI')->first();
    $forada = EfdNota::create([
        'user_id' => $this->user->id, 'cliente_id' => $this->cliente, 'numero' => 88888, 'serie' => '1',
        'data_emissao' => '2024-01-20', 'valor_desconto' => 0, 'cancelada' => false, 'valor_total' => 9999,
        'importacao_id' => $imp->id, 'chave_acesso' => str_pad('F', 44, '0', STR_PAD_LEFT),
        'modelo' => '55', 'tipo_operacao' => 'saida', 'origem_arquivo' => 'fiscal',
    ]);
    DB::table('efd_notas_consolidados')->insert([
        'efd_nota_id' => $forada->id, 'user_id' => $this->user->id, 'cfop' => 5916, 'cst_icms' => '00',
        'aliquota_icms' => 0, 'valor_operacao' => 9999, 'valor_bc_icms' => 0, 'valor_icms' => 0,
        'valor_bc_icms_st' => 0, 'valor_icms_st' => 0, 'valor_reducao_bc' => 0, 'valor_ipi' => 0,
        'created_at' => now(), 'updated_at' => now(),
    ]);

    $r = actingAs($this->user)
        ->getJson('/app/notas/dashboard/visao-geral?periodo_inicio=2024-01&periodo_fim=2024-01')
        ->assertOk()
        ->json();

    // 9999 NÃO entra: saídas seguem 1500, total segue 3 (igual ao teste base).
    expect($r['kpis']['valor_saidas'])->toEqual(1500.0);
    expect($r['kpis']['total_notas'])->toBe(3);
});

it('tributário: ICMS do C190 e PIS/COFINS só de contribuicoes (não dos itens fiscais lixo)', function () {
    $r = actingAs($this->user)
        ->getJson('/app/notas/dashboard/tributario?periodo_inicio=2024-01&periodo_fim=2024-01')
        ->assertOk()
        ->json();

    expect($r['saldos']['icms']['debito'])->toEqual(100.0);   // C190 saída, não itens (0,01)
    expect($r['saldos']['icms']['credito'])->toEqual(50.0);   // C190 entrada
    expect($r['saldos']['pis']['debito'])->toEqual(23.0);     // contrib saída (20+3)
    expect($r['saldos']['pis']['credito'])->toEqual(5.0);     // contrib entrada — não os 99 do item fiscal
    expect($r['saldos']['cofins']['debito'])->toEqual(104.0); // 90+14
    expect($r['saldos']['cofins']['credito'])->toEqual(7.0);  // não 99
});

it('CFOP usa o consolidado C190 (valor_operacao), não os itens esparsos', function () {
    $r = actingAs($this->user)
        ->getJson('/app/notas/dashboard/cfop?periodo_inicio=2024-01&periodo_fim=2024-01')
        ->assertOk()
        ->json();

    $cfop = collect($r['cfops'])->firstWhere('cfop', 5102);
    // C190: fSaida 1000 + fEntrada 700 = 1700 (não a soma dos itens valor_total=100)
    expect($cfop['valor_total'])->toEqual(1700.0);
});

it('com tipo_efd específico (PIS/COFINS) mostra aquela origem sem dedup', function () {
    $r = actingAs($this->user)
        ->getJson('/app/notas/dashboard/visao-geral?periodo_inicio=2024-01&periodo_fim=2024-01&tipo_efd='.urlencode('EFD PIS/COFINS'))
        ->assertOk()
        ->json();

    // contribuicoes: A(1000) + NFS-e(500) saída + C(700) entrada = 3 notas
    expect($r['kpis']['total_notas'])->toBe(3);
    expect($r['kpis']['valor_saidas'])->toEqual(1500.0);
    expect($r['kpis']['valor_entradas'])->toEqual(700.0);
});
