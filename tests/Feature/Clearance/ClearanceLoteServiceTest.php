<?php

use App\Jobs\ProcessarClearanceJob;
use App\Models\Cliente;
use App\Models\ConsultaLote;
use App\Models\EfdImportacao;
use App\Models\EfdNota;
use App\Models\User;
use App\Services\Clearance\ClearanceLoteService;
use App\Services\ValidacaoContabilService;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

function loteSvcNota(User $u, array $overrides = []): EfdNota
{
    $cliente = Cliente::firstOrCreate(
        ['user_id' => $u->id, 'is_empresa_propria' => true],
        ['tipo_pessoa' => 'PJ', 'documento' => '00000000000191', 'razao_social' => 'Empresa Propria']
    );
    $imp = EfdImportacao::create([
        'user_id' => $u->id, 'cliente_id' => $cliente->id, 'tipo_efd' => 'EFD ICMS/IPI', 'status' => 'concluido',
    ]);

    return EfdNota::create(array_merge([
        'user_id' => $u->id,
        'cliente_id' => $cliente->id,
        'importacao_id' => $imp->id,
        'chave_acesso' => '35240413305697000150550000000404041953940992',
        'modelo' => '55',
        'numero' => 40404,
        'serie' => '0',
        'data_emissao' => '2026-01-15',
        'tipo_operacao' => 'entrada',
        'valor_total' => 1000.00,
        'valor_desconto' => 0,
        'origem_arquivo' => 'fiscal',
        'metadados' => [],
    ], $overrides));
}

it('cria lote, debita N×tier e despacha um batch com N jobs', function () {
    Bus::fake();
    $user = User::factory()->create(['credits' => 1000]);
    $nfe = loteSvcNota($user, ['chave_acesso' => '35240413305697000150550000000404041953940992', 'modelo' => '55']);
    $cte = loteSvcNota($user, ['chave_acesso' => '51240146970030000474570010000016901000685610', 'modelo' => '57', 'numero' => 1690]);

    $unit = ValidacaoContabilService::custoUnitarioPorTier('basico');
    $saldoAntes = app(\App\Services\CreditService::class)->getBalance($user);

    $r = app(ClearanceLoteService::class)->iniciar(
        [$nfe->id, $cte->id], [$nfe->id => 'efd', $cte->id => 'efd'], 'basico', $user->id, 'tab-lote'
    );

    expect($r['success'])->toBeTrue();
    expect($r['total_notas'])->toBe(2);
    expect($r['creditos_cobrados'])->toBe(2 * $unit);

    $lote = ConsultaLote::find($r['consulta_lote_id']);
    expect($lote->status)->toBe(ConsultaLote::STATUS_PROCESSANDO);
    expect($lote->creditos_cobrados)->toBe(2 * $unit);

    expect(app(\App\Services\CreditService::class)->getBalance($user->fresh()))->toBe($saldoAntes - 2 * $unit);
    expect(Cache::get("clearance_lote_chaves:{$lote->id}"))->toHaveCount(2);

    Bus::assertBatched(fn ($batch) => count($batch->jobs) === 2
        && collect($batch->jobs)->every(fn ($j) => $j instanceof ProcessarClearanceJob));
});

it('mapeia modelo 57 para tipo cte e 55 para nfe', function () {
    Bus::fake();
    $user = User::factory()->create(['credits' => 1000]);
    $cte = loteSvcNota($user, ['chave_acesso' => '51240146970030000474570010000016901000685610', 'modelo' => '57', 'numero' => 1690]);

    app(ClearanceLoteService::class)->iniciar([$cte->id], [$cte->id => 'efd'], 'basico', $user->id, 'tab-lote');

    Bus::assertBatched(fn ($batch) => $batch->jobs->first()->tipoDocumento === 'cte');
});

it('créditos insuficientes: não debita nem despacha', function () {
    Bus::fake();
    $user = User::factory()->create(['credits' => 1]);
    $nfe = loteSvcNota($user);

    $r = app(ClearanceLoteService::class)->iniciar([$nfe->id], [$nfe->id => 'efd'], 'basico', $user->id, 'tab-lote');

    expect($r['success'])->toBeFalse();
    expect($r['http_status'])->toBe(402);
    expect(ConsultaLote::count())->toBe(0);
    Bus::assertNothingBatched();
});

it('nenhuma nota válida (só NFS-e): 422 sem cobrança', function () {
    Bus::fake();
    $user = User::factory()->create(['credits' => 1000]);
    $nfse = loteSvcNota($user, ['modelo' => '00', 'chave_acesso' => '35240413305697000150990000000404041953940992']);

    $r = app(ClearanceLoteService::class)->iniciar([$nfse->id], [$nfse->id => 'efd'], 'basico', $user->id, 'tab-lote');

    expect($r['success'])->toBeFalse();
    expect($r['http_status'])->toBe(422);
    Bus::assertNothingBatched();
});
