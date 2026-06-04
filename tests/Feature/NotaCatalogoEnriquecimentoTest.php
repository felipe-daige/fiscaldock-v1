<?php

use App\Models\EfdImportacao;
use App\Models\EfdNota;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

/**
 * Detalhe da nota EFD enriquecido com o catálogo (0200): cada item exibe o NCM/
 * cadastro do catálogo, casado por codigo_item, preferindo a MESMA importação.
 */
beforeEach(function () {
    $this->user = User::factory()->create();
    $this->cliente = DB::table('clientes')->insertGetId([
        'user_id' => $this->user->id, 'razao_social' => 'EMPRESA', 'documento' => '00000000000100',
        'is_empresa_propria' => true, 'created_at' => now(), 'updated_at' => now(),
    ]);
    $this->impF = EfdImportacao::create(['user_id' => $this->user->id, 'cliente_id' => $this->cliente, 'tipo_efd' => 'EFD ICMS/IPI', 'filename' => 'jan.txt', 'status' => 'concluido', 'iniciado_em' => now()]);
    $this->impF2 = EfdImportacao::create(['user_id' => $this->user->id, 'cliente_id' => $this->cliente, 'tipo_efd' => 'EFD ICMS/IPI', 'filename' => 'fev.txt', 'status' => 'concluido', 'iniciado_em' => now()]);

    // Catálogo é único por (cliente_id, cod_item) → 1 versão por item.
    DB::table('efd_catalogo_itens')->insert([
        'user_id' => $this->user->id, 'cliente_id' => $this->cliente, 'importacao_id' => $this->impF->id,
        'cod_item' => 'X', 'descr_item' => 'PRODUTO CATALOGO', 'tipo_item' => '00', 'cod_ncm' => '99887766',
        'aliq_icms' => 18, 'unid_inv' => 'UN', 'created_at' => now(), 'updated_at' => now(),
    ]);

    $this->nota = EfdNota::create([
        'user_id' => $this->user->id, 'cliente_id' => $this->cliente, 'importacao_id' => $this->impF->id,
        'numero' => 1, 'serie' => '1', 'data_emissao' => '2024-01-15', 'valor_desconto' => 0, 'cancelada' => false,
        'chave_acesso' => str_pad('A', 44, '0', STR_PAD_LEFT), 'modelo' => '55', 'tipo_operacao' => 'saida',
        'origem_arquivo' => 'fiscal', 'valor_total' => 100,
    ]);
    DB::table('efd_notas_itens')->insert([
        'efd_nota_id' => $this->nota->id, 'user_id' => $this->user->id, 'numero_item' => 1, 'codigo_item' => 'X',
        'descricao' => 'item da nota', 'quantidade' => 1, 'valor_total' => 100, 'cfop' => 5102,
        'created_at' => now(), 'updated_at' => now(),
    ]);
});

it('catalogoPorItem casa o catálogo por codigo_item (escopo do cliente da nota)', function () {
    $map = $this->nota->catalogoPorItem();

    expect($map)->toHaveKey('X');
    expect($map['X']->cod_ncm)->toBe('99887766');
});

it('o detalhe da nota exibe o NCM do catálogo associado ao item', function () {
    $html = actingAs($this->user)->get('/app/notas/efd/'.$this->nota->id)->assertOk()->getContent();

    expect($html)->toContain('99887766');
    expect($html)->toContain('Ver catálogo');                  // toggle do cadastro
    expect($html)->toContain('data-cat-hist="X"');             // gatilho do histórico inline
    expect($html)->toContain('cat-hist-panel');                // painel-alvo do fetch
});

it('quando o catálogo não tem NCM e o tipo não exige, mostra "não exige NCM" (não é furo)', function () {
    DB::table('efd_catalogo_itens')->insert([
        'user_id' => $this->user->id, 'cliente_id' => $this->cliente, 'importacao_id' => $this->impF->id,
        'cod_item' => 'Y', 'descr_item' => 'ITEM USO', 'tipo_item' => '99', 'cod_ncm' => null,
        'created_at' => now(), 'updated_at' => now(),
    ]);
    $n = EfdNota::create([
        'user_id' => $this->user->id, 'cliente_id' => $this->cliente, 'importacao_id' => $this->impF->id,
        'numero' => 9, 'serie' => '1', 'data_emissao' => '2024-01-15', 'valor_desconto' => 0, 'cancelada' => false,
        'chave_acesso' => str_pad('Y', 44, '0', STR_PAD_LEFT), 'modelo' => '55', 'tipo_operacao' => 'entrada',
        'origem_arquivo' => 'fiscal', 'valor_total' => 50,
    ]);
    DB::table('efd_notas_itens')->insert([
        'efd_nota_id' => $n->id, 'user_id' => $this->user->id, 'numero_item' => 1, 'codigo_item' => 'Y',
        'quantidade' => 1, 'valor_total' => 50, 'cfop' => 1102, 'created_at' => now(), 'updated_at' => now(),
    ]);

    $html = actingAs($this->user)->get('/app/notas/efd/'.$n->id)->assertOk()->getContent();

    expect($html)->toContain('não exige NCM');   // tipo 99 não é mercadoria → legítimo
    expect($html)->not->toContain('NCM faltando'); // não é gap de mercadoria
    expect($html)->not->toContain('sem cat.');     // tem catálogo, só não tem NCM
});
