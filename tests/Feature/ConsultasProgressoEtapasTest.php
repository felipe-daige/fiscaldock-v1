<?php

use App\Models\ConsultaLote;
use App\Models\MonitoramentoPlano;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['services.api.token' => 'test-api-token']);
});

function progressoPlano(): MonitoramentoPlano
{
    return MonitoramentoPlano::firstOrCreate(
        ['codigo' => 'licitacao'],
        [
            'nome' => 'Licitação',
            'descricao' => 'Licitação',
            'consultas_incluidas' => ['situacao_cadastral'],
            'etapas' => [
                ['numero' => 1, 'chave' => 'cadastrais', 'label' => 'Cadastrais'],
                ['numero' => 2, 'chave' => 'certidoes_federais', 'label' => 'Certidões Federais'],
                ['numero' => 0, 'chave' => 'finalizacao', 'label' => 'Salvando resultados'],
            ],
            'custo_creditos' => 3,
            'is_gratuito' => false,
            'is_active' => true,
            'ordem' => 3,
        ]
    );
}

function progressoLote(User $u, string $tabId): ConsultaLote
{
    return ConsultaLote::create([
        'user_id' => $u->id,
        'plano_id' => progressoPlano()->id,
        'status' => ConsultaLote::STATUS_PROCESSANDO,
        'total_participantes' => 1,
        'creditos_cobrados' => 3,
        'tab_id' => $tabId,
    ]);
}

it('aceita payload processando com etapa e popula cache', function () {
    $u = User::factory()->create();
    $tabId = 'tab-processando-1';
    $lote = progressoLote($u, $tabId);

    $response = $this->withHeaders(['X-API-Token' => 'test-api-token'])
        ->postJson('/api/consultas/progresso', [
            'user_id' => $u->id,
            'tab_id' => $tabId,
            'consulta_lote_id' => $lote->id,
            'status' => 'processando',
            'progresso' => 37,
            'etapa' => 1,
            'total_etapas' => 2,
            'etapa_label' => 'Cadastrais',
            'mensagem' => 'Consultando cadastrais...',
        ]);

    $response->assertOk();
    $response->assertJson(['success' => true, 'progresso' => 37]);

    $cache = Cache::get("progresso:{$u->id}:{$tabId}");
    expect($cache)->not->toBeNull();
    expect($cache['status'])->toBe('processando');
    expect($cache['progresso'])->toBe(37);
    expect($cache['etapa'])->toBe(1);
    expect($cache['total_etapas'])->toBe(2);
    expect($cache['etapa_label'])->toBe('Cadastrais');
    expect($cache['consulta_lote_id'])->toBe($lote->id);
    expect($cache['ultima_etapa_concluida'])->toBeNull();
});

it('em processando sem progresso, preserva o valor anterior e atualiza apenas a mensagem', function () {
    $u = User::factory()->create();
    $tabId = 'tab-processando-sem-progresso';
    $lote = progressoLote($u, $tabId);

    Cache::put("progresso:{$u->id}:{$tabId}", [
        'user_id' => $u->id,
        'tab_id' => $tabId,
        'consulta_lote_id' => $lote->id,
        'status' => 'processando',
        'progresso' => 40,
        'mensagem' => 'Consultando cadastrais...',
        'etapa' => 1,
        'total_etapas' => 2,
        'etapa_label' => 'Cadastrais',
        'updated_at' => now()->toIso8601String(),
    ], 600);

    $response = $this->withHeaders(['X-API-Token' => 'test-api-token'])
        ->postJson('/api/consultas/progresso', [
            'user_id' => $u->id,
            'tab_id' => $tabId,
            'consulta_lote_id' => $lote->id,
            'status' => 'processando',
            'etapa' => 1,
            'total_etapas' => 2,
            'etapa_label' => 'Cadastrais',
            'mensagem' => 'Validando retorno da Receita...',
        ]);

    $response->assertOk();
    $response->assertJson(['success' => true, 'progresso' => 40]);

    $cache = Cache::get("progresso:{$u->id}:{$tabId}");
    expect($cache)->not->toBeNull();
    expect($cache['status'])->toBe('processando');
    expect($cache['progresso'])->toBe(40);
    expect($cache['mensagem'])->toBe('Validando retorno da Receita...');
    expect($cache['etapa'])->toBe(1);
    expect($cache['total_etapas'])->toBe(2);
    expect($cache['etapa_label'])->toBe('Cadastrais');
});

