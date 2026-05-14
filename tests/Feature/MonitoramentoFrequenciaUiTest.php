<?php

use App\Models\Participante;
use App\Models\User;
use Database\Seeders\MonitoramentoPlanoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('mostra Mensal selecionável e quinzenal/60 dias como em breve', function () {
    $this->seed(MonitoramentoPlanoSeeder::class);
    $user = User::factory()->create();
    $participante = Participante::create([
        'user_id' => $user->id, 'documento' => '11222333000181',
        'tipo_documento' => 'PJ', 'razao_social' => 'Fornecedor X',
    ]);

    $response = $this->actingAs($user)->get("/app/participante/{$participante->id}");

    $response->assertOk();
    $content = $response->getContent();

    expect($content)->toContain('<option value="mensal" selected>Mensal</option>');
    expect($content)->toContain('<option value="quinzenal" disabled>Quinzenal (em breve)</option>');
    expect($content)->toContain('<option value="60dias" disabled>60 dias (em breve)</option>');
    expect($content)->not->toContain('<option value="diario">');
});
