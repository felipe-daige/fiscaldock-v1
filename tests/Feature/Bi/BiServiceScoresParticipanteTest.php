<?php

use App\Models\User;
use App\Services\BiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('mapeia score_total + classificacao por participante, omitindo quem não tem score', function () {
    $user = User::factory()->create();
    $cli = DB::table('clientes')->insertGetId([
        'user_id' => $user->id, 'documento' => '00000000000191', 'razao_social' => 'Empresa',
        'is_empresa_propria' => true, 'created_at' => now(), 'updated_at' => now(),
    ]);
    $mkPart = fn (string $doc) => DB::table('participantes')->insertGetId([
        'user_id' => $user->id, 'cliente_id' => $cli, 'razao_social' => "P {$doc}",
        'documento' => $doc, 'origem_tipo' => 'MANUAL', 'created_at' => now(), 'updated_at' => now(),
    ]);
    $comScore = $mkPart('11111111000111');
    $semScore = $mkPart('22222222000122');

    DB::table('participante_scores')->insert([
        'user_id' => $user->id, 'participante_id' => $comScore,
        'score_total' => 72, 'classificacao' => 'medio', 'created_at' => now(), 'updated_at' => now(),
    ]);

    $map = app(BiService::class)->scoresPorParticipante($user->id, [$comScore, $semScore]);

    expect($map)->toHaveKey($comScore)
        ->and($map[$comScore]['score_total'])->toBe(72)
        ->and($map[$comScore]['classificacao'])->toBe('medio')
        ->and($map)->not->toHaveKey($semScore);
});

it('retorna vazio para lista de ids vazia', function () {
    $user = User::factory()->create();
    expect(app(BiService::class)->scoresPorParticipante($user->id, []))->toBe([]);
});
