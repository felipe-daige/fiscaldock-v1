<?php

use App\Models\MonitoramentoPlano;
use App\Models\Participante;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

use App\Support\Monitoramento\PlanoCatalog;

beforeEach(function () {
    config()->set('services.api.token', 'token-cnpj-teste');
    config()->set('services.webhook.consultas_cnpj_url', 'https://n8n.test/webhook/consultas-cnpj');
});

it('4 planos lineares com precos em credito', function () {
    $b = collect(PlanoCatalog::definitions())->keyBy('codigo');
    expect($b['gratuito']['custo_creditos'])->toBe(0);
    expect($b['validacao']['custo_creditos'])->toBe(15);
    expect($b['licitacao']['custo_creditos'])->toBe(20);
    expect($b['compliance']['custo_creditos'])->toBe(25);
    expect($b['due_diligence']['is_active'])->toBeFalse();
});

it('licitacao = 3 federais; compliance = 6 fontes sem sancoes', function () {
    $b = collect(PlanoCatalog::definitions())->keyBy('codigo');
    expect($b['licitacao']['consultas_incluidas'])->toContain('cnd_federal', 'crf_fgts', 'cndt');
    expect($b['compliance']['consultas_incluidas'])->toContain('cnd_federal', 'crf_fgts', 'cndt', 'cnd_estadual', 'cnd_municipal', 'sintegra');
    expect($b['compliance']['consultas_incluidas'])->not->toContain('cgu_cnc', 'cnj_improbidade');
});

it('migrations sobem os planos com o catalogo atual', function () {
    $gratuito = MonitoramentoPlano::porCodigo('gratuito');
    $compliance = MonitoramentoPlano::porCodigo('compliance');
    $dueDiligence = MonitoramentoPlano::porCodigo('due_diligence');

    expect($gratuito)->not->toBeNull();
    expect($gratuito->etapas)->toHaveCount(3);
    expect($gratuito->etapas[0])->toMatchArray([
        'numero' => 1,
        'chave' => 'inicializacao',
        'label' => 'Preparando consulta',
    ]);
    expect($gratuito->etapas[2])->toMatchArray([
        'numero' => 0,
        'chave' => 'finalizacao',
        'label' => 'Salvando resultados',
    ]);

    expect($compliance)->not->toBeNull();
    expect($compliance->custo_creditos)->toBe(25);
    expect($compliance->is_active)->toBeTrue();

    expect($dueDiligence)->not->toBeNull();
    expect($dueDiligence->is_active)->toBeFalse();

    expect(MonitoramentoPlano::ativos()->pluck('codigo')->all())
        ->toContain('gratuito', 'validacao', 'licitacao', 'compliance')
        ->not->toContain('due_diligence', 'enterprise');
});

it('resolve definicao canonica quando o banco esta legado', function () {
    DB::table('monitoramento_planos')
        ->where('codigo', 'gratuito')
        ->update([
            'descricao' => 'Descricao antiga',
            'consultas_incluidas' => json_encode(['situacao_cadastral'], JSON_UNESCAPED_UNICODE),
            'etapas' => json_encode([
                ['numero' => 1, 'chave' => 'cadastrais', 'label' => 'Cadastrais'],
            ], JSON_UNESCAPED_UNICODE),
            'custo_creditos' => 99,
            'is_active' => false,
            'ordem' => 999,
        ]);

    DB::table('monitoramento_planos')
        ->where('codigo', 'compliance')
        ->update([
            'custo_creditos' => 9,
            'is_active' => false,
        ]);

    $gratuito = MonitoramentoPlano::porCodigo('gratuito');
    $compliance = MonitoramentoPlano::porCodigo('compliance');

    expect($gratuito)->not->toBeNull();
    expect($gratuito->descricao)->toBe('Cartão de visita do CNPJ: confirma que a empresa existe, está ativa e tem endereço válido');
    expect($gratuito->consultas_incluidas)->toBe([
        'situacao_cadastral',
        'dados_cadastrais',
        'endereco',
    ]);
    expect($gratuito->etapas[0]['chave'])->toBe('inicializacao');
    expect($gratuito->etapas[2]['chave'])->toBe('finalizacao');
    expect($gratuito->custo_creditos)->toBe(0);
    expect($gratuito->is_active)->toBeTrue();
    expect($gratuito->ordem)->toBe(1);

    expect($compliance)->not->toBeNull();
    expect($compliance->custo_creditos)->toBe(25);
    expect($compliance->is_active)->toBeTrue();
    expect(MonitoramentoPlano::ativos()->pluck('codigo')->all())->toContain('compliance');
});

it('executar envia payload canonico mesmo com linha legada no banco', function () {
    $user = User::factory()->create(['credits' => 0]);
    $plano = MonitoramentoPlano::porCodigo('gratuito');

    expect($plano)->not->toBeNull();

    DB::table('monitoramento_planos')
        ->where('id', $plano->id)
        ->update([
            'consultas_incluidas' => json_encode(['situacao_cadastral'], JSON_UNESCAPED_UNICODE),
            'etapas' => json_encode([
                ['numero' => 1, 'chave' => 'cadastrais', 'label' => 'Cadastrais'],
            ], JSON_UNESCAPED_UNICODE),
        ]);

    $participante = Participante::create([
        'user_id' => $user->id,
        'documento' => '12345678000199',
        'razao_social' => 'Fornecedor Catalogo',
        'uf' => 'SP',
        'crt' => '3',
    ]);

    Bus::fake();

    $response = actingAs($user)->postJson('/app/consulta/nova/executar', [
        'participante_ids' => [$participante->id],
        'plano_id' => $plano->id,
        'tab_id' => 'tab-catalogo-1',
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('etapas.0.chave', 'inicializacao')
        ->assertJsonPath('etapas.1.chave', 'cadastrais')
        ->assertJsonPath('etapas.2.chave', 'finalizacao');

    $consultaLoteId = $response->json('consulta_lote_id');
    expect($consultaLoteId)->not->toBeNull();
    expect($response->json('redirect_url'))->toEndWith("/app/consulta/lote/{$consultaLoteId}");

    // O dispatch agora usa Bus::batch (não mais webhook n8n)
    Bus::assertBatched(function ($batch) {
        return count($batch->jobs) === 1;
    });
});
