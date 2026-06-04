<?php

use App\Models\MonitoramentoPlano;
use App\Models\Participante;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class);

function preparaUserComParticipante(): array
{
    $user = User::factory()->create();

    $participante = Participante::create([
        'user_id' => $user->id,
        'documento' => '12345678000199',
        'razao_social' => 'Fornecedor Teste',
        'uf' => 'SP',
        'crt' => '3',
    ]);

    $plano = MonitoramentoPlano::porCodigo('gratuito') ?? MonitoramentoPlano::firstOrFail();

    return [$user, $participante, $plano];
}

function payloadExecutarConsulta(Participante $p, MonitoramentoPlano $plano): array
{
    return [
        'participante_ids' => [$p->id],
        'plano_id' => $plano->id,
        'tab_id' => 'tab-webhook-test',
    ];
}

it('despacha para WEBHOOK_CONSULTAS_CNPJ_PARTICIPANTE_URL quando setado', function () {
    [$user, $participante, $plano] = preparaUserComParticipante();

    config([
        'services.webhook.consultas_cnpj_participante_url' => 'https://example.test/webhook/participante',
        'services.webhook.consultas_cnpj_cliente_url' => 'https://example.test/webhook/cliente',
        'services.webhook.consultas_cnpj_url' => 'https://example.test/webhook/legado',
    ]);

    Http::fake([
        '*' => Http::response(['ok' => true], 200),
    ]);

    actingAs($user)
        ->postJson('/app/consulta/nova/executar', payloadExecutarConsulta($participante, $plano))
        ->assertOk();

    Http::assertSent(fn ($req) => $req->url() === 'https://example.test/webhook/participante');
});

it('cai para WEBHOOK_CONSULTAS_CNPJ_URL (legado) se PARTICIPANTE não setado', function () {
    [$user, $participante, $plano] = preparaUserComParticipante();

    config([
        'services.webhook.consultas_cnpj_participante_url' => null,
        'services.webhook.consultas_cnpj_cliente_url' => null,
        'services.webhook.consultas_cnpj_url' => 'https://example.test/webhook/legado',
    ]);

    Http::fake([
        '*' => Http::response(['ok' => true], 200),
    ]);

    actingAs($user)
        ->postJson('/app/consulta/nova/executar', payloadExecutarConsulta($participante, $plano))
        ->assertOk();

    Http::assertSent(fn ($req) => $req->url() === 'https://example.test/webhook/legado');
});

it('retorna 500 quando nenhum webhook está configurado', function () {
    [$user, $participante, $plano] = preparaUserComParticipante();

    config([
        'services.webhook.consultas_cnpj_participante_url' => null,
        'services.webhook.consultas_cnpj_cliente_url' => null,
        'services.webhook.consultas_cnpj_url' => null,
    ]);

    actingAs($user)
        ->postJson('/app/consulta/nova/executar', payloadExecutarConsulta($participante, $plano))
        ->assertStatus(500)
        ->assertJsonPath('error', 'Configuração de webhook ausente. Contate o suporte.');
});
