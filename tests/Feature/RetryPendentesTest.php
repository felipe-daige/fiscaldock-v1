<?php

use App\Models\Cliente;
use App\Models\ConsultaLote;
use App\Models\ConsultaResultado;
use App\Models\MonitoramentoPlano;
use App\Models\Participante;
use App\Models\User;
use App\Models\XmlNota;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

function retryPlanoPago(): MonitoramentoPlano
{
    return MonitoramentoPlano::where('is_gratuito', false)
        ->where('is_active', true)
        ->first()
        ?? MonitoramentoPlano::firstOrFail();
}

function retryUser(int $credits = 100): User
{
    return User::factory()->create(['credits' => $credits]);
}

function retryCriarLoteCompliance(User $user, MonitoramentoPlano $plano, array $overrides = []): ConsultaLote
{
    return ConsultaLote::create(array_merge([
        'user_id' => $user->id,
        'plano_id' => $plano->id,
        'status' => ConsultaLote::STATUS_FINALIZADO,
        'total_participantes' => 1,
        'creditos_cobrados' => 0,
        'tab_id' => 'tab-retry-'.uniqid(),
        'processado_em' => now(),
    ], $overrides));
}

function retryAdicionarParticipanteComStatus(
    ConsultaLote $lote,
    User $user,
    string $documento,
    ?string $status,
    ?string $errorMessage = null
): Participante {
    $participante = Participante::create([
        'user_id' => $user->id,
        'documento' => $documento,
        'razao_social' => 'Fornecedor '.substr($documento, 0, 4),
        'uf' => 'SP',
        'crt' => '3',
    ]);

    $lote->participantes()->attach([$participante->id]);

    if ($status !== null) {
        ConsultaResultado::create([
            'consulta_lote_id' => $lote->id,
            'participante_id' => $participante->id,
            'status' => $status,
            'error_message' => $errorMessage,
            'resultado_dados' => $status === ConsultaResultado::STATUS_SUCESSO
                ? ['situacao_cadastral' => 'ATIVA']
                : null,
            'consultado_em' => now(),
        ]);
    }

    return $participante;
}

function retryClientePropria(User $user): Cliente
{
    return Cliente::firstOrCreate(
        ['user_id' => $user->id, 'is_empresa_propria' => true],
        ['tipo_pessoa' => 'PJ', 'documento' => '00000000000191', 'razao_social' => 'Empresa Própria']
    );
}

function retryCriarXmlNota(
    User $user,
    ConsultaLote $lote,
    string $chave,
    bool $verificada = false
): XmlNota {
    return XmlNota::create([
        'user_id' => $user->id,
        'consulta_lote_id' => $lote->id,
        'nfe_id' => $chave,
        'tipo_documento' => 'NFE',
        'tipo_nota' => 0, // 0=entrada, 1=saída
        'origem' => 'xml_upload',
        'numero_nota' => substr($chave, -8),
        'serie' => '1',
        'data_emissao' => '2026-01-15',
        'valor_total' => 1500.00,
        'emit_cnpj' => substr($chave, 6, 14),
        'emit_razao_social' => 'Emitente Retry',
        'emit_uf' => 'SP',
        'dest_cnpj' => '00000000000191',
        'dest_razao_social' => 'Destinatário Retry',
        'dest_uf' => 'SP',
        'situacao_sefaz' => $verificada ? 'autorizada' : null,
        'verificado_sefaz_em' => $verificada ? now() : null,
        'payload' => [],
    ]);
}

beforeEach(function () {
    config()->set('services.webhook.consultas_cnpj_url', 'https://n8n.test/webhook/consultas-cnpj');
    config()->set('services.webhook.consultas_notas_url', 'https://n8n.test/webhook/consultas-notas');
    config()->set('services.api.token', 'token-retry-teste');
});

// ─── Compliance: pendentes endpoint ──────────────────────────────────────────

