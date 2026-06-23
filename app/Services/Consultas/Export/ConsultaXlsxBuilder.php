<?php

namespace App\Services\Consultas\Export;

use App\Support\Reports\ReportTheme;
use App\Support\Reports\XlsxReport;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Monta o XLSX multi-aba da consulta CNPJ (Resumo · Resultados · Detalhe por fonte)
 * a partir do payload dadosRelatorio(). Recebe as linhas de Resultados já
 * formatadas pelo service (evita ciclo de dependência service<->builder).
 */
final class ConsultaXlsxBuilder
{
    /**
     * @param  array  $dados  saída de ConsultaReportService::dadosRelatorio()
     * @param  array<int,string>  $colunas  cabeçalho dinâmico da aba Resultados
     * @param  array<int,array{valores: array<int,mixed>, risco: ?string}>  $linhas
     */
    public function gerarArquivo(array $dados, array $colunas, array $linhas, string $path): void
    {
        $resumo = $dados['resumo'];
        /** @var Collection|iterable $detalhes */
        $detalhes = $dados['detalhes'];
        $loteId = $dados['lote']->id ?? '';

        $xlsx = XlsxReport::paraArquivo($path);

        // ── Aba 1: Resumo ────────────────────────────────
        $xlsx->addSheet('Resumo')
            ->tituloMarca(ReportTheme::brandName().' — Consulta Lote #'.$loteId)
            ->header(['Indicador', 'Valor'])
            ->linha(['Total consultado', (string) ($resumo['total'] ?? 0)])
            ->linha(['Sucesso', (string) ($resumo['sucesso'] ?? 0)])
            ->linha(['Erros', (string) ($resumo['erro'] ?? 0)])
            ->linha(['Score médio', (string) ($resumo['score_medio'] ?? 0)])
            ->linha(['CND Federal OK', (string) ($resumo['cnd_federal']['negativa'] ?? 0)])
            ->linha(['CND Federal restrita', (string) ($resumo['cnd_federal']['positiva'] ?? 0)]);

        // ── Aba 2: Resultados ────────────────────────────
        $xlsx->addSheet('Resultados')->header($colunas);
        $idxClassificacao = array_search('Classificacao', $colunas, true);
        foreach ($linhas as $linha) {
            $cores = [];
            if ($idxClassificacao !== false) {
                $cores[$idxClassificacao] = ReportTheme::riscoHex($linha['risco'] ?? null);
            }
            $xlsx->linha($linha['valores'], $cores);
        }

        // ── Aba 3: Detalhe por fonte ─────────────────────
        $xlsx->addSheet('Detalhe por fonte')
            ->header(['CNPJ', 'Razão Social', 'Fonte', 'Situação', 'Detalhe', 'Comprovante']);
        foreach ($detalhes as $d) {
            if (($d['status_consulta'] ?? null) !== 'sucesso') {
                $xlsx->linha([
                    $d['documento'] ?? '', $d['razao_social'] ?? '', 'Consulta',
                    mb_strtoupper((string) ($d['status_consulta'] ?? '')),
                    $d['error_message'] ?? '', '',
                ]);

                continue;
            }
            foreach (($d['blocos'] ?? []) as $bloco) {
                $situacao = $bloco['badge']['label'] ?? '';
                $detalheTxt = collect($bloco['itens'] ?? [])
                    ->map(fn ($i) => ($i['label'] ?? '').': '.($i['valor'] ?? ''))
                    ->implode(' · ');
                $cor = $bloco['badge']['hex'] ?? null;
                $xlsx->linha(
                    [
                        $d['documento'] ?? '', $d['razao_social'] ?? '',
                        $bloco['titulo'] ?? '', $situacao, $detalheTxt,
                        $bloco['comprovante_url'] ?? '',
                    ],
                    $cor ? [3 => $cor] : []
                );
            }
        }

        $xlsx->fechar();
    }

    /**
     * Mesmo conteúdo, embrulhado como download HTTP. Grava num arquivo temporário
     * e deixa o framework removê-lo após o envio.
     */
    public function download(array $dados, array $colunas, array $linhas, string $filename): BinaryFileResponse
    {
        $tmp = tempnam(sys_get_temp_dir(), 'xlsxrep');
        $this->gerarArquivo($dados, $colunas, $linhas, $tmp);

        return response()->download($tmp, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }
}
