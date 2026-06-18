<?php

use App\Models\User;
use App\Services\Dashboard\DashboardDataService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(Tests\TestCase::class, RefreshDatabase::class);

function seedScore(int $userId, string $classificacao): void
{
    $pid = DB::table('participantes')->insertGetId([
        'user_id' => $userId,
        'documento' => (string) random_int(10000000000000, 99999999999999),
        'razao_social' => 'PART',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    DB::table('participante_scores')->insert([
        'user_id' => $userId,
        'participante_id' => $pid,
        'classificacao' => $classificacao,
        'score_total' => 50,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

it('agrupa participantes por classificacao na ordem canonica', function () {
    $user = User::factory()->create();
    seedScore($user->id, 'baixo');
    seedScore($user->id, 'alto');
    seedScore($user->id, 'alto');

    $dist = app(DashboardDataService::class)->getRiscoDistribuicao($user->id);

    expect($dist)->toHaveCount(2)
        ->and($dist[0]['label'])->toBe('Baixo')
        ->and($dist[0]['valor'])->toBe(1)
        ->and($dist[1]['label'])->toBe('Alto')
        ->and($dist[1]['valor'])->toBe(2)
        ->and($dist[1]['hex'])->toBe('#ea580c');
});

it('nao vaza risco de outro usuario', function () {
    $eu = User::factory()->create();
    $outro = User::factory()->create();
    seedScore($outro->id, 'critico');

    expect(app(DashboardDataService::class)->getRiscoDistribuicao($eu->id))->toBe([]);
});
