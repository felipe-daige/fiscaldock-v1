<?php

use App\Models\Cliente;
use App\Models\MonitoramentoAssinatura;
use App\Models\MonitoramentoConsulta;
use App\Models\MonitoramentoPlano;
use App\Models\Participante;
use App\Models\User;
use Database\Seeders\MonitoramentoPlanoSeeder;
use Illuminate\Support\Facades\Http;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->seed(MonitoramentoPlanoSeeder::class);
    config()->set('services.webhook.monitoramento_cnpj_participante_url', 'https://n8n.test/monitoramento/participante');
    config()->set('services.webhook.monitoramento_cnpj_cliente_url', 'https://n8n.test/monitoramento/cliente');
    Http::fake(['n8n.test/*' => Http::response(['ok' => true], 200)]);

    $this->user = User::factory()->create();
    $this->user->credits = 1000;
    $this->user->save();

    $this->plano = MonitoramentoPlano::where('codigo', 'licitacao')->first(); // 10 créditos
    $this->cliente = Cliente::create(['user_id' => $this->user->id, 'documento' => '12345678000190', 'razao_social' => 'C1']);
    $this->participante = Participante::create(['user_id' => $this->user->id, 'documento' => '11222333000144', 'razao_social' => 'P1']);
});

it('consulta uma assinatura ativa', function () {
    $assinatura = MonitoramentoAssinatura::create([
        'user_id' => $this->user->id,
        'cliente_id' => $this->cliente->id,
        'plano_id' => $this->plano->id,
        'status' => 'ativo',
        'frequencia_dias' => 30,
        'proxima_execucao_em' => now()->addDays(20),
    ]);

    $r = $this->actingAs($this->user)->postJson('/app/monitoramento/consultar-agora', [
        'ids' => [$assinatura->id],
    ]);

    $r->assertOk()->assertJsonPath('disparadas', 1);
    expect(MonitoramentoConsulta::where('assinatura_id', $assinatura->id)->count())->toBe(1);
});

it('reagenda proxima_execucao_em ao consultar manualmente', function () {
    $assinatura = MonitoramentoAssinatura::create([
        'user_id' => $this->user->id,
        'cliente_id' => $this->cliente->id,
        'plano_id' => $this->plano->id,
        'status' => 'ativo',
        'frequencia_dias' => 30,
        'proxima_execucao_em' => now()->addDays(20),
    ]);

    $this->actingAs($this->user)->postJson('/app/monitoramento/consultar-agora', [
        'ids' => [$assinatura->id],
    ]);

    $assinatura->refresh();
    $dias = now()->diffInDays($assinatura->proxima_execucao_em, false);
    expect($dias)->toBeGreaterThanOrEqual(29)->toBeLessThanOrEqual(31);
});

it('rejeita assinatura pausada', function () {
    $assinatura = MonitoramentoAssinatura::create([
        'user_id' => $this->user->id,
        'cliente_id' => $this->cliente->id,
        'plano_id' => $this->plano->id,
        'status' => 'pausado',
        'frequencia_dias' => 30,
    ]);

    $r = $this->actingAs($this->user)->postJson('/app/monitoramento/consultar-agora', [
        'ids' => [$assinatura->id],
    ]);

    $r->assertStatus(422);
    expect(MonitoramentoConsulta::count())->toBe(0);
});

it('rejeita se saldo insuficiente para todas', function () {
    $this->user->credits = 15; // só dá pra 1 (10 créditos), não pra 2
    $this->user->save();

    $a1 = MonitoramentoAssinatura::create([
        'user_id' => $this->user->id, 'cliente_id' => $this->cliente->id,
        'plano_id' => $this->plano->id, 'status' => 'ativo', 'frequencia_dias' => 30,
    ]);
    $a2 = MonitoramentoAssinatura::create([
        'user_id' => $this->user->id, 'participante_id' => $this->participante->id,
        'plano_id' => $this->plano->id, 'status' => 'ativo', 'frequencia_dias' => 30,
    ]);

    $r = $this->actingAs($this->user)->postJson('/app/monitoramento/consultar-agora', [
        'ids' => [$a1->id, $a2->id],
    ]);

    $r->assertStatus(422);
    expect(MonitoramentoConsulta::count())->toBe(0);
});

it('não vaza assinatura de outro usuário', function () {
    $outro = User::factory()->create();
    $a = MonitoramentoAssinatura::create([
        'user_id' => $outro->id, 'cliente_id' => Cliente::create(['user_id' => $outro->id, 'documento' => '99999999000100', 'razao_social' => 'Outro'])->id,
        'plano_id' => $this->plano->id, 'status' => 'ativo', 'frequencia_dias' => 30,
    ]);

    $r = $this->actingAs($this->user)->postJson('/app/monitoramento/consultar-agora', [
        'ids' => [$a->id],
    ]);

    $r->assertStatus(422);
    expect(MonitoramentoConsulta::count())->toBe(0);
});

it('consulta múltiplas em uma chamada', function () {
    $a1 = MonitoramentoAssinatura::create([
        'user_id' => $this->user->id, 'cliente_id' => $this->cliente->id,
        'plano_id' => $this->plano->id, 'status' => 'ativo', 'frequencia_dias' => 30,
    ]);
    $a2 = MonitoramentoAssinatura::create([
        'user_id' => $this->user->id, 'participante_id' => $this->participante->id,
        'plano_id' => $this->plano->id, 'status' => 'ativo', 'frequencia_dias' => 30,
    ]);

    $r = $this->actingAs($this->user)->postJson('/app/monitoramento/consultar-agora', [
        'ids' => [$a1->id, $a2->id],
    ]);

    $r->assertOk()->assertJsonPath('disparadas', 2);
    expect(MonitoramentoConsulta::count())->toBe(2);
});

it('exige array de ids', function () {
    $r = $this->actingAs($this->user)->postJson('/app/monitoramento/consultar-agora', []);
    $r->assertStatus(422);
});
