<?php

use App\Models\ConsultaLote;
use App\Models\ConsultaResultado;
use App\Models\Participante;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

it('mantem sem mov acima de nao consultada e amplia a coluna de origem na listagem de participantes', function () {
    $user = User::factory()->create();

    Participante::create([
        'user_id' => $user->id,
        'documento' => '12345678000195',
        'razao_social' => 'Fornecedor Teste',
        'origem_tipo' => 'SPED_EFD_CONTRIB',
    ]);

    $response = actingAs($user)
        ->withHeader('X-Requested-With', 'XMLHttpRequest')
        ->get('/app/participantes');

    $response
        ->assertOk()
        ->assertSee('Participantes')
        ->assertSee('EFD Contrib')
        ->assertSee('w-[140px]', false)
        ->assertSeeInOrder(['Sem Mov.', 'Não consultada']);
});

it('propaga return_to nos acessos a ficha do participante na listagem filtrada', function () {
    $user = User::factory()->create();

    $participante = Participante::create([
        'user_id' => $user->id,
        'documento' => '12345678000195',
        'razao_social' => 'Fornecedor Retorno',
        'origem_tipo' => 'MANUAL',
    ]);

    $returnTo = '/app/participantes?busca=Fornecedor';
    $participanteUrl = '/app/participante/'.$participante->id.'?return_to='.urlencode($returnTo);

    actingAs($user)
        ->withHeader('X-Requested-With', 'XMLHttpRequest')
        ->get($returnTo)
        ->assertOk()
        ->assertSee($participanteUrl, false);
});

it('usa return_to valido na ficha do participante', function () {
    $user = User::factory()->create();

    $participante = Participante::create([
        'user_id' => $user->id,
        'documento' => '12345678000195',
        'razao_social' => 'Fornecedor Retorno',
        'origem_tipo' => 'MANUAL',
    ]);

    actingAs($user)
        ->withHeader('X-Requested-With', 'XMLHttpRequest')
        ->get('/app/participante/'.$participante->id.'?return_to='.urlencode('/app/participantes?busca=Fornecedor'))
        ->assertOk()
        ->assertSee('Voltar para participantes')
        ->assertSee('href="/app/participantes?busca=Fornecedor"', false);
});

it('ignora return_to externo e usa dashboard como fallback na ficha do participante', function () {
    $user = User::factory()->create();

    $participante = Participante::create([
        'user_id' => $user->id,
        'documento' => '12345678000195',
        'razao_social' => 'Fornecedor Retorno',
        'origem_tipo' => 'MANUAL',
    ]);

    actingAs($user)
        ->withHeader('X-Requested-With', 'XMLHttpRequest')
        ->get('/app/participante/'.$participante->id.'?return_to='.urlencode('https://evil.test/app/participantes'))
        ->assertOk()
        ->assertSee('Voltar para o dashboard')
        ->assertSee('href="/app/dashboard"', false)
        ->assertDontSee('evil.test');
});

it('exibe a mensagem operacional da ultima consulta na ficha do participante', function () {
    $user = User::factory()->create();

    $participante = Participante::create([
        'user_id' => $user->id,
        'documento' => '12345678000195',
        'razao_social' => 'Fornecedor Mensagem',
        'origem_tipo' => 'SPED_EFD_FISCAL',
        'uf' => 'SP',
    ]);

    $lote = ConsultaLote::create([
        'user_id' => $user->id,
        'status' => ConsultaLote::STATUS_FINALIZADO,
        'total_participantes' => 1,
        'creditos_cobrados' => 0,
        'tab_id' => 'tab-participante-mensagem',
        'processado_em' => now(),
    ]);

    ConsultaResultado::create([
        'consulta_lote_id' => $lote->id,
        'participante_id' => $participante->id,
        'status' => ConsultaResultado::STATUS_SUCESSO,
        'resultado_dados' => [
            'situacao_cadastral' => 'ATIVA',
            'mensagem' => 'Participante conciliado a partir do EFD com sucesso.',
        ],
        'consultado_em' => now(),
    ]);

    actingAs($user)
        ->withHeader('X-Requested-With', 'XMLHttpRequest')
        ->get('/app/participante/'.$participante->id)
        ->assertOk()
        ->assertSee('Mensagem da Consulta')
        ->assertSee('Participante conciliado a partir do EFD com sucesso.');
});
