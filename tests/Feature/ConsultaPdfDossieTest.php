<?php

use App\Models\ConsultaLote;
use App\Models\ConsultaResultado;
use App\Models\EfdImportacao;
use App\Models\EfdNota;
use App\Models\MonitoramentoPlano;
use App\Models\Participante;
use App\Models\User;
use App\Services\ConsultaReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function dossiePlano(): MonitoramentoPlano
{
    return MonitoramentoPlano::porCodigo('due_diligence')
        ?? MonitoramentoPlano::porCodigo('gratuito')
        ?? MonitoramentoPlano::firstOrFail();
}

// Lote com 1 participante COM acervo EFD (movimentação + itens + C190) → fiscal_resumo cheio.
function dossieLoteComAcervo(User $user): array
{
    $cliente = DB::table('clientes')->insertGetId([
        'user_id' => $user->id, 'razao_social' => 'EMPRESA PROPRIA', 'documento' => '00000000000100',
        'is_empresa_propria' => true, 'created_at' => now(), 'updated_at' => now(),
    ]);
    $part = Participante::create([
        'user_id' => $user->id, 'cliente_id' => $cliente, 'documento' => '11111111000111',
        'razao_social' => 'FORNECEDOR ACERVO', 'uf' => 'SP', 'crt' => '3',
    ]);
    $imp = EfdImportacao::create(['user_id' => $user->id, 'cliente_id' => $cliente, 'tipo_efd' => 'EFD ICMS/IPI', 'filename' => 'f.txt', 'status' => 'concluido', 'iniciado_em' => now()]);

    $nota = EfdNota::create([
        'user_id' => $user->id, 'cliente_id' => $cliente, 'participante_id' => $part->id,
        'importacao_id' => $imp->id, 'numero' => '555', 'serie' => '1', 'modelo' => '55',
        'origem_arquivo' => 'fiscal', 'tipo_operacao' => 'entrada', 'valor_total' => 1000,
        'valor_desconto' => 0, 'cancelada' => false, 'data_emissao' => '2024-05-01',
    ]);
    DB::table('efd_notas_itens')->insert([
        'efd_nota_id' => $nota->id, 'user_id' => $user->id, 'numero_item' => 1,
        'codigo_item' => 'AGUA', 'descricao' => 'AGUA MINERAL', 'quantidade' => 1,
        'unidade_medida' => 'UN', 'valor_unitario' => 1000, 'valor_total' => 1000,
        'cfop' => 1102, 'cst_icms' => '00', 'aliquota_icms' => 18, 'created_at' => now(), 'updated_at' => now(),
    ]);
    DB::table('efd_notas_consolidados')->insert([
        'efd_nota_id' => $nota->id, 'user_id' => $user->id, 'cfop' => 1102, 'cst_icms' => '00',
        'aliquota_icms' => 18, 'valor_operacao' => 1000, 'valor_bc_icms' => 1000, 'valor_icms' => 180,
        'valor_bc_icms_st' => 0, 'valor_icms_st' => 0, 'valor_reducao_bc' => 0, 'valor_ipi' => 0,
        'created_at' => now(), 'updated_at' => now(),
    ]);

    $lote = ConsultaLote::create([
        'user_id' => $user->id, 'plano_id' => dossiePlano()->id, 'status' => ConsultaLote::STATUS_FINALIZADO,
        'total_participantes' => 1, 'creditos_cobrados' => 0, 'tab_id' => 'tab-dos-'.uniqid(), 'processado_em' => now(),
    ]);
    $lote->participantes()->attach([$part->id]);
    ConsultaResultado::create([
        'consulta_lote_id' => $lote->id, 'participante_id' => $part->id,
        'status' => ConsultaResultado::STATUS_SUCESSO,
        'resultado_dados' => ['razao_social' => 'FORNECEDOR ACERVO', 'situacao_cadastral' => 'ATIVA', 'regime_tributario' => 'Lucro Real'],
        'consultado_em' => now(),
    ]);

    return [$lote, $part];
}