it('em retry processando com progresso nulo, preserva o ultimo percentual valido', function () {
    $u = User::factory()->create();
    $tabId = 'tab-processando-progresso-nulo';
    $lote = progressoLote($u, $tabId);

    Cache::put("progresso:{$u->id}:{$tabId}", [
        'user_id' => $u->id,
        'tab_id' => $tabId,
        'consulta_lote_id' => $lote->id,
        'status' => 'processando',
        'progresso' => 67,
        'mensagem' => 'Consultando certidões...',
        'etapa' => 2,
        'total_etapas' => 2,
        'etapa_label' => 'Certidões Federais',
        'updated_at' => now()->toIso8601String(),
    ], 600);

    $response = $this->withHeaders(['X-API-Token' => 'test-api-token'])
        ->postJson('/api/consultas/progresso', [
            'user_id' => $u->id,
            'tab_id' => $tabId,
            'consulta_lote_id' => $lote->id,
            'status' => 'processando',
            'progresso' => null,
            'etapa' => 2,
            'total_etapas' => 2,
            'etapa_label' => 'Certidões Federais',
            'mensagem' => 'Retry da consulta externa...',
        ]);

    $response->assertOk();
    $response->assertJson(['success' => true, 'progresso' => 67]);

    $cache = Cache::get("progresso:{$u->id}:{$tabId}");
    expect($cache)->not->toBeNull();
    expect($cache['progresso'])->toBe(67);
    expect($cache['mensagem'])->toBe('Retry da consulta externa...');
    expect($cache['etapa'])->toBe(2);
    expect($cache['etapa_label'])->toBe('Certidões Federais');
});

it('em retry processando com etapa_label nulo, preserva o label anterior e o status do lote nao rebaixa o snapshot', function () {
    $u = User::factory()->create();
    $tabId = 'tab-processando-label-nulo';
    $lote = progressoLote($u, $tabId);

    Cache::put("progresso:{$u->id}:{$tabId}", [
        'user_id' => $u->id,
        'tab_id' => $tabId,
        'consulta_lote_id' => $lote->id,
        'status' => 'processando',
        'progresso' => 67,
        'mensagem' => 'Consultando certidões...',
        'etapa' => 2,
        'total_etapas' => 2,
        'etapa_label' => 'Certidões Federais',
        'updated_at' => now()->toIso8601String(),
    ], 600);

    $this->withHeaders(['X-API-Token' => 'test-api-token'])
        ->postJson('/api/consultas/progresso', [
            'user_id' => $u->id,
            'tab_id' => $tabId,
            'consulta_lote_id' => $lote->id,
            'status' => 'processando',
            'progresso' => null,
            'etapa' => null,
            'total_etapas' => null,
            'etapa_label' => null,
            'mensagem' => 'Retry da consulta externa...',
        ])
        ->assertOk()
        ->assertJson(['success' => true, 'progresso' => 67]);

    $cache = Cache::get("progresso:{$u->id}:{$tabId}");
    expect($cache)->not->toBeNull();
    expect($cache['progresso'])->toBe(67);
    expect($cache['etapa'])->toBe(2);
    expect($cache['total_etapas'])->toBe(2);
    expect($cache['etapa_label'])->toBe('Certidões Federais');

    actingAs($u)
        ->getJson("/app/consulta/lote/{$lote->id}/status")
        ->assertOk()
        ->assertJson([
            'success' => true,
            'status' => 'processando',
            'progresso' => 67,
            'etapa' => 2,
            'total_etapas' => 2,
            'etapa_label' => 'Certidões Federais',
            'consulta_lote_id' => $lote->id,
        ]);
});

it('status do lote prioriza etapa concluida em cache quando o lote ainda está processando', function () {
    $u = User::factory()->create();
    $tabId = 'tab-status-cache';
    $lote = progressoLote($u, $tabId);

    Cache::put("progresso:{$u->id}:{$tabId}", [
        'user_id' => $u->id,
        'tab_id' => $tabId,
        'consulta_lote_id' => $lote->id,
        'status' => 'concluido',
        'progresso' => 100,
        'mensagem' => 'Dados cadastrais concluídos.',
        'etapa' => 2,
        'total_etapas' => 3,
        'etapa_label' => 'Dados cadastrais',
        'updated_at' => now()->toIso8601String(),
    ], 600);

    actingAs($u)
        ->getJson("/app/consulta/lote/{$lote->id}/status")
        ->assertOk()
        ->assertJson([
            'success' => true,
            'status' => 'concluido',
            'progresso' => 100,
            'mensagem' => 'Dados cadastrais concluídos.',
            'etapa' => 2,
            'total_etapas' => 3,
            'etapa_label' => 'Dados cadastrais',
            'ultima_etapa_concluida' => 2,
            'consulta_lote_id' => $lote->id,
        ]);
});

