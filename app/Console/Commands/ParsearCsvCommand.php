<?php

namespace App\Console\Commands;

use App\Services\CsvParserService;
use Illuminate\Console\Command;

class ParsearCsvCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'csv:parse 
                            {arquivo : Caminho para o arquivo CSV}
                            {--delimiter=; : Delimitador usado no CSV}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Parseia um arquivo CSV e exibe os dados';

    /**
     * Execute the console command.
     */
    public function handle(CsvParserService $csvParser)
    {
        $arquivo = $this->argument('arquivo');
        $delimiter = $this->option('delimiter');

        if (!file_exists($arquivo)) {
            $this->error("Arquivo não encontrado: {$arquivo}");
            return Command::FAILURE;
        }

        $this->info("Parseando arquivo CSV: {$arquivo}");

        $conteudo = file_get_contents($arquivo);
        $resultado = $csvParser->parse($conteudo, $delimiter);

        $this->info("Headers encontrados: " . count($resultado['headers']));
        $this->info("Linhas encontradas: " . count($resultado['rows']));

        // Exibe headers
        $this->newLine();
        $this->info("Headers:");
        $this->table(['#', 'Header'], collect($resultado['headers'])->map(function ($header, $index) {
            return [$index + 1, $header];
        })->toArray());

        // Exibe primeiras 10 linhas
        if (count($resultado['rows']) > 0) {
            $this->newLine();
            $this->info("Primeiras 10 linhas:");
            $linhas = array_slice($resultado['rows'], 0, 10);
            $this->table($resultado['headers'], $linhas);

            if (count($resultado['rows']) > 10) {
                $this->warn("... e mais " . (count($resultado['rows']) - 10) . " linha(s)");
            }
        }

        return Command::SUCCESS;
    }
}













