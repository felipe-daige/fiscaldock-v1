<?php

use App\Models\EfdImportacao;
use App\Models\User;
use App\Services\Dashboard\DashboardDataService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

function mkImport(int $userId, string $status): EfdImportacao
{
    return EfdImportacao::create([
        'user_id' => $userId,
        'tipo_efd' => 'EFD ICMS/IPI',
        'filename' => 'x.txt',
        'status' => $status,
        'iniciado_em' => now(),
    ]);
}

it('conta importacoes com erro na triagem', function () {
    $user = User::factory()->create();
    mkImport($user->id, 'erro');
    mkImport($user->id, 'erro');
    mkImport($user->id, 'concluido');

    $triagem = app(DashboardDataService::class)->getTriagem($user->id);
    $imports = collect($triagem)->firstWhere('chave', 'imports_erro');

    expect($imports['count'])->toBe(2)
        ->and($imports['url'])->toBe('/app/importacao/historico');
});

it('nao vaza triagem de outro usuario', function () {
    $eu = User::factory()->create();
    $outro = User::factory()->create();
    mkImport($outro->id, 'erro');
    mkImport($outro->id, 'erro');
    mkImport($outro->id, 'erro');

    $triagem = app(DashboardDataService::class)->getTriagem($eu->id);

    expect(collect($triagem)->firstWhere('chave', 'imports_erro')['count'])->toBe(0);
});
