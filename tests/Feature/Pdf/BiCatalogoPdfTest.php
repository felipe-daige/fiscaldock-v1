<?php

use Illuminate\Support\Collection;

it('gera PDF do catalogo de itens via PdfReport retornando bytes PDF validos', function () {
    // Fixture completo: cobre todas as chaves que a view lê ($dados no controller ~132-137)
    $itens = Collection::make([
        [
            'codigo_item'    => 'ITEM001',
            'descricao'      => 'Produto de Teste para PDF',
            'fontes'         => 'efd',
            'ncm'            => '12345678',
            'cfops'          => '1102',
            'csts'           => '50',
            'quantidade'     => 10.0,
            'ocorrencias'    => 3,
            'aliquota_media' => 12.50,
            'valor_total'    => 1500.00,
            'tem_catalogo'   => true,
            'catalogo'       => ['descr_item' => 'Produto Catálogo EFD'],
        ],
        [
            'codigo_item'    => 'ITEM002',
            'descricao'      => 'Produto XML sem catálogo',
            'fontes'         => 'xml',
            'ncm'            => null,
            'cfops'          => null,
            'csts'           => null,
            'quantidade'     => 5.0,
            'ocorrencias'    => 1,
            'aliquota_media' => null,
            'valor_total'    => 200.00,
            'tem_catalogo'   => false,
            'catalogo'       => [],
        ],
        [
            'codigo_item'    => 'ITEM003',
            'descricao'      => 'Item em ambas as fontes',
            'fontes'         => 'ambas',
            'ncm'            => '87654321',
            'cfops'          => '5102',
            'csts'           => '00',
            'quantidade'     => 20.0,
            'ocorrencias'    => 7,
            'aliquota_media' => 7.0,
            'valor_total'    => 3000.00,
            'tem_catalogo'   => true,
            'catalogo'       => ['descr_item' => 'Item Ambas as Fontes'],
        ],
    ]);

    $dados = [
        'itens'         => $itens,
        'resumoFiltros' => [
            ['rotulo' => 'Período', 'valor' => '01/2026 a 06/2026'],
            ['rotulo' => 'Fonte',   'valor' => 'EFD'],
        ],
        'totalValor' => 4700.00,
        'geradoEm'   => now(),
    ];

    $pdf = \App\Support\PdfReport::render('autenticado.bi.catalogo-itens-pdf', $dados, 'landscape');

    expect(substr($pdf->output(), 0, 4))->toBe('%PDF');
});

it('gera PDF do catalogo de itens sem itens (colecao vazia)', function () {
    $dados = [
        'itens'         => Collection::make([]),
        'resumoFiltros' => [],
        'totalValor'    => 0.0,
        'geradoEm'      => now(),
    ];

    $pdf = \App\Support\PdfReport::render('autenticado.bi.catalogo-itens-pdf', $dados, 'landscape');

    expect(substr($pdf->output(), 0, 4))->toBe('%PDF');
});
