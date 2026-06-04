<?php

use App\Models\EfdCatalogoItem;
use App\Models\EfdImportacao;
use App\Models\EfdNota;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

/**
 * Item de catálogo sem NCM tem dois significados opostos e não pode aparecer
 * igual: tipo mercadoria/produto (00–06) sem NCM é GAP REAL (badge "NCM
 * faltando", âmbar); tipo uso/consumo/ativo/serviço/outras (07–10,99) NÃO exige
 * NCM (badge neutro "não exige NCM"). Antes os dois caíam no mesmo badge cinza
 * "tipo X" com tooltip "NCM não exigido" — tranquilizava errado a mercadoria.
 */
it('exigeNcm: true só para mercadoria/produto (00–06)', function () {
    foreach (['00', '01', '02', '03', '04', '05', '06'] as $t) {
        expect((new EfdCatalogoItem(['tipo_item' => $t]))->exigeNcm())->toBeTrue("tipo {$t}");
    }
    foreach (['07', '08', '09', '10', '99', null] as $t) {
        expect((new EfdCatalogoItem(['tipo_item' => $t]))->exigeNcm())->toBeFalse('tipo '.($t ?? 'null'));
    }
});

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->cliente = DB::table('clientes')->insertGetId([
        'user_id' => $this->user->id, 'razao_social' => 'EMPRESA', 'documento' => '00000000000100',
        'is_empresa_propria' => true, 'created_at' => now(), 'updated_at' => now(),
    ]);
    $this->imp = EfdImportacao::create(['user_id' => $this->user->id, 'cliente_id' => $this->cliente, 'tipo_efd' => 'EFD ICMS/IPI', 'filename' => 'i.txt', 'status' => 'concluido', 'iniciado_em' => now()]);

    // Dois itens de catálogo SEM NCM: um mercadoria (gap real) e um serviço (legítimo).
    $cat = fn (string $cod, string $tipo) => DB::table('efd_catalogo_itens')->insert([
        'user_id' => $this->user->id, 'cliente_id' => $this->cliente, 'importacao_id' => $this->imp->id,
        'cod_item' => $cod, 'descr_item' => "Item {$cod}", 'tipo_item' => $tipo, 'cod_ncm' => null,
        'created_at' => now(), 'updated_at' => now(),
    ]);
    $cat('MERC', '00');   // mercadoria p/ revenda sem NCM → faltando
    $cat('SERV', '09');   // serviço sem NCM → não exige

    $this->nota = EfdNota::create([
        'user_id' => $this->user->id, 'cliente_id' => $this->cliente, 'importacao_id' => $this->imp->id,
        'numero' => 1, 'serie' => '1', 'data_emissao' => '2024-01-15', 'valor_desconto' => 0, 'cancelada' => false,
        'chave_acesso' => str_pad('A', 44, '0', STR_PAD_LEFT), 'modelo' => '55', 'tipo_operacao' => 'saida',
        'origem_arquivo' => 'fiscal', 'valor_total' => 100,
    ]);
    $item = fn (string $cod) => DB::table('efd_notas_itens')->insert([
        'efd_nota_id' => $this->nota->id, 'user_id' => $this->user->id, 'numero_item' => random_int(1, 999),
        'codigo_item' => $cod, 'quantidade' => 1, 'valor_total' => 50, 'cfop' => 5102,
        'created_at' => now(), 'updated_at' => now(),
    ]);
    $item('MERC');
    $item('SERV');
});

it('detalhe: mercadoria sem NCM mostra "NCM faltando" (gap real)', function () {
    $html = actingAs($this->user)->get('/app/notas/efd/'.$this->nota->id)->assertOk()->getContent();

    expect($html)->toContain('NCM faltando');
});

it('detalhe: serviço sem NCM mostra "não exige NCM" (legítimo)', function () {
    $html = actingAs($this->user)->get('/app/notas/efd/'.$this->nota->id)->assertOk()->getContent();

    expect($html)->toContain('não exige NCM');
});