it('status do lote infere a etapa 0 como finalizacao e preserva as etapas positivas concluídas', function () {
    $u = User::factory()->create();
    $tabId = 'tab-status-finalizacao';
    $lote = progressoLote($u, $tabId);

    Cache::put("progresso:{$u->id}:{$tabId}", [
        'user_id' => $u->id,
        'tab_id' => $tabId,
        'consulta_lote_id' => $lote->id,
        'status' => 'processando',
        'progresso' => 85,
        'mensagem' => 'Salvando resultados...',
        'etapa' => 0,
        'total_etapas' => 2,
        'etapa_label' => 'Salvando resultados',
        'updated_at' => now()->toIso8601String(),
    ], 600);

    actingAs($u)
        ->getJson("/app/consulta/lote/{$lote->id}/status")
        ->assertOk()
        ->assertJson([
            'success' => true,
            'status' => 'processando',
            'progresso' => 85,
            'mensagem' => 'Salvando resultados...',
            'etapa' => 0,
            'total_etapas' => 2,
            'etapa_label' => 'Salvando resultados',
            'ultima_etapa_concluida' => 2,
            'consulta_lote_id' => $lote->id,
        ]);
});

it('status do lote retorna mensagem e etapa_label simultaneamente sem perda', function () {
    $u = User::factory()->create();
    $tabId = 'tab-status-mensagem-etapa';
    $lote = progressoLote($u, $tabId);

    Cache::put("progresso:{$u->id}:{$tabId}", [
        'user_id' => $u->id,
        'tab_id' => $tabId,
        'consulta_lote_id' => $lote->id,
        'status' => 'processando',
        'progresso' => 40,
        'mensagem' => 'Validando retorno da Receita...',
        'etapa' => 1,
        'total_etapas' => 2,
        'etapa_label' => 'Cadastrais',
        'updated_at' => now()->toIso8601String(),
    ], 600);

    actingAs($u)
        ->getJson("/app/consulta/lote/{$lote->id}/status")
        ->assertOk()
        ->assertJson([
            'success' => true,
            'status' => 'processando',
            'progresso' => 40,
            'mensagem' => 'Validando retorno da Receita...',
            'etapa' => 1,
            'total_etapas' => 2,
            'etapa_label' => 'Cadastrais',
            'consulta_lote_id' => $lote->id,
        ]);
});

it('rejeita payload com etapa maior que total_etapas', function () {
    $u = User::factory()->create();

    $response = $this->withHeaders(['X-API-Token' => 'test-api-token'])
        ->postJson('/api/consultas/progresso', [
            'user_id' => $u->id,
            'tab_id' => 'tab-invalido',
            'status' => 'processando',
            'progresso' => 10,
            'etapa' => 3,
            'total_etapas' => 2,
        ]);

    $response->assertStatus(422);
    $response->assertJsonPath('errors.etapa.0', 'O número da etapa não pode ser maior que total_etapas.');
});

it('em concluido da etapa 1 com progresso nulo, preserva percentual anterior sem finalizar o lote', function () {
    $u = User::factory()->create();
    $tabId = 'tab-concluido';
    $lote = progressoLote($u, $tabId);

    Cache::put("progresso:{$u->id}:{$tabId}", [
        'user_id' => $u->id,
        'tab_id' => $tabId,
        'consulta_lote_id' => $lote->id,
        'status' => 'processando',
        'progresso' => 42,
        'mensagem' => 'Preparando consulta...',
        'etapa' => 1,
        'total_etapas' => 2,
        'etapa_label' => 'Preparando consulta',
        'updated_at' => now()->toIso8601String(),
    ], 600);

    $response = $this->withHeaders(['X-API-Token' => 'test-api-token'])
        ->postJson('/api/consultas/progresso', [
            'user_id' => $u->id,
            'tab_id' => $tabId,
            'status' => 'concluido',
            'consulta_lote_id' => $lote->id,
            'progresso' => null,
            'etapa' => 1,
            'total_etapas' => 2,
            'etapa_label' => 'Preparando consulta',
            'mensagem' => 'Preparação concluída.',
        ]);

    $response->assertOk();
    $response->assertJson(['success' => true, 'progresso' => 42]);

    $cache = Cache::get("progresso:{$u->id}:{$tabId}");
    expect($cache)->not->toBeNull();
    expect($cache['status'])->toBe('concluido');
    expect($cache['progresso'])->toBe(42);
    expect($cache['mensagem'])->toBe('Preparação concluída.');
    expect($cache['etapa'])->toBe(1);
    expect($cache['total_etapas'])->toBe(2);
    expect($cache['etapa_label'])->toBe('Preparando consulta');
    expect($cache['ultima_etapa_concluida'])->toBe(1);

    $lote->refresh();
    expect($lote->status)->toBe(ConsultaLote::STATUS_PROCESSANDO);
    expect($lote->processado_em)->toBeNull();
});

