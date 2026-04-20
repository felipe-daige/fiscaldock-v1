<?php

namespace App\Services;

use App\Models\ConsultaLote;
use App\Models\ConsultaResultado;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;

class ConsultaReportService
{
    public function __construct(
        protected RiskScoreService $riskScoreService
    ) {}

    /**
     * Gera CSV a partir dos resultados do lote.
     */
    public function gerarCsv(ConsultaLote $lote): string
    {
        $resultados = $this->getResultadosFormatados($lote);

        if ($resultados->isEmpty()) {
            return '';
        }

        // Determinar colunas baseado nos dados disponíveis
        $colunas = $this->getColunasCsv($lote, $resultados);

        $output = fopen('php://temp', 'r+');

        // BOM para UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        // Header
        fputcsv($output, $colunas, ';');

        // Dados
        foreach ($resultados as $resultado) {
            $linha = $this->formatarLinhaCsv($resultado, $colunas);
            fputcsv($output, $linha, ';');
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }

    /**
     * Gera PDF a partir dos resultados do lote.
     */
    public function gerarPdf(ConsultaLote $lote): \Barryvdh\DomPDF\PDF
    {
        $resultados = $this->getResultadosFormatados($lote);
        $resumo = $this->calcularResumo($resultados);
        $plano = $lote->plano;

        $data = [
            'lote' => $lote,
            'plano' => $plano,
            'resultados' => $resultados,
            'resumo' => $resumo,
            'gerado_em' => now()->format('d/m/Y H:i'),
        ];

        $pdf = Pdf::loadView('reports.consulta-lote', $data);

        $pdf->setPaper('a4', 'landscape');
        $pdf->setOptions([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => false,
            'defaultFont' => 'sans-serif',
        ]);

        return $pdf;
    }

    /**
     * Retorna os resultados formatados para relatório.
     */
    public function getResultadosFormatados(ConsultaLote $lote): Collection
    {
        $resultados = $lote->resultados()
            ->with('participante')
            ->get();

        return $resultados->map(function (ConsultaResultado $resultado) {
            $participante = $resultado->participante;
            $dados = $resultado->resultado_dados ?? [];
            $scoreData = $resultado->calcularScore();

            return [
                'participante_id' => $participante->id,
                'documento' => $this->formatarCnpj($participante->documento),
                'razao_social' => $dados['razao_social'] ?? $participante->razao_social,
                'nome_fantasia' => $dados['nome_fantasia'] ?? $participante->nome_fantasia,
                'uf' => $dados['uf'] ?? $participante->uf,
                'status_consulta' => $resultado->status,
                'error_message' => $resultado->error_message,
                'consultado_em' => $resultado->consultado_em?->format('d/m/Y H:i'),

                // Dados básicos
                'situacao_cadastral' => $dados['situacao_cadastral'] ?? null,
                'simples_nacional' => $this->formatarBoolean($dados['simples_nacional'] ?? null),
                'mei' => $this->formatarBoolean($dados['mei'] ?? null),
                'regime_tributario' => $this->formatarCrt($dados['crt'] ?? $participante->crt),
                'cnae_principal' => $this->formatarCnaePrincipal($dados['cnaes'] ?? null),
                'cnaes' => $dados['cnaes'] ?? null,
                'qsa' => $dados['qsa'] ?? null,

                // SINTEGRA
                'sintegra_ie' => $dados['sintegra']['ie'] ?? null,
                'sintegra_situacao' => $dados['sintegra']['situacao'] ?? null,

                // CNDs
                'cnd_federal_status' => $dados['cnd_federal']['status'] ?? null,
                'cnd_federal_validade' => $dados['cnd_federal']['validade'] ?? null,
                'cnd_estadual_status' => $dados['cnd_estadual']['status'] ?? null,
                'cnd_estadual_validade' => $dados['cnd_estadual']['validade'] ?? null,

                // FGTS/Trabalhista
                'crf_fgts_status' => $dados['crf_fgts']['status'] ?? null,
                'cndt_status' => $dados['cndt']['status'] ?? null,
                'cndt_validade' => $dados['cndt']['validade'] ?? null,

                // Compliance
                'tcu_situacao' => $dados['tcu_consolidada']['situacao'] ?? ($dados['tcu'] ?? null),
                'ceis' => $this->formatarBoolean($dados['ceis'] ?? null),
                'cnep' => $this->formatarBoolean($dados['cnep'] ?? null),

                // ESG
                'trabalho_escravo' => $this->formatarBoolean($dados['trabalho_escravo'] ?? null),
                'ibama_autuacoes' => isset($dados['ibama_autuacoes']) ? count($dados['ibama_autuacoes']) : null,

                // Protestos
                'protestos_qtd' => isset($dados['protestos']) ? (is_array($dados['protestos']) ? count($dados['protestos']) : $dados['protestos']) : null,
                'protestos_valor' => $dados['valor_protestos'] ?? null,

                // Score calculado
                'score_total' => $scoreData['score_total'],
                'classificacao' => $scoreData['classificacao'],
                'scores_detalhados' => $scoreData['scores'],

                // Dados brutos para referência
                'dados_completos' => $dados,
            ];
        });
    }

    /**
     * Calcula resumo estatístico dos resultados.
     */
    public function calcularResumo(Collection $resultados): array
    {
        $total = $resultados->count();
        $sucesso = $resultados->where('status_consulta', 'sucesso')->count();
        $erro = $resultados->whereIn('status_consulta', ['erro', 'timeout'])->count();

        // Contagem por classificação de risco
        $porClassificacao = [
            'baixo' => 0,
            'medio' => 0,
            'alto' => 0,
            'critico' => 0,
        ];

        foreach ($resultados->where('status_consulta', 'sucesso') as $r) {
            $classificacao = $r['classificacao'] ?? 'medio';
            if (isset($porClassificacao[$classificacao])) {
                $porClassificacao[$classificacao]++;
            }
        }

        // Contagem por situação cadastral
        $porSituacao = $resultados
            ->where('status_consulta', 'sucesso')
            ->groupBy('situacao_cadastral')
            ->map->count()
            ->toArray();

        // Contagem CNDs
        $cndFederalNegativa = $resultados
            ->where('status_consulta', 'sucesso')
            ->whereIn('cnd_federal_status', ['NEGATIVA', 'REGULAR'])
            ->count();

        $cndFederalPositiva = $resultados
            ->where('status_consulta', 'sucesso')
            ->whereIn('cnd_federal_status', ['POSITIVA', 'IRREGULAR'])
            ->count();

        // Score médio
        $scoresMedio = $resultados
            ->where('status_consulta', 'sucesso')
            ->avg('score_total') ?? 0;

        return [
            'total' => $total,
            'sucesso' => $sucesso,
            'erro' => $erro,
            'por_classificacao' => $porClassificacao,
            'por_situacao' => $porSituacao,
            'cnd_federal' => [
                'negativa' => $cndFederalNegativa,
                'positiva' => $cndFederalPositiva,
            ],
            'score_medio' => round($scoresMedio, 1),
        ];
    }

    /**
     * Define colunas do CSV baseado no plano.
     */
    private function getColunasCsv(ConsultaLote $lote, Collection $resultados): array
    {
        $colunas = [
            'CNPJ',
            'Razao Social',
            'Nome Fantasia',
            'UF',
            'Status da Consulta',
            'Consultado em',
            'Situacao Cadastral',
            'Regime Tributario',
            'Simples Nacional',
            'MEI',
            'CNAE Principal',
        ];

        $plano = $lote->plano;
        $consultasIncluidas = $plano?->consultas_incluidas ?? [];

        // Adicionar colunas baseado nas consultas incluídas
        if (in_array('sintegra', $consultasIncluidas)) {
            $colunas[] = 'SINTEGRA IE';
            $colunas[] = 'SINTEGRA Situacao';
        }

        if (in_array('cnd_federal', $consultasIncluidas)) {
            $colunas[] = 'CND Federal Status';
            $colunas[] = 'CND Federal Validade';
        }

        if (in_array('cnd_estadual', $consultasIncluidas)) {
            $colunas[] = 'CND Estadual Status';
            $colunas[] = 'CND Estadual Validade';
        }

        if (in_array('crf_fgts', $consultasIncluidas)) {
            $colunas[] = 'CRF FGTS Status';
        }

        if (in_array('cndt', $consultasIncluidas)) {
            $colunas[] = 'CNDT Status';
            $colunas[] = 'CNDT Validade';
        }

        if (in_array('tcu_consolidada', $consultasIncluidas)) {
            $colunas[] = 'TCU Situacao';
            $colunas[] = 'CEIS';
            $colunas[] = 'CNEP';
        }

        if (in_array('trabalho_escravo', $consultasIncluidas)) {
            $colunas[] = 'Lista Trabalho Escravo';
        }

        if (in_array('ibama_autuacoes', $consultasIncluidas)) {
            $colunas[] = 'IBAMA Autuacoes';
        }

        if (in_array('protestos', $consultasIncluidas)) {
            $colunas[] = 'Protestos Qtd';
            $colunas[] = 'Protestos Valor';
        }

        // Sempre incluir score
        $colunas[] = 'Score Risco';
        $colunas[] = 'Classificacao';

        return $colunas;
    }

    /**
     * Formata linha para CSV.
     */
    private function formatarLinhaCsv(array $resultado, array $colunas): array
    {
        $linha = [];

        foreach ($colunas as $coluna) {
            $linha[] = match ($coluna) {
                'CNPJ' => $resultado['documento'],
                'Razao Social' => $resultado['razao_social'],
                'Nome Fantasia' => $resultado['nome_fantasia'],
                'UF' => $resultado['uf'],
                'Status da Consulta' => $this->formatarStatusConsulta($resultado['status_consulta']),
                'Consultado em' => $resultado['consultado_em'],
                'Situacao Cadastral' => $resultado['situacao_cadastral'],
                'Regime Tributario' => $resultado['regime_tributario'],
                'Simples Nacional' => $resultado['simples_nacional'],
                'MEI' => $resultado['mei'],
                'CNAE Principal' => $resultado['cnae_principal'],
                'SINTEGRA IE' => $resultado['sintegra_ie'],
                'SINTEGRA Situacao' => $resultado['sintegra_situacao'],
                'CND Federal Status' => $resultado['cnd_federal_status'],
                'CND Federal Validade' => $resultado['cnd_federal_validade'],
                'CND Estadual Status' => $resultado['cnd_estadual_status'],
                'CND Estadual Validade' => $resultado['cnd_estadual_validade'],
                'CRF FGTS Status' => $resultado['crf_fgts_status'],
                'CNDT Status' => $resultado['cndt_status'],
                'CNDT Validade' => $resultado['cndt_validade'],
                'TCU Situacao' => $resultado['tcu_situacao'],
                'CEIS' => $resultado['ceis'],
                'CNEP' => $resultado['cnep'],
                'Lista Trabalho Escravo' => $resultado['trabalho_escravo'],
                'IBAMA Autuacoes' => $resultado['ibama_autuacoes'],
                'Protestos Qtd' => $resultado['protestos_qtd'],
                'Protestos Valor' => $resultado['protestos_valor'] !== null ? number_format($resultado['protestos_valor'], 2, ',', '.') : '',
                'Score Risco' => $resultado['score_total'],
                'Classificacao' => $this->getLabelClassificacao($resultado['classificacao']),
                default => '',
            };
        }

        return $linha;
    }

    private function formatarStatusConsulta(?string $status): string
    {
        return match ($status) {
            'sucesso' => 'Sucesso',
            'erro' => 'Erro',
            'timeout' => 'Timeout',
            'pendente' => 'Pendente',
            default => (string) $status,
        };
    }

    /**
     * Formata CNPJ com máscara.
     */
    private function formatarCnpj(?string $documento): string
    {
        if (empty($documento)) {
            return '';
        }

        $documento = preg_replace('/[^0-9]/', '', $documento);

        if (strlen($documento) !== 14) {
            return $documento;
        }

        return sprintf(
            '%s.%s.%s/%s-%s',
            substr($documento, 0, 2),
            substr($documento, 2, 3),
            substr($documento, 5, 3),
            substr($documento, 8, 4),
            substr($documento, 12, 2)
        );
    }

    /**
     * Formata boolean para string.
     */
    private function formatarBoolean(?bool $value): string
    {
        if ($value === null) {
            return '';
        }

        return $value ? 'Sim' : 'Nao';
    }

    /**
     * Retorna label para classificação.
     */
    private function getLabelClassificacao(string $classificacao): string
    {
        return match ($classificacao) {
            'baixo' => 'Baixo Risco',
            'medio' => 'Medio Risco',
            'alto' => 'Alto Risco',
            'critico' => 'Risco Critico',
            default => 'Nao Avaliado',
        };
    }

    /**
     * Formata CRT para nome legível.
     */
    private function formatarCrt(?int $crt): string
    {
        return match ($crt) {
            1 => 'Simples Nacional',
            2 => 'Simples Excesso',
            3 => 'Lucro Presumido/Real',
            default => '',
        };
    }

    /**
     * Extrai e formata CNAE principal.
     */
    private function formatarCnaePrincipal(?array $cnaes): string
    {
        if (empty($cnaes)) {
            return '';
        }

        // Pode vir como {'principal': {...}} ou como array direto
        $principal = $cnaes['principal'] ?? ($cnaes[0] ?? null);

        if (empty($principal)) {
            return '';
        }

        // Pode ser string ou objeto com código/descrição
        if (is_string($principal)) {
            return $principal;
        }

        $codigo = $principal['codigo'] ?? $principal['code'] ?? '';
        $descricao = $principal['descricao'] ?? $principal['description'] ?? '';

        if ($codigo && $descricao) {
            return "{$codigo} - {$descricao}";
        }

        return $codigo ?: $descricao;
    }
}
