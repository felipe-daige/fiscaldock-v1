<?php

use App\Models\User;
use App\Services\BiExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('portfólio inclui seções user-wide (riscos + score da carteira)', function () {
    $user = User::factory()->create();
    $rel = app(BiExportService::class)->relatorioCompleto($user->id, null, null, null);

    expect($rel['modo'])->toBe('portfolio')
        ->and($rel['secoes'])->toHaveKeys(['contrapartes', 'top-notas', 'catalogo', 'uf', 'devolucoes', 'riscos-notas', 'riscos-fornecedores'])
        ->and($rel['score_carteira'])->not->toBeNull()
        ->and($rel['ordem_secoes'])->toContain('score-carteira');
});

it('modo cliente omite seções user-wide e marca modo=cliente', function () {
    $user = User::factory()->create();
    $cli = DB::table('clientes')->insertGetId([
        'user_id' => $user->id, 'documento' => '00000000000191', 'razao_social' => 'Empresa',
        'is_empresa_propria' => true, 'created_at' => now(), 'updated_at' => now(),
    ]);
    $rel = app(BiExportService::class)->relatorioCompleto($user->id, null, null, $cli);

    expect($rel['modo'])->toBe('cliente')
        ->and($rel['secoes'])->toHaveKeys(['contrapartes', 'top-notas', 'catalogo', 'uf', 'devolucoes'])
        ->and($rel['secoes'])->not->toHaveKey('riscos-notas')
        ->and($rel['secoes'])->not->toHaveKey('riscos-fornecedores')
        ->and($rel['score_carteira'])->toBeNull()
        ->and($rel['ordem_secoes'])->not->toContain('score-carteira');
});
