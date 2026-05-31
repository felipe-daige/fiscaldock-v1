<?php

use App\Models\ConsultaLote;
use App\Models\ConsultaResultado;
use App\Models\MonitoramentoPlano;
use App\Models\Participante;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

function planoCnd(): MonitoramentoPlano
{
    return MonitoramentoPlano::porCodigo('gratuito') ?? MonitoramentoPlano::firstOrFail();
}

function loteComCndIndeterminado(User $user): array
{
    $participante = Participante::create([
        'user_id' => $user->id,
        'documento' => '72983711000134',
        'razao_social' => 'CONSTRUTORA F. N. LTDA',
        'uf' => 'SP',
        'crt' => '3',
    ]);

    $lote = ConsultaLote::create([
        'user_id' => $user->id,
        'plano_id' => planoCnd()->id,
        'status' => ConsultaLote::STATUS_FINALIZADO,
        'total_participantes' => 1,
        'creditos_cobrados' => 0,
        'tab_id' => 'tab-cnd-indet',
        'processado_em' => now(),
    ]);

    $lote->participantes()->attach([$participante->id]);

    ConsultaResultado::create([
        'consulta_lote_id' => $lote->id,
        'participante_id' => $participante->id,
        'status' => ConsultaResultado::STATUS_SUCESSO,
        'resultado_dados' => [
            'situacao_cadastral' => 'INAPTA',
            'cnd_federal' => [
                'status' => 'INDETERMINADO',
                'conseguiu_emitir' => false,
                'mensagem' => 'Inscrição no CNPJ 72.983.711/0001-34 Inapta  - Omissão de declarações, emissão de certidão não permitida.',
            ],
        ],
        'consultado_em' => now(),
    ]);

    return [$lote, $participante];
}

it('endpoint resultadosLote retorna cnd indeterminado com motivo normalizado', function () {
    $user = User::factory()->create();
    [$lote] = loteComCndIndeterminado($user);

    actingAs($user)
        ->getJson("/app/consulta/lote/{$lote->id}/resultados")
        ->assertOk()
        ->assertJsonPath('resultados.0.cnd_federal.indeterminado', true)
        ->assertJsonPath('resultados.0.cnd_federal.label', 'Indeterminada')
        ->assertJsonPath('resultados.0.cnd_federal.hex', '#d97706')
        ->assertJsonPath(
            'resultados.0.cnd_federal.motivo',
            'Inscrição no CNPJ 72.983.711/0001-34 Inapta - Omissão de declarações, emissão de certidão não permitida.'
        );
});
