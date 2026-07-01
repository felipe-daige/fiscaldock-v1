<?php

use App\Models\Cliente;
use App\Models\ConsultaLote;
use App\Models\ConsultaResultado;
use App\Models\EfdImportacao;
use App\Models\EfdNota;
use App\Models\MonitoramentoPlano;
use App\Models\User;
use App\Services\Clientes\DossieClienteBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->builder = app(DossieClienteBuilder::class);
    $this->user = User::factory()->create();
    $this->c = Cliente::create([
        'user_id' => $this->user->id, 'razao_social' => 'CLI DOSSIE', 'documento' => '33333333000133',
        'tipo_pessoa' => 'PJ', 'is_empresa_propria' => false, 'uf' => 'SP',
    ]);
});

function planoDossieCli(): MonitoramentoPlano
{
    return MonitoramentoPlano::ativos()->first() ?? MonitoramentoPlano::create([
        'nome' => 'Gratuito', 'codigo' => 'gratuito', 'ativo' => true,
        'creditos_por_consulta' => 0, 'consultas_incluidas' => [], 'etapas' => [],
    ]);
}

it('monta payload sem consulta: cliente, consulta.tem=false, score default, detalhamento 5 nao avaliadas', function () {
    $d = $this->builder->montar($this->c);

    expect($d['cliente']->id)->toBe($this->c->id)
        ->and($d['consulta']['tem'])->toBeFalse()
        ->and($d['movimentacao']['kpis']['total_notas'])->toBe(0)
        ->and($d['score'])->toHaveKeys(['score_total', 'classificacao', 'scores', 'detalhamento'])
        ->and($d)->toHaveKey('gerado_em');
    expect($d['score']['detalhamento'])->toHaveCount(5);
});

it('com consulta de sucesso inclui blocos e detalhamento avaliado', function () {
    $lote = ConsultaLote::create([
        'user_id' => $this->user->id, 'plano_id' => planoDossieCli()->id,
        'status' => ConsultaLote::STATUS_FINALIZADO, 'total_participantes' => 1, 'creditos_cobrados' => 0,
        'tab_id' => 'tab-cli-'.uniqid(), 'processado_em' => now(),
    ]);
    ConsultaResultado::create([
        'consulta_lote_id' => $lote->id, 'cliente_id' => $this->c->id, 'status' => ConsultaResultado::STATUS_SUCESSO,
        'resultado_dados' => ['situacao_cadastral' => 'ATIVA', 'cnd_federal' => ['status' => 'Negativa']],
        'consultado_em' => now(),
    ]);

    $d = $this->builder->montar($this->c);

    expect($d['consulta']['tem'])->toBeTrue()
        ->and($d['consulta']['blocos'])->not->toBeEmpty();
    expect(array_keys($d['score']['detalhamento']))
        ->toBe(array_keys(app(\App\Services\RiskScoreService::class)->getPesos()));
    expect($d['score']['detalhamento']['cadastral']['avaliado'])->toBeTrue();
});

it('inclui top_produtos e top_cfops do acervo EFD do cliente', function () {
    $imp = EfdImportacao::create(['user_id' => $this->user->id, 'cliente_id' => $this->c->id, 'tipo_efd' => 'EFD ICMS/IPI', 'filename' => 'f.txt', 'status' => 'concluido', 'iniciado_em' => now()]);
    $nota = EfdNota::create([
        'user_id' => $this->user->id, 'cliente_id' => $this->c->id, 'importacao_id' => $imp->id,
        'numero' => '1', 'serie' => '1', 'modelo' => '55', 'origem_arquivo' => 'fiscal',
        'tipo_operacao' => 'entrada', 'valor_total' => 1000, 'valor_desconto' => 0,
        'cancelada' => false, 'data_emissao' => '2024-05-01',
    ]);
    DB::table('efd_notas_itens')->insert([
        'efd_nota_id' => $nota->id, 'user_id' => $this->user->id, 'numero_item' => 1,
        'codigo_item' => 'AGUA', 'descricao' => 'AGUA MINERAL', 'quantidade' => 1,
        'unidade_medida' => 'UN', 'valor_unitario' => 1000, 'valor_total' => 1000,
        'cfop' => 1102, 'cst_icms' => '00', 'aliquota_icms' => 18, 'created_at' => now(), 'updated_at' => now(),
    ]);
    DB::table('efd_notas_consolidados')->insert([
        'efd_nota_id' => $nota->id, 'user_id' => $this->user->id, 'cfop' => 1102, 'cst_icms' => '00',
        'aliquota_icms' => 18, 'valor_operacao' => 1000, 'valor_bc_icms' => 1000, 'valor_icms' => 180,
        'valor_bc_icms_st' => 0, 'valor_icms_st' => 0, 'valor_reducao_bc' => 0, 'valor_ipi' => 0,
        'created_at' => now(), 'updated_at' => now(),
    ]);

    $d = $this->builder->montar($this->c);
    expect($d['top_produtos'][0]['cod_item'])->toBe('AGUA');
    expect($d['top_cfops'][0]['cfop'])->toBe(1102);
});
