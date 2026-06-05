<?php

use App\Models\Cliente;
use App\Models\User;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

it('lista apenas as competências do cliente selecionado', function () {
    [$userId, $clienteComDados] = montarMassaFechamento();

    // Segundo cliente do MESMO usuário, sem nenhuma nota.
    $clienteVazio = DB::table('clientes')->insertGetId([
        'user_id' => $userId, 'razao_social' => 'EMPRESA SEM DADOS',
        'documento' => '00000000000200', 'is_empresa_propria' => false,
        'created_at' => now(), 'updated_at' => now(),
    ]);

    actingAs(User::find($userId));

    $comDados = getJson("/app/resumo-fiscal/competencias?cliente_id={$clienteComDados}");
    $comDados->assertOk();
    expect($comDados->json('competencias'))->toHaveCount(1)
        ->and($comDados->json('competencias.0.value'))->toBe('2024-01');

    $vazio = getJson("/app/resumo-fiscal/competencias?cliente_id={$clienteVazio}");
    $vazio->assertOk();
    expect($vazio->json('competencias'))->toBe([]);
});

it('ignora notas sem data_emissao (canceladas) — não cria competência fantasma "dez/1969"', function () {
    [$userId, $clienteId] = montarMassaFechamento();

    // Documento cancelado cujo C100 não traz DT_DOC: data_emissao NULL.
    \App\Models\EfdNota::create([
        'user_id' => $userId, 'cliente_id' => $clienteId,
        'importacao_id' => \App\Models\EfdImportacao::where('user_id', $userId)->first()->id,
        'chave_acesso' => str_pad('Z', 44, '0', STR_PAD_LEFT), 'modelo' => '55',
        'numero' => 99999, 'serie' => '1', 'data_emissao' => null,
        'tipo_operacao' => 'saida', 'origem_arquivo' => 'fiscal',
        'valor_total' => 0, 'cancelada' => true,
    ]);

    actingAs(User::find($userId));

    $resp = getJson("/app/resumo-fiscal/competencias?cliente_id={$clienteId}");
    $resp->assertOk();

    $valores = collect($resp->json('competencias'))->pluck('value');
    expect($valores)->toContain('2024-01')        // competência real preservada
        ->and($valores)->not->toContain(null)     // nada de NULL
        ->and($valores->filter(fn ($v) => str_starts_with((string) $v, '1969')))->toBeEmpty();
});

it('não vaza competências de cliente de outro usuário', function () {
    [$userId, $clienteComDados] = montarMassaFechamento();

    $outroUser = User::factory()->create();
    actingAs($outroUser);

    getJson("/app/resumo-fiscal/competencias?cliente_id={$clienteComDados}")
        ->assertNotFound();
});

it('exige cliente_id', function () {
    $user = User::factory()->create();
    actingAs($user);

    getJson('/app/resumo-fiscal/competencias')->assertStatus(422);
});

it('index marca o estado sem-importação quando o cliente padrão não tem dados', function () {
    $user = User::factory()->create();
    Cliente::create([
        'user_id' => $user->id, 'razao_social' => 'EMPRESA NOVA',
        'documento' => '00000000000300', 'is_empresa_propria' => true, 'ativo' => true,
    ]);

    actingAs($user);

    $resp = $this->get('/app/resumo-fiscal');
    $resp->assertOk();
    // Bloco de "precisa importar" presente e VISÍVEL (sem a classe hidden no wrapper).
    $resp->assertSee('id="rf-sem-importacao"', false);
    $resp->assertSee('Importar EFD', false);
    $resp->assertSee('id="rf-conteudo" class="hidden"', false);
});

it('index mostra o conteúdo quando o cliente padrão tem dados', function () {
    [$userId] = montarMassaFechamento();

    actingAs(User::find($userId));

    $resp = $this->get('/app/resumo-fiscal');
    $resp->assertOk();
    // sem-importacao renderizado mas oculto; conteúdo visível.
    $resp->assertSee('id="rf-sem-importacao" class="hidden"', false);
    $resp->assertSee('id="rf-conteudo" class=""', false);
});
