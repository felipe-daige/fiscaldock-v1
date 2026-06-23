<?php

use Illuminate\Support\Collection;

it('o PDF de clearance renderiza via PdfReport::render retornando bytes PDF validos', function () {
    $r = [
        'capa' => [
            'lote_id'       => 42,
            'emitido_em_label' => '23/06/2026 10:00',
            'escritorio' => [
                'razao_social' => 'Escritório Teste Ltda',
                'cnpj'         => '12.345.678/0001-99',
            ],
            'cliente_auditado' => [
                'razao_social' => 'Cliente Auditado SA',
            ],
            'periodo' => [
                'label' => '01/01/2026 a 31/01/2026',
            ],
        ],
        'resumo' => [
            'veredito' => [
                'severidade'    => 'ok',
                'mensagem'      => 'Nenhuma divergência crítica encontrada.',
                'total_criticas' => 0,
                'total_revisar'  => 0,
            ],
            'total_documentos'  => 5,
            'total_divergencias' => 0,
            'total_criticas'    => 0,
        ],
        'exposicao' => [
            'base'        => 0.0,
            'multa'       => 0.0,
            'total'       => 0.0,
            'base_label'  => 'R$ 0,00',
            'multa_label' => 'R$ 0,00',
            'total_label' => 'R$ 0,00',
        ],
        'concentracao'   => collect([]),
        'documentos'     => collect([]),
        'sem_divergencia' => collect([]),
        'metodologia' => [
            'tolerancia_absoluta'   => 1.0,
            'tolerancia_percentual' => '1%',
        ],
        'hash'      => str_repeat('a', 64),
        'breakdown' => [],
        'kpis'      => [],
    ];

    $pdf = \App\Support\PdfReport::render('autenticado.clearance.pdf.relatorio', ['r' => $r], 'portrait');

    expect(substr($pdf->output(), 0, 4))->toBe('%PDF');
});
