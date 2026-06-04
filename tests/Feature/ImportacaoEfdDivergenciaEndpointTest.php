<?php

use App\Models\EfdDivergencia;
use App\Models\EfdImportacao;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\postJson;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['services.api.token' => 'test-token-123']);
    $this->user = User::factory()->create();
    $this->imp = EfdImportacao::create([
        'user_id' => $this->user->id,
        'tipo_efd' => 'EFD ICMS/IPI',
        'filename' => 'a.txt',
        'status' => 'processando',
    ]);
});

it('rejeita sem X-API-Token', function () {
    postJson('/api/importacao/efd/divergencia', [
        'user_id' => $this->user->id,
        'importacao_id' => $this->imp->id,
        'bloco' => 'C170',
        'motivo' => 'duplicada_processamento',
        'severidade' => 'erro',
        'payload_descartado' => ['NUM_ITEM' => '3'],
    ])->assertStatus(401);
});

it('grava divergencia valida com token correto', function () {
    postJson('/api/importacao/efd/divergencia', [
        'user_id' => $this->user->id,
        'importacao_id' => $this->imp->id,
        'bloco' => 'C170',
        'motivo' => 'duplicada_processamento',
        'severidade' => 'erro',
        'chave_acesso' => str_repeat('1', 44),
        'numero_documento' => 27571,
        'numero_item' => 3,
        'payload_descartado' => ['NUM_ITEM' => '3', 'COD_ITEM' => '690', 'VL_ITEM' => '600.00'],
        'mensagem' => 'Item duplicado',
    ], ['X-API-Token' => 'test-token-123'])->assertOk();

    expect(EfdDivergencia::count())->toBe(1);
    $div = EfdDivergencia::first();
    expect($div->bloco)->toBe('C170');
    expect($div->payload_descartado['COD_ITEM'])->toBe('690');
});

it('idempotente: repetir mesma divergencia nao duplica linha', function () {
    $payload = [
        'user_id' => $this->user->id,
        'importacao_id' => $this->imp->id,
        'bloco' => 'C170',
        'motivo' => 'duplicada_processamento',
        'severidade' => 'erro',
        'chave_acesso' => str_repeat('1', 44),
        'numero_item' => 3,
        'payload_descartado' => ['x' => 1],
    ];

    postJson('/api/importacao/efd/divergencia', $payload, ['X-API-Token' => 'test-token-123'])->assertOk();
    postJson('/api/importacao/efd/divergencia', $payload, ['X-API-Token' => 'test-token-123'])->assertOk();
    postJson('/api/importacao/efd/divergencia', $payload, ['X-API-Token' => 'test-token-123'])->assertOk();

    expect(EfdDivergencia::count())->toBe(1);
});

it('valida motivo contra enum', function () {
    postJson('/api/importacao/efd/divergencia', [
        'user_id' => $this->user->id,
        'importacao_id' => $this->imp->id,
        'bloco' => 'C170',
        'motivo' => 'motivo_inexistente',
        'severidade' => 'erro',
        'payload_descartado' => ['x' => 1],
    ], ['X-API-Token' => 'test-token-123'])->assertStatus(422);
});

it('valida severidade contra enum', function () {
    postJson('/api/importacao/efd/divergencia', [
        'user_id' => $this->user->id,
        'importacao_id' => $this->imp->id,
        'bloco' => 'C170',
        'motivo' => 'duplicada_processamento',
        'severidade' => 'fatal',
        'payload_descartado' => ['x' => 1],
    ], ['X-API-Token' => 'test-token-123'])->assertStatus(422);
});

it('exige importacao_id existente', function () {
    postJson('/api/importacao/efd/divergencia', [
        'user_id' => $this->user->id,
        'importacao_id' => 999999,
        'bloco' => 'C170',
        'motivo' => 'duplicada_processamento',
        'severidade' => 'erro',
        'payload_descartado' => ['x' => 1],
    ], ['X-API-Token' => 'test-token-123'])->assertStatus(422);
});
