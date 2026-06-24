<?php

use Illuminate\Support\Facades\Blade;

function rfvFiscal(array $over = []): array
{
    return array_merge([
        'perspectiva' => 'cliente', 'papel' => null,
        'total_comprado' => 1200.0, 'total_vendido' => 700.0,
        'qtd_entrada' => 2, 'qtd_saida' => 1, 'qtd_notas' => 3,
        'primeira_nota' => '2024-01-05', 'ultima_nota' => '2024-06-20',
        'top_cfops' => [['cfop' => 1102, 'qtd' => 3]],
        'top_produtos' => [['cod_item' => 'AGUA', 'descricao' => 'AGUA MINERAL 500ML', 'ncm' => '22011000', 'valor' => 1900.0, 'qtd' => 3]],
        'relacionamentos' => [['nome' => 'DISTRIBUIDORA X', 'is_propria' => false, 'papel' => 'fornecedor', 'valor_entrada' => 1200.0, 'valor_saida' => 0.0]],
        'relacionamentos_titulo' => 'Principais contrapartes',
        'empresas_count' => 1,
    ], $over);
}

it('renderiza produtos, título de contrapartes e sem badge de papel no cliente', function () {
    $html = Blade::render('@include("autenticado.consulta.partials.relacionamento-fiscal", ["fiscal" => $fiscal])', ['fiscal' => rfvFiscal()]);

    expect($html)->toContain('AGUA MINERAL 500ML');
    expect($html)->toContain('22011000');                 // NCM
    expect($html)->toContain('Principais contrapartes');  // título dinâmico
    expect($html)->toContain('DISTRIBUIDORA X');
    expect($html)->toContain('Acervo próprio');           // chip neutro (papel null)
});

it('mostra badge de papel quando participante e título Por empresa', function () {
    $html = Blade::render('@include("autenticado.consulta.partials.relacionamento-fiscal", ["fiscal" => $fiscal])', [
        'fiscal' => rfvFiscal([
            'perspectiva' => 'participante', 'papel' => 'fornecedor',
            'relacionamentos' => [['nome' => 'EMPRESA A', 'is_propria' => true, 'papel' => 'fornecedor', 'valor_entrada' => 200.0, 'valor_saida' => 0.0]],
            'relacionamentos_titulo' => 'Por empresa',
        ]),
    ]);
    expect($html)->toContain('Fornecedor');
    expect($html)->toContain('Por empresa');
    expect($html)->toContain('EMPRESA A');
    expect($html)->toContain('(própria)');
});

it('degrada sem erro quando fiscal é null', function () {
    $html = Blade::render('@include("autenticado.consulta.partials.relacionamento-fiscal", ["fiscal" => $fiscal])', ['fiscal' => null]);
    expect($html)->toContain('Sem movimentação');
});

it('mostra "Ver mais (N)" quando produtos excedem o visível e mantém os ocultos no DOM', function () {
    config(['consultas.panorama_fiscal.visivel' => 2]);
    $produtos = [];
    for ($i = 1; $i <= 5; $i++) {
        $produtos[] = ['cod_item' => "P{$i}", 'descricao' => "PROD {$i}", 'ncm' => null, 'valor' => $i * 10.0, 'qtd' => $i];
    }
    $html = Blade::render('@include("autenticado.consulta.partials.relacionamento-fiscal", ["fiscal" => $fiscal])', [
        'fiscal' => rfvFiscal(['top_produtos' => $produtos]),
    ]);

    expect($html)->toContain('Ver mais (3)');   // 5 produtos − 2 visíveis
    expect($html)->toContain('PROD 5');          // item oculto continua no DOM (revelado pelo toggle)
    expect($html)->toContain('data-detalhe-toggle="pf-prod-'); // reusa o handler inline existente
});

it('não mostra "Ver mais" quando a lista cabe no visível', function () {
    config(['consultas.panorama_fiscal.visivel' => 10]);
    $html = Blade::render('@include("autenticado.consulta.partials.relacionamento-fiscal", ["fiscal" => $fiscal])', ['fiscal' => rfvFiscal()]);
    expect($html)->not->toContain('Ver mais');
});
