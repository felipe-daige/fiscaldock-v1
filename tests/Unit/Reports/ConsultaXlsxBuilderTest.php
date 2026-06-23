<?php

use App\Services\Consultas\Export\ConsultaXlsxBuilder;
use App\Support\Reports\XlsxReport;
use Illuminate\Support\Collection;
use OpenSpout\Reader\XLSX\Reader;

uses(Tests\TestCase::class);

beforeEach(function () {
    if (! XlsxReport::disponivel()) {
        $this->markTestSkipped('OpenSpout não instalado (rebuild pendente).');
    }
});

it('gera xlsx de 3 abas a partir do payload do relatório', function () {
    $dados = [
        'lote' => (object) ['id' => 42],
        'resumo' => [
            'total' => 2, 'sucesso' => 1, 'erro' => 1, 'score_medio' => 73.5,
            'cnd_federal' => ['negativa' => 1, 'positiva' => 0],
        ],
        'detalhes' => new Collection([
            [
                'documento' => '00.000.000/0001-00', 'razao_social' => 'ACME LTDA',
                'status_consulta' => 'sucesso', 'error_message' => null, 'resumo' => 'ok',
                'blocos' => [[
                    'titulo' => 'CND Federal',
                    'badge' => ['label' => 'Regular', 'hex' => '#047857'],
                    'itens' => [['label' => 'Validade', 'valor' => '01/01/2027']],
                    'listas' => [], 'mensagem' => null, 'comprovante_url' => 'https://x/y.pdf',
                ]],
            ],
            [
                'documento' => '11.111.111/0001-11', 'razao_social' => 'FALHA SA',
                'status_consulta' => 'erro', 'error_message' => 'timeout', 'resumo' => null,
                'blocos' => [],
            ],
        ]),
    ];
    $colunas = ['CNPJ', 'Razao Social', 'Classificacao'];
    $linhas = [
        ['valores' => ['00.000.000/0001-00', 'ACME LTDA', 'Baixo Risco'], 'risco' => 'baixo'],
        ['valores' => ['11.111.111/0001-11', 'FALHA SA', 'Nao Avaliado'], 'risco' => null],
    ];

    $path = storage_path('framework/testing/cxb_'.uniqid().'.xlsx');
    app(ConsultaXlsxBuilder::class)->gerarArquivo($dados, $colunas, $linhas, $path);

    expect(is_file($path))->toBeTrue();

    $reader = new Reader();
    $reader->open($path);
    $sheets = [];
    foreach ($reader->getSheetIterator() as $sheet) {
        $rows = [];
        foreach ($sheet->getRowIterator() as $row) {
            $rows[] = $row->toArray();
        }
        $sheets[$sheet->getName()] = $rows;
    }
    $reader->close();
    @unlink($path);

    expect(array_keys($sheets))->toBe(['Resumo', 'Resultados', 'Detalhe por fonte']);
    // Resultados: header + 2 linhas
    expect($sheets['Resultados'][0])->toBe(['CNPJ', 'Razao Social', 'Classificacao']);
    expect($sheets['Resultados'])->toHaveCount(3);
    // Detalhe por fonte: header + 1 bloco (sucesso) + 1 linha de erro = 3
    expect($sheets['Detalhe por fonte'][0])->toBe(['CNPJ', 'Razão Social', 'Fonte', 'Situação', 'Detalhe', 'Comprovante']);
    expect($sheets['Detalhe por fonte'])->toHaveCount(3);
});
