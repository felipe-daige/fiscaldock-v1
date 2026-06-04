<?php

use App\Models\EfdImportacao;
use App\Models\EfdNota;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

/**
 * Detalhe da nota nunca fica "vazio": a saída fiscal escriturada só por C190 (sem C170)
 * mostra os produtos da gêmea de contribuicoes (mesma chave) — cruzados com o catálogo,
 * com divergência de alíquota sinalizada.
 */
beforeEach(function () {
    $this->user = User::factory()->create();
    $this->cliente = DB::table('clientes')->insertGetId([
        'user_id' => $this->user->id, 'razao_social' => 'EMPRESA', 'documento' => '00000000000100',
        'is_empresa_propria' => true, 'created_at' => now(), 'updated_at' => now(),
    ]);
    $impF = EfdImportacao::create(['user_id' => $this->user->id, 'cliente_id' => $this->cliente, 'tipo_efd' => 'EFD ICMS/IPI', 'filename' => 'i.txt', 'status' => 'concluido', 'iniciado_em' => now()]);
    $impC = EfdImportacao::create(['user_id' => $this->user->id, 'cliente_id' => $this->cliente, 'tipo_efd' => 'EFD PIS/COFINS', 'filename' => 'p.txt', 'status' => 'concluido', 'iniciado_em' => now()]);

    DB::table('efd_catalogo_itens')->insert([
        'user_id' => $this->user->id, 'cliente_id' => $this->cliente, 'importacao_id' => $impF->id,
        'cod_item' => 'X', 'descr_item' => 'PRODUTO CATALOGO', 'tipo_item' => '00', 'cod_ncm' => '99887766',
        'aliq_icms' => 18, 'unid_inv' => 'UN', 'created_at' => now(), 'updated_at' => now(),
    ]);

    $chave = str_pad('A', 44, '0', STR_PAD_LEFT);
    // Saída FISCAL só com C190 (sem C170)
    $this->fiscal = EfdNota::create([
        'user_id' => $this->user->id, 'cliente_id' => $this->cliente, 'importacao_id' => $impF->id,
        'numero' => 1, 'serie' => '1', 'data_emissao' => '2024-01-15', 'valor_desconto' => 0, 'cancelada' => false,
        'chave_acesso' => $chave, 'modelo' => '55', 'tipo_operacao' => 'saida', 'origem_arquivo' => 'fiscal', 'valor_total' => 100,
    ]);
    DB::table('efd_notas_consolidados')->insert([
        'efd_nota_id' => $this->fiscal->id, 'user_id' => $this->user->id, 'cfop' => 5102, 'cst_icms' => '00', 'aliquota_icms' => 18,
        'valor_operacao' => 100, 'valor_bc_icms' => 0, 'valor_icms' => 18, 'valor_bc_icms_st' => 0,
        'valor_icms_st' => 0, 'valor_reducao_bc' => 0, 'valor_ipi' => 0, 'created_at' => now(), 'updated_at' => now(),
    ]);
    // Gêmea CONTRIBUICOES com o item detalhado (alíquota 12 — divergente do catálogo 18)
    $contrib = EfdNota::create([
        'user_id' => $this->user->id, 'cliente_id' => $this->cliente, 'importacao_id' => $impC->id,
        'numero' => 1, 'serie' => '1', 'data_emissao' => '2024-01-15', 'valor_desconto' => 0, 'cancelada' => false,
        'chave_acesso' => $chave, 'modelo' => '55', 'tipo_operacao' => 'saida', 'origem_arquivo' => 'contribuicoes', 'valor_total' => 100,
    ]);
    DB::table('efd_notas_itens')->insert([
        'efd_nota_id' => $contrib->id, 'user_id' => $this->user->id, 'numero_item' => 1, 'codigo_item' => 'X',
        'descricao' => 'item do produto', 'quantidade' => 1, 'valor_total' => 100, 'cfop' => 5102,
        'aliquota_icms' => 12, 'valor_pis' => 1, 'valor_cofins' => 5, 'created_at' => now(), 'updated_at' => now(),
    ]);
});

it('itensDetalhe faz fallback pros itens da gêmea de contribuicoes quando a fiscal só tem C190', function () {
    expect($this->fiscal->itens)->toHaveCount(0);          // a fiscal não tem C170 próprio
    expect($this->fiscal->itensDetalhe())->toHaveCount(1); // mas exibe o item da gêmea
    expect($this->fiscal->itensViaTwin())->toBeTrue();
    expect($this->fiscal->catalogoPorItem())->toHaveKey('X'); // catálogo cobre o item da gêmea
});

it('o detalhe da saída fiscal mostra o produto (via gêmea) cruzado com o catálogo', function () {
    $html = actingAs($this->user)->get('/app/notas/efd/'.$this->fiscal->id)->assertOk()->getContent();

    expect($html)->toContain('99887766');         // NCM do catálogo
    expect($html)->toContain('item do produto');  // descrição do item da gêmea
});
