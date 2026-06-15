<?php

use App\Models\ConsultaLote;
use App\Models\CreditTransaction;
use App\Models\MonitoramentoPlano;
use App\Models\Participante;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function () {
    config()->set('services.api.token', 'token-teste');
    config()->set('services.webhook.consultas_cnpj_participante_url', 'https://n8n.test/webhook/consultas');
    // Habilita o gate do InfoSimples para que o FonteRegistry cubra os planos pagos em testes
    config(['consultas.infosimples_ativo' => true, 'consultas.providers.infosimples.token' => 'test-token']);
    Http::fake(['*' => Http::response(['ok' => true], 200)]);
    Bus::fake(); // Evita dispatch real de jobs (sync driver rodaria imediatamente em testes)
});

function criarParticipantes(User $user, int $n): array
{
    $ids = [];
    for ($i = 0; $i < $n; $i++) {
        $doc = str_pad((string) random_int(1, 99999999999999), 14, '0', STR_PAD_LEFT);
        $ids[] = Participante::create([
            'user_id' => $user->id,
            'documento' => $doc,
            'razao_social' => 'Empresa '.$i,
        ])->id;
    }

    return $ids;
}

function executarConsulta(User $user, MonitoramentoPlano $plano, array $participanteIds)
{
    return actingAs($user)->postJson(route('app.consulta.nova.executar'), [
        'participante_ids' => $participanteIds,
        'plano_id' => $plano->id,
        'tab_id' => (string) Str::uuid(),
    ]);
}

it('permite ate 5 CNPJs no total e bloqueia o 6o', function () {
    $user = User::factory()->create(['credits' => 1000]);
    $licitacao = MonitoramentoPlano::porCodigo('licitacao');

    executarConsulta($user, $licitacao, criarParticipantes($user, 5))->assertOk();

    $resp = executarConsulta($user, $licitacao, criarParticipantes($user, 1));
    $resp->assertStatus(403);
    $resp->assertJson(['success' => false, 'trial_cap_atingido' => true]);
});

it('bloqueia o lote inteiro quando ultrapassa o teto', function () {
    $user = User::factory()->create(['credits' => 1000]);
    $licitacao = MonitoramentoPlano::porCodigo('licitacao');

    executarConsulta($user, $licitacao, criarParticipantes($user, 3))->assertOk();

    // usou 3, tenta 4 (=7) -> bloqueia tudo, nada e criado a mais
    executarConsulta($user, $licitacao, criarParticipantes($user, 4))->assertStatus(403);

    expect(ConsultaLote::where('user_id', $user->id)->count())->toBe(1);
});

it('o teto e um pool unico: gastar em um plano reduz os outros', function () {
    $user = User::factory()->create(['credits' => 1000]);
    $licitacao = MonitoramentoPlano::porCodigo('licitacao');
    $compliance = MonitoramentoPlano::porCodigo('compliance');

    executarConsulta($user, $licitacao, criarParticipantes($user, 5))->assertOk();
    executarConsulta($user, $licitacao, criarParticipantes($user, 1))->assertStatus(403);
    executarConsulta($user, $compliance, criarParticipantes($user, 1))->assertStatus(403);
});

it('validacao agora consome o mesmo teto dos demais planos', function () {
    $user = User::factory()->create(['credits' => 1000]);
    $validacao = MonitoramentoPlano::porCodigo('validacao');
    $licitacao = MonitoramentoPlano::porCodigo('licitacao');

    executarConsulta($user, $validacao, criarParticipantes($user, 5))->assertOk();
    executarConsulta($user, $licitacao, criarParticipantes($user, 1))->assertStatus(403);
});

it('apos primeira compra libera consultas ilimitadas', function () {
    $user = User::factory()->create(['credits' => 1000]);
    $licitacao = MonitoramentoPlano::porCodigo('licitacao');
    CreditTransaction::create([
        'user_id' => $user->id, 'amount' => 250, 'balance_after' => 250, 'type' => 'purchase',
    ]);

    executarConsulta($user, $licitacao, criarParticipantes($user, 8))->assertOk();
});

it('gratuito nao consome o teto de consultas pagas', function () {
    $user = User::factory()->create(['credits' => 1000]);
    $gratuito = MonitoramentoPlano::porCodigo('gratuito');
    $licitacao = MonitoramentoPlano::porCodigo('licitacao');

    executarConsulta($user, $gratuito, criarParticipantes($user, 12))->assertOk();
    // gratuito nao contou -> ainda restam 5 do pool pago
    executarConsulta($user, $licitacao, criarParticipantes($user, 5))->assertOk();
});

it('pagina nova renderiza e mostra saldo global do teste', function () {
    $user = User::factory()->create(['credits' => 1000]);
    $licitacao = MonitoramentoPlano::porCodigo('licitacao');

    ConsultaLote::create([
        'user_id' => $user->id, 'plano_id' => $licitacao->id,
        'status' => ConsultaLote::STATUS_PROCESSANDO, 'total_participantes' => 2,
        'creditos_cobrados' => 20, 'tab_id' => (string) Str::uuid(),
    ]);

    $resp = actingAs($user)->get(route('app.consulta.nova'));

    $resp->assertOk();
    $resp->assertSee('3 de 5 no teste'); // restam 3 de 5 no pool global
});

it('calcular-custo retorna restantes do teto', function () {
    $user = User::factory()->create(['credits' => 1000]);
    $licitacao = MonitoramentoPlano::porCodigo('licitacao');

    ConsultaLote::create([
        'user_id' => $user->id, 'plano_id' => $licitacao->id,
        'status' => ConsultaLote::STATUS_PROCESSANDO, 'total_participantes' => 2,
        'creditos_cobrados' => 20, 'tab_id' => (string) Str::uuid(),
    ]);

    $resp = actingAs($user)->postJson(route('app.consulta.nova.calcular-custo'), [
        'participante_ids' => criarParticipantes($user, 1),
        'plano_id' => $licitacao->id,
    ]);

    $resp->assertOk();
    $resp->assertJsonPath('trial_cap.restantes', 3);
    $resp->assertJsonPath('trial_cap.limite', 5);
});
