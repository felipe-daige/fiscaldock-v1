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
