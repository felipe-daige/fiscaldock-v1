<?php

use App\Models\EfdDivergencia;
use App\Models\EfdImportacao;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('cria divergencia com payload jsonb e severidade', function () {
    $user = User::factory()->create();
    $imp = EfdImportacao::create([
        'user_id' => $user->id,
        'tipo_efd' => 'EFD ICMS/IPI',
        'filename' => 'test.txt',
        'status' => 'concluido',
    ]);

    $div = EfdDivergencia::create([
        'importacao_id' => $imp->id,
        'user_id' => $user->id,
        'bloco' => 'C170',
        'motivo' => EfdDivergencia::MOTIVO_DUPLICADA_PROCESSAMENTO,
        'severidade' => EfdDivergencia::SEVERIDADE_ERRO,
        'chave_acesso' => str_repeat('1', 44),
        'numero_documento' => 27571,
        'numero_item' => 3,
        'payload_descartado' => ['NUM_ITEM' => '3', 'COD_ITEM' => '690', 'VL_ITEM' => '600.00'],
        'mensagem' => 'Item duplicado ignorado pelo ON CONFLICT',
    ]);

    expect($div->id)->not->toBeNull();
    expect($div->payload_descartado)->toBeArray();
    expect($div->payload_descartado['COD_ITEM'])->toBe('690');
    expect($div->severidade)->toBe('erro');
});

it('scopes filtram corretamente', function () {
    $u1 = User::factory()->create();
    $u2 = User::factory()->create();
    $imp1 = EfdImportacao::create(['user_id' => $u1->id, 'tipo_efd' => 'EFD ICMS/IPI', 'filename' => 'a.txt', 'status' => 'concluido']);
    $imp2 = EfdImportacao::create(['user_id' => $u2->id, 'tipo_efd' => 'EFD PIS/COFINS', 'filename' => 'b.txt', 'status' => 'concluido']);

    EfdDivergencia::create(['importacao_id' => $imp1->id, 'user_id' => $u1->id, 'bloco' => 'C100', 'motivo' => EfdDivergencia::MOTIVO_CANCELADA_DESCARTADA, 'severidade' => 'info', 'payload_descartado' => ['a' => 1]]);
    EfdDivergencia::create(['importacao_id' => $imp1->id, 'user_id' => $u1->id, 'bloco' => 'C170', 'motivo' => EfdDivergencia::MOTIVO_DUPLICADA_PROCESSAMENTO, 'severidade' => 'erro', 'payload_descartado' => ['a' => 2]]);
    EfdDivergencia::create(['importacao_id' => $imp2->id, 'user_id' => $u2->id, 'bloco' => 'C100', 'motivo' => EfdDivergencia::MOTIVO_CANCELADA_DESCARTADA, 'severidade' => 'info', 'payload_descartado' => ['a' => 3]]);

    expect(EfdDivergencia::doUsuario($u1->id)->count())->toBe(2);
    expect(EfdDivergencia::daImportacao($imp1->id)->count())->toBe(2);
    expect(EfdDivergencia::doMotivo(EfdDivergencia::MOTIVO_CANCELADA_DESCARTADA)->count())->toBe(2);
    expect(EfdDivergencia::naoResolvidas()->count())->toBe(3);
});

it('marca como resolvida', function () {
    $u = User::factory()->create();
    $imp = EfdImportacao::create(['user_id' => $u->id, 'tipo_efd' => 'EFD ICMS/IPI', 'filename' => 'x.txt', 'status' => 'concluido']);
    $div = EfdDivergencia::create([
        'importacao_id' => $imp->id, 'user_id' => $u->id, 'bloco' => 'C170',
        'motivo' => EfdDivergencia::MOTIVO_DUPLICADA_PROCESSAMENTO, 'severidade' => 'erro',
        'payload_descartado' => ['x' => 1],
    ]);

    $div->update(['resolvido_em' => now()]);

    expect(EfdDivergencia::naoResolvidas()->count())->toBe(0);
});