it('compliance: lista apenas participantes não-concluídos como pendentes', function () {
    $user = retryUser();
    $plano = retryPlanoPago();
    $lote = retryCriarLoteCompliance($user, $plano, ['total_participantes' => 3]);

    retryAdicionarParticipanteComStatus($lote, $user, '11111111000111', ConsultaResultado::STATUS_SUCESSO);
    retryAdicionarParticipanteComStatus($lote, $user, '22222222000222', ConsultaResultado::STATUS_ERRO, 'timeout do provedor');
    retryAdicionarParticipanteComStatus($lote, $user, '33333333000333', null);

    $response = actingAs($user)->getJson("/app/consulta/lote/{$lote->id}/pendentes");

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(2, 'pendentes');

    $cnpjs = collect($response->json('pendentes'))->pluck('cnpj')->all();
    expect($cnpjs)->toContain('22222222000222', '33333333000333')
        ->not->toContain('11111111000111');
});

it('compliance: pendentes 404 quando lote pertence a outro usuário', function () {
    $owner = retryUser();
    $intruder = retryUser();
    $lote = retryCriarLoteCompliance($owner, retryPlanoPago());

    actingAs($intruder)
        ->getJson("/app/consulta/lote/{$lote->id}/pendentes")
        ->assertNotFound();
});

// ─── Compliance: retentar endpoint ──────────────────────────────────────────

it('compliance: retry cria novo lote com parent_lote_id e despacha webhook', function () {
    $user = retryUser(credits: 200);
    $plano = retryPlanoPago();
    $lote = retryCriarLoteCompliance($user, $plano, ['total_participantes' => 1]);
    $pendente = retryAdicionarParticipanteComStatus($lote, $user, '44444444000144', ConsultaResultado::STATUS_ERRO);

    Http::fake(['n8n.test/*' => Http::response(['ok' => true], 200)]);

    $response = actingAs($user)->postJson("/app/consulta/lote/{$lote->id}/retentar", [
        'participante_ids' => [$pendente->id],
        'tab_id' => 'tab-retry-1',
    ]);

    $response->assertOk()->assertJsonPath('success', true);
    $novoLoteId = $response->json('consulta_lote_id');

    $novo = ConsultaLote::find($novoLoteId);
    expect($novo)->not->toBeNull();
    expect($novo->parent_lote_id)->toBe($lote->id);
    expect($novo->user_id)->toBe($user->id);
    expect($novo->plano_id)->toBe($plano->id);
    expect($novo->total_participantes)->toBe(1);
    expect($novo->participantes->pluck('id')->all())->toBe([$pendente->id]);

    Http::assertSent(function ($request) use ($novoLoteId) {
        $body = $request->data();

        return $request->url() === 'https://n8n.test/webhook/consultas-cnpj'
            && $body['consulta_lote_id'] === $novoLoteId
            && $body['is_retry'] === true;
    });
});

it('compliance: retry estorna créditos quando webhook falha', function () {
    $user = retryUser(credits: 50);
    $plano = retryPlanoPago();
    $lote = retryCriarLoteCompliance($user, $plano);
    $pendente = retryAdicionarParticipanteComStatus($lote, $user, '55555555000155', ConsultaResultado::STATUS_ERRO);

    Http::fake(['n8n.test/*' => Http::response(['error' => 'down'], 502)]);

    $saldoInicial = $user->fresh()->credits;

    actingAs($user)
        ->postJson("/app/consulta/lote/{$lote->id}/retentar", [
            'participante_ids' => [$pendente->id],
            'tab_id' => 'tab-retry-fail',
        ])
        ->assertStatus(502)
        ->assertJsonPath('success', false);

    expect($user->fresh()->credits)->toBe($saldoInicial);

    $loteRetry = ConsultaLote::where('parent_lote_id', $lote->id)->first();
    expect($loteRetry)->not->toBeNull();
    expect($loteRetry->status)->toBe(ConsultaLote::STATUS_ERRO);
    expect($loteRetry->error_code)->toBe('WEBHOOK_ERROR');
});

