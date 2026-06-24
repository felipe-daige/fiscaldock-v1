<?php

use App\Models\ConsultaLote;
use App\Models\ConsultaResultado;
use App\Services\Consultas\FonteRegistry;
use App\Services\Consultas\Persistencia\PersistenciaCnpj;
use App\Services\Consultas\RetryConsultaService;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

/** Grava o mapa _fontes_erro direto num ConsultaResultado (participante). */
function gravarFontesErro(int $loteId, int $participanteId, array $erros): void
{
    $linha = ConsultaResultado::firstOrNew([
        'consulta_lote_id' => $loteId,
        'participante_id' => $participanteId,
    ]);
    $dados = $linha->resultado_dados ?? [];
    $dados['_fontes_erro'] = $erros;
    $linha->resultado_dados = $dados;
    $linha->status = $linha->status ?: 'erro';
    $linha->save();
}

function custoFonte(string $chave): int
{
    return app(FonteRegistry::class)->get($chave)->custoCreditos();
}

// ---------------------------------------------------------------------------
// Task 1 — _fontes_erro enriquecido (objeto + tentativas + retrocompat)
// ---------------------------------------------------------------------------

it('grava _fontes_erro como objeto com status/codigo/tentativas', function () {
    [$loteId, $participanteId] = montarLoteParticipante();
    $svc = app(PersistenciaCnpj::class);

    $svc->marcarErroFonte($loteId, 'participante', $participanteId, 'cnd_federal', 'integracao', 'retry', 600);

    $row = ConsultaResultado::where('consulta_lote_id', $loteId)->first();
    expect($row->resultado_dados['_fontes_erro']['cnd_federal'])->toMatchArray([
        'origem' => 'integracao', 'status' => 'retry', 'codigo' => 600, 'tentativas' => 0,
    ]);
});

it('preserva tentativas numa re-falha da mesma fonte', function () {
    [$loteId, $participanteId] = montarLoteParticipante();
    $svc = app(PersistenciaCnpj::class);

    $svc->marcarErroFonte($loteId, 'participante', $participanteId, 'cnd_federal', 'integracao', 'retry', 600);
    $svc->incrementarTentativaFonte($loteId, 'participante', $participanteId, 'cnd_federal');
    $svc->marcarErroFonte($loteId, 'participante', $participanteId, 'cnd_federal', 'integracao', 'retry', 600);

    $row = ConsultaResultado::where('consulta_lote_id', $loteId)->first();
    expect($row->resultado_dados['_fontes_erro']['cnd_federal']['tentativas'])->toBe(1);
});

it('normaliza entrada string legada como retry/tentativas-0', function () {
    $svc = app(PersistenciaCnpj::class);

    expect($svc->normalizarFontesErro(['cnd_federal' => 'integracao']))->toMatchArray([
        'cnd_federal' => ['origem' => 'integracao', 'status' => 'retry', 'codigo' => null, 'tentativas' => 0],
    ]);
    expect($svc->normalizarFontesErro(['x' => 'interno'])['x']['status'])->toBeNull();
});

// ---------------------------------------------------------------------------
// Task 2 — RetryConsultaService: pendentesRetry + precificar
// ---------------------------------------------------------------------------

it('lista só fontes retry com tentativas 0 como elegíveis e precifica 50% off', function () {
    config()->set('consultas.retry.desconto_pct', 50);
    config()->set('consultas.retry.max_por_fonte', 1);
    [$loteId, $participanteId] = montarLoteParticipante();

    gravarFontesErro($loteId, $participanteId, [
        'cnd_federal' => ['origem' => 'integracao', 'status' => 'retry', 'codigo' => 600, 'tentativas' => 0],
        'cndt'        => ['origem' => 'integracao', 'status' => 'fatal', 'codigo' => 602, 'tentativas' => 0],
        'crf_fgts'    => ['origem' => 'integracao', 'status' => 'retry', 'codigo' => 600, 'tentativas' => 1],
    ]);

    $out = app(RetryConsultaService::class)->pendentesRetry(ConsultaLote::find($loteId));

    expect(collect($out['elegiveis'])->pluck('fonte')->all())->toBe(['cnd_federal']);
    expect(collect($out['inelegiveis'])->pluck('fonte')->sort()->values()->all())->toBe(['cndt', 'crf_fgts']);
    expect($out['elegiveis'][0]['preco_creditos'])->toBe((int) ceil(custoFonte('cnd_federal') * 0.5));
    expect($out['total_preco_creditos'])->toBe((int) ceil(custoFonte('cnd_federal') * 0.5));
});

