<?php

use App\Models\Alerta;
use App\Models\MonitoramentoAssinatura;
use App\Models\MonitoramentoConsulta;
use App\Models\MonitoramentoPlano;
use App\Models\Participante;
use App\Models\User;
use Database\Seeders\MonitoramentoPlanoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(MonitoramentoPlanoSeeder::class);
    $this->plano = MonitoramentoPlano::query()->where('custo_creditos', '>', 0)->first();
    config()->set('services.webhook.monitoramento_cnpj_participante_url', 'https://n8n.test/monitoramento/participante');
    config()->set('services.webhook.monitoramento_cnpj_cliente_url', 'https://n8n.test/monitoramento/cliente');
    Http::fake(['n8n.test/*' => Http::response(['ok' => true], 200)]);
});

function cenarioRetry($ctx, int $retryCount = 0): array
{
    $user = User::factory()->create(['credits' => 100]);
    $participante = Participante::create([
        'user_id' => $user->id, 'documento' => '11222333000181',
        'tipo_documento' => 'PJ', 'razao_social' => 'Fornecedor X',
    ]);
    $assinatura = MonitoramentoAssinatura::create([
        'user_id' => $user->id, 'participante_id' => $participante->id,
        'plano_id' => $ctx->plano->id, 'status' => 'ativo', 'frequencia_dias' => 30,
        'proxima_execucao_em' => now()->addDays(20),
    ]);

    $parentId = null;
    $consulta = null;
    for ($i = 0; $i <= $retryCount; $i++) {
        $consulta = MonitoramentoConsulta::create([
            'user_id' => $user->id, 'participante_id' => $participante->id,
            'plano_id' => $ctx->plano->id, 'assinatura_id' => $assinatura->id,
            'tipo' => 'assinatura', 'status' => 'erro', 'creditos_cobrados' => 5,
            'parent_consulta_id' => $parentId, 'executado_em' => now()->subDays(2),
        ]);
        $parentId = $consulta->id;
    }

    return [$user, $assinatura, $consulta];
}

it('cria consulta de retry para erro com mais de 1 dia', function () {
    [, $assinatura] = cenarioRetry($this);

    $this->artisan('monitoramento:executar-pendentes')->assertSuccessful();

    $retry = MonitoramentoConsulta::where('assinatura_id', $assinatura->id)
        ->whereNotNull('parent_consulta_id')->first();
    expect($retry)->not->toBeNull();
    expect($retry->status)->toBe('pendente');
});

it('não retenta erro recente (menos de 1 dia)', function () {
    [, $assinatura, $consulta] = cenarioRetry($this);
    $consulta->update(['executado_em' => now()->subHours(2)]);

    $this->artisan('monitoramento:executar-pendentes')->assertSuccessful();

    expect(MonitoramentoConsulta::where('assinatura_id', $assinatura->id)->count())->toBe(1);
});

it('pausa a assinatura após 3 retries falhos', function () {
    [, $assinatura] = cenarioRetry($this, 3);

    $this->artisan('monitoramento:executar-pendentes')->assertSuccessful();

    expect($assinatura->fresh()->status)->toBe('pausado');
    expect(Alerta::where('tipo', 'monitoramento_pausado_falhas')->count())->toBe(1);
    expect(MonitoramentoConsulta::where('assinatura_id', $assinatura->id)->count())->toBe(4);
});

it('ignora retry de assinatura não-ativa', function () {
    [, $assinatura] = cenarioRetry($this);
    $assinatura->update(['status' => 'cancelado']);

    $this->artisan('monitoramento:executar-pendentes')->assertSuccessful();

    expect(MonitoramentoConsulta::where('assinatura_id', $assinatura->id)->count())->toBe(1);
});
