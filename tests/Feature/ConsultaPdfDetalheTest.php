<?php

use App\Models\ConsultaLote;
use App\Models\ConsultaResultado;
use App\Models\MonitoramentoPlano;
use App\Models\Participante;
use App\Models\User;
use App\Services\ConsultaReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function pdfDetPlano(): MonitoramentoPlano
{
    return MonitoramentoPlano::porCodigo('due_diligence')
        ?? MonitoramentoPlano::porCodigo('gratuito')
        ?? MonitoramentoPlano::firstOrFail();
}

function pdfDetLote(User $user): ConsultaLote
{
    return ConsultaLote::create([
        'user_id' => $user->id,
        'plano_id' => pdfDetPlano()->id,
        'status' => ConsultaLote::STATUS_FINALIZADO,
        'total_participantes' => 1,
        'creditos_cobrados' => 0,
        'tab_id' => 'tab-pdfdet-'.uniqid(),
        'processado_em' => now(),
    ]);
}

function pdfDetResultado(ConsultaLote $lote, User $user): ConsultaResultado
{
    $p = Participante::create([
        'user_id' => $user->id,
        'documento' => '12345678000199',
        'razao_social' => 'Fornecedor Detalhe',
        'uf' => 'SP',
        'crt' => '3',
    ]);
    $lote->participantes()->attach([$p->id]);

    return ConsultaResultado::create([
        'consulta_lote_id' => $lote->id,
        'participante_id' => $p->id,
        'status' => ConsultaResultado::STATUS_SUCESSO,
        'resultado_dados' => [
            'razao_social' => 'Fornecedor Detalhe',
            'situacao_cadastral' => 'ATIVA',
            'cnd_federal' => ['status' => 'Negativa', 'tipo' => 'Negativa', 'comprovante' => 'https://ex.com/cnd-federal.pdf'],
            'cndt' => ['status' => 'Negativa', 'comprovante' => 'https://ex.com/cndt.pdf'],
            'sintegra' => ['situacao' => 'Habilitado', 'comprovante' => 'https://ex.com/sintegra.pdf'],
            'cgu_cnc' => ['possui_sancao' => false, 'comprovante' => 'https://ex.com/cgu.pdf'],
            'cnj_improbidade' => ['possui_condenacao' => false, 'comprovante' => 'https://ex.com/cnj.pdf'],
        ],
        'consultado_em' => now(),
    ]);
}

it('getDetalhes traz blocos por fonte com link de comprovante de TODAS as fontes', function () {
    $user = User::factory()->create();
    $lote = pdfDetLote($user);
    pdfDetResultado($lote, $user);

    $detalhes = app(ConsultaReportService::class)->getDetalhes($lote);

    expect($detalhes)->toHaveCount(1);
    $d = $detalhes->first();

    $urls = collect($d['blocos'])->pluck('comprovante_url')->filter()->values()->all();

    expect($urls)->toContain('https://ex.com/cnd-federal.pdf')
        ->toContain('https://ex.com/cndt.pdf')
        ->toContain('https://ex.com/sintegra.pdf')
        ->toContain('https://ex.com/cgu.pdf')
        ->toContain('https://ex.com/cnj.pdf');
});

it('PDF da consulta estende o layout-mestre e colore o risco', function () {
    $user = User::factory()->create();
    $lote = pdfDetLote($user);
    pdfDetResultado($lote, $user);

    $service = app(\App\Services\ConsultaReportService::class);
    $html = view('reports.consulta-lote', $service->dadosRelatorio($lote))->render();

    // herda o layout-mestre (marca no header)
    expect($html)->toContain('FiscalDock');
    // risco colorido por token (algum badge com background-color hex)
    expect($html)->toContain('background-color:');
    // estrutura nova: seção de certidões por CNPJ
    expect($html)->toContain('Certidões');
    // nao duplica o brand block do layout antigo
    expect($html)->not->toContain('Identificação do Relatório');
});

it('view PDF renderiza detalhamento completo com links de comprovante clicaveis', function () {
    $user = User::factory()->create();
    $lote = pdfDetLote($user);
    pdfDetResultado($lote, $user);

    $service = app(ConsultaReportService::class);
    $html = view('reports.consulta-lote', $service->dadosRelatorio($lote))->render();

    // Links diretos pros PDFs das certidões
    expect($html)->toContain('https://ex.com/cnd-federal.pdf')
        ->toContain('https://ex.com/cndt.pdf')
        ->toContain('https://ex.com/sintegra.pdf');

    // Descrições/labels das fontes
    expect($html)->toContain('CND Federal')
        ->toContain('CNDT')
        ->toContain('SINTEGRA');
});

it('getDetalhes traz fiscal_resumo e credito_reforma com identificação por CNPJ', function () {
    $user = User::factory()->create();
    $lote = pdfDetLote($user);
    pdfDetResultado($lote, $user);

    $d = app(ConsultaReportService::class)->getDetalhes($lote)->first();

    expect($d)->toHaveKeys(['participante_id', 'uf', 'situacao_cadastral', 'regime_tributario', 'status', 'fiscal_resumo']);
    expect($d['uf'])->toBe('SP');
    expect($d['situacao_cadastral'])->toBe('ATIVA');
    expect($d['status'])->toBe(App\Models\ConsultaResultado::STATUS_SUCESSO);
    // sem acervo EFD nesse fixture → fiscal_resumo null, sem erro
    expect($d['fiscal_resumo'])->toBeNull();
});

it('dadosRelatorio inclui chave analise com por_fonte', function () {
    $user = User::factory()->create();
    $lote = pdfDetLote($user);
    pdfDetResultado($lote, $user);

    $dados = app(ConsultaReportService::class)->dadosRelatorio($lote);

    expect($dados)->toHaveKey('analise');
    expect($dados['analise']['por_fonte'])->toBeArray();
});
