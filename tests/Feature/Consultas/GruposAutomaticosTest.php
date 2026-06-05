<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

it('getGrupos retorna grupo automático "Participantes de <cliente>" por cliente com participantes', function () {
    $user = User::factory()->create();
    $clienteId = DB::table('clientes')->insertGetId([
        'user_id' => $user->id, 'razao_social' => 'EMPRESA ABC', 'documento' => '00000000000100',
        'is_empresa_propria' => false, 'created_at' => now(), 'updated_at' => now(),
    ]);
    // 2 participantes associados ao cliente
    foreach (['11111111111111', '22222222222222'] as $doc) {
        DB::table('participantes')->insert([
            'user_id' => $user->id, 'cliente_id' => $clienteId, 'documento' => $doc,
            'razao_social' => 'PART '.$doc, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    actingAs($user);
    $resp = getJson('/app/consulta/nova/grupos')->assertOk();

    $auto = collect($resp->json('data'))->firstWhere('tipo', 'cliente');
    expect($auto)->not->toBeNull();
    expect($auto['nome'])->toBe('Participantes de EMPRESA ABC');
    expect($auto['cliente_id'])->toBe($clienteId);
    expect($auto['participantes_count'])->toBe(2);
    expect($auto['id'])->toBe('cliente:'.$clienteId);
});

it('não cria grupo automático para cliente sem participantes', function () {
    $user = User::factory()->create();
    DB::table('clientes')->insert([
        'user_id' => $user->id, 'razao_social' => 'SEM PART', 'documento' => '00000000000200',
        'is_empresa_propria' => false, 'created_at' => now(), 'updated_at' => now(),
    ]);

    actingAs($user);
    $resp = getJson('/app/consulta/nova/grupos')->assertOk();

    expect(collect($resp->json('data'))->where('tipo', 'cliente'))->toBeEmpty();
});
