<?php

it('header embute a logo base64', function () {
    $html = view('reports.partials._header')->render();
    expect($html)->toContain('data:image/png;base64,');
});

it('footer tem placeholder de pagina e a marca', function () {
    $html = view('reports.partials._footer')->render();
    expect($html)->toContain('{PAGE_NUM}')->toContain('FiscalDock');
});

it('layout renderiza em PDF via PdfReport', function () {
    $pdf = \App\Support\PdfReport::render('reports.layout', [], 'portrait');
    expect(substr($pdf->output(), 0, 4))->toBe('%PDF');
});
