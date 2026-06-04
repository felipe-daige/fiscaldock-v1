<?php

use App\Models\EfdImportacao;
use App\Models\EfdNota;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

/**
 * Catálogo período-fiel: o catálogo (0200) é UNIQUE por (cliente_id, cod_item)
 * e vira a ÚLTIMA versão importada (n8n DO UPDATE). Mas a nota de um período
 * antigo deve cruzar com o catálogo COMO ELE ERA na importação dela — não com a
 * versão sobrescrita por uma importação posterior. A reconstrução é read-side,
 * a partir do change-log (efd_catalogo_historico).
 */
beforeEach(function () {
    $this->user = User::factory()->create();
    $this->cliente = DB::table('clientes')->insertGetId([
        'user_id' => $this->user->id, 'razao_social' => 'EMPRESA', 'documento' => '00000000000100',
        'is_empresa_propria' => true, 'created_at' => now(), 'updated_at' => now(),
    ]);

    // Importação de janeiro (anterior) e de julho (posterior, dona da versão atual do catálogo).
    $this->impJan = EfdImportacao::create(['user_id' => $this->user->id, 'cliente_id' => $this->cliente, 'tipo_efd' => 'EFD ICMS/IPI', 'filename' => 'jan.txt', 'status' => 'concluido', 'iniciado_em' => now()]);
    $this->impJul = EfdImportacao::create(['user_id' => $this->user->id, 'cliente_id' => $this->cliente, 'tipo_efd' => 'EFD ICMS/IPI', 'filename' => 'jul.txt', 'status' => 'concluido', 'iniciado_em' => now()]);
    DB::table('efd_importacoes')->where('id', $this->impJan->id)->update(['created_at' => '2024-02-01 10:00:00', 'concluido_em' => '2024-02-01 10:05:00']);
    DB::table('efd_importacoes')->where('id', $this->impJul->id)->update(['created_at' => '2024-08-01 10:00:00', 'concluido_em' => '2024-08-01 10:05:00']);

    // Estado ATUAL do catálogo (= versão de julho, após o DO UPDATE).
    DB::table('efd_catalogo_itens')->insert([
        'user_id' => $this->user->id, 'cliente_id' => $this->cliente, 'importacao_id' => $this->impJul->id,
        'cod_item' => 'X', 'descr_item' => 'DESCR JULHO', 'tipo_item' => '00', 'cod_ncm' => '99999999',
        'aliq_icms' => 20, 'unid_inv' => 'CX', 'created_at' => '2024-02-01 10:05:00', 'updated_at' => '2024-08-01 10:03:00',
    ]);

    // Change-log: julho mudou NCM 11111111→99999999 e alíquota 18→20.
    DB::table('efd_catalogo_historico')->insert([
        ['user_id' => $this->user->id, 'cliente_id' => $this->cliente, 'cod_item' => 'X', 'campo' => 'cod_ncm', 'valor_anterior' => '11111111', 'valor_novo' => '99999999', 'importacao_id' => $this->impJul->id, 'changed_at' => '2024-08-01 10:03:00'],
        ['user_id' => $this->user->id, 'cliente_id' => $this->cliente, 'cod_item' => 'X', 'campo' => 'aliq_icms', 'valor_anterior' => '18.00', 'valor_novo' => '20.00', 'importacao_id' => $this->impJul->id, 'changed_at' => '2024-08-01 10:03:00'],
    ]);

    $mkNota = function (int $imp, string $chave) {
        $n = EfdNota::create([
            'user_id' => $this->user->id, 'cliente_id' => $this->cliente, 'importacao_id' => $imp,
            'numero' => random_int(1, 99999), 'serie' => '1', 'data_emissao' => '2024-01-15', 'valor_desconto' => 0,
            'cancelada' => false, 'chave_acesso' => str_pad($chave, 44, '0', STR_PAD_LEFT), 'modelo' => '55',
            'tipo_operacao' => 'saida', 'origem_arquivo' => 'fiscal', 'valor_total' => 100,
        ]);
        DB::table('efd_notas_itens')->insert([
            'efd_nota_id' => $n->id, 'user_id' => $this->user->id, 'numero_item' => 1, 'codigo_item' => 'X',
            'quantidade' => 1, 'valor_total' => 100, 'cfop' => 5102, 'aliquota_icms' => 18,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return $n;
    };
    $this->notaJan = $mkNota($this->impJan->id, 'A');
    $this->notaJul = $mkNota($this->impJul->id, 'B');
});

it('nota de janeiro cruza com a versão do catálogo daquele período (NCM/alíquota antigos)', function () {
    $cat = $this->notaJan->catalogoPorItem()['X'];

    expect($cat->cod_ncm)->toBe('11111111');        // NCM como era em janeiro
    expect((float) $cat->aliq_icms)->toBe(18.0);     // alíquota como era em janeiro
});

it('nota de julho cruza com a versão atual (NCM/alíquota novos)', function () {
    $cat = $this->notaJul->catalogoPorItem()['X'];

    expect($cat->cod_ncm)->toBe('99999999');
    expect((float) $cat->aliq_icms)->toBe(20.0);
});

it('o detalhe da nota de janeiro mostra o NCM do período, não o sobrescrito', function () {
    $html = actingAs($this->user)->get('/app/notas/efd/'.$this->notaJan->id)->assertOk()->getContent();

    expect($html)->toContain('11111111');
    expect($html)->not->toContain('99999999');
});

it('sem histórico, mantém a versão atual (zero regressão no caso comum)', function () {
    // Item sem nenhuma mudança registrada → catálogo atual vale pra qualquer período.
    DB::table('efd_catalogo_itens')->insert([
        'user_id' => $this->user->id, 'cliente_id' => $this->cliente, 'importacao_id' => $this->impJul->id,
        'cod_item' => 'Z', 'descr_item' => 'ESTAVEL', 'tipo_item' => '00', 'cod_ncm' => '55554444',
        'aliq_icms' => 12, 'unid_inv' => 'UN', 'created_at' => now(), 'updated_at' => now(),
    ]);
    DB::table('efd_notas_itens')->where('efd_nota_id', $this->notaJan->id)->update(['codigo_item' => 'Z']);
    $this->notaJan->refresh();

    expect($this->notaJan->catalogoPorItem()['Z']->cod_ncm)->toBe('55554444');
});
