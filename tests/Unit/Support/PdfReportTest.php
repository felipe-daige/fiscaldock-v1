<?php

use App\Support\PdfReport;

it('logoDataUri devolve data-uri png base64', function () {
    expect(PdfReport::logoDataUri())->toStartWith('data:image/png;base64,');
});

it('render devolve PDF A4 e gera bytes %PDF', function () {
    $pdf = PdfReport::render('reports.layout', [], 'landscape');
    expect($pdf)->toBeInstanceOf(\Barryvdh\DomPDF\PDF::class);
    expect(substr($pdf->output(), 0, 4))->toBe('%PDF');
});

it('hashDocumento e deterministico e curto por identificadores', function () {
    $a = PdfReport::hashDocumento('lote', 13, 1700000000);
    $b = PdfReport::hashDocumento('lote', 13, 1700000000);
    $c = PdfReport::hashDocumento('lote', 14, 1700000000);
    expect($a)->toBe($b)                 // mesmo doc => mesmo hash
        ->not->toBe($c)                  // doc diferente => hash diferente
        ->toMatch('/^[0-9A-F]{12}$/');   // 12 hex maiusculo
});

it('emissor e o dominio da marca', function () {
    expect(PdfReport::emissor())->toBe('fiscaldock.com.br');
});
