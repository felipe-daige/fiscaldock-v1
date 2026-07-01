<?php

use App\Models\ConsultaLote;
use App\Models\ConsultaResultado;
use App\Models\MonitoramentoPlano;
use App\Models\Participante;
use App\Models\User;
use App\Services\Consultas\ParticipanteFiscalResumoService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(\Database\Seeders\MonitoramentoPlanoSeeder::class));

function loteRegularidade(User $user): ConsultaLote
{
    $plano = MonitoramentoPlano::porCodigo('gratuito') ?? MonitoramentoPlano::firstOrFail();

    return ConsultaLote::create([
        'user_id' => $user->id,
        'plano_id' => $plano->id,
        'status' => ConsultaLote::STATUS_FINALIZADO,
        'total_participantes' => 1,
        'creditos_cobrados' => 0,
        'tab_id' => 'tab-reg-'.uniqid(),
        'processado_em' => now(),
    ]);
}

function resultadoComCnd(ConsultaLote $lote, Participante $p, array $cndFederal): void
{
    ConsultaResultado::create([
        'consulta_lote_id' => $lote->id,
        'participante_id' => $p->id,
        'status' => ConsultaResultado::STATUS_SUCESSO,
        'resultado_dados' => ['cnd_federal' => $cndFederal],
        'consultado_em' => now(),
    ]);
}

it('classifica participante por regularidade da ultima consulta', function () {
    $user = User::factory()->create();
    $lote = loteRegularidade($user);

    $pRegular = Participante::create(['user_id' => $user->id, 'documento' => '11444777000161', 'razao_social' => 'Regular SA']);
    $pIrregular = Participante::create(['user_id' => $user->id, 'documento' => '11444777000242', 'razao_social' => 'Irregular SA']);
    $pSemConsulta = Participante::create(['user_id' => $user->id, 'documento' => '11444777000323', 'razao_social' => 'Nunca SA']);

    resultadoComCnd($lote, $pRegular, ['status' => 'Negativa']);
    resultadoComCnd($lote, $pIrregular, ['status' => 'Positiva']);

    $map = app(ParticipanteFiscalResumoService::class)->regularidadePorParticipante($user->id);

    expect($map[$pRegular->id])->toBe('regular');
    expect($map[$pIrregular->id])->toBe('irregular');
    expect($map)->not->toHaveKey($pSemConsulta->id);
});

it('usa apenas a ultima consulta sucesso por participante', function () {
    $user = User::factory()->create();
    $p = Participante::create(['user_id' => $user->id, 'documento' => '11444777000404', 'razao_social' => 'Virou Regular']);

    // Consulta antiga irregular (lote 1), consulta nova regular (lote 2) — vale a nova.
    // Unique (lote, participante) força lotes distintos por consulta.
    $loteAntigo = loteRegularidade($user);
    ConsultaResultado::create([
        'consulta_lote_id' => $loteAntigo->id,
        'participante_id' => $p->id,
        'status' => ConsultaResultado::STATUS_SUCESSO,
        'resultado_dados' => ['cnd_federal' => ['status' => 'Positiva']],
        'consultado_em' => now()->subDays(10),
    ]);

    $loteNovo = loteRegularidade($user);
    resultadoComCnd($loteNovo, $p, ['status' => 'Negativa']);

    $map = app(ParticipanteFiscalResumoService::class)->regularidadePorParticipante($user->id);

    expect($map[$p->id])->toBe('regular');
});
