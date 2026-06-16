<?php

use App\Support\Monitoramento\PlanoCatalog;
use App\Support\Monitoramento\PlanoConsultaLabels;

/**
 * Rótulos derivados das consultas REALMENTE incluídas no plano (catálogo canônico).
 * Garante que os cards nunca mais divirjam do que o backend consulta.
 */
function rotulosDoPlano(string $codigo): array
{
    return PlanoConsultaLabels::paraConsultas(
        PlanoCatalog::forCodigo($codigo)['consultas_incluidas']
    );
}

it('não promete CNAEs nem QSA no plano Gratuito', function () {
    $rotulos = rotulosDoPlano('gratuito');

    expect($rotulos)->not->toContain('Quadro societário (QSA)');
    expect(collect($rotulos)->contains(fn ($r) => str_contains($r, 'CNAE')))->toBeFalse();
    // ainda mostra o básico que ele de fato entrega
    expect($rotulos)->toContain('Situação cadastral (ativa, inapta, baixada)');
});

it('destaca o parecer fiscal automático no plano Validação', function () {
    expect(rotulosDoPlano('validacao'))->toContain('Parecer fiscal automático');
});

it('no Licitação inclui CND Federal mas não CNDT/FGTS nem parecer fiscal', function () {
    $rotulos = rotulosDoPlano('licitacao');

    expect($rotulos)->toContain('CND Federal (PGFN/RFB)');
    expect($rotulos)->not->toContain('CNDT (débitos trabalhistas)');
    expect($rotulos)->not->toContain('Regularidade do FGTS (CRF)');
    expect($rotulos)->not->toContain('Parecer fiscal automático');
});

it('inclui CNDT, FGTS e certidões estaduais/municipais a partir do Compliance', function () {
    $rotulos = rotulosDoPlano('compliance');

    expect($rotulos)->toContain('CNDT (débitos trabalhistas)');
    expect($rotulos)->toContain('Regularidade do FGTS (CRF)');
    expect($rotulos)->toContain('CND Estadual');
    expect($rotulos)->toContain('CND Municipal');
});

it('mostra sanções e improbidade no Due Diligence', function () {
    $rotulos = rotulosDoPlano('due_diligence');

    expect($rotulos)->toContain('Sanções e idoneidade (CGU)');
    expect($rotulos)->toContain('Improbidade administrativa (CNJ)');
});

it('dobra sub-chaves redundantes sem duplicar rótulos', function () {
    $rotulos = PlanoConsultaLabels::paraConsultas([
        'situacao_cadastral', 'dados_cadastrais', 'endereco',
        'cnaes', 'cnaes_secundarios', 'qsa', 'qsa_detalhado',
    ]);

    expect($rotulos)->toBe(array_values(array_unique($rotulos)));
    expect($rotulos)->toContain('CNAEs (principal e secundários)');
    expect(collect($rotulos)->filter(fn ($r) => str_contains($r, 'CNAE')))->toHaveCount(1);
});

it('preserva a ordem das consultas e ignora chaves desconhecidas', function () {
    $rotulos = PlanoConsultaLabels::paraConsultas([
        'cnd_federal', 'chave_inexistente', 'situacao_cadastral',
    ]);

    expect($rotulos)->toBe([
        'CND Federal (PGFN/RFB)',
        'Situação cadastral (ativa, inapta, baixada)',
    ]);
});
