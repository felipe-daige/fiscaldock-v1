<?php

use App\Jobs\ProcessarClearanceJob;
use App\Models\Cliente;
use App\Models\ConsultaLote;
use App\Models\EfdImportacao;
use App\Models\EfdNota;
use App\Models\NfeConsulta;
use App\Models\User;
use App\Services\ValidacaoContabilService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

function fullPhUser(): User
{
    return User::factory()->create(['credits' => 1000]);
}

function fullPhCliente(User $u): Cliente
{
    return Cliente::firstOrCreate(
        ['user_id' => $u->id, 'is_empresa_propria' => true],
        ['tipo_pessoa' => 'PJ', 'documento' => '00000000000191', 'razao_social' => 'Propria']
    );
}

function fullPhEfdNota(User $u): EfdNota
{
    $cliente = fullPhCliente($u);
    $imp = EfdImportacao::firstOrCreate(
        ['user_id' => $u->id, 'cliente_id' => $cliente->id, 'tipo_efd' => 'EFD ICMS/IPI'],
        ['status' => 'concluido']
    );

    return EfdNota::create([
        'user_id' => $u->id, 'cliente_id' => $cliente->id, 'importacao_id' => $imp->id,
        'chave_acesso' => '35240413305697000150550000000404041953940992', 'modelo' => '55',
        'numero' => 1, 'serie' => '0', 'data_emissao' => '2026-01-15', 'tipo_operacao' => 'entrada',
        'valor_total' => 1000.00, 'valor_desconto' => 0, 'origem_arquivo' => 'fiscal', 'metadados' => [],
    ]);
}

it('com flag full OFF, tier=full é coagido para basico (não cobra o dobro)', function () {
    config()->set('clearance.full.habilitado', false);
    Bus::fake();
    Http::fake();
    $user = fullPhUser();
    $nota = fullPhEfdNota($user);

    actingAs($user)->postJson('/app/clearance/notas/validar', [
        'nota_ids' => [$nota->id], 'origens' => [$nota->id => 'efd'], 'tipo' => 'full', 'tab_id' => 'tab-full',
    ])->assertOk();

    $lote = ConsultaLote::latest('id')->first();
    expect($lote->creditos_cobrados)->toBe(ValidacaoContabilService::custoUnitarioPorTier('basico'));
    Bus::assertBatched(fn ($b) => collect($b->jobs)->every(fn ($j) => $j instanceof ProcessarClearanceJob));
});

it('resultado mostra placeholder em breve de tributos/itens quando Full está off', function () {
    config()->set('clearance.full.habilitado', false);
    $user = fullPhUser();
    $lote = ConsultaLote::create([
        'user_id' => $user->id, 'plano_id' => null, 'status' => ConsultaLote::STATUS_FINALIZADO,
        'total_participantes' => 1, 'creditos_cobrados' => 3, 'tab_id' => 'tab-ph', 'processado_em' => now(),
    ]);
    NfeConsulta::create([
        'user_id' => $user->id, 'consulta_lote_id' => $lote->id, 'chave_acesso' => str_repeat('5', 44),
        'tipo_documento' => 'NFE', 'modelo' => '55', 'status' => 'AUTORIZADA', 'valor_total' => 100, 'consultado_em' => now(),
    ]);

    actingAs($user)->get("/app/clearance/notas/resultado/{$lote->id}")
        ->assertOk()
        ->assertSee('Tributos e itens', false)
        ->assertSee('Em breve', false)
        ->assertSee('certificado', false);
});

it('tela de notas marca o tier Full como em breve quando Full está off', function () {
    config()->set('clearance.full.habilitado', false);
    $user = fullPhUser();
    fullPhEfdNota($user);

    actingAs($user)->get('/app/clearance/notas')
        ->assertOk()
        ->assertSee('plan-card-full', false)
        ->assertSee('Em breve', false)
        ->assertSee('pointer-events-none', false);
});

it('Empresa mostra card Certificado Digital em breve quando Full está off', function () {
    config()->set('clearance.full.habilitado', false);
    $user = fullPhUser();
    fullPhCliente($user);

    actingAs($user)->get('/app/minha-empresa')
        ->assertOk()
        ->assertSee('Certificado Digital', false)
        ->assertSee('Em breve', false);
});