it('em concluido da etapa 1 com progresso explicito, grava o percentual recebido sem finalizar o lote', function () {
    $u = User::factory()->create();
    $tabId = 'tab-concluido-progresso-explicito';
    $lote = progressoLote($u, $tabId);

    $response = $this->withHeaders(['X-API-Token' => 'test-api-token'])
        ->postJson('/api/consultas/progresso', [
            'user_id' => $u->id,
            'tab_id' => $tabId,
            'status' => 'concluido',
            'consulta_lote_id' => $lote->id,
            'progresso' => 65,
            'etapa' => 1,
            'total_etapas' => 2,
            'etapa_label' => 'Preparando consulta',
            'mensagem' => 'Preparação concluída.',
        ]);

    $response->assertOk();
    $response->assertJson(['success' => true, 'progresso' => 65]);

    $cache = Cache::get("progresso:{$u->id}:{$tabId}");
    expect($cache)->not->toBeNull();
    expect($cache['status'])->toBe('concluido');
    expect($cache['progresso'])->toBe(65);
    expect($cache['mensagem'])->toBe('Preparação concluída.');
    expect($cache['etapa'])->toBe(1);
    expect($cache['total_etapas'])->toBe(2);
    expect($cache['etapa_label'])->toBe('Preparando consulta');
    expect($cache['etapas_concluidas'])->toBe([1]);
    expect($cache['ultima_etapa_concluida'])->toBe(1);

    $lote->refresh();
    expect($lote->status)->toBe(ConsultaLote::STATUS_PROCESSANDO);
    expect($lote->processado_em)->toBeNull();
});

it('marca preparacao como concluida quando a inicializacao chega como processando', function () {
    $u = User::factory()->create();
    $tabId = 'tab-inicializacao-processando';
    $lote = progressoLote($u, $tabId);

    $response = $this->withHeaders(['X-API-Token' => 'test-api-token'])
        ->postJson('/api/consultas/progresso', [
            'user_id' => $u->id,
            'tab_id' => $tabId,
            'consulta_lote_id' => $lote->id,
            'status' => 'processando',
            'progresso' => 5,
            'etapa' => 1,
            'total_etapas' => 2,
            'etapa_label' => 'Preparando consulta',
            'mensagem' => 'Preparando consulta...',
        ]);

    $response->assertOk();
    $response->assertJson(['success' => true, 'progresso' => 5]);

    $cache = Cache::get("progresso:{$u->id}:{$tabId}");
    expect($cache)->not->toBeNull();
    expect($cache['status'])->toBe('processando');
    expect($cache['etapa'])->toBe(1);
    expect($cache['etapa_label'])->toBe('Preparando consulta');
    expect($cache['etapas_concluidas'])->toBe([1]);
    expect($cache['ultima_etapa_concluida'])->toBe(1);

    actingAs($u)
        ->getJson("/app/consulta/lote/{$lote->id}/status")
        ->assertOk()
        ->assertJson([
            'success' => true,
            'status' => 'processando',
            'etapa' => 1,
            'total_etapas' => 2,
            'etapa_label' => 'Preparando consulta',
            'ultima_etapa_concluida' => 1,
            'consulta_lote_id' => $lote->id,
        ]);
});

