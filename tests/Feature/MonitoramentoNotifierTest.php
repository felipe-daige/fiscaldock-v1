<?php

use App\Models\Alerta;
use App\Models\MonitoramentoAssinatura;
use App\Models\MonitoramentoConsulta;
use App\Models\MonitoramentoPlano;
use App\Models\Participante;
use App\Models\User;
use App\Support\Monitoramento\MonitoramentoNotifier;
use Database\Seeders\MonitoramentoPlanoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(MonitoramentoPlanoSeeder::class);
    $this->plano = MonitoramentoPlano::query()->first();
    $this->user = User::factory()->create();
    $this->participante = Participante::create([
        'user_id' => $this->user->id, 'documento' => '11222333000181',
        'tipo_documento' => 'PJ', 'razao_social' => 'Fornecedor X',
    ]);
    $this->assinatura = MonitoramentoAssinatura::create([
        'user_id' => $this->user->id, 'participante_id' => $this->participante->id,
        'plano_id' => $this->plano->id, 'status' => 'ativo', 'frequencia_dias' => 30,
    ]);
    $this->notifier = app(MonitoramentoNotifier::class);
});

it('registra alerta de assinatura pausada por saldo', function () {
    $this->notifier->assinaturaPausadaSemSaldo($this->assinatura);

    $alerta = Alerta::where('user_id', $this->user->id)->first();
    expect($alerta)->not->toBeNull();
    expect($alerta->categoria)->toBe('monitoramento');
    expect($alerta->tipo)->toBe('monitoramento_pausado_saldo');
    expect($alerta->participante_id)->toBe($this->participante->id);
});

it('registra alerta de piora de situação', function () {
    $consulta = MonitoramentoConsulta::create([
        'user_id' => $this->user->id, 'participante_id' => $this->participante->id,
        'plano_id' => $this->plano->id, 'assinatura_id' => $this->assinatura->id,
        'tipo' => 'assinatura', 'status' => 'sucesso', 'situacao_geral' => 'irregular',
        'creditos_cobrados' => 5, 'executado_em' => now(),
    ]);

    $this->notifier->situacaoPiorou($consulta, null);

    $alerta = Alerta::where('tipo', 'monitoramento_situacao_piorou')->first();
    expect($alerta)->not->toBeNull();
    expect($alerta->severidade)->toBe('alta');
    expect($alerta->detalhes['monitoramento_consulta_id'])->toBe($consulta->id);
});

it('não duplica alerta do mesmo tipo para o mesmo alvo', function () {
    $this->notifier->assinaturaPausadaSemSaldo($this->assinatura);
    $this->notifier->assinaturaPausadaSemSaldo($this->assinatura);

    expect(Alerta::where('tipo', 'monitoramento_pausado_saldo')->count())->toBe(1);
});
