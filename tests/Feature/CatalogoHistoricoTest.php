<?php

use App\Models\EfdImportacao;
use App\Models\User;
use App\Services\CatalogoHistoricoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

/**
 * Histórico de catálogo (0200). Um trigger no UPDATE de efd_catalogo_itens grava
 * em efd_catalogo_historico a mudança de campo rastreado (NCM, alíquota, unidade,
 * descrição). A re-importação faz ON CONFLICT DO UPDATE → o trigger captura o drift.
 */
beforeEach(function () {
    $this->user = User::factory()->create();
    $this->cliente = DB::table('clientes')->insertGetId([
        'user_id' => $this->user->id, 'razao_social' => 'EMPRESA', 'documento' => '00000000000100',
        'is_empresa_propria' => true, 'created_at' => now(), 'updated_at' => now(),
    ]);
    $this->imp1 = EfdImportacao::create(['user_id' => $this->user->id, 'cliente_id' => $this->cliente, 'tipo_efd' => 'EFD ICMS/IPI', 'filename' => 'jan.txt', 'status' => 'concluido', 'iniciado_em' => now()]);
    $this->imp2 = EfdImportacao::create(['user_id' => $this->user->id, 'cliente_id' => $this->cliente, 'tipo_efd' => 'EFD ICMS/IPI', 'filename' => 'fev.txt', 'status' => 'concluido', 'iniciado_em' => now()]);

    // item importado em jan
    $this->id = DB::table('efd_catalogo_itens')->insertGetId([
        'user_id' => $this->user->id, 'cliente_id' => $this->cliente, 'importacao_id' => $this->imp1->id,
        'cod_item' => 'P1', 'descr_item' => 'PARAFUSO', 'tipo_item' => '00', 'cod_ncm' => '12345678',
        'aliq_icms' => 18, 'unid_inv' => 'UN', 'created_at' => now(), 'updated_at' => now(),
    ]);
    $this->svc = new CatalogoHistoricoService;
});

it('trigger grava mudança de NCM e alíquota no UPDATE (re-importação)', function () {
    // fev re-importa o mesmo item com NCM e alíquota diferentes (ON CONFLICT DO UPDATE)
    DB::table('efd_catalogo_itens')->where('id', $this->id)->update([
        'importacao_id' => $this->imp2->id, 'cod_ncm' => '87654321', 'aliq_icms' => 12,
    ]);

    $hist = DB::table('efd_catalogo_historico')->where('cod_item', 'P1')->get();
    expect($hist)->toHaveCount(2);

    $ncm = $hist->firstWhere('campo', 'cod_ncm');
    expect($ncm->valor_anterior)->toBe('12345678');
    expect($ncm->valor_novo)->toBe('87654321');
    expect((int) $ncm->importacao_id)->toBe($this->imp2->id);

    $aliq = $hist->firstWhere('campo', 'aliq_icms');
    expect((float) $aliq->valor_anterior)->toBe(18.0);
    expect((float) $aliq->valor_novo)->toBe(12.0);
});

it('não grava histórico quando nada muda', function () {
    DB::table('efd_catalogo_itens')->where('id', $this->id)->update([
        'importacao_id' => $this->imp2->id, 'cod_ncm' => '12345678', 'aliq_icms' => 18, // iguais
    ]);

    expect(DB::table('efd_catalogo_historico')->where('cod_item', 'P1')->count())->toBe(0);
});

it('service: timeline do item e resumo de drift', function () {
    DB::table('efd_catalogo_itens')->where('id', $this->id)->update(['importacao_id' => $this->imp2->id, 'cod_ncm' => '87654321', 'unid_inv' => 'CX']);

    $timeline = $this->svc->timelineItem($this->user->id, 'P1');
    expect($timeline)->toHaveCount(2);
    expect(collect($timeline)->pluck('campo')->sort()->values()->all())->toBe(['cod_ncm', 'unid_inv']);

    $resumo = $this->svc->resumoMudancas($this->user->id, $this->imp2->id);
    expect($resumo['total'])->toBe(2);
    expect($resumo['por_campo']['cod_ncm'])->toBe(1);
    expect($resumo['itens_afetados'])->toBe(1);
});

it('UI: index expõe drift e o drill-down mostra a timeline', function () {
    DB::table('efd_catalogo_itens')->where('id', $this->id)->update(['importacao_id' => $this->imp2->id, 'cod_ncm' => '87654321']);

    $drift = actingAs($this->user)->get('/app/catalogo')->assertOk()->viewData('drift');
    expect($drift['total'])->toBe(1);
    expect($drift['por_campo']['cod_ncm'])->toBe(1);

    $html = actingAs($this->user)->get('/app/catalogo/historico/P1?cliente_id='.$this->cliente)->assertOk()->getContent();
    expect($html)->toContain('Mudanças de cadastro'); // bloco da timeline
    expect($html)->toContain('87654321');             // NCM novo
});
