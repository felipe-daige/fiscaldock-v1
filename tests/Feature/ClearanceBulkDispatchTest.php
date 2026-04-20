<?php

use App\Models\Cliente;
use App\Models\ConsultaLote;
use App\Models\EfdImportacao;
use App\Models\EfdNota;
use App\Models\Participante;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