it('marca o motivo dos inelegíveis (fatal / esgotado)', function () {
    config()->set('consultas.retry.max_por_fonte', 1);
    [$loteId, $participanteId] = montarLoteParticipante();
    gravarFontesErro($loteId, $participanteId, [
        'cndt'     => ['origem' => 'integracao', 'status' => 'fatal', 'codigo' => 602, 'tentativas' => 0],
        'crf_fgts' => ['origem' => 'integracao', 'status' => 'retry', 'codigo' => 600, 'tentativas' => 1],
    ]);

    $out = app(RetryConsultaService::class)->pendentesRetry(ConsultaLote::find($loteId));
    $motivos = collect($out['inelegiveis'])->pluck('motivo', 'fonte');
    expect($motivos['cndt'])->toBe('fatal');
    expect($motivos['crf_fgts'])->toBe('esgotado');
});

it('precifica somando ceil por fonte', function () {
    config()->set('consultas.retry.desconto_pct', 50);
    $r = app(RetryConsultaService::class)->precificar([
        ['custo_creditos' => 5], ['custo_creditos' => 3], // ceil(2.5)+ceil(1.5)=3+2=5
    ]);
    expect($r['creditos'])->toBe(5);
});

// ---------------------------------------------------------------------------
// Task 3 — Job somenteFontes + executar + FecharRetryService (settlement)
// ---------------------------------------------------------------------------

use App\Jobs\ProcessarConsultaJob;
use App\Models\MonitoramentoPlano;
use App\Models\Participante;
use App\Models\User;
use App\Services\CreditService;
use App\Services\Consultas\FecharRetryService;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;

function montarLoteComPlano(string $codigo = 'licitacao'): array
{
    $user = User::factory()->create();
    $participante = Participante::create([
        'user_id' => $user->id, 'documento' => '19131243000197', 'razao_social' => 'PART', 'uf' => 'SP',
    ]);
    $lote = ConsultaLote::create([
        'user_id' => $user->id,
        'plano_id' => MonitoramentoPlano::porCodigo($codigo)->id,
        'status' => ConsultaLote::STATUS_FINALIZADO,
        'total_participantes' => 1,
        'creditos_cobrados' => 10,
        'tab_id' => 'tab-test',
    ]);

    return [$lote, $participante, $user];
}

it('executar debita 50% e despacha job escopado às fontes selecionadas', function () {
    Bus::fake();
    config()->set('consultas.retry.desconto_pct', 50);
    [$lote, $p, $user] = montarLoteComPlano();
    app(CreditService::class)->add($user, 100);
    gravarFontesErro($lote->id, $p->id, [
        'cnd_federal' => ['origem' => 'integracao', 'status' => 'retry', 'codigo' => 600, 'tentativas' => 0],
    ]);

    $preco = (int) ceil(custoFonte('cnd_federal') * 0.5);
    $saldoAntes = app(CreditService::class)->getBalance($user);

    app(RetryConsultaService::class)->executar($lote->fresh(), [
        ['alvo_tipo' => 'participante', 'alvo_id' => $p->id, 'fonte' => 'cnd_federal'],
    ]);

    expect(app(CreditService::class)->getBalance($user->fresh()))->toBe($saldoAntes - $preco);

    Bus::assertBatched(fn ($batch) => collect($batch->jobs)->contains(
        fn ($job) => $job instanceof ProcessarConsultaJob && $job->somenteFontes === ['cnd_federal']
    ));

    $row = ConsultaResultado::where('consulta_lote_id', $lote->id)->first();
    $erros = app(PersistenciaCnpj::class)->normalizarFontesErro($row->resultado_dados['_fontes_erro']);
    expect($erros['cnd_federal']['tentativas'])->toBe(1); // trava 1x antes do dispatch
});

it('executar recusa fonte inelegível forjada no payload', function () {
    [$lote, $p, $user] = montarLoteComPlano();
    app(CreditService::class)->add($user, 100);
    gravarFontesErro($lote->id, $p->id, [
        'cndt' => ['origem' => 'integracao', 'status' => 'fatal', 'codigo' => 602, 'tentativas' => 0],
    ]);

    expect(fn () => app(RetryConsultaService::class)->executar($lote->fresh(), [
        ['alvo_tipo' => 'participante', 'alvo_id' => $p->id, 'fonte' => 'cndt'],
    ]))->toThrow(\Illuminate\Validation\ValidationException::class);
});

