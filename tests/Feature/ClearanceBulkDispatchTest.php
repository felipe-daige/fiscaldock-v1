<?php

use App\Models\Cliente;
use App\Models\ConsultaLote;
use App\Models\EfdImportacao;
use App\Models\EfdNota;
use App\Models\Participante;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

function bulkDispatchUser(int $credits = 100): User
{
    return User::factory()->create(['credits' => $credits]);
}

function bulkDispatchClientePropria(User $u): Cliente
{
    return Cliente::firstOrCreate(
        ['user_id' => $u->id, 'is_empresa_propria' => true],
        ['tipo_pessoa' => 'PJ', 'documento' => '00000000000191', 'razao_social' => 'Empresa Propria']
    );
}

function bulkDispatchMakeEfdNota(User $u, string $chave, array $overrides = []): EfdNota
{
    $cliente = bulkDispatchClientePropria($u);

    $imp = EfdImportacao::firstOrCreate(
        ['user_id' => $u->id, 'cliente_id' => $cliente->id, 'tipo_efd' => 'EFD ICMS/IPI'],
        ['status' => 'concluido']
    );

    $part = Participante::firstOrCreate(
        ['user_id' => $u->id, 'documento' => $overrides['part_cnpj'] ?? '13305697000150'],
        ['cliente_id' => $cliente->id, 'razao_social' => 'Fornecedor Bulk']
    );

    unset($overrides['part_cnpj']);

    return EfdNota::create(array_merge([
        'user_id' => $u->id,
        'cliente_id' => $cliente->id,
        'participante_id' => $part->id,
        'importacao_id' => $imp->id,
        'chave_acesso' => $chave,
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

beforeEach(function () {
    config()->set('services.webhook.consultas_notas_url', 'https://n8n.test/webhook/consultas-notas');
    config()->set('services.api.token', 'token-bulk-teste');
});

it('happy path: valida local, despacha webhook com payload das notas e retorna consulta_lote_id', function () {
    $user = bulkDispatchUser(credits: 100);

    $chave1 = '35240413305697000150550000000404041953940992';
    $chave2 = '35240413305697000150550010000404041953940993';

    $nota1 = bulkDispatchMakeEfdNota($user, $chave1);
    $nota2 = bulkDispatchMakeEfdNota($user, $chave2, ['numero' => 40405]);

    Http::fake([
        'n8n.test/*' => Http::response(['ok' => true], 200),
    ]);

    $response = actingAs($user)->postJson('/app/validacao/notas/validar', [
        'nota_ids' => [$nota1->id, $nota2->id],
        'origens' => [$nota1->id => 'efd', $nota2->id => 'efd'],
        'tipo' => 'basico',
        'tab_id' => 'tab-bulk-happy',
    ]);

    $response->assertOk()
        ->assertJsonPath('webhook_disparado', true)
        ->assertJsonPath('tab_id', 'tab-bulk-happy')
        ->assertJsonStructure(['consulta_lote_id', 'novo_saldo', 'creditos_utilizados']);

    $lote = ConsultaLote::latest('id')->first();
    expect($lote)->not->toBeNull();
    expect($lote->user_id)->toBe($user->id);
    expect($lote->plano_id)->toBeNull();
    expect($lote->cliente_id)->toBeNull();
    expect($lote->total_participantes)->toBe(2);
    expect($lote->creditos_cobrados)->toBe(20);
    expect($lote->status)->toBe(ConsultaLote::STATUS_PROCESSANDO);
    expect($lote->tab_id)->toBe('tab-bulk-happy');

    expect($user->fresh()->credits)->toBe(100 - 20);

    Http::assertSent(function ($request) use ($chave1, $chave2, $lote) {
        $body = $request->data();
        if ($request->url() !== 'https://n8n.test/webhook/consultas-notas') {
            return false;
        }
        if (! $request->hasHeader('X-API-Token', 'token-bulk-teste')) {
            return false;
        }
        if (($body['consulta_lote_id'] ?? null) !== $lote->id) {
            return false;
        }
        if (($body['tipo_validacao'] ?? null) !== 'basico') {
            return false;
        }
        if (($body['total_notas'] ?? null) !== 2) {
            return false;
        }

        $chavesPayload = collect($body['notas'] ?? [])->pluck('chave_acesso')->all();

        return in_array($chave1, $chavesPayload, true)
            && in_array($chave2, $chavesPayload, true)
            && array_key_exists('progress_url', $body);
    });
});

it('estorna créditos e retorna 502 quando webhook responde com erro', function () {
    $user = bulkDispatchUser(credits: 100);

    $chave = '35240413305697000150550000000404041953940992';
    $nota = bulkDispatchMakeEfdNota($user, $chave);

    Http::fake([
        'n8n.test/*' => Http::response(['error' => 'boom'], 500),
    ]);

    $response = actingAs($user)->postJson('/app/validacao/notas/validar', [
        'nota_ids' => [$nota->id],
        'origens' => [$nota->id => 'efd'],
        'tipo' => 'basico',
        'tab_id' => 'tab-bulk-fail',
    ]);

    $response->assertStatus(502)
        ->assertJsonPath('refund_aplicado', true);

    $lote = ConsultaLote::latest('id')->first();
    expect($lote->status)->toBe(ConsultaLote::STATUS_ERRO);
    expect($lote->error_code)->toBe('WEBHOOK_ERROR');
    expect($user->fresh()->credits)->toBe(100);
});

it('retorna 402 e não dispara webhook quando saldo é insuficiente', function () {
    $user = bulkDispatchUser(credits: 5);

    $chave = '35240413305697000150550000000404041953940992';
    $nota = bulkDispatchMakeEfdNota($user, $chave);

    Http::fake([
        'n8n.test/*' => Http::response(['ok' => true], 200),
    ]);

    $response = actingAs($user)->postJson('/app/validacao/notas/validar', [
        'nota_ids' => [$nota->id],
        'origens' => [$nota->id => 'efd'],
        'tipo' => 'basico',
        'tab_id' => 'tab-bulk-saldo',
    ]);

    $response->assertStatus(402)
        ->assertJsonPath('custo_necessario', 10)
        ->assertJsonPath('saldo_atual', 5);

    expect(ConsultaLote::count())->toBe(0);
    expect($user->fresh()->credits)->toBe(5);

    Http::assertNothingSent();
});

it('retorna webhook_disparado=false quando WEBHOOK_CONSULTAS_NOTAS_URL não está configurado', function () {
    config()->set('services.webhook.consultas_notas_url', null);

    $user = bulkDispatchUser(credits: 100);
    $chave = '35240413305697000150550000000404041953940992';
    $nota = bulkDispatchMakeEfdNota($user, $chave);

    Http::fake();

    $response = actingAs($user)->postJson('/app/validacao/notas/validar', [
        'nota_ids' => [$nota->id],
        'origens' => [$nota->id => 'efd'],
        'tipo' => 'basico',
        'tab_id' => 'tab-bulk-sem-webhook',
    ]);

    $response->assertOk()
        ->assertJsonPath('webhook_disparado', false);

    expect(ConsultaLote::count())->toBe(0);
    Http::assertNothingSent();
});

it('resultado de notas em processamento hidrata snapshot inicial de progresso do cache', function () {
    $user = bulkDispatchUser(credits: 100);
    $tabId = 'tab-clearance-progress-snapshot';
    $lote = ConsultaLote::create([
        'user_id' => $user->id,
        'cliente_id' => null,
        'plano_id' => null,
        'status' => ConsultaLote::STATUS_PROCESSANDO,
        'total_participantes' => 1,
        'creditos_cobrados' => 10,
        'tab_id' => $tabId,
    ]);

    Cache::put("progresso:{$user->id}:{$tabId}", [
        'user_id' => $user->id,
        'tab_id' => $tabId,
        'consulta_lote_id' => $lote->id,
        'fluxo' => 'clearance_notas',
        'status' => ConsultaLote::STATUS_CONCLUIDO,
        'progresso' => 100,
        'mensagem' => 'Preparando consulta concluída.',
        'etapa' => 1,
        'total_etapas' => 4,
        'etapa_label' => 'Preparando consulta',
        'etapas_puladas' => [3],
        'trilha_etapas' => [
            ['etapa' => 1, 'etapa_label' => 'Preparando consulta', 'status' => 'done'],
            ['etapa' => 2, 'etapa_label' => 'Consultando NF-e na Receita Federal', 'status' => 'pending'],
            ['etapa' => 3, 'etapa_label' => 'Consultando CT-e na Receita Federal', 'status' => 'skipped'],
            ['etapa' => 0, 'etapa_label' => 'Preparando resultados', 'status' => 'pending'],
        ],
        'updated_at' => now()->toIso8601String(),
    ], 600);

    actingAs($user)
        ->get("/app/clearance/notas/resultado/{$lote->id}?tipo_validacao=basico")
        ->assertOk()
        ->assertSee('data-progress-snapshot', false)
        ->assertSee('Preparando consulta concluída.', false)
        ->assertSee('clearance-resultado-progresso', false)
        ->assertDontSee('Resultado Consolidado', false);
});

it('resultado de notas só exibe resumo consolidado quando lote está finalizado', function () {
    $user = bulkDispatchUser(credits: 100);
    $lote = ConsultaLote::create([
        'user_id' => $user->id,
        'cliente_id' => null,
        'plano_id' => null,
        'status' => ConsultaLote::STATUS_FINALIZADO,
        'total_participantes' => 1,
        'creditos_cobrados' => 10,
        'tab_id' => 'tab-clearance-finalizado',
        'processado_em' => now(),
    ]);

    actingAs($user)
        ->get("/app/clearance/notas/resultado/{$lote->id}?tipo_validacao=basico")
        ->assertOk()
        ->assertSee('Resultado Consolidado', false)
        ->assertDontSee('clearance-resultado-progresso', false);
});

it('resultado de notas ajax informa quando o resumo final está pronto', function () {
    $user = bulkDispatchUser(credits: 100);
    $lote = ConsultaLote::create([
        'user_id' => $user->id,
        'cliente_id' => null,
        'plano_id' => null,
        'status' => ConsultaLote::STATUS_FINALIZADO,
        'total_participantes' => 1,
        'creditos_cobrados' => 10,
        'tab_id' => 'tab-clearance-ajax-pronto',
        'processado_em' => now(),
    ]);

    \Illuminate\Support\Facades\DB::table('nfe_consultas')->insert([
        'user_id' => $user->id,
        'consulta_lote_id' => $lote->id,
        'chave_acesso' => '35240413305697000150550000000404041953940992',
        'tipo_documento' => 'NFE',
        'modelo' => '55',
        'numero' => '40404',
        'serie' => 1,
        'status' => 'AUTORIZADA',
        'valor_total' => 1000,
        'consultado_em' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    actingAs($user)
        ->getJson("/app/clearance/notas/resultado/{$lote->id}?tipo_validacao=basico")
        ->assertOk()
        ->assertJsonPath('status_lote', ConsultaLote::STATUS_FINALIZADO)
        ->assertJsonPath('total_resultados', 1)
        ->assertJsonPath('resultado_pronto', true)
        ->assertJsonPath('resumo.total', 1);
});

it('resultado de notas ajax mantém resultado_pronto falso sem snapshots persistidos', function () {
    $user = bulkDispatchUser(credits: 100);
    $lote = ConsultaLote::create([
        'user_id' => $user->id,
        'cliente_id' => null,
        'plano_id' => null,
        'status' => ConsultaLote::STATUS_FINALIZADO,
        'total_participantes' => 1,
        'creditos_cobrados' => 10,
        'tab_id' => 'tab-clearance-ajax-aguardando',
        'processado_em' => now(),
    ]);

    actingAs($user)
        ->getJson("/app/clearance/notas/resultado/{$lote->id}?tipo_validacao=basico")
        ->assertOk()
        ->assertJsonPath('status_lote', ConsultaLote::STATUS_FINALIZADO)
        ->assertJsonPath('total_resultados', 0)
        ->assertJsonPath('resultado_pronto', false);
});
