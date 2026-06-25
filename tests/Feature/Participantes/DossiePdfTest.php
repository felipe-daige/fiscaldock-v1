<?php

use App\Models\Cliente;
use App\Models\ConsultaLote;
use App\Models\ConsultaResultado;
use App\Models\EfdImportacao;
use App\Models\EfdNota;
use App\Models\MonitoramentoPlano;
use App\Models\Participante;
use App\Models\User;
use App\Services\Participantes\DossieParticipanteBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->trialAtivo()->create();
    $this->p = Participante::create(['user_id' => $this->user->id, 'documento' => '07863768000138', 'razao_social' => 'ACME DOSSIE LTDA', 'uf' => 'SP', 'crt' => '3']);
    $plano = MonitoramentoPlano::ativos()->first() ?? MonitoramentoPlano::create([
        'nome' => 'Gratuito', 'codigo' => 'gratuito', 'ativo' => true, 'creditos_por_consulta' => 0, 'consultas_incluidas' => [], 'etapas' => [],
    ]);
    $lote = ConsultaLote::create([
        'user_id' => $this->user->id, 'plano_id' => $plano->id,
        'status' => ConsultaLote::STATUS_FINALIZADO, 'total_participantes' => 1, 'creditos_cobrados' => 0,
        'tab_id' => 'tab-'.uniqid(), 'processado_em' => now(),
    ]);
    ConsultaResultado::create([
        'consulta_lote_id' => $lote->id, 'participante_id' => $this->p->id, 'status' => ConsultaResultado::STATUS_SUCESSO,
        'resultado_dados' => ['razao_social' => 'ACME DOSSIE LTDA', 'situacao_cadastral' => 'ATIVA', 'cnd_federal' => ['status' => 'NEGATIVA']],
        'consultado_em' => now(),
    ]);
    criarNotaEfd($this->user, $this->p, 'saida', '2026-01-10', 500);
});

it('baixa o dossie em pdf do dono', function () {
    $resp = $this->actingAs($this->user)->get("/app/participante/{$this->p->id}/dossie");
    $resp->assertOk();
    $resp->assertHeader('content-type', 'application/pdf');
});

it('bloqueia dossie de participante de outro usuario', function () {
    $outro = User::factory()->trialAtivo()->create();
    $this->actingAs($outro)->get("/app/participante/{$this->p->id}/dossie")->assertNotFound();
});

it('a view do dossie renderiza secoes de consulta e movimentacao', function () {
    $dados = app(\App\Services\Participantes\DossieParticipanteBuilder::class)->montar($this->p);
    $html = view('reports.dossie.participante', $dados)->render();
    expect($html)->toContain('ACME DOSSIE LTDA')
        ->and($html)->toContain('Movimentações')
        ->and($html)->toContain('Regularidade')
        ->and($html)->toContain('Infográficos')
        ->and($html)->toContain('Detalhamento');
});

it('dossiê PDF lista principais produtos e CFOP detalhado', function () {
    $user = User::factory()->create();
    $p = Participante::create(['user_id' => $user->id, 'documento' => '07863768000138', 'razao_social' => 'ACME LTDA', 'uf' => 'SP', 'crt' => '3']);
    $cliente = DB::table('clientes')->insertGetId([
        'user_id' => $user->id, 'razao_social' => 'EMP', 'documento' => '00000000000100',
        'is_empresa_propria' => true, 'created_at' => now(), 'updated_at' => now(),
    ]);
    $imp = EfdImportacao::create(['user_id' => $user->id, 'cliente_id' => $cliente, 'tipo_efd' => 'EFD ICMS/IPI', 'filename' => 'f.txt', 'status' => 'concluido', 'iniciado_em' => now()]);
    $nota = EfdNota::create([
        'user_id' => $user->id, 'cliente_id' => $cliente, 'participante_id' => $p->id,
        'importacao_id' => $imp->id, 'numero' => '1', 'serie' => '1', 'modelo' => '55',
        'origem_arquivo' => 'fiscal', 'tipo_operacao' => 'entrada', 'valor_total' => 1000,
        'valor_desconto' => 0, 'cancelada' => false, 'data_emissao' => '2024-05-01',
    ]);
    DB::table('efd_notas_itens')->insert([
        'efd_nota_id' => $nota->id, 'user_id' => $user->id, 'numero_item' => 1,
        'codigo_item' => 'AGUA', 'descricao' => 'AGUA MINERAL', 'quantidade' => 1,
        'unidade_medida' => 'UN', 'valor_unitario' => 1000, 'valor_total' => 1000,
        'cfop' => 1102, 'cst_icms' => '00', 'aliquota_icms' => 18, 'created_at' => now(), 'updated_at' => now(),
    ]);
    DB::table('efd_notas_consolidados')->insert([
        'efd_nota_id' => $nota->id, 'user_id' => $user->id, 'cfop' => 1102, 'cst_icms' => '00',
        'aliquota_icms' => 18, 'valor_operacao' => 1000, 'valor_bc_icms' => 1000, 'valor_icms' => 180,
        'valor_bc_icms_st' => 0, 'valor_icms_st' => 0, 'valor_reducao_bc' => 0, 'valor_ipi' => 0,
        'created_at' => now(), 'updated_at' => now(),
    ]);

    $dados = app(DossieParticipanteBuilder::class)->montar($p);
    $html = view('reports.dossie.participante', $dados)->render();

    expect($html)->toContain('Principais produtos')
        ->toContain('AGUA MINERAL')   // descrição do produto do acervo
        ->toContain('1102');          // CFOP na lista detalhada
});
