<?php

use App\Models\ConsultaLote;
use App\Models\ConsultaResultado;
use App\Models\EfdImportacao;
use App\Models\EfdNota;
use App\Models\MonitoramentoPlano;
use App\Models\Participante;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

it('resultado do lote renderiza bloco de relacionamento fiscal do participante', function () {
    $user = User::factory()->create();
    $empresa = DB::table('clientes')->insertGetId([
        'user_id' => $user->id, 'razao_social' => 'EMPRESA ALFA', 'documento' => '00000000000100',
        'is_empresa_propria' => true, 'created_at' => now(), 'updated_at' => now(),
    ]);
    $part = Participante::create(['user_id' => $user->id, 'cliente_id' => $empresa, 'documento' => '11111111000111', 'razao_social' => 'FORN ALFA', 'uf' => 'SP', 'crt' => '3']);
    $imp = EfdImportacao::create(['user_id' => $user->id, 'cliente_id' => $empresa, 'tipo_efd' => 'EFD ICMS/IPI', 'filename' => 'f.txt', 'status' => 'concluido', 'iniciado_em' => now()]);
    EfdNota::create(['user_id' => $user->id, 'cliente_id' => $empresa, 'participante_id' => $part->id, 'importacao_id' => $imp->id, 'numero' => 1, 'serie' => '1', 'modelo' => '55', 'origem_arquivo' => 'fiscal', 'tipo_operacao' => 'entrada', 'valor_total' => 2500, 'valor_desconto' => 0, 'cancelada' => false, 'data_emissao' => '2024-05-01']);

    $plano = MonitoramentoPlano::porCodigo('gratuito') ?? MonitoramentoPlano::firstOrFail();
    $lote = ConsultaLote::create(['user_id' => $user->id, 'plano_id' => $plano->id, 'status' => ConsultaLote::STATUS_FINALIZADO, 'total_participantes' => 1, 'creditos_cobrados' => 0, 'tab_id' => 'tab-fr-1']);
    ConsultaResultado::create(['consulta_lote_id' => $lote->id, 'participante_id' => $part->id, 'status' => ConsultaResultado::STATUS_SUCESSO, 'resultado_dados' => ['situacao_cadastral' => 'ATIVA'], 'consultado_em' => now()]);
    $lote->participantes()->attach([$part->id]);

    actingAs($user)->get("/app/consulta/lote/{$lote->id}")
        ->assertOk()
        ->assertSee('Movimentação Fiscal')
        ->assertSee('EMPRESA ALFA')
        ->assertSee('Fornecedor');
});

it('resultado do lote renderiza panorama fiscal de alvo CLIENTE (ledger próprio)', function () {
    $user = User::factory()->create();
    $empresa = DB::table('clientes')->insertGetId([
        'user_id' => $user->id, 'razao_social' => 'HIDRATOP LTDA', 'documento' => '00000000000900',
        'is_empresa_propria' => false, 'created_at' => now(), 'updated_at' => now(),
    ]);
    $forn = Participante::create(['user_id' => $user->id, 'cliente_id' => $empresa, 'documento' => '11111111000111', 'razao_social' => 'DISTRIBUIDORA X', 'uf' => 'SP', 'crt' => '3']);
    $imp = EfdImportacao::create(['user_id' => $user->id, 'cliente_id' => $empresa, 'tipo_efd' => 'EFD ICMS/IPI', 'filename' => 'f.txt', 'status' => 'concluido', 'iniciado_em' => now()]);
    DB::table('efd_catalogo_itens')->insert([
        'user_id' => $user->id, 'cliente_id' => $empresa, 'importacao_id' => $imp->id,
        'cod_item' => 'AGUA', 'descr_item' => 'AGUA MINERAL 500ML', 'tipo_item' => '00',
        'cod_ncm' => '22011000', 'created_at' => now(), 'updated_at' => now(),
    ]);
    $nota = EfdNota::create(['user_id' => $user->id, 'cliente_id' => $empresa, 'participante_id' => $forn->id, 'importacao_id' => $imp->id, 'numero' => 1, 'serie' => '1', 'modelo' => '55', 'origem_arquivo' => 'fiscal', 'tipo_operacao' => 'entrada', 'valor_total' => 1500, 'valor_desconto' => 0, 'cancelada' => false, 'data_emissao' => '2024-05-01']);
    DB::table('efd_notas_itens')->insert([
        'efd_nota_id' => $nota->id, 'user_id' => $user->id, 'numero_item' => 1, 'codigo_item' => 'AGUA',
        'descricao' => 'x', 'quantidade' => 1, 'unidade_medida' => 'UN', 'valor_unitario' => 1500,
        'valor_total' => 1500, 'cfop' => 1102, 'cst_icms' => '00', 'aliquota_icms' => 18,
        'created_at' => now(), 'updated_at' => now(),
    ]);

    $plano = MonitoramentoPlano::porCodigo('gratuito') ?? MonitoramentoPlano::firstOrFail();
    $lote = ConsultaLote::create(['user_id' => $user->id, 'plano_id' => $plano->id, 'status' => ConsultaLote::STATUS_FINALIZADO, 'total_participantes' => 1, 'creditos_cobrados' => 0, 'tab_id' => 'tab-fr-cli']);
    // alvo CLIENTE: cliente_id setado, participante_id null
    ConsultaResultado::create(['consulta_lote_id' => $lote->id, 'cliente_id' => $empresa, 'status' => ConsultaResultado::STATUS_SUCESSO, 'resultado_dados' => ['situacao_cadastral' => 'ATIVA', 'razao_social' => 'HIDRATOP LTDA'], 'consultado_em' => now()]);

    actingAs($user)->get("/app/consulta/lote/{$lote->id}")
        ->assertOk()
        ->assertSee('Movimentação Fiscal')
        ->assertSee('AGUA MINERAL 500ML')        // top produto do catálogo
        ->assertSee('Principais contrapartes')    // título do bloco no cliente
        ->assertSee('DISTRIBUIDORA X');           // contraparte
});
