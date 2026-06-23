<?php

namespace App\Support;

use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Gerador canônico de CSV do sistema. Padrão único:
 * BOM UTF-8 + delimitador ";" → abre formatado no Excel/Sheets (pt-BR).
 *
 * Todo export tabular do sistema passa por aqui — não duplicar a lógica
 * de fputcsv/BOM em controllers ou services.
 */
final class CsvExport
{
    /** Delimitador padrão (Excel pt-BR lê ";" como separador de coluna). */
    public const DELIMITADOR = ';';

    /** BOM UTF-8 — faz o Excel reconhecer acentuação. */
    private const BOM = "\xEF\xBB\xBF";

    /**
     * Monta a string CSV completa (com BOM e cabeçalho).
     *
     * @param  array<int,string|int|float|null>  $colunas
     * @param  iterable<array<int,string|int|float|null>>  $linhas
     */
    public static function build(array $colunas, iterable $linhas): string
    {
        $h = fopen('php://temp', 'r+');
        fwrite($h, self::BOM);
        fputcsv($h, $colunas, self::DELIMITADOR);
        foreach ($linhas as $linha) {
            fputcsv($h, $linha, self::DELIMITADOR);
        }
        rewind($h);
        $csv = stream_get_contents($h);
        fclose($h);

        return $csv;
    }

    /**
     * Resposta de download streamado com headers padronizados.
     *
     * @param  array<int,string|int|float|null>  $colunas
     * @param  iterable<array<int,string|int|float|null>>  $linhas
     */
    public static function download(string $filename, array $colunas, iterable $linhas): StreamedResponse
    {
        return self::stream($filename, self::build($colunas, $linhas));
    }

    /**
     * Resposta de download a partir de um CSV já montado (ex.: quando o
     * service tem lógica própria de colunas dinâmicas).
     */
    public static function stream(string $filename, string $csv): StreamedResponse
    {
        return new StreamedResponse(function () use ($csv) {
            echo $csv;
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.addslashes($filename).'"',
        ]);
    }
}