it('settlement estorna só a parcela do retry quando a fonte re-falha', function () {
    config()->set('consultas.retry.desconto_pct', 50);
    [$lote, $p, $user] = montarLoteComPlano();
    $preco = (int) ceil(custoFonte('cnd_federal') * 0.5);

    // simula retry cobrado e re-falhado: envelope no cache + _fontes_erro ainda marcado
    Cache::put("consulta_retry_charge:{$lote->id}:participante:{$p->id}", ['cnd_federal' => $preco], 86400);
    gravarFontesErro($lote->id, $p->id, [
        'cnd_federal' => ['origem' => 'integracao', 'status' => 'retry', 'codigo' => 600, 'tentativas' => 1],
    ]);
    $saldoAntes = app(CreditService::class)->getBalance($user);

    app(FecharRetryService::class)->fechar($lote->id, [
        ['alvo_tipo' => 'participante', 'alvo_id' => $p->id, 'fonte' => 'cnd_federal'],
    ]);

    expect(app(CreditService::class)->getBalance($user->fresh()))->toBe($saldoAntes + $preco);
});

it('settlement não estorna quando a reconsulta teve sucesso (marca limpa)', function () {
    config()->set('consultas.retry.desconto_pct', 50);
    [$lote, $p, $user] = montarLoteComPlano();
    $preco = (int) ceil(custoFonte('cnd_federal') * 0.5);

    Cache::put("consulta_retry_charge:{$lote->id}:participante:{$p->id}", ['cnd_federal' => $preco], 86400);
    // sucesso → sem _fontes_erro pra cnd_federal (gravar teria limpado)
    ConsultaResultado::create([
        'consulta_lote_id' => $lote->id, 'participante_id' => $p->id, 'status' => 'sucesso',
        'resultado_dados' => ['cnd_federal' => ['status' => 'Negativa']],
    ]);
    $saldoAntes = app(CreditService::class)->getBalance($user);

    app(FecharRetryService::class)->fechar($lote->id, [
        ['alvo_tipo' => 'participante', 'alvo_id' => $p->id, 'fonte' => 'cnd_federal'],
    ]);

    expect(app(CreditService::class)->getBalance($user->fresh()))->toBe($saldoAntes); // receita mantida
});

// ---------------------------------------------------------------------------
// Task 4 — Endpoints HTTP (pendentes + retry)
// ---------------------------------------------------------------------------

it('GET retry/pendentes lista elegíveis e saldo do dono', function () {
    [$lote, $p, $user] = montarLoteComPlano();
    app(CreditService::class)->add($user, 50);
    gravarFontesErro($lote->id, $p->id, [
        'cnd_federal' => ['origem' => 'integracao', 'status' => 'retry', 'codigo' => 600, 'tentativas' => 0],
    ]);

    $this->actingAs($user)
        ->getJson("/app/consulta/lote/{$lote->id}/retry/pendentes")
        ->assertOk()
        ->assertJsonPath('elegiveis.0.fonte', 'cnd_federal')
        ->assertJsonPath('saldo', 50);
});

it('GET retry/pendentes de lote de outro user dá 404', function () {
    [$lote, $p, $user] = montarLoteComPlano();
    $outro = User::factory()->create();

    $this->actingAs($outro)
        ->getJson("/app/consulta/lote/{$lote->id}/retry/pendentes")
        ->assertNotFound();
});

it('POST retry sem saldo dá 402 e nada é cobrado/despachado', function () {
    Bus::fake();
    [$lote, $p, $user] = montarLoteComPlano();
    gravarFontesErro($lote->id, $p->id, [
        'cnd_federal' => ['origem' => 'integracao', 'status' => 'retry', 'codigo' => 600, 'tentativas' => 0],
    ]);

    $this->actingAs($user)
        ->postJson("/app/consulta/lote/{$lote->id}/retry", [
            'selecao' => [['alvo_tipo' => 'participante', 'alvo_id' => $p->id, 'fonte' => 'cnd_federal']],
        ])
        ->assertStatus(402);

    Bus::assertNothingBatched();
});

it('POST retry feliz cobra, despacha e responde novo saldo', function () {
    Bus::fake();
    config()->set('consultas.retry.desconto_pct', 50);
    [$lote, $p, $user] = montarLoteComPlano();
    app(CreditService::class)->add($user, 100);
    gravarFontesErro($lote->id, $p->id, [
        'cnd_federal' => ['origem' => 'integracao', 'status' => 'retry', 'codigo' => 600, 'tentativas' => 0],
    ]);

    $this->actingAs($user)
        ->postJson("/app/consulta/lote/{$lote->id}/retry", [
            'selecao' => [['alvo_tipo' => 'participante', 'alvo_id' => $p->id, 'fonte' => 'cnd_federal']],
        ])
        ->assertOk()
        ->assertJsonPath('success', true);

    Bus::assertBatched(fn ($b) => $b->name === "consulta-retry-{$lote->id}");
});

