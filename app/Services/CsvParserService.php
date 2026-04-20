<?php

namespace App\Services;

class CsvParserService
{
    /**
     * Converte string CSV (separador ;) em headers e rows.
     *
     * @param string $csv Conteúdo CSV como string
     * @param string $delimiter Separador de colunas (padrão: ;)
     * @return array{headers: array, rows: array}
     */
    public function parse(string $csv, string $delimiter = ';'): array
    {
        $lines = preg_split("/\\r\\n|\\r|\\n/", trim($csv));
        $rows = [];
        $headers = [];

        foreach ($lines as $index => $line) {
            if ($line === '') {
                continue;
            }
            $columns = str_getcsv($line, $delimiter);

            if ($index === 0) {
                $headers = $columns;
                continue;
            }

            $rows[] = $columns;
        }

        return [
            'headers' => $headers,
            'rows' => $rows,
        ];
    }
}


