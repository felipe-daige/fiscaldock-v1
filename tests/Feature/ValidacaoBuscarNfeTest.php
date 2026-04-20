<?php

use App\Models\Cliente;
use App\Models\ConsultaLote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

function buscarNfeValidKey(string $base43 = '3524041330569700015055000000040404195394099'): string
{
    $base43 = preg_replace('/\D/', '', $base43);
    if (strlen($base43) !== 43) {
        throw new \InvalidArgumentException('base precisa ter 43 dígitos');
    }

    $peso = 2;
    $soma = 0;
    for ($i = strlen($base43) - 1; $i >= 0; $i--) {
        $soma += ((int) $base43[$i]) * $peso;
        $peso = $peso === 9 ? 2 : $peso + 1;
    }
    $resto = $soma % 11;
    $dv = ($resto === 0 || $resto === 1) ? 0 : 11 - $resto;

    return $base43 . $dv;
}

function buscarNfeUser(int $credits = 100): User
{
    return User::factory()->create(['credits' => $credits]);
}

function buscarNfeEmpresaPropria(User $u): Cliente
{
    return Cliente::firstOrCreate(
        ['user_id' => $u->id, 'is_empresa_propria' => true],
        ['tipo_pessoa' => 'PJ', 'documento' => '00000000000191', 'razao_social' => 'Empresa Propria']
    );
}

beforeEach(function () {
    config()->set('services.webhook.buscas_notas_url', 'https://n8n.test/webhook/buscas-notas');
    config()->set('services.api.token', 'token-teste');
});

