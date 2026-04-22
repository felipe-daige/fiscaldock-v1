<?php

use App\Models\ConsultaLote;
use App\Models\ConsultaResultado;
use App\Models\MonitoramentoPlano;
use App\Models\Participante;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

function detalhePlano(): MonitoramentoPlano
{
    return MonitoramentoPlano::porCodigo('gratuito') ?? MonitoramentoPlano::firstOrFail();
}

function criarLoteDetalhe(User $user, array $overrides = []): ConsultaLote
{
    return ConsultaLote::create(array_merge([
        'user_id' => $user->id,
        'plano_id' => detalhePlano()->id,
        'status' => ConsultaLote::STATUS_PROCESSANDO,
        'total_participantes' => 1,
        'creditos_cobrados' => 0,
        'tab_id' => 'tab-detalhe-1',
    ], $overrides));
}

function adicionarResultadosDetalhe(ConsultaLote $lote, User $user, int $quantidade): void
{
    $participanteIds = [];

    foreach (range(1, $quantidade) as $indice) {
        $participante = Participante::create([
            'user_id' => $user->id,
            'documento' => sprintf('12345678%04d99', $indice),
            'razao_social' => sprintf('Fornecedor %02d', $indice),
            'uf' => 'SP',
            'crt' => '3',
        ]);

        $participanteIds[] = $participante->id;

        ConsultaResultado::create([
            'consulta_lote_id' => $lote->id,
            'participante_id' => $participante->id,
            'status' => ConsultaResultado::STATUS_SUCESSO,
            'resultado_dados' => [
                'situacao_cadastral' => 'ATIVA',
                'cnd_federal' => ['status' => 'regular'],
                'crf_fgts' => ['status' => 'regular'],
                'cndt' => ['status' => 'regular'],
            ],
            'consultado_em' => now()->subMinutes($indice - 1),
        ]);
    }

    $lote->participantes()->attach($participanteIds);
}

function adicionarResultadoDetalheCustom(
    ConsultaLote $lote,
    User $user,
    int $indice,
    array $resultadoOverrides = [],
    array $participanteOverrides = []
): ConsultaResultado {
    $participante = Participante::create(array_merge([
        'user_id' => $user->id,
        'documento' => sprintf('22334455%04d99', $indice),
        'razao_social' => sprintf('Fornecedor Filtro %02d', $indice),
        'uf' => 'SP',
        'crt' => null,
    ], $participanteOverrides));

    $lote->participantes()->attach([$participante->id]);

    return ConsultaResultado::create(array_merge([
        'consulta_lote_id' => $lote->id,
        'participante_id' => $participante->id,
        'status' => ConsultaResultado::STATUS_SUCESSO,
        'resultado_dados' => [
            'situacao_cadastral' => 'ATIVA',
        ],
        'consultado_em' => now()->subMinutes($indice - 1),
    ], $resultadoOverrides));
}

it('renderiza andamento no detalhe para lote em processamento', function () {
    $user = User::factory()->create();
    $lote = criarLoteDetalhe($user);

    actingAs($user)
        ->get("/app/consulta/lote/{$lote->id}")
        ->assertOk()
        ->assertSee('Detalhe da Consulta')
        ->assertSee('Andamento da Consulta')
        ->assertSee('O resultado consolidado aparecerá aqui assim que o processamento terminar.');
});

it('renderiza resultado consolidado no detalhe para lote finalizado', function () {
    $user = User::factory()->create();
    $participante = Participante::create([
        'user_id' => $user->id,
        'documento' => '12345678000199',
        'razao_social' => 'Fornecedor Final',
        'uf' => 'SP',
        'crt' => '3',
    ]);

    $lote = criarLoteDetalhe($user, [
        'status' => ConsultaLote::STATUS_FINALIZADO,
        'processado_em' => now(),
    ]);

    $lote->participantes()->attach([$participante->id]);

    ConsultaResultado::create([
        'consulta_lote_id' => $lote->id,
        'participante_id' => $participante->id,
        'status' => ConsultaResultado::STATUS_SUCESSO,
        'resultado_dados' => [
            'situacao_cadastral' => 'ATIVA',
            'simples_nacional' => true,
            'mei' => false,
            'cnd_federal' => ['status' => 'regular'],
            'crf_fgts' => ['status' => 'regular'],
            'cndt' => ['status' => 'regular'],
        ],
        'consultado_em' => now(),
    ]);

    actingAs($user)
        ->get("/app/consulta/lote/{$lote->id}")
        ->assertOk()
        ->assertSee('Resultado Consolidado')
        ->assertSee('Fornecedor Final')
        ->assertSee('12.345.678/0001-99')
        ->assertDontSee('12345678000199')
        ->assertSee('Regime Tributário')
        ->assertSee('Lucro Presumido/Real')
        ->assertSee('Regime: Lucro Presumido/Real')
        ->assertSee('Sucesso')
        ->assertSee('CND Federal')
        ->assertDontSee('>MEI<', false)
        ->assertSee('Consultado em')
        ->assertDontSee("applyConsultaLoteSort(", false);
});

it('mantem uma unica pagina quando o lote tiver menos de 20 participantes', function () {
    $user = User::factory()->create();
    $lote = criarLoteDetalhe($user, [
        'status' => ConsultaLote::STATUS_FINALIZADO,
        'total_participantes' => 12,
        'processado_em' => now(),
    ]);

    adicionarResultadosDetalhe($lote, $user, 12);

    actingAs($user)
        ->get("/app/consulta/lote/{$lote->id}")
        ->assertOk()
        ->assertSee('Participantes Consultados')
        ->assertSee('20 por página')
        ->assertSee('Mostrando 1–12 de 12 participantes')
        ->assertSee('1 / 1')
        ->assertSee('Fornecedor 01')
        ->assertSee('Fornecedor 12')
        ->assertDontSee('page_resultados=2', false);
});

it('pagina participantes no detalhe finalizado de 20 em 20', function () {
    $user = User::factory()->create();
    $lote = criarLoteDetalhe($user, [
        'status' => ConsultaLote::STATUS_FINALIZADO,
        'total_participantes' => 21,
        'processado_em' => now(),
    ]);

    adicionarResultadosDetalhe($lote, $user, 21);

    actingAs($user)
        ->get("/app/consulta/lote/{$lote->id}")
        ->assertOk()
        ->assertSee('Mostrando 1–20 de 21 participantes')
        ->assertSee('1 / 2')
        ->assertSee('Fornecedor 01')
        ->assertSee('Fornecedor 11')
        ->assertSee('Fornecedor 20')
        ->assertDontSee('Fornecedor 21');

    actingAs($user)
        ->get("/app/consulta/lote/{$lote->id}?page_resultados=2")
        ->assertOk()
        ->assertSee('Mostrando 21–21 de 21 participantes')
        ->assertSee('2 / 2')
        ->assertSee('Fornecedor 21')
        ->assertDontSee('Fornecedor 20');
});

it('retorna 404 ao tentar abrir lote de outro usuario', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $lote = criarLoteDetalhe($owner);

    actingAs($intruder)
        ->get("/app/consulta/lote/{$lote->id}")
        ->assertNotFound();
});

it('historico exibe acao abrir para qualquer lote', function () {
    $user = User::factory()->create();
    $lote = criarLoteDetalhe($user, [
        'status' => ConsultaLote::STATUS_ERRO,
        'error_message' => 'Falha no provedor',
    ]);

    actingAs($user)
        ->get('/app/consulta/historico')
        ->assertOk()
        ->assertSee('/app/consulta/lote/'.$lote->id, false)
        ->assertSee('Abrir');
});
