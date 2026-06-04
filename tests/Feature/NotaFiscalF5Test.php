<?php

use App\Models\EfdImportacao;
use App\Models\EfdNota;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

/**
 * F5 — Notas Fiscais (listagem unificada + KPIs). A MESMA NF-e coexiste em
 * 'fiscal' e 'contribuicoes' (P1): não pode aparecer 2× na lista nem dobrar os
 * KPIs. Tributo vem da fonte certa (ICMS do C190, PIS/COFINS dos itens
 * contribuicoes — P2/P8). Canceladas (P4) fora de tudo.
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
    $cSaida = $mk(['importacao_id' => $impContrib->id, 'chave_acesso' => $a, 'modelo' => '55', 'tipo_operacao' => 'saida', 'origem_arquivo' => 'contribuicoes', 'valor_total' => 1000]); // dup de A
    $nfse = $mk(['importacao_id' => $impContrib->id, 'chave_acesso' => null, 'modelo' => '00', 'tipo_operacao' => 'saida', 'origem_arquivo' => 'contribuicoes', 'valor_total' => 500]);
    $mk(['importacao_id' => $impFiscal->id, 'chave_acesso' => str_pad('E', 44, '0', STR_PAD_LEFT), 'modelo' => '55', 'tipo_operacao' => 'saida', 'origem_arquivo' => 'fiscal', 'valor_total' => 9999, 'cancelada' => true]);
    $fEntrada = $mk(['importacao_id' => $impFiscal->id, 'chave_acesso' => $c, 'modelo' => '55', 'tipo_operacao' => 'entrada', 'origem_arquivo' => 'fiscal', 'valor_total' => 700]);
    $cEntrada = $mk(['importacao_id' => $impContrib->id, 'chave_acesso' => $c, 'modelo' => '55', 'tipo_operacao' => 'entrada', 'origem_arquivo' => 'contribuicoes', 'valor_total' => 700]); // dup de C

    $cons = fn (EfdNota $n, float $icms) => DB::table('efd_notas_consolidados')->insert([
        'efd_nota_id' => $n->id, 'user_id' => $this->user->id, 'cfop' => 5102, 'cst_icms' => '00', 'aliquota_icms' => 18,
        'valor_operacao' => $n->valor_total, 'valor_bc_icms' => 0, 'valor_icms' => $icms, 'valor_bc_icms_st' => 0,
        'valor_icms_st' => 0, 'valor_reducao_bc' => 0, 'valor_ipi' => 0, 'created_at' => now(), 'updated_at' => now(),
    ]);
    $cons($fSaida, 100);    // ICMS débito (C190)
    $cons($fEntrada, 50);   // ICMS crédito (C190)

    $item = fn (EfdNota $n, array $v) => DB::table('efd_notas_itens')->insert(array_merge([
        'efd_nota_id' => $n->id, 'user_id' => $this->user->id, 'numero_item' => 1, 'codigo_item' => 'X',
        'quantidade' => 1, 'valor_total' => 100, 'cfop' => 5102, 'valor_icms' => 0, 'valor_pis' => 0,
        'valor_cofins' => 0, 'created_at' => now(), 'updated_at' => now(),
    ], $v));
    $item($fSaida, ['valor_icms' => 0.01]);                       // item fiscal lixo (ICMS≈0, P2)
    $item($fEntrada, ['valor_pis' => 99, 'valor_cofins' => 99]);  // fiscal entrada lixo — NÃO é crédito PIS/COFINS
    $item($cSaida, ['valor_pis' => 20, 'valor_cofins' => 90]);    // débito real
    $item($nfse, ['valor_pis' => 3, 'valor_cofins' => 14]);       // débito serviço
    $item($cEntrada, ['valor_pis' => 5, 'valor_cofins' => 7]);    // crédito real
});

it('listagem não duplica a mesma NF-e das 2 origens nem lista cancelada', function () {
    $notas = actingAs($this->user)->get('/app/notas')->assertOk()->viewData('notas');

    // A (1×, não 2), NFS-e, C (1×) = 3. Sem a cancelada E.
    expect($notas->total())->toBe(3);

    $chaves = collect($notas->items())->pluck('chave_acesso')->filter()->values();
    expect($chaves->duplicates())->toBeEmpty();         // nenhuma chave repetida
    expect(collect($notas->items())->pluck('valor_total'))->not->toContain(9999.0); // cancelada fora
});

it('KPI de operações não dobra origem e exclui cancelada (P1/P4)', function () {
    $kpis = actingAs($this->user)->get('/app/notas')->assertOk()->viewData('kpis');

    expect($kpis['operacoes']['saidas']['valor'])->toEqual(1500.0);   // 1000 + 500 (não 2500/2000)
    expect($kpis['operacoes']['saidas']['quantidade'])->toBe(2);      // A + NFS-e (cancelada fora)
    expect($kpis['operacoes']['entradas']['valor'])->toEqual(700.0);  // não 1400
});

it('detalhe de NF-e fiscal mostra ICMS do C190, não o item zerado (P2)', function () {
    $impId = EfdNota::where('user_id', $this->user->id)->where('origem_arquivo', 'fiscal')->value('importacao_id');
    $n = EfdNota::create([
        'user_id' => $this->user->id, 'cliente_id' => $this->cliente, 'importacao_id' => $impId,
        'numero' => 7777, 'serie' => '1', 'data_emissao' => '2024-01-20', 'valor_desconto' => 0, 'cancelada' => false,
        'chave_acesso' => str_pad('F', 44, '7', STR_PAD_LEFT), 'modelo' => '55', 'tipo_operacao' => 'saida',
        'origem_arquivo' => 'fiscal', 'valor_total' => 4242,
    ]);
    DB::table('efd_notas_consolidados')->insert([
        'efd_nota_id' => $n->id, 'user_id' => $this->user->id, 'cfop' => 5102, 'cst_icms' => '00', 'aliquota_icms' => 18,
        'valor_operacao' => 4242, 'valor_bc_icms' => 0, 'valor_icms' => 137.77, 'valor_bc_icms_st' => 0,
        'valor_icms_st' => 0, 'valor_reducao_bc' => 0, 'valor_ipi' => 0, 'created_at' => now(), 'updated_at' => now(),
    ]);
    DB::table('efd_notas_itens')->insert([
        'efd_nota_id' => $n->id, 'user_id' => $this->user->id, 'numero_item' => 1, 'codigo_item' => 'Y',
        'quantidade' => 1, 'valor_total' => 88, 'cfop' => 5102, 'valor_icms' => 0.01, 'valor_pis' => 0,
        'valor_cofins' => 0, 'created_at' => now(), 'updated_at' => now(),
    ]);

    $html = actingAs($this->user)->get('/app/notas/efd/'.$n->id)->assertOk()->getContent();

    // O total de tributos do cabeçalho deve refletir o ICMS do C190 (137,77),
    // não o item fiscal zerado (0,01). Sem o C190 carregado, "137,77" não existiria.
    expect($html)->toContain('137,77');
});

it('KPI de tributos lê ICMS do C190 e PIS/COFINS só de contribuicoes (P2/P8)', function () {
    $kpis = actingAs($this->user)->get('/app/notas')->assertOk()->viewData('kpis');
    $t = $kpis['tributos'];

    expect($t['icms']['debito'])->toEqual(100.0);   // C190 saída, não o item 0,01
    expect($t['icms']['credito'])->toEqual(50.0);   // C190 entrada
    expect($t['pis']['credito'])->toEqual(5.0);     // contrib entrada — não os 99 do item fiscal
    expect($t['cofins']['credito'])->toEqual(7.0);  // não 99
    expect($t['pis']['debito'])->toEqual(23.0);     // 20 + 3
    expect($t['cofins']['debito'])->toEqual(104.0); // 90 + 14
});

/**
 * Navegação SPA (data-link → fetch com X-Requested-With) para o detalhe da nota
 * deve servir a PÁGINA CHEIA (efd-nota), igual ao reload direto da URL — e não o
 * card compacto de drill-down (efd-inline). Sem isso, ir de /app/alertas/{id}
 * para /app/notas/efd/{id} via SPA renderiza o mini-card no #app.
 */
