<?php

use App\Models\EfdImportacao;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function efdTravadaImportacao(string $status, int $minutosAtras): EfdImportacao
{
    $user = User::factory()->create();
    $imp = EfdImportacao::create([
        'user_id'  => $user->id,
        'tipo_efd' => 'EFD ICMS/IPI',
        'status'   => $status,
    ]);
    DB::table('efd_importacoes')
        ->where('id', $imp->id)
        ->update(['updated_at' => now()->subMinutes($minutosAtras)]);

    return $imp->fresh();
}

beforeEach(fn () => config()->set('importacao.stale_minutos', 3));

it('scopeTravadas inclui processando sem atualizacao alem da janela', function () {
    $imp = efdTravadaImportacao('processando', 10);

    expect(EfdImportacao::travadas()->pluck('id'))->toContain($imp->id);
});

it('scopeTravadas exclui processando dentro da janela', function () {
    $imp = efdTravadaImportacao('processando', 1);

    expect(EfdImportacao::travadas()->pluck('id'))->not->toContain($imp->id);
});

it('scopeTravadas exclui importacao concluida antiga', function () {
    $imp = efdTravadaImportacao('concluido', 10);

    expect(EfdImportacao::travadas()->pluck('id'))->not->toContain($imp->id);
});

it('marcarComoTravada seta status erro', function () {
    $imp = efdTravadaImportacao('processando', 10);

    $imp->marcarComoTravada();

    expect($imp->fresh()->status)->toBe('erro');
});
