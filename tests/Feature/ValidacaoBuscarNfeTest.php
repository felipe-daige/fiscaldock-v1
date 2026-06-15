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
    // Habilita a feature flag para que os testes de validação cheguem além do guard 503
    config(['clearance.busca_avulsa.habilitada' => true]);
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
        ->assertJsonPath('errors.blocos.0', 'A chave #1 do bloco 1 deve ter 44 dígitos numéricos.');
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
        ->assertJsonPath('errors.blocos.0', 'A chave #1 do bloco 1 possui dígito verificador inválido.');
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
        ->assertJsonPath('errors.blocos.0', 'NFS-e ainda não é suportada. Em breve.');
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
