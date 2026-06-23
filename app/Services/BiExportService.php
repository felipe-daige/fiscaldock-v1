<?php

namespace App\Services;

use App\Support\CsvExport;

class BiExportService
{
    public function __construct(protected BiService $bi) {}

    private function brl(float $v): string
    {
        return number_format($v, 2, ',', '.');
    }

    public function toCsv(array $colunas, array $linhas): string
    {
        return CsvExport::build($colunas, $linhas);
    }

    /**
     * Dataset tabular da aba (1 tabela canônica por aba). Reusa os getters do BI.
     */
    public function dataset(string $aba, int $userId, ?string $ini, ?string $fim, ?int $cli): array
    {
        return match ($aba) {
            'faturamento' => $this->fromMensal($this->bi->getFaturamentoPorPeriodo($userId, $ini, $fim, $cli),
                ['Mês', 'Faturamento', 'Qtd Notas'],
                fn ($r) => [$r['mes_formatado'], $this->brl($r['faturamento']), $r['qtd_notas']]),

            'tributos' => $this->fromMensal($this->bi->getCargaTributaria($userId, $ini, $fim, $cli),
                ['Mês', 'Faturamento', 'ICMS', 'PIS', 'COFINS', 'Total Tributos', 'Alíq. Efetiva %'],
                fn ($r) => [$r['mes_formatado'], $this->brl($r['faturamento']), $this->brl($r['icms']), $this->brl($r['pis']), $this->brl($r['cofins']), $this->brl($r['tributos_total']), $r['aliquota_efetiva']]),

            'apuracao-notas' => $this->datasetApuracao($userId, $ini, $fim, $cli),

            'cfop' => $this->datasetCfop($userId, $ini, $fim, $cli),

            default => ['colunas' => [], 'linhas' => []],
        };
    }

    private function fromMensal(array $rows, array $colunas, callable $map): array
    {
        return ['colunas' => $colunas, 'linhas' => array_map($map, $rows)];
    }

    private function datasetApuracao(int $userId, ?string $ini, ?string $fim, ?int $cli): array
    {
        $data = $this->bi->getApuracaoVsNotas($userId, $ini, $fim, $cli);
        $colunas = ['Mês', 'ICMS Declarado', 'ICMS Computado', 'PIS Declarado', 'PIS Computado', 'COFINS Declarado', 'COFINS Computado'];
        $linhas = array_map(fn ($m) => [
            $m['label'],
            $this->brl($m['icms']['declarado']), $this->brl($m['icms']['computado']),
            $this->brl($m['pis']['declarado']), $this->brl($m['pis']['computado']),
            $this->brl($m['cofins']['declarado']), $this->brl($m['cofins']['computado']),
        ], $data['mensal']);

        return ['colunas' => $colunas, 'linhas' => $linhas];
    }

    private function datasetCfop(int $userId, ?string $ini, ?string $fim, ?int $cli): array
    {
        $ranking = $this->bi->getCfopAnalitico($userId, $ini, $fim, $cli)['ranking'];
        $colunas = ['CFOP / Natureza', 'Tipo', 'Valor', 'Qtd Notas', 'Tributos', '% Total'];
        $linhas = array_map(fn ($r) => [
            $r['descricao'], $r['tipo'], $this->brl($r['valor']), $r['qtd'], $this->brl($r['tributos']), $r['percentual'],
        ], $ranking);

        return ['colunas' => $colunas, 'linhas' => $linhas];
    }
}
