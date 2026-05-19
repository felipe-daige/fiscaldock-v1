<?php

use App\Models\EfdImportacao;
use App\Models\User;
use App\Models\XmlImportacao;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(fn () => config()->set('importacao.stale_minutos', 3));

function expirarEfd(string $status, int $minutosAtras): EfdImportacao
{
    $user = User::factory()->create();
    $imp = EfdImportacao::create([
        'user_id'  => $user->id,
        'tipo_efd' => 'EFD ICMS/IPI',
        'status'   => $status,
    ]);
    DB::table('efd_importacoes')->where('id', $imp->id)
        ->update(['updated_at' => now()->subMinutes($minutosAtras)]);

    return $imp->fresh();
}

function expirarXml(string $status, int $minutosAtras): XmlImportacao
{
    $user = User::factory()->create();
    $imp = XmlImportacao::create([
        'user_id'        => $user->id,
        'tipo_documento' => 'nfe',
        'status'         => $status,
    ]);
    DB::table('xml_importacoes')->where('id', $imp->id)
        ->update(['updated_at' => now()->subMinutes($minutosAtras)]);

    return $imp->fresh();
}

it('marca importacao EFD travada como erro', function () {
    $imp = expirarEfd('processando', 10);

    $this->artisan('importacao:expirar-travadas')->assertExitCode(0);

    expect($imp->fresh()->status)->toBe('erro');
});

it('marca importacao XML travada como erro com mensagem', function () {
    $imp = expirarXml('processando', 10);

    $this->artisan('importacao:expirar-travadas')->assertExitCode(0);

    $fresh = $imp->fresh();
    expect($fresh->status)->toBe('erro')
        ->and($fresh->erro_mensagem)->not->toBeNull();
});

it('nao toca importacao processando dentro da janela', function () {
    $efd = expirarEfd('processando', 1);
    $xml = expirarXml('processando', 1);

    $this->artisan('importacao:expirar-travadas')->assertExitCode(0);

    expect($efd->fresh()->status)->toBe('processando')
        ->and($xml->fresh()->status)->toBe('processando');
});

it('nao toca importacao ja concluida', function () {
    $imp = expirarEfd('concluido', 10);

    $this->artisan('importacao:expirar-travadas')->assertExitCode(0);

    expect($imp->fresh()->status)->toBe('concluido');
});
