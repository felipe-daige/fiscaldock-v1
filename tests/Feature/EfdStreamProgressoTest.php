<?php

use App\Models\EfdImportacao;
use App\Models\EfdNota;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->cliente = \DB::table('clientes')->insertGetId([
        'user_id' => $this->user->id,
        'razao_social' => 'EMP',
        'documento' => '00000000000100',
        'is_empresa_propria' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $this->actingAs($this->user);
});

it('SSE retorna 400 sem importacao_id', function () {
    $r = $this->get('/app/importacao/efd/progresso/stream');
    expect($r->status())->toBe(400);
});

it('SSE retorna 404 quando importacao nao pertence ao user', function () {
    $outro = User::factory()->create();
    $imp = EfdImportacao::create([
        'user_id' => $outro->id, 'cliente_id' => $this->cliente, 'tipo_efd' => 'EFD PIS/COFINS',
        'filename' => 'x.txt', 'status' => 'concluido', 'iniciado_em' => now(),
    ]);

    $r = $this->get('/app/importacao/efd/progresso/stream?importacao_id='.$imp->id);
    expect($r->status())->toBe(404);
});

it('SSE responde com payload do banco e termina quando status=concluido', function () {
    $imp = EfdImportacao::create([
        'user_id' => $this->user->id, 'cliente_id' => $this->cliente, 'tipo_efd' => 'EFD PIS/COFINS',
        'filename' => 'x.txt', 'status' => 'concluido', 'iniciado_em' => now()->subMinute(),
    ]);
    EfdNota::create([
        'user_id' => $this->user->id, 'cliente_id' => $this->cliente, 'importacao_id' => $imp->id,
        'chave_acesso' => 'NFSE_TEST', 'modelo' => '00', 'numero' => 1, 'serie' => '',
        'data_emissao' => '2024-02-01', 'tipo_operacao' => 'saida', 'valor_total' => 100, 'cancelada' => false,
    ]);

    $r = $this->get('/app/importacao/efd/progresso/stream?importacao_id='.$imp->id);

    $r->assertOk();
    $body = $r->streamedContent();
    expect($body)->toContain('"status":"concluido"');
    expect($body)->toContain('"notas_servicos"');
    expect($body)->toContain('"resumo_final"');
});

it('SSE reflete status=erro do banco', function () {
    $imp = EfdImportacao::create([
        'user_id' => $this->user->id, 'cliente_id' => $this->cliente, 'tipo_efd' => 'EFD PIS/COFINS',
        'filename' => 'x.txt', 'status' => 'erro', 'iniciado_em' => now()->subMinute(),
    ]);

    $r = $this->get('/app/importacao/efd/progresso/stream?importacao_id='.$imp->id);

    $r->assertOk();
    expect($r->streamedContent())->toContain('"status":"erro"');
});
