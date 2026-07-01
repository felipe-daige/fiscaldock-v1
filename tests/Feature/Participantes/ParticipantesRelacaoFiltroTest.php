<?php

use App\Models\Cliente;
use App\Models\EfdImportacao;
use App\Models\Participante;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

function notaFiscalParticipante(User $user, Participante $p, string $tipoOperacao): void
{
    $cliente = Cliente::firstOrCreate(
        ['user_id' => $user->id, 'documento' => '00000000000191'],
        ['razao_social' => 'Empresa Teste']
    );
    $imp = EfdImportacao::firstOrCreate(
        ['user_id' => $user->id, 'tipo_efd' => 'EFD ICMS/IPI'],
        []
    );

    DB::table('efd_notas')->insert([
        'user_id' => $user->id,
        'cliente_id' => $cliente->id,
        'importacao_id' => $imp->id,
        'participante_id' => $p->id,
        'modelo' => '55',
        'numero' => random_int(1, 9999999),
        'tipo_operacao' => $tipoOperacao,
        'origem_arquivo' => 'fiscal',
        'valor_total' => 100.0,
        'cancelada' => false,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

it('filtra participantes por relacao = fornecedor (inclui ambos)', function () {
    $user = User::factory()->create();
    $forn = Participante::create(['user_id' => $user->id, 'documento' => '11444777000161', 'razao_social' => 'FORNECEDOR LTDA']);
    $cli = Participante::create(['user_id' => $user->id, 'documento' => '11444777000242', 'razao_social' => 'CLIENTE LTDA']);
    $ambos = Participante::create(['user_id' => $user->id, 'documento' => '11444777000323', 'razao_social' => 'AMBOS LTDA']);

    notaFiscalParticipante($user, $forn, 'entrada');
    notaFiscalParticipante($user, $cli, 'saida');
    notaFiscalParticipante($user, $ambos, 'entrada');
    notaFiscalParticipante($user, $ambos, 'saida');

    $resp = actingAs($user)->get('/app/participantes?relacao=fornecedor');

    $resp->assertOk()
        ->assertSee('FORNECEDOR LTDA')
        ->assertSee('AMBOS LTDA')
        ->assertDontSee('CLIENTE LTDA');
});

it('filtra participantes por relacao = sem_movimentacao', function () {
    $user = User::factory()->create();
    $comNota = Participante::create(['user_id' => $user->id, 'documento' => '11444777000161', 'razao_social' => 'COM NOTA LTDA']);
    $semNota = Participante::create(['user_id' => $user->id, 'documento' => '11444777000242', 'razao_social' => 'SEM NOTA LTDA']);

    notaFiscalParticipante($user, $comNota, 'entrada');

    $resp = actingAs($user)->get('/app/participantes?relacao=sem_movimentacao');

    $resp->assertOk()
        ->assertSee('SEM NOTA LTDA')
        ->assertDontSee('COM NOTA LTDA');
});

it('exibe badge de relacao na linha do participante', function () {
    $user = User::factory()->create();
    $forn = Participante::create(['user_id' => $user->id, 'documento' => '11444777000161', 'razao_social' => 'FORNECEDOR LTDA']);
    notaFiscalParticipante($user, $forn, 'entrada');

    actingAs($user)->get('/app/participantes')
        ->assertOk()
        ->assertSee('Fornecedor');
});

it('ignora relacao invalida sem quebrar (whitelist)', function () {
    $user = User::factory()->create();
    $p = Participante::create(['user_id' => $user->id, 'documento' => '11444777000161', 'razao_social' => 'QUALQUER LTDA']);
    notaFiscalParticipante($user, $p, 'entrada');

    // Valor fora da whitelist é ignorado — lista normal, sem 5xx.
    actingAs($user)->get('/app/participantes?relacao=hackzor')
        ->assertOk()
        ->assertSee('QUALQUER LTDA');
});
