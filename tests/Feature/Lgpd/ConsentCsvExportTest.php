<?php

use App\Models\ConsentLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

it('exporta a trilha de consentimento em CSV', function () {
    $user = User::factory()->create();
    ConsentLog::create([
        'user_id' => $user->id,
        'tipo' => 'termos',
        'acao' => 'aceite',
        'versao' => '1.0',
        'ip' => '203.0.113.7',
        'created_at' => now(),
    ]);

    $response = actingAs($user)->get('/app/privacidade/exportar-csv')->assertOk();

    expect($response->headers->get('content-type'))->toContain('text/csv');
    expect($response->headers->get('content-disposition'))->toContain('attachment');

    $csv = $response->streamedContent();
    expect($csv)->toContain('termos');
    expect($csv)->toContain('aceite');
    expect($csv)->toContain('1.0');
});

it('o CSV só inclui os eventos do próprio titular', function () {
    $user = User::factory()->create();
    $outro = User::factory()->create();
    ConsentLog::create(['user_id' => $outro->id, 'tipo' => 'marketing', 'acao' => 'revogacao', 'valor' => false, 'created_at' => now()]);

    $csv = actingAs($user)->get('/app/privacidade/exportar-csv')->assertOk()->streamedContent();

    expect($csv)->not->toContain('revogacao');
});