it('compliance: retry rejeita ids que não estão entre os pendentes', function () {
    $user = retryUser();
    $plano = retryPlanoPago();
    $lote = retryCriarLoteCompliance($user, $plano);
    $sucesso = retryAdicionarParticipanteComStatus($lote, $user, '66666666000166', ConsultaResultado::STATUS_SUCESSO);
    retryAdicionarParticipanteComStatus($lote, $user, '77777777000177', ConsultaResultado::STATUS_ERRO);

    Http::fake();

    actingAs($user)
        ->postJson("/app/consulta/lote/{$lote->id}/retentar", [
            'participante_ids' => [$sucesso->id],
            'tab_id' => 'tab-retry-x',
        ])
        ->assertStatus(422)
        ->assertJsonPath('error', 'Os participantes selecionados não estão pendentes neste lote.');

    Http::assertNothingSent();
});

it('compliance: retry rejeita quando saldo é insuficiente', function () {
    $user = retryUser(credits: 0);
    $plano = retryPlanoPago();
    $lote = retryCriarLoteCompliance($user, $plano);
    $pendente = retryAdicionarParticipanteComStatus($lote, $user, '88888888000188', ConsultaResultado::STATUS_ERRO);

    Http::fake();

    actingAs($user)
        ->postJson("/app/consulta/lote/{$lote->id}/retentar", [
            'participante_ids' => [$pendente->id],
            'tab_id' => 'tab-retry-poor',
        ])
        ->assertStatus(402)
        ->assertJsonPath('success', false);

    Http::assertNothingSent();
    expect(ConsultaLote::where('parent_lote_id', $lote->id)->exists())->toBeFalse();
});

it('compliance: retry de retry preserva cadeia parent_lote_id', function () {
    $user = retryUser(credits: 200);
    $plano = retryPlanoPago();
    $loteOriginal = retryCriarLoteCompliance($user, $plano);
    $pendente = retryAdicionarParticipanteComStatus($loteOriginal, $user, '99999999000199', ConsultaResultado::STATUS_ERRO);

    Http::fake(['n8n.test/*' => Http::response(['ok' => true], 200)]);

    $resp1 = actingAs($user)->postJson("/app/consulta/lote/{$loteOriginal->id}/retentar", [
        'participante_ids' => [$pendente->id],
        'tab_id' => 'tab-retry-l1',
    ])->assertOk();

    $loteL1Id = $resp1->json('consulta_lote_id');

    // Lote L1 acaba como erro também — adiciona pendente nele e retenta
    ConsultaLote::find($loteL1Id)->update(['status' => ConsultaLote::STATUS_FINALIZADO]);
    ConsultaResultado::create([
        'consulta_lote_id' => $loteL1Id,
        'participante_id' => $pendente->id,
        'status' => ConsultaResultado::STATUS_ERRO,
        'error_message' => 'falhou de novo',
        'consultado_em' => now(),
    ]);

    $resp2 = actingAs($user)->postJson("/app/consulta/lote/{$loteL1Id}/retentar", [
        'participante_ids' => [$pendente->id],
        'tab_id' => 'tab-retry-l2',
    ])->assertOk();

    $loteL2 = ConsultaLote::find($resp2->json('consulta_lote_id'));
    expect($loteL2->parent_lote_id)->toBe($loteL1Id);

    // Nem o lote L2 aponta pro original — só pro intermediário
    expect($loteL2->parent_lote_id)->not->toBe($loteOriginal->id);
});

// ─── Clearance: pendentes/retentar ───────────────────────────────────────────

it('clearance: lista apenas notas sem snapshot SEFAZ como pendentes', function () {
    $user = retryUser();
    $lote = retryCriarLoteCompliance($user, retryPlanoPago(), ['total_participantes' => 3]);

    retryCriarXmlNota($user, $lote, '35240413305697000150550000000404041953940001', verificada: true);
    retryCriarXmlNota($user, $lote, '35240413305697000150550000000404041953940002', verificada: false);
    retryCriarXmlNota($user, $lote, '35240413305697000150550000000404041953940003', verificada: false);

    $response = actingAs($user)->getJson("/app/clearance/notas/resultado/{$lote->id}/pendentes");

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(2, 'pendentes');

    $chaves = collect($response->json('pendentes'))->pluck('chave_acesso')->all();
    expect($chaves)->toContain(
        '35240413305697000150550000000404041953940002',
        '35240413305697000150550000000404041953940003'
    )->not->toContain('35240413305697000150550000000404041953940001');
});