// ---------------------------------------------------------------------------
// Task 6 — Regressão Model A (estorno do 600) + duplo-clique
// ---------------------------------------------------------------------------

it('código 600 (classe retry) agora acumula estorno no fechamento do lote', function () {
    [$loteId, $participanteId, $userId] = montarLoteParticipante();

    \Illuminate\Support\Facades\Http::fake([
        'api.infosimples.com/*' => \Illuminate\Support\Facades\Http::response(['code' => 600, 'code_message' => 'temporariamente indisponível'], 200),
    ]);

    ProcessarConsultaJob::dispatchSync(
        loteId: $loteId, alvoTipo: 'participante', alvoId: $participanteId, userId: $userId, tabId: 'tab-test',
        consultasIncluidas: ['cnd_federal'], alvo: ['cnpj' => '19131243000197'],
        etapas: ['Preparando consulta', 'Certidões Federais'],
    );

    // Model A: retry passou a ser estornável → custoCreditos da cnd_federal vai pro estorno.
    expect((int) Cache::get("consulta_estorno:{$loteId}:participante:{$participanteId}"))
        ->toBe((int) config('consultas.fontes.cnd_federal', 2));
});

it('POST retry com lock ativo (duplo-clique) responde 409 sem cobrar duas vezes', function () {
    Bus::fake();
    config()->set('consultas.retry.desconto_pct', 50);
    [$lote, $p, $user] = montarLoteComPlano();
    app(CreditService::class)->add($user, 100);
    gravarFontesErro($lote->id, $p->id, [
        'cnd_federal' => ['origem' => 'integracao', 'status' => 'retry', 'codigo' => 600, 'tentativas' => 0],
    ]);

    // simula a 1ª requisição ainda processando: lock já tomado
    Cache::lock("consulta_retry_lock:{$user->id}:{$lote->id}", 10)->get();
    $saldoAntes = app(CreditService::class)->getBalance($user);

    $this->actingAs($user)
        ->postJson("/app/consulta/lote/{$lote->id}/retry", [
            'selecao' => [['alvo_tipo' => 'participante', 'alvo_id' => $p->id, 'fonte' => 'cnd_federal']],
        ])
        ->assertStatus(409);

    expect(app(CreditService::class)->getBalance($user->fresh()))->toBe($saldoAntes); // não cobrou
    Bus::assertNothingBatched();
});

// ---------------------------------------------------------------------------
// GUARD — invariante: fonte em retry/fatal NÃO é persistida como blob (fica retriável).
// Verificado contra dado real (lote 213 prod): nenhuma fonte escapa hoje — os normalizers
// InfoSimples retornam [] em retry/fatal, então o job marca _fontes_erro. Este teste trava
// esse contrato (se um normalizer futuro passar a fabricar blob no erro, quebra aqui).
// ---------------------------------------------------------------------------

it('fonte com timeout (retry) não é persistida como blob e vira retriável', function () {
    [$loteId, $participanteId, $userId] = montarLoteParticipante();
    config()->set('consultas.cnd_estadual.ufs_cobertas', ['SP']);
    config()->set('consultas.infosimples_ativo', true);
    config()->set('consultas.providers.infosimples.token', 'tok');

    // code 610 = retry → normalizar devolve [] → job marca _fontes_erro, sem persistir blob.
    \Illuminate\Support\Facades\Http::fake([
        'api.infosimples.com/*' => \Illuminate\Support\Facades\Http::response(['code' => 610, 'code_message' => 'Tentativas excedidas'], 200),
    ]);

    ProcessarConsultaJob::dispatchSync(
        loteId: $loteId, alvoTipo: 'participante', alvoId: $participanteId, userId: $userId, tabId: 'tab-test',
        consultasIncluidas: ['cnd_estadual'], alvo: ['cnpj' => '19131243000197', 'uf' => 'SP'],
        etapas: ['Preparando consulta', 'Certidões Estaduais'],
    );

    $r = ConsultaResultado::where('consulta_lote_id', $loteId)->first();
    $d = (array) $r->resultado_dados;
    expect($d['cnd_estadual'] ?? null)->toBeNull(); // NÃO persistido (re-consultável)
    expect($d['_fontes_erro']['cnd_estadual'] ?? null)->toMatchArray(['origem' => 'integracao', 'status' => 'retry', 'codigo' => 610]);
});
