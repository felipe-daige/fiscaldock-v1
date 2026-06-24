<?php

it('header embute a logo base64', function () {
    $html = view('reports.partials._header')->render();
    expect($html)->toContain('data:image/png;base64,');
});

it('footer numera por CSS counter, identifica emissor e a marca', function () {
    $html = view('reports.partials._footer')->render();
    expect($html)
        ->toContain('counter(page)')      // numeração por página (repete em todas)
        ->toContain('Emitido por')        // identificador de quem emitiu
        ->toContain('FiscalDock');
});

it('layout inclui a marca dagua diagonal unica', function () {
    $html = view('reports.partials._marca-dagua')->render();
    expect($html)
        ->toContain('FISCALDOCK')
        ->toContain('rotate');            // diagonal via transform, nao ladrilhada
});

it('layout renderiza em PDF via PdfReport', function () {
    $pdf = \App\Support\PdfReport::render('reports.layout', [], 'portrait');
    expect(substr($pdf->output(), 0, 4))->toBe('%PDF');
});