it('PDF do dossiê: render sem exceção + seções de panorama presentes', function () {
    $user = User::factory()->create();
    [$lote] = dossieLoteComAcervo($user);

    $html = view('reports.consulta-lote', app(ConsultaReportService::class)->dadosRelatorio($lote))->render();

    expect($html)->toContain('Movimentação')
        ->toContain('Top CFOPs')
        ->toContain('Top produtos')
        ->toContain('AGUA MINERAL')   // produto do acervo
        ->toContain('1102');          // CFOP

    // dompdf gera bytes sem exceção (pega CSS/HTML que o dompdf rejeita)
    expect(app(ConsultaReportService::class)->gerarPdf($lote)->output())->not->toBeEmpty();
});

function pdfDetLoteSemAcervo(User $user): ConsultaLote
{
    $part = Participante::create([
        'user_id' => $user->id, 'documento' => '22222222000122',
        'razao_social' => 'SEM ACERVO', 'uf' => 'RJ', 'crt' => '3',
    ]);
    $lote = ConsultaLote::create([
        'user_id' => $user->id, 'plano_id' => dossiePlano()->id, 'status' => ConsultaLote::STATUS_FINALIZADO,
        'total_participantes' => 1, 'creditos_cobrados' => 0, 'tab_id' => 'tab-sa-'.uniqid(), 'processado_em' => now(),
    ]);
    $lote->participantes()->attach([$part->id]);
    ConsultaResultado::create([
        'consulta_lote_id' => $lote->id, 'participante_id' => $part->id,
        'status' => ConsultaResultado::STATUS_SUCESSO,
        'resultado_dados' => ['razao_social' => 'SEM ACERVO', 'situacao_cadastral' => 'ATIVA'],
        'consultado_em' => now(),
    ]);

    return $lote;
}

it('PDF do dossiê: crédito IBS/CBS com passo a passo (valores batem com o service)', function () {
    $user = User::factory()->create();
    [$lote, $part] = dossieLoteComAcervo($user);

    $html = view('reports.consulta-lote', app(ConsultaReportService::class)->dadosRelatorio($lote))->render();

    expect($html)->toContain('Crédito tributário')
        ->toContain('Crédito potencial')
        ->toContain('Base');

    // paridade: usa o MESMO credito_reforma que o PDF renderizou (via getDetalhes)
    $cr = app(ConsultaReportService::class)->getDetalhes($lote)->first()['fiscal_resumo']['credito_reforma'];
    $potencial = number_format($cr['fornecedor']['credito_potencial'], 2, ',', '.');
    expect($html)->toContain($potencial);
});

it('PDF do dossiê: CNPJ sem acervo omite panorama com nota', function () {
    $user = User::factory()->create();
    $lote = pdfDetLoteSemAcervo($user);

    $html = view('reports.consulta-lote', app(ConsultaReportService::class)->dadosRelatorio($lote))->render();

    expect($html)->toContain('Sem movimentação no acervo')
        ->not->toContain('AGUA MINERAL');
});

it('PDF do dossiê: certidão canônica não consultada consta marcada no relatório', function () {
    $user = User::factory()->create();
    $plano = MonitoramentoPlano::porCodigo('gratuito') ?? dossiePlano();
    $part = Participante::create([
        'user_id' => $user->id, 'documento' => '44444444000144',
        'razao_social' => 'SO CADASTRO', 'uf' => 'SP', 'crt' => '3',
    ]);
    $lote = ConsultaLote::create([
        'user_id' => $user->id, 'plano_id' => $plano->id, 'status' => ConsultaLote::STATUS_FINALIZADO,
        'total_participantes' => 1, 'creditos_cobrados' => 0, 'tab_id' => 'tab-nc-'.uniqid(), 'processado_em' => now(),
    ]);
    $lote->participantes()->attach([$part->id]);
    ConsultaResultado::create([
        'consulta_lote_id' => $lote->id, 'participante_id' => $part->id,
        'status' => ConsultaResultado::STATUS_SUCESSO,
        'resultado_dados' => ['razao_social' => 'SO CADASTRO', 'situacao_cadastral' => 'ATIVA'],  // nenhuma certidão
        'consultado_em' => now(),
    ]);

    $html = view('reports.consulta-lote', app(ConsultaReportService::class)->dadosRelatorio($lote))->render();

    expect($html)->toContain('Não consultada')
        ->toContain('CRF FGTS')
        ->toContain('CND Municipal');
});

