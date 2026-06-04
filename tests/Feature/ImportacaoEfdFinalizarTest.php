<?php

use App\Models\EfdImportacao;
use App\Models\EfdNota;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

use function Pest\Laravel\postJson;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['services.api.token' => 'test-token-123']);
    $this->user = User::factory()->create();
    $this->cliente = \DB::table('clientes')->insertGetId([
        'user_id' => $this->user->id,
        'razao_social' => 'EMPRESA',
        'documento' => '00000000000100',
        'is_empresa_propria' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $this->imp = EfdImportacao::create([
        'user_id' => $this->user->id,
        'cliente_id' => $this->cliente,
        'tipo_efd' => 'EFD ICMS/IPI',
        'filename' => 'icms.txt',
        'status' => 'processando',
        'iniciado_em' => now()->subMinutes(2),
    ]);
});

it('rejeita sem X-API-Token', function () {
    postJson('/api/importacao/efd/finalizar', [
        'user_id' => $this->user->id,
        'tab_id' => 'tab-1',
        'importacao_id' => $this->imp->id,
    ])->assertStatus(401);
});

it('retorna 404 quando importacao nao existe', function () {
    postJson('/api/importacao/efd/finalizar', [
        'user_id' => $this->user->id,
        'tab_id' => 'tab-1',
        'importacao_id' => 999999,
    ], ['X-API-Token' => 'test-token-123'])->assertStatus(404);
});

it('retorna 404 quando importacao pertence a outro user', function () {
    $otherUser = User::factory()->create();
    postJson('/api/importacao/efd/finalizar', [
        'user_id' => $otherUser->id,
        'tab_id' => 'tab-1',
        'importacao_id' => $this->imp->id,
    ], ['X-API-Token' => 'test-token-123'])->assertStatus(404);
});

it('finaliza importacao processando: muda status, persiste resumo e atualiza cache', function () {
    // 2 NF-e regulares + 1 cancelada
    EfdNota::create([
        'user_id' => $this->user->id, 'cliente_id' => $this->cliente, 'importacao_id' => $this->imp->id,
        'chave_acesso' => str_pad('1', 44, '0', STR_PAD_LEFT),
        'modelo' => '55', 'numero' => 1, 'serie' => '1', 'data_emissao' => '2024-01-01',
        'tipo_operacao' => 'entrada', 'valor_total' => 100, 'cancelada' => false,
    ]);
    EfdNota::create([
        'user_id' => $this->user->id, 'cliente_id' => $this->cliente, 'importacao_id' => $this->imp->id,
        'chave_acesso' => str_pad('2', 44, '0', STR_PAD_LEFT),
        'modelo' => '55', 'numero' => 2, 'serie' => '1', 'data_emissao' => '2024-01-02',
        'tipo_operacao' => 'saida', 'valor_total' => 250, 'cancelada' => false,
    ]);
    EfdNota::create([
        'user_id' => $this->user->id, 'cliente_id' => $this->cliente, 'importacao_id' => $this->imp->id,
        'chave_acesso' => str_pad('3', 44, '0', STR_PAD_LEFT),
        'modelo' => '55', 'numero' => 3, 'serie' => '1', 'data_emissao' => '2024-01-03',
        'tipo_operacao' => 'saida', 'valor_total' => 0, 'cancelada' => true,
    ]);

    $response = postJson('/api/importacao/efd/finalizar', [
        'user_id' => $this->user->id,
        'tab_id' => 'tab-xyz',
        'importacao_id' => $this->imp->id,
    ], ['X-API-Token' => 'test-token-123'])->assertOk();

    $response->assertJsonPath('status', 'ok');
    $response->assertJsonPath('importacao_id', $this->imp->id);
    $response->assertJsonPath('status_final', 'concluido');
    $response->assertJsonPath('resumo_final.estatisticas.total_notas_processadas', 3);
    $response->assertJsonPath('resumo_final.estatisticas.notas_canceladas', 1);
    $response->assertJsonPath('resumo_final.blocos.notas_mercadorias.total_notas', 2);

    $this->imp->refresh();
    expect($this->imp->status)->toBe('concluido');
    expect($this->imp->resumo_final)->toBeArray();
    expect($this->imp->concluido_em)->not->toBeNull();
    expect($this->imp->tempo_processamento_segundos)->toBeGreaterThan(0);

    // Cache para SSE atualizado
    $cache = Cache::get("progresso:{$this->user->id}:tab-xyz");
    expect($cache)->toBeArray();
    expect($cache['status'])->toBe('concluido');
    expect($cache['progresso'])->toBe(100);
    expect($cache['resumo_final'])->toBeArray();
    expect($cache['importacao_id'])->toBe($this->imp->id);
});

it('idempotente: importacao ja concluida retorna 200 com resumo existente', function () {
    $resumoExistente = [
        'estatisticas' => ['total_notas_processadas' => 42],
        'totais' => ['notas' => 42, 'valor' => 999.99],
    ];
    $this->imp->update([
        'status' => 'concluido',
        'resumo_final' => $resumoExistente,
        'concluido_em' => now()->subMinute(),
    ]);

    $response = postJson('/api/importacao/efd/finalizar', [
        'user_id' => $this->user->id,
        'tab_id' => 'tab-1',
        'importacao_id' => $this->imp->id,
    ], ['X-API-Token' => 'test-token-123'])->assertOk();

    $response->assertJsonPath('status_final', 'concluido');
    $response->assertJsonPath('resumo_final.estatisticas.total_notas_processadas', 42);
});

it('valida campos obrigatorios', function () {
    postJson('/api/importacao/efd/finalizar', [
        'user_id' => $this->user->id,
        // falta tab_id e importacao_id
    ], ['X-API-Token' => 'test-token-123'])->assertStatus(422);
});