it('navegação SPA do detalhe serve a página cheia, não o card inline (efd)', function () {
    $n = EfdNota::where('user_id', $this->user->id)->where('origem_arquivo', 'fiscal')->first();

    $html = actingAs($this->user)
        ->get('/app/notas/efd/'.$n->id, ['X-Requested-With' => 'XMLHttpRequest'])
        ->assertOk()->getContent();

    // Marcador exclusivo da página cheia (efd-nota), ausente no card inline.
    expect($html)->toContain('Voltar para Notas Fiscais');
    expect($html)->not->toContain('Ver detalhes completos');
});

/**
 * O drill-down inline da listagem sinaliza via header X-Nota-Detalhe: inline e
 * continua recebendo o card compacto (efd-inline) para embutir na linha da tabela.
 */
it('drill-down inline da listagem serve o card compacto (efd)', function () {
    $n = EfdNota::where('user_id', $this->user->id)->where('origem_arquivo', 'fiscal')->first();

    $html = actingAs($this->user)
        ->get('/app/notas/efd/'.$n->id, [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-Nota-Detalhe' => 'inline',
        ])
        ->assertOk()->getContent();

    // Marcador exclusivo do card inline; a página cheia não tem este link.
    expect($html)->toContain('Ver detalhes completos');
    expect($html)->not->toContain('Voltar para Notas Fiscais');
});
