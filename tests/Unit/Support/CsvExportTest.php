<?php

use App\Support\CsvExport;
use Symfony\Component\HttpFoundation\StreamedResponse;

it('gera csv com BOM UTF-8 e delimitador ponto-e-virgula', function () {
    $csv = CsvExport::build(['Mês', 'Valor'], [['jan', '1.000,00'], ['fev', '2.000,00']]);

    // BOM UTF-8 no início
    expect(substr($csv, 0, 3))->toBe(chr(0xEF).chr(0xBB).chr(0xBF));
    // delimitador ";" e linhas (sem ";" no valor → fputcsv não envolve em aspas)
    expect($csv)->toContain("Mês;Valor");
    expect($csv)->toContain("jan;1.000,00");
    expect($csv)->toContain("fev;2.000,00");
});

it('gera csv so com cabecalho quando nao ha linhas', function () {
    $csv = CsvExport::build(['A', 'B'], []);

    expect(substr($csv, 0, 3))->toBe(chr(0xEF).chr(0xBB).chr(0xBF));
    expect($csv)->toContain('A;B');
});

it('aceita linhas como iterable (generator)', function () {
    $gen = (function () {
        yield ['x', '1'];
        yield ['y', '2'];
    })();

    $csv = CsvExport::build(['Col1', 'Col2'], $gen);

    expect($csv)->toContain('x;1');
    expect($csv)->toContain('y;2');
});

it('download devolve StreamedResponse com headers padronizados', function () {
    $resp = CsvExport::download('relatorio.csv', ['A'], [['1']]);

    expect($resp)->toBeInstanceOf(StreamedResponse::class);
    expect($resp->headers->get('Content-Type'))->toBe('text/csv; charset=UTF-8');
    expect($resp->headers->get('Content-Disposition'))->toContain('relatorio.csv');
    expect($resp->headers->get('Content-Disposition'))->toContain('attachment');
});

it('download emite o mesmo conteudo que build', function () {
    $colunas = ['Nome', 'Doc'];
    $linhas = [['Acme', '123'], ['Beta', '456']];

    $esperado = CsvExport::build($colunas, $linhas);
    $resp = CsvExport::download('x.csv', $colunas, $linhas);

    ob_start();
    $resp->sendContent();
    $saida = ob_get_clean();

    expect($saida)->toBe($esperado);
});
