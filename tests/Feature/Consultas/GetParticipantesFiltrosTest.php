<?php

use App\Models\ConsultaLote;
use App\Models\ConsultaResultado;
use App\Models\MonitoramentoAssinatura;
use App\Models\MonitoramentoPlano;
use App\Models\Participante;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(\Database\Seeders\MonitoramentoPlanoSeeder::class));

function getParticipantesReq(User $user, array $query = [])
{
    return actingAs($user)->getJson('/app/consulta/nova/participantes?'.http_build_query($query));
}

function participanteFiltro(User $user, array $attrs = []): Participante
{
    return Participante::create(array_merge([
        'user_id' => $user->id,
        'documento' => (string) random_int(10000000000000, 99999999999999),
        'razao_social' => 'Participante '.uniqid(),
    ], $attrs));
}

it('filtra por status_consulta = nunca', function () {
    $user = User::factory()->create();
    $nunca = participanteFiltro($user, ['ultima_consulta_em' => null]);
    $recente = participanteFiltro($user, ['ultima_consulta_em' => now()->subDays(2)]);

    $ids = collect(getParticipantesReq($user, ['status_consulta' => 'nunca'])->json('data'))->pluck('id');

    expect($ids)->toContain($nunca->id)->not->toContain($recente->id);
});

it('filtra por status_consulta = desatualizada (>30d)', function () {
    $user = User::factory()->create();
    $velho = participanteFiltro($user, ['ultima_consulta_em' => now()->subDays(40)]);
    $novo = participanteFiltro($user, ['ultima_consulta_em' => now()->subDays(5)]);

    $ids = collect(getParticipantesReq($user, ['status_consulta' => 'desatualizada'])->json('data'))->pluck('id');

    expect($ids)->toContain($velho->id)->not->toContain($novo->id);
});

it('filtra por status_consulta = recente (<=30d)', function () {
    $user = User::factory()->create();
    $novo = participanteFiltro($user, ['ultima_consulta_em' => now()->subDays(5)]);
    $velho = participanteFiltro($user, ['ultima_consulta_em' => now()->subDays(40)]);

    $ids = collect(getParticipantesReq($user, ['status_consulta' => 'recente'])->json('data'))->pluck('id');

    expect($ids)->toContain($novo->id)->not->toContain($velho->id);
});

it('filtra por monitorado', function () {
    $user = User::factory()->create();
    $plano = MonitoramentoPlano::porCodigo('gratuito') ?? MonitoramentoPlano::firstOrFail();
    $monitorado = participanteFiltro($user);
    $solto = participanteFiltro($user);

    MonitoramentoAssinatura::create([
        'user_id' => $user->id,
        'participante_id' => $monitorado->id,
        'plano_id' => $plano->id,
        'status' => 'ativo',
        'frequencia_dias' => 30,
    ]);

    $idsSim = collect(getParticipantesReq($user, ['monitorado' => 'sim'])->json('data'))->pluck('id');
    expect($idsSim)->toContain($monitorado->id)->not->toContain($solto->id);

    $idsNao = collect(getParticipantesReq($user, ['monitorado' => 'nao'])->json('data'))->pluck('id');
    expect($idsNao)->toContain($solto->id)->not->toContain($monitorado->id);
});

it('filtra por regularidade = irregular', function () {
    $user = User::factory()->create();
    $plano = MonitoramentoPlano::porCodigo('gratuito') ?? MonitoramentoPlano::firstOrFail();
    $lote = ConsultaLote::create([
        'user_id' => $user->id, 'plano_id' => $plano->id, 'status' => ConsultaLote::STATUS_FINALIZADO,
        'total_participantes' => 1, 'creditos_cobrados' => 0, 'tab_id' => 'tab-'.uniqid(), 'processado_em' => now(),
    ]);

    $irregular = participanteFiltro($user);
    $regular = participanteFiltro($user);

    ConsultaResultado::create([
        'consulta_lote_id' => $lote->id, 'participante_id' => $irregular->id,
        'status' => ConsultaResultado::STATUS_SUCESSO,
        'resultado_dados' => ['cnd_federal' => ['status' => 'Positiva']], 'consultado_em' => now(),
    ]);
    ConsultaResultado::create([
        'consulta_lote_id' => $lote->id, 'participante_id' => $regular->id,
        'status' => ConsultaResultado::STATUS_SUCESSO,
        'resultado_dados' => ['cnd_federal' => ['status' => 'Negativa']], 'consultado_em' => now(),
    ]);

    $ids = collect(getParticipantesReq($user, ['regularidade' => 'irregular'])->json('data'))->pluck('id');
    expect($ids)->toContain($irregular->id)->not->toContain($regular->id);
});

it('filtra por regularidade = nao_consultado', function () {
    $user = User::factory()->create();
    $plano = MonitoramentoPlano::porCodigo('gratuito') ?? MonitoramentoPlano::firstOrFail();
    $lote = ConsultaLote::create([
        'user_id' => $user->id, 'plano_id' => $plano->id, 'status' => ConsultaLote::STATUS_FINALIZADO,
        'total_participantes' => 1, 'creditos_cobrados' => 0, 'tab_id' => 'tab-'.uniqid(), 'processado_em' => now(),
    ]);

    $consultado = participanteFiltro($user);
    $novo = participanteFiltro($user);
    ConsultaResultado::create([
        'consulta_lote_id' => $lote->id, 'participante_id' => $consultado->id,
        'status' => ConsultaResultado::STATUS_SUCESSO,
        'resultado_dados' => ['cnd_federal' => ['status' => 'Negativa']], 'consultado_em' => now(),
    ]);

    $ids = collect(getParticipantesReq($user, ['regularidade' => 'nao_consultado'])->json('data'))->pluck('id');
    expect($ids)->toContain($novo->id)->not->toContain($consultado->id);
});

it('ordena por ultima_consulta com nulls primeiro', function () {
    $user = User::factory()->create();
    $comData = participanteFiltro($user, ['razao_social' => 'AAA', 'ultima_consulta_em' => now()->subDays(5)]);
    $semData = participanteFiltro($user, ['razao_social' => 'ZZZ', 'ultima_consulta_em' => null]);

    $ids = collect(getParticipantesReq($user, ['ordenar' => 'ultima_consulta'])->json('data'))->pluck('id');

    expect($ids->first())->toBe($semData->id);
});

it('rejeita ordenar invalido', function () {
    $user = User::factory()->create();
    getParticipantesReq($user, ['ordenar' => 'hackzor'])->assertStatus(422);
});

it('rejeita status_consulta invalido', function () {
    $user = User::factory()->create();
    getParticipantesReq($user, ['status_consulta' => 'talvez'])->assertStatus(422);
});

it('devolve total na paginacao', function () {
    $user = User::factory()->create();
    participanteFiltro($user);
    participanteFiltro($user);
    participanteFiltro($user);

    getParticipantesReq($user)->assertJsonPath('pagination.total', 3);
});
