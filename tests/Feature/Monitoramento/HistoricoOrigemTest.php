<?php

use App\Models\ConsultaLote;
use App\Models\MonitoramentoConsulta;
use App\Models\MonitoramentoPlano;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function loteSimples(User $user, int $planoId): ConsultaLote
{
    return ConsultaLote::create([
        'user_id' => $user->id, 'plano_id' => $planoId, 'status' => ConsultaLote::STATUS_CONCLUIDO,
        'total_participantes' => 1, 'creditos_cobrados' => 10, 'tab_id' => (string) Str::uuid(),
    ]);
}

it('flag eh_monitoramento marca só os lotes com consulta de monitoramento ligada', function () {
    $user = User::factory()->create();
    $plano = MonitoramentoPlano::porCodigo('licitacao');

    $loteMon = loteSimples($user, $plano->id);
    MonitoramentoConsulta::create([
        'user_id' => $user->id, 'plano_id' => $plano->id, 'tipo' => 'assinatura',
        'status' => 'sucesso', 'consulta_lote_id' => $loteMon->id, 'creditos_cobrados' => 10,
    ]);
    $loteNormal = loteSimples($user, $plano->id);

    $lotes = ConsultaLote::withCount(['monitoramentoConsulta as eh_monitoramento'])->get()->keyBy('id');

    expect((bool) $lotes[$loteMon->id]->eh_monitoramento)->toBeTrue()
        ->and((bool) $lotes[$loteNormal->id]->eh_monitoramento)->toBeFalse();
});
