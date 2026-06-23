<?php

use App\Models\ConsultaLote;
use App\Models\ConsultaResultado;
use App\Models\MonitoramentoPlano;
use App\Models\Participante;
use App\Models\User;
use App\Services\Participantes\DossieParticipanteBuilder;

beforeEach(function () {
    $this->builder = app(DossieParticipanteBuilder::class);
    $this->user = User::factory()->create();
    $this->p = Participante::create(['user_id' => $this->user->id, 'documento' => '07863768000138', 'razao_social' => 'ACME LTDA', 'uf' => 'SP', 'crt' => '3']);
});

it('monta payload com blocos esperados mesmo sem consulta nem movimentacao', function () {
    $d = $this->builder->montar($this->p);

    expect($d['participante']->id)->toBe($this->p->id)
        ->and($d['consulta']['tem'])->toBeFalse()
        ->and($d['movimentacao']['kpis']['total_notas'])->toBe(0)
        ->and($d['score'])->toHaveKeys(['score_total', 'classificacao', 'scores'])
        ->and($d)->toHaveKey('gerado_em');
});

it('inclui dados da ultima consulta sucesso quando existe', function () {
    $plano = MonitoramentoPlano::ativos()->first();
    if (! $plano) {
        $plano = MonitoramentoPlano::create([
            'nome' => 'Gratuito',
            'codigo' => 'gratuito',
            'ativo' => true,
            'creditos_por_consulta' => 0,
            'consultas_incluidas' => [],
            'etapas' => [],
        ]);
    }

    $lote = ConsultaLote::create([
        'user_id' => $this->user->id, 'plano_id' => $plano->id,
        'status' => ConsultaLote::STATUS_FINALIZADO, 'total_participantes' => 1, 'creditos_cobrados' => 0,
        'tab_id' => 'tab-'.uniqid(), 'processado_em' => now(),
    ]);
    ConsultaResultado::create([
        'consulta_lote_id' => $lote->id,
        'participante_id' => $this->p->id,
        'status' => ConsultaResultado::STATUS_SUCESSO,
        'resultado_dados' => ['razao_social' => 'ACME LTDA', 'situacao_cadastral' => 'ATIVA', 'cnd_federal' => ['status' => 'NEGATIVA']],
        'consultado_em' => now(),
    ]);

    $d = $this->builder->montar($this->p);

    expect($d['consulta']['tem'])->toBeTrue()
        ->and($d['consulta']['blocos'])->not->toBeEmpty();
});