it('em processando na etapa 0, preserva as etapas positivas como concluídas', function () {
    $u = User::factory()->create();
    $tabId = 'tab-processando-finalizacao';
    $lote = progressoLote($u, $tabId);

    $response = $this->withHeaders(['X-API-Token' => 'test-api-token'])
        ->postJson('/api/consultas/progresso', [
            'user_id' => $u->id,
            'tab_id' => $tabId,
            'consulta_lote_id' => $lote->id,
            'status' => 'processando',
            'progresso' => 90,
            'etapa' => 0,
            'total_etapas' => 2,
            'etapa_label' => 'Salvando resultados',
            'mensagem' => 'Salvando resultados...',
        ]);

    $response->assertOk();
    $response->assertJson(['success' => true, 'progresso' => 90]);

    $cache = Cache::get("progresso:{$u->id}:{$tabId}");
    expect($cache)->not->toBeNull();
    expect($cache['status'])->toBe('processando');
    expect($cache['progresso'])->toBe(90);
    expect($cache['etapa'])->toBe(0);
    expect($cache['total_etapas'])->toBe(2);
    expect($cache['etapa_label'])->toBe('Salvando resultados');
    expect($cache['ultima_etapa_concluida'])->toBe(2);
});

it('preserva a etapa 1 concluida quando o provedor avanca rapido para a etapa 2', function () {
    $u = User::factory()->create();
    $tabId = 'tab-etapa-sticky';
    $lote = progressoLote($u, $tabId);

    $this->withHeaders(['X-API-Token' => 'test-api-token'])
        ->postJson('/api/consultas/progresso', [
            'user_id' => $u->id,
            'tab_id' => $tabId,
            'status' => 'concluido',
            'consulta_lote_id' => $lote->id,
            'etapa' => 1,
            'total_etapas' => 2,
            'etapa_label' => 'Preparando consulta',
        ])
        ->assertOk();

    $response = $this->withHeaders(['X-API-Token' => 'test-api-token'])
        ->postJson('/api/consultas/progresso', [
            'user_id' => $u->id,
            'tab_id' => $tabId,
            'status' => 'processando',
            'consulta_lote_id' => $lote->id,
            'progresso' => 15,
            'etapa' => 2,
            'total_etapas' => 2,
            'etapa_label' => 'Dados cadastrais',
            'mensagem' => 'Consultando dados cadastrais...',
        ]);

    $response->assertOk();

    $cache = Cache::get("progresso:{$u->id}:{$tabId}");
    expect($cache)->not->toBeNull();
    expect($cache['status'])->toBe('processando');
    expect($cache['etapa'])->toBe(2);
    expect($cache['ultima_etapa_concluida'])->toBe(1);

    actingAs($u)
        ->getJson("/app/consulta/lote/{$lote->id}/status")
        ->assertOk()
        ->assertJson([
            'success' => true,
            'status' => 'processando',
            'etapa' => 2,
            'total_etapas' => 2,
            'etapa_label' => 'Dados cadastrais',
            'ultima_etapa_concluida' => 1,
            'consulta_lote_id' => $lote->id,
        ]);
});

it('em finalizado na etapa 0, persiste o encerramento do lote e marca a finalizacao no cache', function () {
    $u = User::factory()->create();
    $tabId = 'tab-finalizado';
    $lote = progressoLote($u, $tabId);

    $response = $this->withHeaders(['X-API-Token' => 'test-api-token'])
        ->postJson('/api/consultas/progresso', [
            'user_id' => $u->id,
            'tab_id' => $tabId,
            'status' => 'finalizado',
            'consulta_lote_id' => $lote->id,
            'etapa' => 0,
            'total_etapas' => 2,
            'etapa_label' => 'Salvando resultados',
            'resultado_resumo' => [
                'participantes' => 1,
            ],
        ]);

    $response->assertOk();
    $response->assertJson(['success' => true]);

    $cache = Cache::get("progresso:{$u->id}:{$tabId}");
    expect($cache)->not->toBeNull();
    expect($cache['status'])->toBe(ConsultaLote::STATUS_FINALIZADO);
    expect($cache['progresso'])->toBe(100);
    expect($cache['etapa'])->toBe(0);
    expect($cache['total_etapas'])->toBe(2);
    expect($cache['etapa_label'])->toBe('Salvando resultados');
    expect($cache['ultima_etapa_concluida'])->toBe(0);

    $lote->refresh();
    expect($lote->status)->toBe(ConsultaLote::STATUS_FINALIZADO);
    expect($lote->resultado_resumo)->toBe(['participantes' => 1]);
    expect($lote->processado_em)->not->toBeNull();
});

