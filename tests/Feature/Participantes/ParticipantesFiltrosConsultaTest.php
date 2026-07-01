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

function partConsulta(User $user, array $attrs = []): Participante
{
    return Participante::create(array_merge([
        'user_id' => $user->id,
        'documento' => (string) random_int(10000000000000, 99999999999999),
        'razao_social' => 'PART '.strtoupper(uniqid()),
    ], $attrs));
}

function resultadoParaPart(User $user, Participante $p, string $cndStatus): void
{
    $plano = MonitoramentoPlano::porCodigo('gratuito') ?? MonitoramentoPlano::firstOrFail();
    $lote = ConsultaLote::create([
        'user_id' => $user->id, 'plano_id' => $plano->id, 'status' => ConsultaLote::STATUS_FINALIZADO,
        'total_participantes' => 1, 'creditos_cobrados' => 0, 'tab_id' => 'tab-'.uniqid(), 'processado_em' => now(),
    ]);
    ConsultaResultado::create([
        'consulta_lote_id' => $lote->id, 'participante_id' => $p->id,
        'status' => ConsultaResultado::STATUS_SUCESSO,
        'resultado_dados' => ['cnd_federal' => ['status' => $cndStatus]], 'consultado_em' => now(),
    ]);
}

it('filtra participantes por status_consulta = nunca', function () {
    $user = User::factory()->create();
    $nunca = partConsulta($user, ['razao_social' => 'NUNCA SA', 'ultima_consulta_em' => null]);
    $recente = partConsulta($user, ['razao_social' => 'RECENTE SA', 'ultima_consulta_em' => now()->subDays(2)]);

    actingAs($user)->get('/app/participantes?status_consulta=nunca')
        ->assertOk()->assertSee('NUNCA SA')->assertDontSee('RECENTE SA');
});

it('filtra participantes por regularidade = irregular', function () {
    $user = User::factory()->create();
    $irr = partConsulta($user, ['razao_social' => 'DEVEDORA ALPHA']);
    $reg = partConsulta($user, ['razao_social' => 'LIMPA BETA']);
    resultadoParaPart($user, $irr, 'Positiva');
    resultadoParaPart($user, $reg, 'Negativa');

    actingAs($user)->get('/app/participantes?regularidade=irregular')
        ->assertOk()->assertSee('DEVEDORA ALPHA')->assertDontSee('LIMPA BETA');
});

it('filtra participantes por monitorado = sim', function () {
    $user = User::factory()->create();
    $plano = MonitoramentoPlano::porCodigo('gratuito') ?? MonitoramentoPlano::firstOrFail();
    $mon = partConsulta($user, ['razao_social' => 'MONITORADO SA']);
    $solto = partConsulta($user, ['razao_social' => 'SOLTO SA']);
    MonitoramentoAssinatura::create([
        'user_id' => $user->id, 'participante_id' => $mon->id, 'plano_id' => $plano->id,
        'status' => 'ativo', 'frequencia_dias' => 30,
    ]);

    actingAs($user)->get('/app/participantes?monitorado=sim')
        ->assertOk()->assertSee('MONITORADO SA')->assertDontSee('SOLTO SA');
});

it('ignora status_consulta invalido sem quebrar', function () {
    $user = User::factory()->create();
    partConsulta($user, ['razao_social' => 'QUALQUER SA']);

    actingAs($user)->get('/app/participantes?status_consulta=xpto')
        ->assertOk()->assertSee('QUALQUER SA');
});