it('clearance: retry cria novo lote com parent_lote_id e despacha webhook', function () {
    $user = retryUser(credits: 200);
    $plano = retryPlanoPago();
    $lote = retryCriarLoteCompliance($user, $plano, ['creditos_cobrados' => 5, 'total_participantes' => 1]);
    $nota = retryCriarXmlNota($user, $lote, '35240413305697000150550000000404041953940099', verificada: false);

    Http::fake(['n8n.test/*' => Http::response(['ok' => true], 200)]);

    $response = actingAs($user)->postJson("/app/clearance/notas/resultado/{$lote->id}/retentar", [
        'nota_ids' => [$nota->id],
        'tipo' => 'basico',
        'tab_id' => 'tab-clearance-retry',
    ]);

    $response->assertOk()->assertJsonPath('success', true);
    $novoLoteId = $response->json('consulta_lote_id');
    $novo = ConsultaLote::find($novoLoteId);
    expect($novo->parent_lote_id)->toBe($lote->id);
    expect($novo->status)->toBe(ConsultaLote::STATUS_PROCESSANDO);

    Http::assertSent(function ($request) use ($novoLoteId, $lote) {
        $body = $request->data();

        return $request->url() === 'https://n8n.test/webhook/consultas-notas'
            && $body['consulta_lote_id'] === $novoLoteId
            && $body['parent_lote_id'] === $lote->id
            && $body['is_retry'] === true;
    });
});

it('clearance: retry estorna créditos quando webhook falha', function () {
    $user = retryUser(credits: 50);
    $plano = retryPlanoPago();
    $lote = retryCriarLoteCompliance($user, $plano, ['creditos_cobrados' => 5, 'total_participantes' => 1]);
    $nota = retryCriarXmlNota($user, $lote, '35240413305697000150550000000404041953940088', verificada: false);

    Http::fake(['n8n.test/*' => Http::response([], 503)]);

    $saldoInicial = $user->fresh()->credits;

    actingAs($user)
        ->postJson("/app/clearance/notas/resultado/{$lote->id}/retentar", [
            'nota_ids' => [$nota->id],
            'tipo' => 'basico',
            'tab_id' => 'tab-clearance-retry-fail',
        ])
        ->assertStatus(502);

    expect($user->fresh()->credits)->toBe($saldoInicial);

    $loteRetry = ConsultaLote::where('parent_lote_id', $lote->id)->first();
    expect($loteRetry)->not->toBeNull();
    expect($loteRetry->status)->toBe(ConsultaLote::STATUS_ERRO);
});

it('clearance: retry rejeita 404 para lote de outro usuário', function () {
    $owner = retryUser();
    $intruder = retryUser();
    $lote = retryCriarLoteCompliance($owner, retryPlanoPago());
    retryCriarXmlNota($owner, $lote, '35240413305697000150550000000404041953940077', verificada: false);

    actingAs($intruder)
        ->getJson("/app/clearance/notas/resultado/{$lote->id}/pendentes")
        ->assertNotFound();
});

// ─── UI/listagem: badges ─────────────────────────────────────────────────────

it('historico exibe badge "Retry de #X" no lote derivado e "+N retry" no lote pai', function () {
    $user = retryUser();
    $plano = retryPlanoPago();
    $cliente = Cliente::create([
        'user_id' => $user->id,
        'tipo_pessoa' => 'PJ',
        'documento' => '12345678000100',
        'razao_social' => 'Cliente Histórico',
        'is_empresa_propria' => false,
    ]);

    $pai = retryCriarLoteCompliance($user, $plano, ['cliente_id' => $cliente->id]);
    $filho = ConsultaLote::create([
        'user_id' => $user->id,
        'cliente_id' => $cliente->id,
        'plano_id' => $plano->id,
        'status' => ConsultaLote::STATUS_FINALIZADO,
        'total_participantes' => 1,
        'creditos_cobrados' => 1,
        'tab_id' => 'tab-filho',
        'parent_lote_id' => $pai->id,
        'processado_em' => now(),
    ]);

    actingAs($user)
        ->get('/app/consulta/historico')
        ->assertOk()
        ->assertSee('Retry de #'.$pai->id, false)
        ->assertSee('+1 retry', false);
});
