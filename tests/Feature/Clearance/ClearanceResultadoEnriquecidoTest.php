<?php

use App\Models\ConsultaLote;
use App\Models\NfeConsulta;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

function loteEnriquecido(User $u): ConsultaLote
{
    return ConsultaLote::create([
        'user_id' => $u->id, 'plano_id' => null, 'status' => ConsultaLote::STATUS_FINALIZADO,
        'total_participantes' => 1, 'creditos_cobrados' => 3, 'tab_id' => 'tab-enr', 'processado_em' => now(),
    ]);
}

it('resultado expõe eventos, url_html e situacao_ambiente do snapshot', function () {
    $user = User::factory()->create();
    $lote = loteEnriquecido($user);
    NfeConsulta::create([
        'user_id' => $user->id, 'consulta_lote_id' => $lote->id,
        'chave_acesso' => str_repeat('5', 44), 'tipo_documento' => 'NFE', 'modelo' => '55',
        'status' => 'AUTORIZADA', 'valor_total' => 100, 'consultado_em' => now(),
        'natureza_operacao' => 'VENDA', 'url_html' => 'https://receita.example/danfe',
        'eventos' => [['evento' => 'Carta de Correção Eletrônica (1)', 'protocolo' => '999', 'data_autorizacao' => '29/04/2024']],
        'payload' => ['nfe_clearance' => ['situacao_ambiente' => 'produção']],
    ]);

    $r = actingAs($user)->getJson("/app/clearance/notas/resultado/{$lote->id}");
    $r->assertOk();
    $r->assertJsonPath('total_resultados', 1)->assertJsonPath('resultado_pronto', true);
})->group('clearance-enriquecido');

it('tela mostra chip de evento e link oficial do snapshot', function () {
    $user = User::factory()->create();
    $lote = loteEnriquecido($user);
    NfeConsulta::create([
        'user_id' => $user->id, 'consulta_lote_id' => $lote->id,
        'chave_acesso' => str_repeat('5', 44), 'tipo_documento' => 'NFE', 'modelo' => '55',
        'status' => 'AUTORIZADA', 'valor_total' => 100, 'consultado_em' => now(),
        'url_html' => 'https://receita.example/danfe',
        'natureza_operacao' => 'VENDA DE MERCADORIA',
        'eventos' => [['evento' => 'Carta de Correção Eletrônica (1)', 'protocolo' => '999']],
        'payload' => ['nfe_clearance' => ['situacao_ambiente' => 'produção']],
    ]);

    actingAs($user)->get("/app/clearance/notas/resultado/{$lote->id}")
        ->assertOk()
        ->assertSee('CC-e', false)
        ->assertSee('ver na Receita', false)
        ->assertSee('https://receita.example/danfe', false)
        // documento OK também é justificado e enriquecido (não só os divergentes)
        ->assertSee('VENDA DE MERCADORIA', false)
        ->assertSee('AUTORIZADA', false)
        ->assertSee('sem divergência', false);
})->group('clearance-enriquecido');
