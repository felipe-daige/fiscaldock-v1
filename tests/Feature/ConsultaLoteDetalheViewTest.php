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
        'regime_tributario' => 'Lucro Presumido',
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
        ->assertSee('Com Sinalização')
        ->assertSee('Fornecedor Final')
        ->assertSee('12.345.678/0001-99')
        ->assertDontSee('12345678000199')
        ->assertSee('Regime Tributário')
        ->assertSee('<th class="px-3 py-2.5 text-center text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50 whitespace-nowrap">Regime Tributário</th>', false)
        ->assertSee('Lucro Presumido')
        ->assertDontSee('>Regime: Lucro Presumido<', false)
        ->assertDontSee('Lucro Presumido/Real')
        ->assertSee('Sucesso')
        // Coluna agrupada "Certidões": as 6 fontes de regularidade viram mini-badges (FED/EST/MUN/...)
        ->assertSee('<th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Certidões</th>', false)
        ->assertSee('CND Federal (Receita/PGFN) · Regular', false)
        ->assertDontSee('>MEI<', false)
        ->assertSee('Consultado em')
        ->assertDontSee("applyConsultaLoteSort(", false);
});

it('renderiza parecer resumido com badge curto quando houver sinalizacao acionavel', function () {
    $user = User::factory()->create();
    $participante = Participante::create([
        'user_id' => $user->id,
        'documento' => '12345678000199',
        'razao_social' => 'Fornecedor Inativo',
        'uf' => 'SP',
        'regime_tributario' => 'Lucro Presumido',
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
            'situacao_cadastral' => 'BAIXADA',
            'motivo_situacao_cadastral' => 'EXTINCAO POR ENCERRAMENTO',
            'cnd_federal' => ['status' => 'regular'],
            'crf_fgts' => ['status' => 'regular'],
            'cndt' => ['status' => 'regular'],
        ],
        'consultado_em' => now(),
    ]);

    actingAs($user)
        ->get("/app/consulta/lote/{$lote->id}")
        ->assertOk()
        ->assertSee('Sinalizações')
        ->assertSee('Inativa na RF')
        ->assertDontSee('>Regime: Lucro Presumido<', false);
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

it('retorna o regime tributario textual no endpoint de resultados do lote', function () {
    $user = User::factory()->create();
    $participante = Participante::create([
        'user_id' => $user->id,
        'documento' => '98765432000111',
        'razao_social' => 'Fornecedor JSON',
        'uf' => 'SP',
        'regime_tributario' => 'Lucro Presumido',
        'crt' => 3,
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
        ],
        'consultado_em' => now(),
    ]);

    actingAs($user)
        ->getJson("/app/consulta/lote/{$lote->id}/resultados")
        ->assertOk()
        ->assertJsonPath('resultados.0.regime_tributario', 'Lucro Presumido');
});

it('retorna parecer resumido no endpoint de resultados do lote', function () {
    $user = User::factory()->create();
    $participante = Participante::create([
        'user_id' => $user->id,
        'documento' => '11122233000144',
        'razao_social' => 'Fornecedor API',
        'uf' => 'SP',
        'crt' => 3,
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
            'situacao_cadastral' => 'BAIXADA',
            'motivo_situacao_cadastral' => 'EXTINCAO POR ENCERRAMENTO',
            'regime_tributario' => 'Lucro Presumido',
        ],
        'consultado_em' => now(),
    ]);

    actingAs($user)
        ->getJson("/app/consulta/lote/{$lote->id}/resultados")
        ->assertOk()
        ->assertJsonPath('resultados.0.parecer.0.badge_label', 'Inativa na RF')
        ->assertJsonCount(1, 'resultados.0.parecer');
});