it('rejeita chave com menos de 44 dígitos (422)', function () {
    $user = buscarNfeUser();
    $empresaPropria = buscarNfeEmpresaPropria($user);

    $response = actingAs($user)->postJson('/app/clearance/buscar/consultar', [
        'tipo_documento' => 'nfe',
        'chave_acesso' => '12345',
        'cliente_id' => $empresaPropria->id,
        'tab_id' => 'tab-123',
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('errors.chave_acesso.0', 'A chave de acesso deve ter 44 dígitos numéricos.');
});

it('rejeita chave com dígito verificador inválido (422)', function () {
    $user = buscarNfeUser();
    $empresaPropria = buscarNfeEmpresaPropria($user);

    $chave = str_repeat('1', 44);

    $response = actingAs($user)->postJson('/app/clearance/buscar/consultar', [
        'tipo_documento' => 'nfe',
        'chave_acesso' => $chave,
        'cliente_id' => $empresaPropria->id,
        'tab_id' => 'tab-123',
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('errors.chave_acesso.0', 'Dígito verificador da chave de acesso inválido.');
});

it('rejeita nfse no MVP (422)', function () {
    $user = buscarNfeUser();
    $empresaPropria = buscarNfeEmpresaPropria($user);

    $response = actingAs($user)->postJson('/app/clearance/buscar/consultar', [
        'tipo_documento' => 'nfse',
        'chave_acesso' => buscarNfeValidKey(),
        'cliente_id' => $empresaPropria->id,
        'tab_id' => 'tab-123',
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('errors.tipo_documento.0', 'NFS-e ainda não é suportada. Em breve.');
});

it('aceita cte modelo 57 e despacha no mesmo webhook (200)', function () {
    $user = buscarNfeUser(credits: 50);
    $empresaPropria = buscarNfeEmpresaPropria($user);

    Http::fake([
        'n8n.test/*' => Http::response(['ok' => true], 200),
    ]);

    $chaveCte = buscarNfeValidKey('3524041330569700015057000000040404195394099');

    $response = actingAs($user)->postJson('/app/clearance/buscar/consultar', [
        'tipo_documento' => 'cte',
        'chave_acesso' => $chaveCte,
        'cliente_id' => $empresaPropria->id,
        'tab_id' => 'tab-cte-123',
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonPath('tab_id', 'tab-cte-123');

    $lote = ConsultaLote::latest('id')->first();
    expect($lote)->not->toBeNull();
    expect($lote->creditos_cobrados)->toBe(14);
    expect($user->fresh()->credits)->toBe(36);

    Http::assertSent(function ($request) use ($chaveCte, $lote, $empresaPropria) {
        $body = $request->data();
        return $request->url() === 'https://n8n.test/webhook/clearance-nfe'
            && $request->hasHeader('X-API-Token', 'token-teste')
            && ($body['tipo_documento'] ?? null) === 'CTE'
            && ($body['chave_acesso'] ?? null) === $chaveCte
            && ($body['cliente_id'] ?? null) === $empresaPropria->id
            && ($body['consulta_lote_id'] ?? null) === $lote->id;
    });
});

it('rejeita quando cliente_id nao e enviado (422)', function () {
    $user = buscarNfeUser();
    buscarNfeEmpresaPropria($user);

    $response = actingAs($user)->postJson('/app/clearance/buscar/consultar', [
        'tipo_documento' => 'nfe',
        'chave_acesso' => buscarNfeValidKey(),
        'cliente_id' => null,
        'tab_id' => 'tab-sem-cliente',
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('errors.cliente_id.0', 'Selecione o cliente associado antes de consultar.');
});

it('rejeita cliente_id que pertence a outro usuário (403)', function () {
    $user = buscarNfeUser();
    $outro = buscarNfeUser();
    $clienteOutro = Cliente::create([
        'user_id' => $outro->id,
        'tipo_pessoa' => 'PJ',
        'documento' => '11222333000181',
        'razao_social' => 'Outro Cliente',
    ]);

    $response = actingAs($user)->postJson('/app/clearance/buscar/consultar', [
        'tipo_documento' => 'nfe',
        'chave_acesso' => buscarNfeValidKey(),
        'cliente_id' => $clienteOutro->id,
        'tab_id' => 'tab-123',
    ]);

    $response->assertStatus(403);
});

it('rejeita quando usuário não tem créditos suficientes (402)', function () {
    $user = buscarNfeUser(credits: 5);
    $empresaPropria = buscarNfeEmpresaPropria($user);

    $response = actingAs($user)->postJson('/app/clearance/buscar/consultar', [
        'tipo_documento' => 'nfe',
        'chave_acesso' => buscarNfeValidKey(),
        'cliente_id' => $empresaPropria->id,
        'tab_id' => 'tab-123',
    ]);

    $response->assertStatus(402)
        ->assertJsonPath('custo_necessario', 14)
        ->assertJsonPath('saldo_atual', 5);

    expect(ConsultaLote::count())->toBe(0);
    expect($user->fresh()->credits)->toBe(5);
});

it('happy path: cria lote, debita créditos, despacha webhook (200)', function () {
    $user = buscarNfeUser(credits: 50);
    $empresaPropria = buscarNfeEmpresaPropria($user);

    Http::fake([
        'n8n.test/*' => Http::response(['ok' => true], 200),
    ]);

    $chave = buscarNfeValidKey();

    $response = actingAs($user)->postJson('/app/clearance/buscar/consultar', [
        'tipo_documento' => 'nfe',
        'chave_acesso' => $chave,
        'cliente_id' => $empresaPropria->id,
        'tab_id' => 'tab-happy-123',
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonStructure(['consulta_lote_id', 'tab_id', 'progress_url', 'novo_saldo']);

    $lote = ConsultaLote::latest('id')->first();
    expect($lote)->not->toBeNull();
    expect($lote->user_id)->toBe($user->id);
    expect($lote->plano_id)->toBeNull();
    expect($lote->total_participantes)->toBe(1);
    expect($lote->creditos_cobrados)->toBe(14);
    expect($lote->cliente_id)->toBe($empresaPropria->id);
    expect($lote->status)->toBe(ConsultaLote::STATUS_PROCESSANDO);
    expect($lote->tab_id)->toBe('tab-happy-123');

    expect($user->fresh()->credits)->toBe(50 - 14);

    Http::assertSent(function ($request) use ($chave, $lote, $empresaPropria) {
        $body = $request->data();
        return $request->url() === 'https://n8n.test/webhook/clearance-nfe'
            && $request->hasHeader('X-API-Token', 'token-teste')
            && ($body['tipo_documento'] ?? null) === 'NFE'
            && ($body['chave_acesso'] ?? null) === $chave
            && ($body['cliente_id'] ?? null) === $empresaPropria->id
            && ($body['tab_id'] ?? null) === 'tab-happy-123'
            && ($body['consulta_lote_id'] ?? null) === $lote->id
            && array_key_exists('progress_url', $body)
            && array_key_exists('user_id', $body);
    });
});

it('estorna créditos e retorna 502 quando webhook responde com erro', function () {
    $user = buscarNfeUser(credits: 50);
    $empresaPropria = buscarNfeEmpresaPropria($user);

    Http::fake([
        'n8n.test/*' => Http::response(['error' => 'boom'], 500),
    ]);

    $response = actingAs($user)->postJson('/app/clearance/buscar/consultar', [
        'tipo_documento' => 'nfe',
        'chave_acesso' => buscarNfeValidKey(),
        'cliente_id' => $empresaPropria->id,
        'tab_id' => 'tab-fail-502',
    ]);

    $response->assertStatus(502);

    $lote = ConsultaLote::latest('id')->first();
    expect($lote->status)->toBe(ConsultaLote::STATUS_ERRO);
    expect($lote->error_code)->toBe('WEBHOOK_ERROR');
    expect($user->fresh()->credits)->toBe(50);
});

it('estorna créditos quando Http::fake lança exceção', function () {
    $user = buscarNfeUser(credits: 50);
    $empresaPropria = buscarNfeEmpresaPropria($user);

    Http::fake(function () {
        throw new \RuntimeException('rede caiu');
    });

    $response = actingAs($user)->postJson('/app/clearance/buscar/consultar', [
        'tipo_documento' => 'nfe',
        'chave_acesso' => buscarNfeValidKey(),
        'cliente_id' => $empresaPropria->id,
        'tab_id' => 'tab-exc-500',
    ]);

    $response->assertStatus(500);

    $lote = ConsultaLote::latest('id')->first();
    expect($lote->status)->toBe(ConsultaLote::STATUS_ERRO);
    expect($user->fresh()->credits)->toBe(50);
});
