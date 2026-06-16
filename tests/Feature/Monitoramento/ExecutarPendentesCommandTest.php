<?php

use App\Models\MonitoramentoAssinatura;
use App\Models\MonitoramentoConsulta;
use App\Models\MonitoramentoPlano;
use App\Models\Participante;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;

uses(RefreshDatabase::class);

function assinaturaAtiva(User $user, Participante $p, $quando): MonitoramentoAssinatura
{
    return MonitoramentoAssinatura::create([
        'user_id' => $user->id, 'participante_id' => $p->id,
        'plano_id' => MonitoramentoPlano::porCodigo('licitacao')->id,
        'status' => 'ativo', 'frequencia_dias' => 30, 'proxima_execucao_em' => $quando,
    ]);
}

it('sweep 1: dispara assinatura vencida com saldo e reagenda', function () {
    Bus::fake();
    $user = User::factory()->create(['credits' => 100]);
    $p = Participante::create(['user_id' => $user->id, 'documento' => '11222333000181', 'uf' => 'SP', 'razao_social' => 'ACME']);
    $a = assinaturaAtiva($user, $p, now()->subDay());

    $this->artisan('monitoramento:executar-pendentes')->assertSuccessful();

    expect(MonitoramentoConsulta::where('assinatura_id', $a->id)->count())->toBe(1)
        ->and($a->fresh()->proxima_execucao_em->isFuture())->toBeTrue();
});

it('sweep 1: saldo insuficiente pausa a assinatura sem disparar', function () {
    Bus::fake();
    $user = User::factory()->create(['credits' => 0]);
    $p = Participante::create(['user_id' => $user->id, 'documento' => '11222333000181', 'razao_social' => 'ACME']);
    $a = assinaturaAtiva($user, $p, now()->subDay());

    $this->artisan('monitoramento:executar-pendentes')->assertSuccessful();

    expect($a->fresh()->status)->toBe('pausado')
        ->and(MonitoramentoConsulta::where('assinatura_id', $a->id)->count())->toBe(0);
});

it('sweep 2: retenta erro de >1 dia criando filha com parent', function () {
    Bus::fake();
    $user = User::factory()->create(['credits' => 100]);
    $p = Participante::create(['user_id' => $user->id, 'documento' => '11222333000181', 'uf' => 'SP', 'razao_social' => 'ACME']);
    $a = assinaturaAtiva($user, $p, now()->addDays(10));
    $erro = MonitoramentoConsulta::create([
        'user_id' => $user->id, 'participante_id' => $p->id, 'assinatura_id' => $a->id,
        'plano_id' => $a->plano_id, 'tipo' => 'assinatura', 'status' => 'erro',
        'creditos_cobrados' => 10, 'executado_em' => now()->subDays(2),
    ]);

    $this->artisan('monitoramento:executar-pendentes')->assertSuccessful();

    expect(MonitoramentoConsulta::where('parent_consulta_id', $erro->id)->count())->toBe(1);
});

it('sweep 2: cadeia de 4 erros (retryCount 3) pausa a assinatura', function () {
    Bus::fake();
    $user = User::factory()->create(['credits' => 100]);
    $p = Participante::create(['user_id' => $user->id, 'documento' => '11222333000181', 'razao_social' => 'ACME']);
    $a = assinaturaAtiva($user, $p, now()->addDays(10));

    $parent = null;
    foreach (range(1, 4) as $i) {
        $parent = MonitoramentoConsulta::create([
            'user_id' => $user->id, 'participante_id' => $p->id, 'assinatura_id' => $a->id,
            'plano_id' => $a->plano_id, 'tipo' => 'assinatura', 'status' => 'erro',
            'creditos_cobrados' => 10, 'executado_em' => now()->subDays(2),
            'parent_consulta_id' => $parent?->id,
        ]);
    }

    $this->artisan('monitoramento:executar-pendentes')->assertSuccessful();

    expect($a->fresh()->status)->toBe('pausado');
});