it('PDF do dossiê: 1 página por CNPJ (page-break com 2+ CNPJs)', function () {
    $user = User::factory()->create();
    [$lote] = dossieLoteComAcervo($user);
    // 2º CNPJ no mesmo lote → o 2º recebe page-break-before
    $p2 = Participante::create(['user_id' => $user->id, 'documento' => '33333333000133', 'razao_social' => 'SEGUNDO', 'uf' => 'MG', 'crt' => '3']);
    $lote->participantes()->attach([$p2->id]);
    ConsultaResultado::create([
        'consulta_lote_id' => $lote->id, 'participante_id' => $p2->id,
        'status' => ConsultaResultado::STATUS_SUCESSO,
        'resultado_dados' => ['razao_social' => 'SEGUNDO', 'situacao_cadastral' => 'ATIVA'],
        'consultado_em' => now(),
    ]);
    $html = view('reports.consulta-lote', app(ConsultaReportService::class)->dadosRelatorio($lote))->render();
    expect($html)->toContain('page-break-before');
});

it('PDF do dossiê: Resumo Operacional vira panorama de risco', function () {
    $user = User::factory()->create();
    [$lote] = dossieLoteComAcervo($user);

    $html = view('reports.consulta-lote', app(ConsultaReportService::class)->dadosRelatorio($lote))->render();

    expect($html)->toContain('Distribuição de risco')
        ->toContain('Regularidade fiscal')
        ->toContain('Situação cadastral')
        ->toContain('ATIVA')        // situação do fixture
        ->toContain('Score Médio'); // KPI compacto mantido
});

it('PDF do dossiê: panorama não quebra com lote só-erro', function () {
    $user = User::factory()->create();
    $plano = dossiePlano();
    $part = Participante::create([
        'user_id' => $user->id, 'documento' => '55555555000155',
        'razao_social' => 'FALHA', 'uf' => 'SP', 'crt' => '3',
    ]);
    $lote = ConsultaLote::create([
        'user_id' => $user->id, 'plano_id' => $plano->id, 'status' => ConsultaLote::STATUS_FINALIZADO,
        'total_participantes' => 1, 'creditos_cobrados' => 0, 'tab_id' => 'tab-err-'.uniqid(), 'processado_em' => now(),
    ]);
    $lote->participantes()->attach([$part->id]);
    ConsultaResultado::create([
        'consulta_lote_id' => $lote->id, 'participante_id' => $part->id,
        'status' => ConsultaResultado::STATUS_ERRO, 'error_message' => 'timeout',
        'resultado_dados' => [], 'consultado_em' => now(),
    ]);

    $html = view('reports.consulta-lote', app(ConsultaReportService::class)->dadosRelatorio($lote))->render();
    expect($html)->toContain('Distribuição de risco');
    expect(app(ConsultaReportService::class)->gerarPdf($lote)->output())->not->toBeEmpty();
});

it('dadosRelatorio inclui emitente = razão da empresa própria, com fallback', function () {
    $user = User::factory()->create(['name' => 'Contador Fulano']);
    // empresa própria com CNPJ 14 díg
    DB::table('clientes')->insert([
        'user_id' => $user->id, 'razao_social' => 'MINHA CONTABILIDADE LTDA', 'documento' => '99888777000166',
        'is_empresa_propria' => true, 'created_at' => now(), 'updated_at' => now(),
    ]);
    $lote = ConsultaLote::create([
        'user_id' => $user->id, 'plano_id' => dossiePlano()->id, 'status' => ConsultaLote::STATUS_FINALIZADO,
        'total_participantes' => 0, 'creditos_cobrados' => 0, 'tab_id' => 'tab-emi-'.uniqid(), 'processado_em' => now(),
    ]);

    expect(app(ConsultaReportService::class)->dadosRelatorio($lote)['emitente'])->toBe('MINHA CONTABILIDADE LTDA');

    // sem empresa própria → fallback pro nome do usuário
    $user2 = User::factory()->create(['name' => 'Sem Empresa']);
    $lote2 = ConsultaLote::create([
        'user_id' => $user2->id, 'plano_id' => dossiePlano()->id, 'status' => ConsultaLote::STATUS_FINALIZADO,
        'total_participantes' => 0, 'creditos_cobrados' => 0, 'tab_id' => 'tab-emi2-'.uniqid(), 'processado_em' => now(),
    ]);
    expect(app(ConsultaReportService::class)->dadosRelatorio($lote2)['emitente'])->toBe('Sem Empresa');
});
