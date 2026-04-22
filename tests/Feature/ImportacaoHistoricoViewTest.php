<?php

use App\Models\EfdImportacao;
use App\Models\User;
use App\Models\XmlImportacao;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

it('exibe o volume de efd pelo total_participantes quando novos e duplicados vierem zerados', function () {
    $user = User::factory()->create();

    EfdImportacao::create([
        'user_id' => $user->id,
        'tipo_efd' => 'EFD ICMS/IPI',
        'filename' => 'atual.txt',
        'status' => 'concluido',
        'total_participantes' => 135,
        'novos' => 0,
        'duplicados' => 0,
        'participante_ids' => range(1, 135),
    ]);

    actingAs($user)
        ->withHeader('X-Requested-With', 'XMLHttpRequest')
        ->get('/app/importacao/historico')
        ->assertOk()
        ->assertSee('135 participante(s)');
});

it('faz fallback para participante_ids e para contadores legados no historico de efd', function () {
    $user = User::factory()->create();

    EfdImportacao::create([
        'user_id' => $user->id,
        'tipo_efd' => 'EFD ICMS/IPI',
        'filename' => 'ids.txt',
        'status' => 'concluido',
        'total_participantes' => 0,
        'novos' => 0,
        'duplicados' => 0,
        'participante_ids' => [10, 11, 12],
    ]);

    EfdImportacao::create([
        'user_id' => $user->id,
        'tipo_efd' => 'EFD PIS/COFINS',
        'filename' => 'legado.txt',
        'status' => 'concluido',
        'total_participantes' => 0,
        'novos' => 7,
        'duplicados' => 2,
        'participante_ids' => null,
    ]);

    $response = actingAs($user)
        ->withHeader('X-Requested-With', 'XMLHttpRequest')
        ->get('/app/importacao/historico');

    $response
        ->assertOk()
        ->assertSee('3 participante(s)')
        ->assertSee('9 participante(s)');
});

it('mantem o volume de xml no historico unificado', function () {
    $user = User::factory()->create();

    XmlImportacao::create([
        'user_id' => $user->id,
        'tipo_documento' => 'nfe',
        'status' => 'concluido',
        'total_xmls' => 4,
    ]);

    actingAs($user)
        ->withHeader('X-Requested-With', 'XMLHttpRequest')
        ->get('/app/importacao/historico')
        ->assertOk()
        ->assertSee('4 XMLs');
});
