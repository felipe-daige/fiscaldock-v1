<?php

it('marca dagua tem FISCALDOCK repetido', function () {
    $html = view('reports.partials._marca-dagua')->render();
    expect(substr_count($html, 'FISCALDOCK'))->toBeGreaterThan(3);
});

it('header embute a logo base64', function () {
    $html = view('reports.partials._header')->render();
    expect($html)->toContain('data:image/png;base64,');
});

it('footer tem placeholders de pagina e nota de uso interno', function () {
    $html = view('reports.partials._footer')->render();
    expect($html)->toContain('{PAGE_NUM}')->toContain('uso interno');
});

it('layout renderiza em PDF via PdfReport', function () {
    $pdf = \App\Support\PdfReport::render('reports.layout', [], 'portrait');
    expect(substr($pdf->output(), 0, 4))->toBe('%PDF');
});
