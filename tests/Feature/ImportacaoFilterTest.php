<?php

use App\Models\EfdImportacao;
use App\Models\EfdNota;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

/**
 * Filtro por importação específica nas 4 telas (notas, catálogo, clientes,
 * participantes). Cada entidade liga à importação por uma coluna própria:
 *   efd_notas.importacao_id, efd_catalogo_itens.importacao_id,
 *   participantes.importacao_efd_id, e clientes pelo efd_importacoes.cliente_id.
 * Escopo EFD: ao filtrar notas por uma importação EFD, as notas XML (que não
 * pertencem a ela) saem da listagem.
 */
beforeEach(function () {
    $this->user = User::factory()->create();

    $mkCliente = fn (string $razao, string $doc, bool $propria) => DB::table('clientes')->insertGetId([
        'user_id' => $this->user->id, 'razao_social' => $razao, 'documento' => $doc,
        'is_empresa_propria' => $propria, 'ativo' => true,
        'created_at' => now(), 'updated_at' => now(),
    ]);
    $this->clienteA = $mkCliente('CLIENTE A', '00000000000100', true);
    $this->clienteB = $mkCliente('CLIENTE B', '00000000000200', false);

    $mkImp = fn (int $cliente) => EfdImportacao::create([
        'user_id' => $this->user->id, 'cliente_id' => $cliente,
        'tipo_efd' => 'EFD ICMS/IPI', 'filename' => 'f.txt', 'status' => 'concluido', 'iniciado_em' => now(),
    ]);
    $this->impA = $mkImp($this->clienteA)->id;
    $this->impB = $mkImp($this->clienteB)->id;

    // Notas EFD: uma por importação
    $mkNota = fn (int $imp, int $cliente, string $chave) => EfdNota::create([
        'user_id' => $this->user->id, 'cliente_id' => $cliente, 'importacao_id' => $imp,
        'numero' => random_int(1, 99999), 'serie' => '1', 'data_emissao' => '2024-01-15',
        'valor_desconto' => 0, 'cancelada' => false, 'chave_acesso' => str_pad($chave, 44, '0', STR_PAD_LEFT),
        'modelo' => '55', 'tipo_operacao' => 'saida', 'origem_arquivo' => 'fiscal', 'valor_total' => 100,
    ]);
    $mkNota($this->impA, $this->clienteA, 'A');
    $mkNota($this->impB, $this->clienteB, 'B');

    // Nota XML do clienteA — não pertence a nenhuma importação EFD
    DB::table('xml_notas')->insert([
        'user_id' => $this->user->id, 'cliente_id' => $this->clienteA, 'importacao_xml_id' => null,
        'chave_acesso' => str_pad('X', 44, '0', STR_PAD_LEFT), 'tipo_documento' => 'NFE', 'origem' => 'xml_upload',
        'numero_documento' => 555, 'serie' => '1', 'data_emissao' => '2024-01-16', 'valor_total' => 100,
        'tipo_nota' => 1, 'emit_documento' => '00000000000100', 'dest_documento' => '11111111111111',
        'created_at' => now(), 'updated_at' => now(),
    ]);

    // Catálogo: um item por importação
    $mkCat = fn (int $imp, int $cliente, string $cod) => DB::table('efd_catalogo_itens')->insert([
        'user_id' => $this->user->id, 'cliente_id' => $cliente, 'importacao_id' => $imp,
        'cod_item' => $cod, 'descr_item' => "Produto {$cod}", 'tipo_item' => '00', 'cod_ncm' => '12345678',
        'aliq_icms' => 18, 'unid_inv' => 'UN', 'created_at' => now(), 'updated_at' => now(),
    ]);
    $mkCat($this->impA, $this->clienteA, 'CODA');
    $mkCat($this->impB, $this->clienteB, 'CODB');

    // Participantes: um por importação
    $mkPart = fn (int $imp, string $doc) => DB::table('participantes')->insert([
        'user_id' => $this->user->id, 'importacao_efd_id' => $imp, 'documento' => $doc,
        'tipo_documento' => 'PJ', 'razao_social' => "PART {$doc}", 'origem_tipo' => 'SPED_EFD_FISCAL',
        'created_at' => now(), 'updated_at' => now(),
    ]);
    $mkPart($this->impA, '22222222000100');
    $mkPart($this->impB, '33333333000100');
});

it('notas: filtra pela importação EFD e exclui XML', function () {
    $notas = actingAs($this->user)->get('/app/notas?importacao_id='.$this->impA)
        ->assertOk()->viewData('notas');

    $chaves = collect($notas->items())->pluck('chave_acesso')->map(fn ($c) => ltrim($c, '0'))->all();

    expect($chaves)->toContain('A');     // nota da importação A
    expect($chaves)->not->toContain('B'); // nota de outra importação fora
    expect($chaves)->not->toContain('X'); // XML não pertence à importação EFD
});

it('notas: expõe a lista de importações para o filtro', function () {
    $importacoes = actingAs($this->user)->get('/app/notas')->assertOk()->viewData('importacoes');

    expect($importacoes->pluck('id')->all())->toContain($this->impA, $this->impB);
});

it('catálogo: filtra os itens pela importação', function () {
    $itens = actingAs($this->user)->get('/app/catalogo?importacao_id='.$this->impA)
        ->assertOk()->viewData('itens');

    $cods = collect($itens)->pluck('cod_item')->all();
    expect($cods)->toContain('CODA');
    expect($cods)->not->toContain('CODB');
});

it('clientes: filtra pelo cliente da importação', function () {
    $clientes = actingAs($this->user)->get('/app/clientes?importacao='.$this->impA)
        ->assertOk()->viewData('clientes');

    $ids = collect($clientes->items())->pluck('id')->all();
    expect($ids)->toContain($this->clienteA);
    expect($ids)->not->toContain($this->clienteB);
});

it('participantes: filtra pela importação EFD', function () {
    $participantes = actingAs($this->user)->get('/app/participantes?importacao='.$this->impA)
        ->assertOk()->viewData('participantes');

    $docs = collect($participantes->items())->pluck('documento')->all();
    expect($docs)->toContain('22222222000100');
    expect($docs)->not->toContain('33333333000100');
});
