<?php

namespace App\Services;

use App\Models\ConsultaLote;
use App\Models\ConsultaResultado;
use App\Services\Consultas\ClienteFiscalResumoService;
use App\Services\Consultas\ParticipanteFiscalResumoService;
use App\Services\Consultas\ResultadoDetalhePresenter;
use App\Services\Reforma\CreditoReformaCardService;
use App\Support\CsvExport;
use Illuminate\Support\Collection;

class ConsultaReportService
{
    public function __construct(
        protected RiskScoreService $riskScoreService,
        protected ResultadoDetalhePresenter $detalhePresenter
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
        $colunas = $this->getColunasRelatorio($lote, $resultados);

        $linhas = $resultados->map(fn ($r) => $this->formatarLinhaRelatorio($r, $colunas));

        return CsvExport::build($colunas, $linhas);
    }

    /**
     * Monta o payload completo do relatório (resumo + tabela + detalhamento por CNPJ).
     * Exposto para que view e testes consumam exatamente o mesmo conjunto de dados.
     */
    public function dadosRelatorio(ConsultaLote $lote): array
    {
        $resultados = $this->getResultadosFormatados($lote);
        $detalhes = $this->getDetalhes($lote);

        return [
            'lote' => $lote,
            'plano' => $lote->plano,
            'resultados' => $resultados,
            'resumo' => $this->calcularResumo($resultados),
            'detalhes' => $detalhes,
            'analise' => $this->detalhePresenter->analiseLote(
                $detalhes->map(fn ($d) => ['detalhe_blocos' => $d['blocos']])->all()
            ),
            'gerado_em' => now()->format('d/m/Y H:i'),
            'emitente' => $this->emitenteDoLote($lote),
        ];
    }

    /**
     * Gera PDF a partir dos resultados do lote.
     */
    public function gerarPdf(ConsultaLote $lote): \Barryvdh\DomPDF\PDF
    {
        return \App\Support\PdfReport::render('reports.consulta-lote', $this->dadosRelatorio($lote), 'portrait');
    }

    public function xlsxDisponivel(): bool
    {
        return \App\Support\Reports\XlsxReport::disponivel();
    }

    public function gerarXlsx(ConsultaLote $lote): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $dados = $this->dadosRelatorio($lote);
        $colunas = $this->getColunasRelatorio($lote, $dados['resultados']);

        $linhas = $dados['resultados']->map(fn ($r) => [
            'valores' => $this->formatarLinhaRelatorio($r, $colunas),
            'risco' => $r['classificacao'] ?? null,
        ])->all();

