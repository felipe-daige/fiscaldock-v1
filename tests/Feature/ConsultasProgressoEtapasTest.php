<?php

use App\Models\ConsultaLote;
use App\Models\MonitoramentoPlano;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

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

    $response = $this->withHeaders(['X-API-Token' => 'test-api-token'])
        ->postJson('/api/consultas/progresso', [
            'user_id' => $u->id,
            'tab_id' => $tabId,
            'status' => 'processando',
            'progresso' => 40,
            'etapa' => 1,
            'total_etapas' => 2,
            'etapa_label' => 'Cadastrais',
            'mensagem' => 'Consultando cadastrais...',
        ]);

    $response->assertOk();
    $response->assertJson(['success' => true, 'progresso' => 40]);

    $cache = Cache::get("progresso:{$u->id}:{$tabId}");
    expect($cache)->not->toBeNull();
    expect($cache['status'])->toBe('processando');
    expect($cache['progresso'])->toBe(40);
    expect($cache['etapa'])->toBe(1);
    expect($cache['total_etapas'])->toBe(2);
    expect($cache['etapa_label'])->toBe('Cadastrais');
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

it('em concluido, força etapa=total_etapas e progresso=100 no cache', function () {
    $u = User::factory()->create();
    $tabId = 'tab-concluido';
    $lote = progressoLote($u, $tabId);

    $response = $this->withHeaders(['X-API-Token' => 'test-api-token'])
        ->postJson('/api/consultas/progresso', [
            'user_id' => $u->id,
            'tab_id' => $tabId,
            'status' => 'concluido',
            'consulta_lote_id' => $lote->id,
            'total_etapas' => 2,
            'etapa_label' => 'Certidões Federais',
        ]);

    $response->assertOk();
    $response->assertJson(['success' => true]);

    $cache = Cache::get("progresso:{$u->id}:{$tabId}");
    expect($cache)->not->toBeNull();
    expect($cache['status'])->toBe('concluido');
    expect($cache['progresso'])->toBe(100);
    expect($cache['etapa'])->toBe(2);
    expect($cache['total_etapas'])->toBe(2);
    expect($cache['etapa_label'])->toBe('Certidões Federais');

    $lote->refresh();
    expect($lote->status)->toBe(ConsultaLote::STATUS_CONCLUIDO);
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
    expect($cache['error_code'])->toBe('FONTE_INDISPONIVEL');

    $lote->refresh();
    expect($lote->status)->toBe(ConsultaLote::STATUS_ERRO);
    expect($lote->error_code)->toBe('FONTE_INDISPONIVEL');
});
