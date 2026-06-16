<?php

use App\Models\Cliente;
use App\Models\User;
use App\Models\XmlImportacao;
use App\Models\XmlNota;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

function validarImpClientePropria(User $u): Cliente
{
    return Cliente::firstOrCreate(
        ['user_id' => $u->id, 'is_empresa_propria' => true],
        [
            'tipo_pessoa' => 'PJ',
            'documento' => '00000000000191',
            'razao_social' => 'Empresa Propria',
        ]
    );
}

function validarImpMakeImportacaoComNotas(User $u, int $qtd): XmlImportacao
{
    $cliente = validarImpClientePropria($u);

    $imp = XmlImportacao::create([
        'user_id' => $u->id,
        'cliente_id' => $cliente->id,
        'status' => 'concluido',
        'tipo_documento' => 'NFE',
    ]);

    for ($i = 1; $i <= $qtd; $i++) {
        XmlNota::create([
            'user_id' => $u->id,
            'importacao_xml_id' => $imp->id,
            'cliente_id' => $cliente->id,
            'chave_acesso' => str_repeat((string) $i, 44),
            'tipo_documento' => 'NFE',
            'numero_documento' => 1000 + $i,
            'serie' => 1,
            'data_emissao' => '2026-01-15 10:00:00',
            'valor_total' => 500.00,
            'tipo_nota' => XmlNota::TIPO_SAIDA,
            'emit_documento' => '00000000000191',
            'emit_razao_social' => 'Empresa Propria',
            'dest_documento' => '13305697000150',
            'dest_razao_social' => 'Destinatario Teste',
            'payload' => ['emit' => ['CRT' => 3], 'det' => [], 'total' => ['ICMSTot' => []]],
        ]);
    }

    return $imp;
}

it('valida uma importacao XML: persiste validacao local e despacha clearance no Laravel', function () {
    Bus::fake();
    Http::fake();
    $u = User::factory()->trialAtivo()->create(['credits' => 1000]);
    $imp = validarImpMakeImportacaoComNotas($u, 2);

    $response = actingAs($u)
        ->postJson("/app/clearance/importacao/{$imp->id}/validar", [
            'tipo' => 'basico',
            'tab_id' => 'tab-imp',
        ]);

    $response->assertOk();
    $response->assertJson(['success' => true]);

    // Validação contábil local roda como enriquecimento (popula validacao).
    $notas = XmlNota::where('importacao_xml_id', $imp->id)->get();
    expect($notas)->toHaveCount(2);
    foreach ($notas as $nota) {
        expect($nota->validacao)->not->toBeNull();
        expect($nota->validacao)->toHaveKey('classificacao');
    }

    // Clearance SEFAZ via Laravel (sem webhook n8n).
    Bus::assertBatched(fn ($batch) => count($batch->jobs) === 2);
    Http::assertNothingSent();
});

it('retorna 404 quando a importacao nao tem notas', function () {
    Bus::fake();
    $u = User::factory()->trialAtivo()->create(['credits' => 1000]);
    $cliente = validarImpClientePropria($u);

    $imp = XmlImportacao::create([
        'user_id' => $u->id,
        'cliente_id' => $cliente->id,
        'status' => 'concluido',
        'tipo_documento' => 'NFE',
    ]);

    $response = actingAs($u)
        ->postJson("/app/clearance/importacao/{$imp->id}/validar", [
            'tipo' => 'basico',
        ]);

    $response->assertStatus(404);
    $response->assertJson(['success' => false]);
    Bus::assertNothingBatched();
});