        return app(\App\Services\Consultas\Export\ConsultaXlsxBuilder::class)
            ->download($dados, $colunas, $linhas, "consulta_lote_{$lote->id}.xlsx");
    }

    /**
     * Detalhamento completo por CNPJ — TODOS os dados, descrições e links de comprovante
     * (PDF das certidões emitidas) de cada fonte consultada. Reusa o mesmo presenter da
     * tela web (ResultadoDetalhePresenter) para garantir paridade visual/dados.
     */
    public function getDetalhes(ConsultaLote $lote): Collection
    {
        // Mesmo critério da tela web: certidões pedidas pelo plano mas sem retorno viram
        // card "Falhou" no detalhamento (em vez de sumir).
        $esperadasCert = array_values(array_intersect(
            $lote->plano?->consultas_incluidas ?? [],
            ['cnd_federal', 'cnd_estadual', 'cnd_municipal', 'crf_fgts', 'cndt', 'sintegra'],
        ));

        $resultados = $lote->resultados()
            ->with(['participante', 'participante.score', 'cliente'])
            ->get();

        $participanteIds = $resultados->pluck('participante_id')->filter()->unique()->values()->all();
        $clienteIds = $resultados->pluck('cliente_id')->filter()->unique()->values()->all();

        $fiscalResumos = app(ParticipanteFiscalResumoService::class)
            ->paraParticipantes($lote->user_id, $participanteIds, comCfops: true, comProdutos: true, comNotas: true);
        $clienteResumos = app(ClienteFiscalResumoService::class)
            ->paraClientes($lote->user_id, $clienteIds);
        $creditoService = app(CreditoReformaCardService::class);

        return $resultados->map(function (ConsultaResultado $resultado) use ($esperadasCert, $fiscalResumos, $clienteResumos, $creditoService, $lote) {
            $alvo = $resultado->participante ?? $resultado->cliente;
            $dados = $resultado->resultado_dados ?? [];

            $fiscalResumo = $resultado->participante_id
                ? ($fiscalResumos[$resultado->participante_id] ?? null)
                : ($resultado->cliente_id ? ($clienteResumos[$resultado->cliente_id] ?? null) : null);

            if ($fiscalResumo !== null && $resultado->participante_id && $resultado->participante) {
                $fiscalResumo['credito_reforma'] = $creditoService
                    ->montar($lote->user_id, $resultado->participante, $fiscalResumo, $dados);
            }

            $situacao = trim((string) $resultado->getDado('situacao_cadastral'));

            return [
                'participante_id' => $resultado->participante_id,
                'cliente_id' => $resultado->cliente_id,
                'documento' => $this->formatarCnpj($alvo?->documento),
                'razao_social' => $dados['razao_social'] ?? $alvo?->razao_social,
                'nome_fantasia' => $dados['nome_fantasia'] ?? $alvo?->nome_fantasia,
                'uf' => $alvo?->uf ?: ($dados['endereco']['uf'] ?? null),
                'situacao_cadastral' => $situacao !== '' ? $situacao : '—',
                'regime_tributario' => $resultado->getRegimeTributarioLabel() ?: '—',
                'status' => $resultado->status,
                'status_consulta' => $resultado->status,
                'error_message' => $resultado->publicErrorMessage(),
                'consultado_em' => $resultado->consultado_em?->format('d/m/Y H:i'),
                'resumo' => $resultado->status === ConsultaResultado::STATUS_SUCESSO
                    ? $this->detalhePresenter->resumoTextual($resultado)
                    : null,
                'blocos' => $resultado->status === ConsultaResultado::STATUS_SUCESSO
                    ? $this->detalhePresenter->blocos($resultado, $esperadasCert)
                    : [],
                'fiscal_resumo' => $fiscalResumo,
            ];
        });
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
            // Resultado pode ser de escopo participante OU cliente (participante nulo).
            // Sem este fallback, o acesso a ->documento/->uf de um participante nulo
            // emitia warnings que vazavam pro stream e corrompiam o CSV/PDF baixado.
            $alvo = $resultado->participante ?? $resultado->cliente;
            $dados = $resultado->resultado_dados ?? [];
            $scoreData = $resultado->calcularScore();

            return [
                'participante_id' => $alvo?->id,
                'documento' => $this->formatarCnpj($alvo?->documento),
                'razao_social' => $dados['razao_social'] ?? $alvo?->razao_social,
                'nome_fantasia' => $dados['nome_fantasia'] ?? $alvo?->nome_fantasia,
                'uf' => $dados['uf'] ?? $alvo?->uf,
                'status_consulta' => $resultado->status,
                'error_message' => $resultado->publicErrorMessage(),
                'consultado_em' => $resultado->consultado_em?->format('d/m/Y H:i'),

                // Dados básicos
                'situacao_cadastral' => $dados['situacao_cadastral'] ?? null,
                'simples_nacional' => $this->formatarBoolean($dados['simples_nacional'] ?? null),
                'mei' => $this->formatarBoolean($dados['mei'] ?? null),
                'regime_tributario' => $resultado->getRegimeTributarioLabel() ?? '',
                'cnae_principal' => $this->formatarCnaePrincipal($dados['cnaes'] ?? null),
                'cnaes' => $dados['cnaes'] ?? null,
                'qsa' => $dados['qsa'] ?? null,

                // SINTEGRA (chave normalizada = inscricao_estadual, não "ie")
                'sintegra_ie' => $dados['sintegra']['inscricao_estadual'] ?? null,
                'sintegra_situacao' => $dados['sintegra']['situacao'] ?? null,

                // CNDs (chave normalizada de validade = data_validade, não "validade")
                'cnd_federal_status' => $dados['cnd_federal']['status'] ?? null,
                'cnd_federal_validade' => $dados['cnd_federal']['data_validade'] ?? null,
                'cnd_estadual_status' => $dados['cnd_estadual']['status'] ?? null,
                'cnd_estadual_validade' => $dados['cnd_estadual']['data_validade'] ?? null,

                // FGTS/Trabalhista
                'crf_fgts_status' => $dados['crf_fgts']['status'] ?? null,
                'cndt_status' => $dados['cndt']['status'] ?? null,
                'cndt_validade' => $dados['cndt']['data_validade'] ?? null,

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

    /** Emitente do relatório: razão social da empresa própria (CNPJ 14 díg) ou nome do usuário. */
    private function emitenteDoLote(ConsultaLote $lote): string
    {
        $empresa = \App\Models\Cliente::where('user_id', $lote->user_id)
            ->empresaPropria()
            ->whereRaw("length(regexp_replace(coalesce(documento, ''), '[^0-9]', '', 'g')) = 14")
            ->value('razao_social');

        return $empresa ?: ($lote->user?->name ?? 'FiscalDock');
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
     * Define colunas do relatório baseado no plano.
     */
    public function getColunasRelatorio(ConsultaLote $lote, Collection $resultados): array
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
     * Formata linha para relatório.
     */
    public function formatarLinhaRelatorio(array $resultado, array $colunas): array
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
