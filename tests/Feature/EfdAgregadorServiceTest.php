<?php

use App\Models\EfdImportacao;
use App\Models\EfdNota;
use App\Models\User;
use App\Services\EfdAgregadorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

/**
 * Cobre as queries canônicas Q-MOV (faturamento) e Q-CARGA (carga tributária)
 * do consumo de EFD. Cenário sintético reproduz as peculiaridades reais:
 *  P1 — mesma NF-e (chave A) escriturada em fiscal E contribuicoes (não dobrar)
 *  P4 — nota cancelada (não somar)
 *  P5 — entrada × saída (faturamento = só saída)
 *  P7 — NFS-e (modelo 00) sem chave, só em contribuicoes (é receita, somar)
 *  Edge — NF-e órfã que só existe em contribuicoes (somar, não tem gêmea fiscal)
 *  P2 — ICMS dos itens FISCAIS é ~0 (perfil B usa C190); ICMS verdadeiro vem do C190
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

    $this->impFiscal = EfdImportacao::create([
        'user_id' => $this->user->id, 'cliente_id' => $this->cliente,
        'tipo_efd' => 'EFD ICMS/IPI', 'filename' => 'icms.txt',
        'status' => 'concluido', 'iniciado_em' => now()->subMinutes(2),
    ]);
    $this->impContrib = EfdImportacao::create([
        'user_id' => $this->user->id, 'cliente_id' => $this->cliente,
        'tipo_efd' => 'EFD PIS/COFINS', 'filename' => 'pc.txt',
        'status' => 'concluido', 'iniciado_em' => now()->subMinutes(2),
    ]);

    $chaveA = str_pad('A', 44, '0', STR_PAD_LEFT);
    $chaveC = str_pad('C', 44, '0', STR_PAD_LEFT);
    $chaveD = str_pad('D', 44, '0', STR_PAD_LEFT);

    $mk = function (array $attrs): EfdNota {
        return EfdNota::create(array_merge([
            'user_id' => $this->user->id, 'cliente_id' => $this->cliente,
            'numero' => random_int(1, 99999), 'serie' => '1',
            'data_emissao' => '2024-01-15', 'valor_desconto' => 0, 'cancelada' => false,
        ], $attrs));
    };

    // (1) FISCAL saída NF-e chave A, R$ 1000 — base de faturamento
    $this->fSaida = $mk([
        'importacao_id' => $this->impFiscal->id, 'chave_acesso' => $chaveA, 'modelo' => '55',
        'tipo_operacao' => 'saida', 'origem_arquivo' => 'fiscal', 'valor_total' => 1000,
    ]);
    // (2) CONTRIB saída NF-e chave A (mesma nota, P1) R$ 1000 — NÃO deve somar em faturamento
    $this->cDup = $mk([
        'importacao_id' => $this->impContrib->id, 'chave_acesso' => $chaveA, 'modelo' => '55',
        'tipo_operacao' => 'saida', 'origem_arquivo' => 'contribuicoes', 'valor_total' => 1000,
    ]);
    // (3) CONTRIB saída NFS-e (modelo 00) sem chave, R$ 500 — serviços, DEVE somar
    $this->cNfse = $mk([
        'importacao_id' => $this->impContrib->id, 'chave_acesso' => null, 'modelo' => '00',
        'tipo_operacao' => 'saida', 'origem_arquivo' => 'contribuicoes', 'valor_total' => 500,
    ]);
    // (4) CONTRIB saída NF-e órfã chave D (sem gêmea fiscal) R$ 300 — DEVE somar
    $this->cOrfa = $mk([
        'importacao_id' => $this->impContrib->id, 'chave_acesso' => $chaveD, 'modelo' => '55',
        'tipo_operacao' => 'saida', 'origem_arquivo' => 'contribuicoes', 'valor_total' => 300,
    ]);
    // (5) FISCAL saída cancelada R$ 9999 — NÃO somar (P4)
    $this->fCanc = $mk([
        'importacao_id' => $this->impFiscal->id, 'chave_acesso' => str_pad('E', 44, '0', STR_PAD_LEFT),
        'modelo' => '55', 'tipo_operacao' => 'saida', 'origem_arquivo' => 'fiscal',
        'valor_total' => 9999, 'cancelada' => true,
    ]);
    // (6) FISCAL entrada NF-e chave C R$ 700 — entra só no faturamento(entrada)
    $this->fEntrada = $mk([
        'importacao_id' => $this->impFiscal->id, 'chave_acesso' => $chaveC, 'modelo' => '55',
        'tipo_operacao' => 'entrada', 'origem_arquivo' => 'fiscal', 'valor_total' => 700,
    ]);

    // C190 (consolidado fiscal) — ICMS verdadeiro
    $cons = function (EfdNota $n, array $vals) {
        DB::table('efd_notas_consolidados')->insert(array_merge([
            'efd_nota_id' => $n->id, 'user_id' => $this->user->id, 'cfop' => 5102,
            'cst_icms' => '00', 'aliquota_icms' => 0,
            'valor_operacao' => 0, 'valor_bc_icms' => 0, 'valor_icms' => 0,
            'valor_bc_icms_st' => 0, 'valor_icms_st' => 0, 'valor_reducao_bc' => 0, 'valor_ipi' => 0,
            'created_at' => now(), 'updated_at' => now(),
        ], $vals));
    };
    $cons($this->fSaida, ['valor_icms' => 100, 'valor_icms_st' => 10, 'valor_ipi' => 5]); // débito saída
    $cons($this->fEntrada, ['valor_icms' => 50, 'cfop' => 1102]);                          // crédito entrada (NÃO é débito)

    // Itens: fiscais com ICMS ~0 (P2); contribuicoes carregam PIS/COFINS
    $item = function (EfdNota $n, array $vals) {
        DB::table('efd_notas_itens')->insert(array_merge([
            'efd_nota_id' => $n->id, 'user_id' => $this->user->id, 'numero_item' => 1,
            'codigo_item' => 'X', 'quantidade' => 1, 'valor_total' => 0, 'cfop' => 5102,
            'valor_icms' => 0, 'valor_pis' => 0, 'valor_cofins' => 0,
            'created_at' => now(), 'updated_at' => now(),
        ], $vals));
    };
    $item($this->fSaida, ['valor_icms' => 0.01]); // perfil B: ICMS no item fiscal é lixo
    $item($this->cDup, ['valor_pis' => 20, 'valor_cofins' => 90]);   // débito PIS/COFINS saída
    $item($this->cNfse, ['valor_pis' => 3, 'valor_cofins' => 14]);   // serviço
    // contribuicoes ENTRADA (crédito) — não deve entrar em débito-saída
    $cEntrada = $mk([
        'importacao_id' => $this->impContrib->id, 'chave_acesso' => $chaveC, 'modelo' => '55',
        'tipo_operacao' => 'entrada', 'origem_arquivo' => 'contribuicoes', 'valor_total' => 700,
    ]);
    $item($cEntrada, ['valor_pis' => 99, 'valor_cofins' => 99]);

    // Apuração declarada (gold) — a recolher
    DB::table('efd_apuracoes_icms')->insert([
        'importacao_id' => $this->impFiscal->id, 'user_id' => $this->user->id, 'cliente_id' => $this->cliente,
        'periodo_inicio' => '2024-01-01', 'periodo_fim' => '2024-01-31',
        'icms_tot_debitos' => 115, 'icms_tot_creditos' => 35, 'icms_a_recolher' => 80,
        'st_icms_recolher' => 0, 'created_at' => now(), 'updated_at' => now(),
    ]);
    DB::table('efd_apuracoes_contribuicoes')->insert([
        'importacao_id' => $this->impContrib->id, 'user_id' => $this->user->id, 'cliente_id' => $this->cliente,
        'pis_total_recolher' => 20, 'cofins_total_recolher' => 90,
        'created_at' => now(), 'updated_at' => now(),
    ]);

    $this->svc = app(EfdAgregadorService::class);
});

it('notasDedup: base reusável já deduplicada por origem, sem cancelada', function () {
    // saída: fiscal A + NFS-e + órfã D = 3 (exclui dup A contrib, cancelada, entrada)
    expect($this->svc->notasDedup($this->user->id, 'saida')->count())->toBe(3);
    // sem filtro de tipo: as 3 saídas + 1 entrada fiscal = 4 (entrada contrib é gêmea → fora)
    expect($this->svc->notasDedup($this->user->id)->count())->toBe(4);
    // soma de valor pela base reusável bate com faturamento
    expect((float) $this->svc->notasDedup($this->user->id, 'saida')->sum('n.valor_total'))->toBe(1800.0);
});

it('Q-MOV: faturamento de saída deduplica origem, inclui NFS-e, exclui cancelada/entrada', function () {
    // 1000 (fiscal A) + 500 (NFS-e) + 300 (órfã D); exclui dup A contrib, cancelada, entrada
    expect($this->svc->faturamento($this->user->id, 'saida'))->toBe(1800.0);
});

it('Q-MOV: faturamento de entrada usa base fiscal e ignora duplicata de contribuicoes', function () {
    // só a entrada fiscal R$ 700; a entrada contribuicoes (chave C) é gêmea → descartada
    expect($this->svc->faturamento($this->user->id, 'entrada'))->toBe(700.0);
});

it('Q-MOV: respeita filtro de cliente e período', function () {
    expect($this->svc->faturamento($this->user->id, 'saida', '2024-01-01', '2024-01-31', $this->cliente))->toBe(1800.0);
    expect($this->svc->faturamento($this->user->id, 'saida', '2024-02-01', '2024-02-28'))->toBe(0.0);
});

it('Q-CARGA débito-saída: ICMS/ST/IPI do C190 saída; PIS/COFINS dos itens contribuicoes saída', function () {
    $c = $this->svc->cargaTributariaDebitoSaida($this->user->id);
    expect($c['icms'])->toBe(100.0);      // C190 saída (não os itens fiscais lixo 0.01, nem entrada 50)
    expect($c['icms_st'])->toBe(10.0);
    expect($c['ipi'])->toBe(5.0);
    expect($c['pis'])->toBe(23.0);        // 20 (dup A) + 3 (NFS-e); ignora entrada 99
    expect($c['cofins'])->toBe(104.0);    // 90 + 14
    expect($c['total'])->toBe(242.0);
});

it('Q-CARGA débito-saída mensal: agrupa por mês de emissão', function () {
    // todas as notas-saída do cenário são 2024-01 → um único mês com os mesmos totais
    $rows = $this->svc->cargaTributariaDebitoSaidaMensal($this->user->id);
    expect($rows)->toHaveCount(1);
    expect($rows[0]['icms'])->toBe(100.0);
    expect($rows[0]['pis'])->toBe(23.0);
    expect($rows[0]['cofins'])->toBe(104.0);
    expect($rows[0]['total'])->toBe(242.0);
});

it('tributarioCreditoDebito: ICMS do C190, PIS/COFINS dos itens contribuicoes, split entrada/saída', function () {
    $t = $this->svc->tributarioCreditoDebito($this->user->id);
    expect($t['icms']['debito'])->toBe(100.0);   // C190 saída
    expect($t['icms']['credito'])->toBe(50.0);    // C190 entrada (não os itens fiscais lixo)
    expect($t['pis']['debito'])->toBe(23.0);      // contrib itens saída (20+3)
    expect($t['pis']['credito'])->toBe(99.0);     // contrib itens entrada
    expect($t['cofins']['debito'])->toBe(104.0);  // 90+14
    expect($t['cofins']['credito'])->toBe(99.0);
});

it('Q-CARGA apurada: lê o a-recolher declarado nas apurações (gold)', function () {
    $c = $this->svc->cargaTributariaApurada($this->user->id);
    expect($c['icms'])->toBe(80.0);
    expect($c['icms_st'])->toBe(0.0);
    expect($c['pis'])->toBe(20.0);
    expect($c['cofins'])->toBe(90.0);
    expect($c['total'])->toBe(190.0);
});
