<?php

use App\Models\User;
use App\Models\XmlImportacao;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function xmlTravadaImportacao(string $status, int $minutosAtras): XmlImportacao
{
    $user = User::factory()->create();
    $imp = XmlImportacao::create([
        'user_id'        => $user->id,
        'tipo_documento' => 'nfe',
        'status'         => $status,
    ]);
    DB::table('xml_importacoes')
        ->where('id', $imp->id)
        ->update(['updated_at' => now()->subMinutes($minutosAtras)]);

    return $imp->fresh();
}

beforeEach(fn () => config()->set('importacao.stale_minutos', 3));

it('scopeTravadas inclui processando sem atualizacao alem da janela', function () {
    $imp = xmlTravadaImportacao('processando', 10);

    expect(XmlImportacao::travadas()->pluck('id'))->toContain($imp->id);
});

it('scopeTravadas exclui processando dentro da janela', function () {
    $imp = xmlTravadaImportacao('processando', 1);

    expect(XmlImportacao::travadas()->pluck('id'))->not->toContain($imp->id);
});

it('scopeTravadas exclui importacao concluida antiga', function () {
    $imp = xmlTravadaImportacao('concluido', 10);

    expect(XmlImportacao::travadas()->pluck('id'))->not->toContain($imp->id);
});

it('marcarComoTravada seta status erro e preenche erro_mensagem', function () {
    $imp = xmlTravadaImportacao('processando', 10);

    $imp->marcarComoTravada();

    $fresh = $imp->fresh();
    expect($fresh->status)->toBe('erro')
        ->and($fresh->erro_mensagem)->not->toBeNull();
});
