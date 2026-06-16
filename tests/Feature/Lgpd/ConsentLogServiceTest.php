<?php

use App\Models\ConsentLog;
use App\Models\User;
use App\Services\Lgpd\ConsentLogService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('registra um evento de consentimento com versão, ip e user agent', function () {
    $user = User::factory()->create();

    $log = (new ConsentLogService)->registrar(
        userId: $user->id,
        tipo: ConsentLog::TIPO_TERMOS,
        acao: ConsentLog::ACAO_ACEITE,
        versao: '1.0',
        ip: '203.0.113.7',
        userAgent: 'Mozilla/5.0',
    );

    expect($log->exists)->toBeTrue();
    expect($log->created_at)->not->toBeNull();
    $this->assertDatabaseHas('consent_logs', [
        'user_id' => $user->id,
        'tipo' => 'termos',
        'acao' => 'aceite',
        'versao' => '1.0',
        'ip' => '203.0.113.7',
        'user_agent' => 'Mozilla/5.0',
    ]);
});

it('grava o estado booleano do opt-in de marketing', function () {
    $user = User::factory()->create();

    (new ConsentLogService)->registrar(
        userId: $user->id,
        tipo: ConsentLog::TIPO_MARKETING,
        acao: ConsentLog::ACAO_REVOGACAO,
        valor: false,
    );

    $log = ConsentLog::where('user_id', $user->id)->first();
    expect($log->valor)->toBeFalse();
    expect($log->acao)->toBe('revogacao');
});

it('trunca user agent muito longo para caber na coluna', function () {
    $user = User::factory()->create();

    (new ConsentLogService)->registrar(
        userId: $user->id,
        tipo: ConsentLog::TIPO_TERMOS,
        acao: ConsentLog::ACAO_ACEITE,
        userAgent: str_repeat('x', 1000),
    );

    $log = ConsentLog::where('user_id', $user->id)->first();
    expect(strlen((string) $log->user_agent))->toBeLessThanOrEqual(500);
});

it('é append-only: não tem coluna updated_at gerenciada', function () {
    expect(ConsentLog::UPDATED_AT)->toBeNull();
});
