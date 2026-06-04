<?php

use App\Models\EfdImportacao;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

/**
 * KPI "NCM faltando" do catálogo conta só mercadoria/produto (tipo 00–06) sem
 * NCM — gap fiscal real. Itens que não exigem NCM (07–10/99) NÃO entram, senão o
 * número engana o contador (na massa real: 10 sem NCM, mas só 2 são problema).
 */
beforeEach(function () {
    $this->user = User::factory()->create();
    $this->cliente = DB::table('clientes')->insertGetId([
        'user_id' => $this->user->id, 'razao_social' => 'EMPRESA', 'documento' => '00000000000100',
        'is_empresa_propria' => true, 'created_at' => now(), 'updated_at' => now(),
    ]);
    $this->imp = EfdImportacao::create(['user_id' => $this->user->id, 'cliente_id' => $this->cliente, 'tipo_efd' => 'EFD ICMS/IPI', 'filename' => 'i.txt', 'status' => 'concluido', 'iniciado_em' => now()]);

    $cat = fn (string $cod, string $tipo, ?string $ncm) => DB::table('efd_catalogo_itens')->insert([
        'user_id' => $this->user->id, 'cliente_id' => $this->cliente, 'importacao_id' => $this->imp->id,
        'cod_item' => $cod, 'descr_item' => "Item {$cod}", 'tipo_item' => $tipo, 'cod_ncm' => $ncm,
        'created_at' => now(), 'updated_at' => now(),
    ]);
    $cat('MERC', '00', null);        // mercadoria sem NCM → conta
    $cat('SERV', '09', null);        // serviço sem NCM → NÃO conta (não exige)
    $cat('USO', '07', '');           // uso e consumo, NCM vazio → NÃO conta
    $cat('OK', '00', '12345678');    // mercadoria com NCM → NÃO conta
});

it('ncm_faltando conta só mercadoria/produto sem NCM (não o legítimo)', function () {
    $kpis = actingAs($this->user)->get('/app/catalogo')->assertOk()->viewData('kpis');

    expect($kpis['ncm_faltando'])->toBe(1); // só MERC; SERV/USO não exigem, OK tem NCM
});