it('exibe mensagem operacional no detalhe do lote quando o resultado traz mensagem raiz', function () {
    $user = User::factory()->create();
    $lote = criarLoteDetalhe($user, [
        'status' => ConsultaLote::STATUS_FINALIZADO,
        'processado_em' => now(),
    ]);

    adicionarResultadoDetalheCustom($lote, $user, 1, [
        'resultado_dados' => [
            'situacao_cadastral' => 'ATIVA',
            'mensagem' => 'Participante conciliado a partir do EFD com sucesso.',
        ],
    ], [
        'documento' => '55667788000199',
        'razao_social' => 'Fornecedor Mensagem',
    ]);

    actingAs($user)
        ->get("/app/consulta/lote/{$lote->id}")
        ->assertOk()
        ->assertSee('Participante conciliado a partir do EFD com sucesso.');
});

it('retorna mensagem exibivel no endpoint do lote com fallback para bloco aninhado', function () {
    $user = User::factory()->create();
    $lote = criarLoteDetalhe($user, [
        'status' => ConsultaLote::STATUS_FINALIZADO,
        'processado_em' => now(),
    ]);

    adicionarResultadoDetalheCustom($lote, $user, 1, [
        'resultado_dados' => [
            'situacao_cadastral' => 'ATIVA',
            'cnd_federal' => [
                'status' => 'INDETERMINADO',
                'mensagem' => 'Receita sem dados suficientes para emitir a certidao.',
            ],
        ],
    ]);

    actingAs($user)
        ->getJson("/app/consulta/lote/{$lote->id}/resultados")
        ->assertOk()
        ->assertJsonPath('resultados.0.mensagem_exibivel', 'Receita sem dados suficientes para emitir a certidao.');
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

it('renderiza erro critico sanitizado com contato de suporte no detalhe do lote', function () {
    $user = User::factory()->create();
    $lote = criarLoteDetalhe($user, [
        'status' => ConsultaLote::STATUS_ERRO,
        'error_code' => 'INFOSIMPLES_PARAMETROS_VAZIOS',
        'error_message' => 'CND Federal (undefined / undefined): Parâmetros obrigatórios não foram enviados.',
    ]);

    actingAs($user)
        ->get("/app/consulta/lote/{$lote->id}")
        ->assertOk()
        ->assertSee('Falha no processamento')
        ->assertSee('Falar com o suporte')
        ->assertSee('wa.me/5567999844366', false)
        ->assertDontSee('INFOSIMPLES')
        ->assertDontSee('Parâmetros obrigatórios');
});

it('exibe detalhe expansível com TODAS as fontes consultadas, inclusive as ausentes da tabela', function () {
    $user = User::factory()->create();
    $participante = Participante::create([
        'user_id' => $user->id,
        'documento' => '97551165000193',
        'razao_social' => 'Fornecedor Completo',
        'uf' => 'MS',
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
            'razao_social' => 'Fornecedor Completo',
            'cnaes' => [['codigo' => '6201-5/01', 'descricao' => 'Software', 'principal' => true]],
            'cnd_federal' => [
                'status' => 'Positiva com efeitos de negativa',
                'mensagem' => 'CERTIDAO POSITIVA COM EFEITOS DE NEGATIVA DE DEBITOS',
                'certidao_codigo' => 'A3E5.6BE2.FAB3',
                'data_validade' => '15/11/2026',
                'conseguiu_emitir' => true,
            ],
            'cnd_estadual' => ['status' => 'Negativa', 'certidao_codigo' => '573628/2026'],
            'sintegra' => ['situacao' => 'HABILITADO', 'inscricao_estadual' => '28.368.441-0'],
            'cgu_cnc' => [
                'possui_sancao' => false,
                'bases' => [['nome' => 'CEIS', 'situacao' => 'Nada Consta']],
                'comprovante' => 'https://exemplo.test/cgu.pdf',
            ],
            'cnj_improbidade' => ['possui_condenacao' => false, 'comprovante' => 'https://exemplo.test/cnj.pdf'],
        ],
        'consultado_em' => now(),
    ]);

    actingAs($user)
        ->get("/app/consulta/lote/{$lote->id}")
        ->assertOk()
        // fontes que a tabela resumida NÃO mostra agora aparecem no detalhe
        ->assertSee('CND Estadual (SEFAZ)')
        ->assertSee('SINTEGRA')
        ->assertSee('Sanções (CGU)')
        ->assertSee('Improbidade (CNJ)')
        // detalhes ricos das certidões
        ->assertSee('A3E5.6BE2.FAB3')
        ->assertSee('573628/2026')
        ->assertSee('28.368.441-0')
        ->assertSee('CERTIDAO POSITIVA COM EFEITOS DE NEGATIVA DE DEBITOS')
        // comprovantes baixados
        ->assertSee('https://exemplo.test/cgu.pdf', false)
        ->assertSee('Ver comprovante')
        // controles de expansão
        ->assertSee('data-detalhe-toggle="consulta-detalhe-d-0"', false)
        ->assertSee('Ver detalhes da consulta')
        // resumo escrito por CNPJ
        ->assertSee('Resumo da análise')
        ->assertSee('Situação cadastral ATIVA')
        ->assertSee('Sem sanções na CGU.')
        // análise agregada do lote (texto + tabela + gráfico)
        ->assertSee('Análise da Consulta')
        ->assertSee('Distribuição');
});

it('exibe créditos por consulta como tag do produto em vez do código cru', function () {
    $user = User::factory()->create();
    $plano = MonitoramentoPlano::porCodigo('due_diligence') ?? detalhePlano();

    $lote = ConsultaLote::create([
        'user_id' => $user->id,
        'plano_id' => $plano->id,
        'status' => ConsultaLote::STATUS_FINALIZADO,
        'total_participantes' => 1,
        'creditos_cobrados' => 0,
        'tab_id' => 'tab-produto-tag',
        'processado_em' => now(),
    ]);

    $resp = actingAs($user)->get("/app/consulta/lote/{$lote->id}")->assertOk();

    $resp->assertSee($plano->nome);
    if ($plano->codigo === 'due_diligence') {
        $resp->assertSee('R$ 7,00')->assertDontSee('due_diligence'); // 35 créditos internos × R$0,20
    }
});

it('exibe razão social e CNPJ do CLIENTE quando o resultado é do escopo clientes', function () {
    $user = User::factory()->create();

    $cliente = \App\Models\Cliente::create([
        'user_id' => $user->id,
        'documento' => '97551165000193',
        'razao_social' => 'HIDRATOP COMERCIO DE PECAS E SERVICOS HIDRAULICOS LTDA',
        'uf' => 'MS',
    ]);

    $lote = criarLoteDetalhe($user, [
        'status' => ConsultaLote::STATUS_FINALIZADO,
        'processado_em' => now(),
    ]);

    ConsultaResultado::create([
        'consulta_lote_id' => $lote->id,
        'participante_id' => null,
        'cliente_id' => $cliente->id,
        'status' => ConsultaResultado::STATUS_SUCESSO,
        'resultado_dados' => [
            'situacao_cadastral' => 'ATIVA',
            'razao_social' => 'HIDRATOP COMERCIO DE PECAS E SERVICOS HIDRAULICOS LTDA',
        ],
        'consultado_em' => now(),
    ]);

    actingAs($user)
        ->get("/app/consulta/lote/{$lote->id}")
        ->assertOk()
        ->assertSee('HIDRATOP COMERCIO DE PECAS E SERVICOS HIDRAULICOS LTDA')
        ->assertSee('97.551.165/0001-93')
        ->assertDontSee('Sem razão social');
});

it('classifica certidão Negativa como Regular na tabela (corrige bug str_contains negativa)', function () {
    $user = User::factory()->create();
    $participante = Participante::create([
        'user_id' => $user->id,
        'documento' => '11122233000199',
        'razao_social' => 'Fornecedor Negativa',
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
            'cndt' => ['status' => 'Negativa'],
            'crf_fgts' => ['status' => 'REGULAR'],
        ],
        'consultado_em' => now(),
    ]);

    $html = actingAs($user)->get("/app/consulta/lote/{$lote->id}")->assertOk()->getContent();

    // a célula CNDT (status "Negativa") deve ser verde Regular, nunca vermelho Irregular
    expect($html)->toContain('#047857');
    // não pode classificar a Negativa como Irregular
    expect(substr_count($html, 'Irregular'))->toBe(0);
});
