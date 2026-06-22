<?php

use App\Models\Alerta;
use App\Models\Cliente;
use App\Models\EfdImportacao;
use App\Models\EfdNota;
use App\Models\Participante;
use App\Models\User;
use App\Services\AlertaCentralService;

/**
 * "Nunca consultado" deve virar UM alerta agregado (N participantes), não um por
 * CNPJ — senão um import de empresa própria gera dezenas de alertas de ruído.
 */
beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
    $this->service = app(AlertaCentralService::class);

    $this->cliente = Cliente::create([
        'user_id' => $this->user->id,
        'documento' => '97551165000193',
        'nome' => 'Empresa Própria',
        'razao_social' => 'Empresa Própria',
        'is_empresa_propria' => true,
    ]);
    $this->importacao = EfdImportacao::create([
        'user_id' => $this->user->id,
        'cliente_id' => $this->cliente->id,
        'tipo_efd' => 'EFD PIS/COFINS',
        'status' => 'concluido',
    ]);
});

afterEach(function () {
    $uid = $this->user->id;
    Alerta::where('user_id', $uid)->delete();
    EfdNota::where('user_id', $uid)->forceDelete();
    Participante::where('user_id', $uid)->forceDelete();
    EfdImportacao::where('user_id', $uid)->delete();
    Cliente::where('user_id', $uid)->forceDelete();
    $this->user->forceDelete();
});

function criarContraparteComNota($ctx, string $doc): Participante
{
    $p = Participante::create([
        'user_id' => $ctx->user->id,
        'cliente_id' => $ctx->cliente->id,
        'documento' => $doc,
        'razao_social' => "Fornecedor {$doc}",
    ]);
    EfdNota::create([
        'user_id' => $ctx->user->id,
        'cliente_id' => $ctx->cliente->id,
        'importacao_id' => $ctx->importacao->id,
        'participante_id' => $p->id,
        'numero' => random_int(1, 999999),
        'serie' => '1',
        'modelo' => '55',
        'data_emissao' => now()->toDateString(),
        'tipo_operacao' => 'entrada',
        'valor_total' => 100,
    ]);

    return $p;
}

it('agrupa participantes nunca consultados em um único alerta', function () {
    foreach (['11111111000111', '22222222000122', '33333333000133'] as $doc) {
        criarContraparteComNota($this, $doc);
    }

    $this->service->recalcular($this->user->id);

    $alertas = Alerta::where('user_id', $this->user->id)
        ->where('tipo', 'nunca_consultado')
        ->get();

    expect($alertas)->toHaveCount(1);

    $alerta = $alertas->first();
    expect($alerta->total_afetados)->toBe(3);
    expect($alerta->severidade)->toBe('baixa');
    expect($alerta->participante_id)->toBeNull();

    $det = is_string($alerta->detalhes) ? json_decode($alerta->detalhes, true) : $alerta->detalhes;
    expect($det)->toHaveCount(3);
    expect($det[0])->toHaveKeys(['razao_social', 'documento']);
});

it('não cria alerta de nunca consultado quando todos já foram consultados', function () {
    $p = criarContraparteComNota($this, '44444444000144');
    $p->update(['ultima_consulta_em' => now()]);

    $this->service->recalcular($this->user->id);

    $alerta = Alerta::where('user_id', $this->user->id)
        ->where('tipo', 'nunca_consultado')
        ->first();

    expect($alerta)->toBeNull();
});
