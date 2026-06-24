<?php

use App\Models\EfdImportacao;
use App\Models\EfdNota;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

/**
 * F4 — Catálogo (`CatalogoController`). Perfil comercial (B): os itens da EFD
 * ICMS/IPI (C170 fiscal) NÃO carregam alíquota de ICMS (P2) — vêm 0. Cruzar a
 * alíquota do catálogo contra esses zeros gera "divergência" e "média" falsas.
 * Notas canceladas (P4) não podem entrar na movimentação.
 *
 * Importante (medido na massa real): itens fiscais e de contribuicoes são
 * DISJUNTOS por chave — a MESMA NF-e nunca detalha C170 nas duas origens. Logo
 * NÃO se aplica dedup de origem ao somar movimentação (subtrairia itens reais).
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

    $cat = fn (string $cod, float $aliq) => DB::table('efd_catalogo_itens')->insert([
        'user_id' => $this->user->id, 'cliente_id' => $this->cliente, 'importacao_id' => $impFiscal->id,
        'cod_item' => $cod, 'descr_item' => "Produto {$cod}", 'tipo_item' => '00', 'cod_ncm' => '12345678',
        'aliq_icms' => $aliq, 'unid_inv' => 'UN', 'created_at' => now(), 'updated_at' => now(),
    ]);
    $cat('P1', 18);  // catálogo 18%
    $cat('P2', 18);  // catálogo 18%
    $cat('P3', 18);  // catálogo 18% — só movimenta em nota cancelada

    $mk = fn (string $chave, string $origem, int $imp, bool $cancelada = false) => EfdNota::create([
        'user_id' => $this->user->id, 'cliente_id' => $this->cliente, 'importacao_id' => $imp,
        'numero' => random_int(1, 99999), 'serie' => '1', 'data_emissao' => '2024-01-15',
        'valor_desconto' => 0, 'cancelada' => $cancelada, 'chave_acesso' => str_pad($chave, 44, '0', STR_PAD_LEFT),
        'modelo' => '55', 'tipo_operacao' => 'saida', 'origem_arquivo' => $origem, 'valor_total' => 100,
    ]);
    $item = fn (EfdNota $n, string $cod, float $aliq, float $valor) => DB::table('efd_notas_itens')->insert([
        'efd_nota_id' => $n->id, 'user_id' => $this->user->id, 'numero_item' => 1, 'codigo_item' => $cod,
        'quantidade' => 1, 'valor_total' => $valor, 'cfop' => 5102, 'cst_icms' => '00',
        'valor_icms' => 0, 'aliquota_icms' => $aliq, 'created_at' => now(), 'updated_at' => now(),
    ]);

    // P1: aparece na origem fiscal (aliq=0, artefato perfil B) E na contribuicoes (aliq=18 real) — chaves distintas
    $item($mk('A', 'fiscal', $impFiscal->id), 'P1', 0, 100);
    $item($mk('B', 'contribuicoes', $impContrib->id), 'P1', 18, 100);
    // P2: contribuicoes aliq=12 — divergência REAL contra catálogo 18
    $item($mk('C', 'contribuicoes', $impContrib->id), 'P2', 12, 50);
    // P3: só numa nota CANCELADA — não pode contar movimentação
    $item($mk('D', 'contribuicoes', $impContrib->id, true), 'P3', 18, 999);
});

it('aliq_divergente ignora itens de alíquota zero (perfil B, P2)', function () {
    $kpis = actingAs($this->user)->get('/app/catalogo')->assertOk()->viewData('kpis');

    // Só P2 (12 vs 18) é divergência real. P1 NÃO diverge: o 18 real existe na
    // contribuicoes; o 0 fiscal é artefato. O catálogo bugado contaria 2.
    expect($kpis['aliq_divergente'])->toBe(1);
});

it('alíquota média das notas exclui os zeros artefato (P2)', function () {
    $itens = actingAs($this->user)->get('/app/catalogo')->assertOk()->viewData('itens');

    $p1 = $itens->firstWhere('cod_item', 'P1');
    // média deve ser 18 (só o item real), não (0+18)/2 = 9
    expect(round((float) $p1->aliq_icms_media_notas, 2))->toBe(18.0);
});

it('valor_movimentado e com_movimentacao excluem notas canceladas (P4)', function () {
    $kpis = actingAs($this->user)->get('/app/catalogo')->assertOk()->viewData('kpis');

    // P1 (100 fiscal + 100 contrib) + P2 (50) = 250. P3 cancelada (999) fora.
    expect((float) $kpis['valor_movimentado'])->toBe(250.0);
    expect($kpis['com_movimentacao'])->toBe(2);     // P1, P2 — P3 cancelada não conta
    expect($kpis['sem_movimentacao'])->toBe(1);     // P3
});

it('não deduplica origem na movimentação (itens fiscal e contrib são disjuntos por chave)', function () {
    $itens = actingAs($this->user)->get('/app/catalogo')->assertOk()->viewData('itens');

    // P1 movimenta nas DUAS origens em chaves distintas → 200, não 100
    $p1 = $itens->firstWhere('cod_item', 'P1');
    expect((float) $p1->valor_movimentado)->toBe(200.0);
});

it('filtra o catálogo por CFOP da movimentação (nota não cancelada) e expõe a faceta', function () {
    // faceta: CFOP/CST vêm da movimentação não cancelada
    $resp = actingAs($this->user)->get('/app/catalogo')->assertOk();
    $facetas = $resp->viewData('facetas');
    expect($facetas['cfops'])->toContain('5102');
    expect($facetas['csts'])->toContain('00');

    // cfop 5102 → produtos com movimentação real (P1, P2). P3 só em nota cancelada → fora.
    $itens = actingAs($this->user)->get('/app/catalogo?cfops[]=5102')->assertOk()->viewData('itens');
    $cods = $itens->pluck('cod_item')->all();
    expect($cods)->toContain('P1')->toContain('P2');
    expect($cods)->not->toContain('P3');

    // CFOP inexistente → tabela vazia
    $vazio = actingAs($this->user)->get('/app/catalogo?cfops[]=9999')->assertOk()->viewData('itens');
    expect($vazio)->toHaveCount(0);
});

it('drill-down historico é roteado e exclui notas canceladas (P4)', function () {
    // P3 só movimenta numa nota CANCELADA (999) → não pode aparecer nos números do detalhe
    $html = actingAs($this->user)
        ->get('/app/catalogo/historico/P3?cliente_id='.$this->cliente)
        ->assertOk()
        ->getContent();

    expect($html)->not->toContain('999');
});
