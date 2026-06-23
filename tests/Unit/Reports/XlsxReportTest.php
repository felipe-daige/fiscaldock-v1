<?php

use App\Support\Reports\XlsxReport;
use OpenSpout\Reader\XLSX\Reader;

uses(Tests\TestCase::class);

beforeEach(function () {
    if (! XlsxReport::disponivel()) {
        $this->markTestSkipped('OpenSpout não instalado (rebuild pendente).');
    }
});

it('gera xlsx multi-aba com header e celula colorida, lido de volta', function () {
    $path = storage_path('framework/testing/xlsx_'.uniqid().'.xlsx');

    XlsxReport::paraArquivo($path)
        ->addSheet('Resumo')
        ->tituloMarca('FiscalDock — Consulta Lote #1')
        ->header(['Indicador', 'Valor'])
        ->linha(['Total consultado', 'doze'])
        ->totais(['Total', 'fim'])
        ->addSheet('Resultados')
        ->header(['CNPJ', 'Classificacao'])
        ->linha(['00.000.000/0001-00', 'ALTO'], [1 => '#ea580c'])
        ->fechar();

    expect(is_file($path))->toBeTrue();

    $reader = new Reader();
    $reader->open($path);

    $sheets = [];
    foreach ($reader->getSheetIterator() as $sheet) {
        $linhas = [];
        foreach ($sheet->getRowIterator() as $row) {
            $linhas[] = $row->toArray();
        }
        $sheets[$sheet->getName()] = $linhas;
    }
    $reader->close();
    @unlink($path);

    // duas abas, nomeadas
    expect(array_keys($sheets))->toBe(['Resumo', 'Resultados']);

    // aba Resumo: título, header, linha, totais → 4 linhas
    expect($sheets['Resumo'])->toHaveCount(4);
    expect($sheets['Resumo'][0])->toBe(['FiscalDock — Consulta Lote #1']);
    expect($sheets['Resumo'][1])->toBe(['Indicador', 'Valor']);
    expect($sheets['Resumo'][2])->toBe(['Total consultado', 'doze']);

    // aba Resultados: header + 1 linha
    expect($sheets['Resultados'][0])->toBe(['CNPJ', 'Classificacao']);
    expect($sheets['Resultados'][1])->toBe(['00.000.000/0001-00', 'ALTO']);
});
