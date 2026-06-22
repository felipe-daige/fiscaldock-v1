<?php

use App\Models\EfdImportacao;
use App\Models\EfdNota;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

it('getParticipantes traz fiscal_resumo com papel e valor quando há notas', function () {
    $user = User::factory()->create();
    $empresa = DB::table('clientes')->insertGetId([
        'user_id' => $user->id, 'razao_social' => 'MINHA EMPRESA', 'documento' => '00000000000100',
        'is_empresa_propria' => true, 'created_at' => now(), 'updated_at' => now(),
    ]);
    $part = DB::table('participantes')->insertGetId([
        'user_id' => $user->id, 'cliente_id' => $empresa, 'razao_social' => 'FORNECEDOR X',
        'documento' => '11111111000111', 'origem_tipo' => 'MANUAL', 'created_at' => now(), 'updated_at' => now(),
    ]);
    $imp = EfdImportacao::create(['user_id' => $user->id, 'cliente_id' => $empresa, 'tipo_efd' => 'EFD ICMS/IPI', 'filename' => 'f.txt', 'status' => 'concluido', 'iniciado_em' => now()]);
    EfdNota::create(['user_id' => $user->id, 'cliente_id' => $empresa, 'participante_id' => $part, 'importacao_id' => $imp->id, 'numero' => 1, 'serie' => '1', 'modelo' => '55', 'origem_arquivo' => 'fiscal', 'tipo_operacao' => 'entrada', 'valor_total' => 1500, 'valor_desconto' => 0, 'cancelada' => false, 'data_emissao' => '2024-02-02']);

    $resp = actingAs($user)->getJson('/app/consulta/nova/participantes')->assertOk()->json();
    $item = collect($resp['data'])->firstWhere('id', $part);

    expect($item['fiscal_resumo'])->not->toBeNull();
    expect($item['fiscal_resumo']['papel'])->toBe('fornecedor');
    expect($item['fiscal_resumo']['papel_label'])->toBe('Fornecedor');
    expect($item['fiscal_resumo']['empresa_label'])->toBe('MINHA EMPRESA');
    expect($item['fiscal_resumo']['total_formatado'])->toBe('R$ 1.500,00');
});

it('fiscal_resumo é null para participante sem notas', function () {
    $user = User::factory()->create();
    $part = DB::table('participantes')->insertGetId([
        'user_id' => $user->id, 'razao_social' => 'SEM NOTAS', 'documento' => '99999999000199',
        'origem_tipo' => 'MANUAL', 'created_at' => now(), 'updated_at' => now(),
    ]);
    $resp = actingAs($user)->getJson('/app/consulta/nova/participantes')->assertOk()->json();
    $item = collect($resp['data'])->firstWhere('id', $part);
    expect($item['fiscal_resumo'])->toBeNull();
});
