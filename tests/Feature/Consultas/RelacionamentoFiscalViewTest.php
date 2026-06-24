<?php

use Illuminate\Support\Facades\Blade;

function rfvFiscal(array $over = []): array
{
    return array_merge([
        'perspectiva' => 'cliente', 'papel' => null,
        'total_comprado' => 1200.0, 'total_vendido' => 700.0,
        'qtd_entrada' => 2, 'qtd_saida' => 1, 'qtd_notas' => 3,
        'primeira_nota' => '2024-01-05', 'ultima_nota' => '2024-06-20',
        'top_cfops' => [['cfop' => 1102, 'descricao' => '1102 — Compra para industrialização', 'qtd' => 3, 'valor' => 5000.0]],
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

it('CFOP: renderiza descrição + valor + qtd e oferece seletor Top N como produtos', function () {
    config(['consultas.panorama_fiscal.visivel' => 5]);
    $cfops = [];
    for ($i = 1; $i <= 8; $i++) {
        $cfops[] = ['cfop' => 5100 + $i, 'descricao' => "{$i} — VENDA TIPO {$i}", 'qtd' => $i, 'valor' => $i * 100.0];
    }
    $html = Blade::render('@include("autenticado.consulta.partials.relacionamento-fiscal", ["fiscal" => $fiscal])', [
        'fiscal' => rfvFiscal(['top_cfops' => $cfops]),
    ]);

    expect($html)->toContain('Principais CFOPs');
    expect($html)->toContain('VENDA TIPO 1');     // descrição da CFOP
    expect($html)->toContain('5101');             // código
    expect($html)->toContain('R$ 100,00');        // valor (não só qtd)
    expect($html)->toContain('×1');               // qtd
    expect($html)->toContain('Todos (8)');        // mesmo seletor das outras listas
    expect($html)->toContain('VENDA TIPO 8');     // todas as linhas no DOM
});

it('degrada sem erro quando fiscal é null', function () {
    $html = Blade::render('@include("autenticado.consulta.partials.relacionamento-fiscal", ["fiscal" => $fiscal])', ['fiscal' => null]);
    expect($html)->toContain('Sem movimentação');
});

it('oferece seletor Top N (5/10/Todos) quando a lista excede 5 e marca extras como hidden', function () {
    config(['consultas.panorama_fiscal.visivel' => 5]);
    $produtos = [];
    for ($i = 1; $i <= 12; $i++) {
        $produtos[] = ['cod_item' => "P{$i}", 'descricao' => "PROD {$i}", 'ncm' => null, 'valor' => $i * 10.0, 'qtd' => $i];
    }
    $html = Blade::render('@include("autenticado.consulta.partials.relacionamento-fiscal", ["fiscal" => $fiscal])', [
        'fiscal' => rfvFiscal(['top_produtos' => $produtos]),
    ]);

    expect($html)->toContain('<select');
    expect($html)->toContain('Top 5');
    expect($html)->toContain('Top 10');
    expect($html)->toContain('Todos (12)');
    expect($html)->toContain('data-pf-list');
    expect($html)->toContain('PROD 12');                               // todas as linhas no DOM; controle é client-side
    expect(substr_count($html, 'data-pf-row'))->toBeGreaterThanOrEqual(12);
    expect($html)->toContain('value="5" selected');                    // default = visivel(5)
});

it('não renderiza seletor quando a lista cabe em 5', function () {
    config(['consultas.panorama_fiscal.visivel' => 10]);
    $html = Blade::render('@include("autenticado.consulta.partials.relacionamento-fiscal", ["fiscal" => $fiscal])', ['fiscal' => rfvFiscal()]);  // 1 produto, 1 contraparte
    expect($html)->not->toContain('<select');
});
