<?php

use App\Jobs\ProcessarXmlImportacaoJob;
use App\Models\Cliente;
use App\Models\User;
use App\Models\XmlImportacao;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function payloadImportar(int $clienteId): array
{
    $xml = file_get_contents(base_path('tests/Fixtures/nfe/50240197551165000193550010000248021000214750-nfe.xml'));
    return [
        'tipo_documento' => 'NFE', 'modo_envio' => 'xml', 'cliente_id' => $clienteId,
        'tab_id' => 'tab-xyz',
        'arquivos' => [[
            'nome' => 'nota.xml', 'tipo' => 'text/xml',
            'conteudo_base64' => base64_encode($xml),
        ]],
    ];
}

it('exige cliente_id (escolha do cliente é obrigatória no upload)', function () {
    Bus::fake();
    Storage::fake('local');
    $user = User::factory()->create();
    $payload = payloadImportar(1);
    unset($payload['cliente_id']);

    $this->actingAs($user)->postJson('/app/importacao/xml/importar', $payload)
        ->assertStatus(422)->assertJsonValidationErrorFor('cliente_id');

    Bus::assertNotDispatched(ProcessarXmlImportacaoJob::class);
});

it('despacha o Job e persiste os XMLs no storage', function () {
    Bus::fake();
    Storage::fake('local');
    $user = User::factory()->create();
    $cliente = Cliente::create(['user_id' => $user->id, 'documento' => '97551165000193', 'razao_social' => 'HIDRATOP', 'is_empresa_propria' => true]);

    $this->actingAs($user)->postJson('/app/importacao/xml/importar', payloadImportar($cliente->id))
        ->assertOk()->assertJson(['success' => true]);

    $imp = XmlImportacao::where('user_id', $user->id)->firstOrFail();
    expect($imp->status)->toBe('processando');
    expect(Storage::disk('local')->files("xml-imports/{$imp->id}"))->not->toBeEmpty();
    Bus::assertDispatched(ProcessarXmlImportacaoJob::class, fn ($job) => $job->ownerDoc === '97551165000193');
});

it('rejeita cliente de outro usuário', function () {
    $user = User::factory()->create();
    $outro = User::factory()->create();
    $cliente = Cliente::create(['user_id' => $outro->id, 'documento' => '11111111000111', 'razao_social' => 'X']);

    $this->actingAs($user)->postJson('/app/importacao/xml/importar', payloadImportar($cliente->id))
        ->assertStatus(422)->assertJsonValidationErrorFor('cliente_id');
});
