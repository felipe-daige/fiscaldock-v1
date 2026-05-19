<?php

use App\Models\EfdImportacao;
use App\Models\User;
use App\Models\XmlImportacao;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(fn () => config()->set('services.api.token', 'token-heartbeat-teste'));

function heartbeatEfd(string $status): EfdImportacao
{
    $user = User::factory()->create();
    $imp = EfdImportacao::create([
        'user_id'  => $user->id,
        'tipo_efd' => 'EFD ICMS/IPI',
        'status'   => $status,
    ]);
    DB::table('efd_importacoes')->where('id', $imp->id)
        ->update(['updated_at' => now()->subMinutes(20)]);

    return $imp->fresh();
}

it('progresso EFD atualiza updated_at de importacao processando', function () {
    $imp = heartbeatEfd('processando');

    $this->withHeaders(['X-API-Token' => 'token-heartbeat-teste'])
        ->postJson('/api/importacao/efd/progresso', [
            'user_id'       => $imp->user_id,
            'tab_id'        => 'tab-teste',
            'status'        => 'processando',
            'progresso'     => 40,
            'importacao_id' => $imp->id,
        ])
        ->assertOk();

    expect($imp->fresh()->updated_at->diffInMinutes(now()))->toBeLessThan(1);
});

it('progresso por bloco EFD atualiza updated_at de importacao processando', function () {
    $imp = heartbeatEfd('processando');

    $this->withHeaders(['X-API-Token' => 'token-heartbeat-teste'])
        ->postJson('/api/importacao/efd/notas/progresso', [
            'user_id'       => $imp->user_id,
            'tab_id'        => 'tab-teste',
            'status'        => 'processando',
            'bloco'         => 'notas_mercadorias',
            'progresso'     => 30,
            'importacao_id' => $imp->id,
        ])
        ->assertOk();

    expect($imp->fresh()->updated_at->diffInMinutes(now()))->toBeLessThan(1);
});

it('progresso EFD nao rebaixa nem rejuvenesce importacao concluida', function () {
    $imp = heartbeatEfd('concluido');

    $this->withHeaders(['X-API-Token' => 'token-heartbeat-teste'])
        ->postJson('/api/importacao/efd/progresso', [
            'user_id'       => $imp->user_id,
            'tab_id'        => 'tab-teste',
            'status'        => 'processando',
            'progresso'     => 40,
            'importacao_id' => $imp->id,
        ])
        ->assertOk();

    expect($imp->fresh()->updated_at->diffInMinutes(now()))->toBeGreaterThan(10);
});

it('progresso XML atualiza updated_at de importacao processando', function () {
    $user = User::factory()->create();
    $imp = XmlImportacao::create([
        'user_id'        => $user->id,
        'tipo_documento' => 'nfe',
        'status'         => 'processando',
    ]);
    DB::table('xml_importacoes')->where('id', $imp->id)
        ->update(['updated_at' => now()->subMinutes(20)]);

    $this->withHeaders(['X-API-Token' => 'token-heartbeat-teste'])
        ->postJson('/api/importacao/xml/progress', [
            'user_id'       => $user->id,
            'tab_id'        => 'tab-teste',
            'status'        => 'processando',
            'progresso'     => 50,
            'importacao_id' => $imp->id,
        ])
        ->assertOk();

    expect($imp->fresh()->updated_at->diffInMinutes(now()))->toBeLessThan(1);
});