it('em erro, preserva etapa corrente para marcar em vermelho no frontend', function () {
    $u = User::factory()->create();
    $tabId = 'tab-erro';
    $lote = progressoLote($u, $tabId);

    $response = $this->withHeaders(['X-API-Token' => 'test-api-token'])
        ->postJson('/api/consultas/progresso', [
            'user_id' => $u->id,
            'tab_id' => $tabId,
            'status' => 'erro',
            'consulta_lote_id' => $lote->id,
            'etapa' => 2,
            'total_etapas' => 2,
            'error_code' => 'FONTE_INDISPONIVEL',
            'error_message' => 'CND Federal indisponível no momento.',
        ]);

    $response->assertOk();
    $response->assertJson(['success' => true]);

    $cache = Cache::get("progresso:{$u->id}:{$tabId}");
    expect($cache)->not->toBeNull();
    expect($cache['status'])->toBe('erro');
    expect($cache['etapa'])->toBe(2);
    expect($cache['total_etapas'])->toBe(2);
    expect($cache['ultima_etapa_concluida'])->toBe(1);
    expect($cache['error_code'])->toBe('FONTE_INDISPONIVEL');

    $lote->refresh();
    expect($lote->status)->toBe(ConsultaLote::STATUS_ERRO);
    expect($lote->error_code)->toBe('FONTE_INDISPONIVEL');
});

it('em erro na etapa 0, preserva as etapas positivas concluídas e mantém a finalizacao como etapa corrente', function () {
    $u = User::factory()->create();
    $tabId = 'tab-erro-finalizacao';
    $lote = progressoLote($u, $tabId);

    $response = $this->withHeaders(['X-API-Token' => 'test-api-token'])
        ->postJson('/api/consultas/progresso', [
            'user_id' => $u->id,
            'tab_id' => $tabId,
            'status' => 'erro',
            'consulta_lote_id' => $lote->id,
            'etapa' => 0,
            'total_etapas' => 2,
            'etapa_label' => 'Salvando resultados',
            'error_code' => 'ERRO_PERSISTENCIA',
            'error_message' => 'Falha ao salvar resultados.',
        ]);

    $response->assertOk();
    $response->assertJson(['success' => true]);

    $cache = Cache::get("progresso:{$u->id}:{$tabId}");
    expect($cache)->not->toBeNull();
    expect($cache['status'])->toBe('erro');
    expect($cache['etapa'])->toBe(0);
    expect($cache['total_etapas'])->toBe(2);
    expect($cache['etapa_label'])->toBe('Salvando resultados');
    expect($cache['ultima_etapa_concluida'])->toBe(2);
    expect($cache['error_code'])->toBe('ERRO_PERSISTENCIA');

    $lote->refresh();
    expect($lote->status)->toBe(ConsultaLote::STATUS_ERRO);
    expect($lote->error_code)->toBe('ERRO_PERSISTENCIA');
});

it('sanitiza erro critico no cache de progresso sem expor detalhes internos', function () {
    $u = User::factory()->create();
    $tabId = 'tab-erro-sanitizado';
    $lote = progressoLote($u, $tabId);

    $response = $this->withHeaders(['X-API-Token' => 'test-api-token'])
        ->postJson('/api/consultas/progresso', [
            'user_id' => $u->id,
            'tab_id' => $tabId,
            'status' => 'erro',
            'consulta_lote_id' => $lote->id,
            'etapa' => 3,
            'total_etapas' => 4,
            'etapa_label' => 'Certidões Federais',
            'error_code' => 'INFOSIMPLES_PARAMETROS_VAZIOS',
            'error_message' => 'CND Federal (undefined / undefined): Parâmetros obrigatórios não foram enviados.',
        ]);

    $response->assertOk();

    $cache = Cache::get("progresso:{$u->id}:{$tabId}");
    expect($cache)->not->toBeNull();
    expect($cache['status'])->toBe('erro');
    expect($cache['error_message'])->toContain('instabilidade interna');
    expect($cache['error_message'])->not->toContain('INFOSIMPLES');
    expect($cache['ui_error']['action_url'])->toContain('wa.me/5567999844366');

    $lote->refresh();
    expect($lote->error_code)->toBe('INFOSIMPLES_PARAMETROS_VAZIOS');
    expect($lote->error_message)->toContain('Parâmetros obrigatórios');
});
