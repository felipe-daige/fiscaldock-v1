<?php

use App\Jobs\ProcessarClearanceJob;
use App\Models\Cliente;
use App\Models\ConsultaLote;
use App\Models\EfdImportacao;
use App\Models\EfdNota;
use App\Models\Participante;
use App\Models\User;
use App\Models\XmlImportacao;
use App\Models\XmlNota;
use App\Services\ValidacaoContabilService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

function clrUser(int $credits = 1000): User
{
    return User::factory()->trialAtivo()->create(['credits' => $credits]);
}

function clrClientePropria(User $u): Cliente
{
    return Cliente::firstOrCreate(
        ['user_id' => $u->id, 'is_empresa_propria' => true],
        ['tipo_pessoa' => 'PJ', 'documento' => '00000000000191', 'razao_social' => 'Empresa Propria']
    );
}

function clrEfdNota(User $u, array $overrides = []): EfdNota
{
    $cliente = clrClientePropria($u);
    $imp = EfdImportacao::firstOrCreate(
        ['user_id' => $u->id, 'cliente_id' => $cliente->id, 'tipo_efd' => 'EFD ICMS/IPI'],
        ['status' => 'concluido']
    );
    $part = Participante::firstOrCreate(
        ['user_id' => $u->id, 'documento' => '13305697000150'],
        ['cliente_id' => $cliente->id, 'razao_social' => 'Fornecedor']
    );

    return EfdNota::create(array_merge([
        'user_id' => $u->id,
        'cliente_id' => $cliente->id,
        'participante_id' => $part->id,
        'importacao_id' => $imp->id,
        'chave_acesso' => '35240413305697000150550000000404041953940992',
        'modelo' => '55',
        'numero' => 40404,
        'serie' => '0',
        'data_emissao' => '2026-01-15',
        'tipo_operacao' => 'entrada',
        'valor_total' => 1000.00,
        'valor_desconto' => 0,
        'origem_arquivo' => 'fiscal',
        'metadados' => [],
    ], $overrides));
}

it('valida via rota Laravel: cria lote, debita N×tier, despacha batch e NÃO chama webhook', function () {
    Bus::fake();
    Http::fake(); // qualquer chamada externa falharia a asserção
    $user = clrUser();
    $n1 = clrEfdNota($user, ['chave_acesso' => '35240413305697000150550000000404041953940992']);
    $n2 = clrEfdNota($user, ['chave_acesso' => '35240413305697000150550010000404041953940993', 'numero' => 40405]);

    $unit = ValidacaoContabilService::custoUnitarioPorTier('basico');
    $saldoAntes = $user->credits;

    actingAs($user)->postJson('/app/clearance/notas/validar', [
        'nota_ids' => [$n1->id, $n2->id],
        'origens' => [$n1->id => 'efd', $n2->id => 'efd'],
        'tipo' => 'basico',
        'tab_id' => 'tab-laravel',
    ])->assertOk()
        ->assertJsonPath('success', true)
        // contrato consumido pelo front (clearance-notas.js) p/ abrir o progresso/redirect.
        ->assertJsonPath('webhook_disparado', true)
        ->assertJsonPath('creditos_utilizados', 2 * $unit)
        ->assertJsonStructure(['consulta_lote_id', 'tab_id', 'novo_saldo', 'creditos_cobrados', 'resultado_url']);

    $lote = ConsultaLote::latest('id')->first();
    expect($lote->status)->toBe(ConsultaLote::STATUS_PROCESSANDO);
    expect($lote->creditos_cobrados)->toBe(2 * $unit);
    expect($user->fresh()->credits)->toBe($saldoAntes - 2 * $unit);

    Bus::assertBatched(fn ($batch) => count($batch->jobs) === 2
        && collect($batch->jobs)->every(fn ($j) => $j instanceof ProcessarClearanceJob));
    Http::assertNothingSent();
});

it('valida via rota Laravel ainda roda a validação contábil local (popula validacao)', function () {
    Bus::fake();
    $user = clrUser();
    $nota = clrEfdNota($user, ['tipo_operacao' => 'entrada']);
    \App\Models\EfdNotaItem::create([
        'efd_nota_id' => $nota->id, 'user_id' => $user->id, 'numero_item' => 1,
        'codigo_item' => 'SKU001', 'descricao' => 'Item', 'valor_total' => 1000.00,
        'cfop' => 5102, 'cst_icms' => '00', 'aliquota_icms' => 18.0, // CFOP saída em nota de entrada
    ]);

    actingAs($user)->postJson('/app/clearance/notas/validar', [
        'nota_ids' => [$nota->id],
        'origens' => [$nota->id => 'efd'],
        'tipo' => 'basico',
        'tab_id' => 'tab-local',
    ])->assertOk();

    $nota->refresh();
    expect($nota->validacao)->not->toBeNull();
    $codigos = collect($nota->validacao['alertas'])->pluck('codigo')->all();
    expect($codigos)->toContain('CFOP_TIPO_INCONSISTENTE');
});

it('créditos insuficientes: 402, sem lote e sem batch', function () {
    Bus::fake();
    $user = clrUser(credits: 1);
    $nota = clrEfdNota($user);

    actingAs($user)->postJson('/app/clearance/notas/validar', [
        'nota_ids' => [$nota->id],
        'origens' => [$nota->id => 'efd'],
        'tipo' => 'basico',
        'tab_id' => 'tab-saldo',
    ])->assertStatus(402);

    expect(ConsultaLote::count())->toBe(0);
    expect($user->fresh()->credits)->toBe(1);
    Bus::assertNothingBatched();
});

it('validarImportacao via rota Laravel despacha batch das notas XML da importação', function () {
    Bus::fake();
    Http::fake();
    $user = clrUser();
    $cliente = clrClientePropria($user);
    $imp = XmlImportacao::create([
        'user_id' => $user->id, 'cliente_id' => $cliente->id, 'status' => 'concluido', 'tipo_documento' => 'NFE',
    ]);
    XmlNota::create([
        'user_id' => $user->id, 'importacao_xml_id' => $imp->id, 'cliente_id' => $cliente->id,
        'chave_acesso' => '35240413305697000150550000000404041953940992',
        'tipo_documento' => 'NFE', 'numero_documento' => 222, 'serie' => 1,
        'data_emissao' => '2026-01-20 10:00:00', 'valor_total' => 500.00, 'tipo_nota' => XmlNota::TIPO_SAIDA,
        'emit_documento' => '00000000000191', 'emit_razao_social' => 'Empresa Propria',
        'dest_documento' => '13305697000150', 'dest_razao_social' => 'Cliente',
        'payload' => [],
    ]);

    actingAs($user)->postJson("/app/clearance/importacao/{$imp->id}/validar", [
        'tipo' => 'basico',
        'tab_id' => 'tab-imp',
    ])->assertOk()->assertJsonPath('success', true);

    Bus::assertBatched(fn ($batch) => count($batch->jobs) === 1);
    Http::assertNothingSent();
});
