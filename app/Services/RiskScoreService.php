<?php

namespace App\Services;

use App\Models\Participante;
use App\Models\ParticipanteScore;
use Illuminate\Support\Facades\DB;

class RiskScoreService
{
    /**
     * Pesos para cada categoria de score.
     * Soma total = 1.0
     */
    private array $pesos = [
        'cadastral' => 0.15,
        'cnd_federal' => 0.20,
        'cnd_estadual' => 0.15,
        'fgts' => 0.10,
        'trabalhista' => 0.10,
        'compliance' => 0.15,
        'esg' => 0.10,
        'protestos' => 0.05,
    ];

    /**
     * Calcula o score de um participante baseado nos dados consultados.
     *
     * @param  array  $dados  Dados retornados das consultas (InfoSimples, etc)
     */
    public function calcularScores(array $dados): array
    {
        $scores = [];

        // Score Cadastral (situacao_cadastral)
        $scores['cadastral'] = $this->calcularScoreCadastral($dados);

        // Score CND Federal
        $scores['cnd_federal'] = $this->calcularScoreCndFederal($dados);

        // Score CND Estadual
        $scores['cnd_estadual'] = $this->calcularScoreCndEstadual($dados);

        // Score FGTS/CRF
        $scores['fgts'] = $this->calcularScoreFgts($dados);

        // Score Trabalhista (CNDT)
        $scores['trabalhista'] = $this->calcularScoreTrabalhista($dados);

        // Score Compliance (CEIS/CNEP/TCU)
        $scores['compliance'] = $this->calcularScoreCompliance($dados);

        // Score ESG (trabalho escravo, IBAMA)
        $scores['esg'] = $this->calcularScoreEsg($dados);

        // Score Protestos
        $scores['protestos'] = $this->calcularScoreProtestos($dados);

        return $scores;
    }

    /**
     * Calcula o score total ponderado.
     */
    public function calcularScoreTotal(array $scores): int
    {
        $total = 0;

        foreach ($this->pesos as $key => $peso) {
            $total += ($scores[$key] ?? 50) * $peso;
        }

        return (int) round($total);
    }

    /**
     * Classifica o risco baseado no score total.
     */
    public function classificar(int $scoreTotal): string
    {
        return match (true) {
            $scoreTotal <= 20 => 'baixo',
            $scoreTotal <= 50 => 'medio',
            $scoreTotal <= 80 => 'alto',
            default => 'critico',
        };
    }

    /**
     * Retorna a cor CSS para a classificacao.
     */
    public function getCorClassificacao(string $classificacao): string
    {
        return match ($classificacao) {
            'baixo' => 'green',
            'medio' => 'yellow',
            'alto' => 'orange',
            'critico' => 'red',
            default => 'gray',
        };
    }

    /**
     * Retorna o label legivel para a classificacao.
     */
    public function getLabelClassificacao(string $classificacao): string
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
     * Atualiza ou cria o score de um participante.
     */
    public function atualizarScore(Participante $participante, array $dados): ParticipanteScore
    {
        $scores = $this->calcularScores($dados);
        $scoreTotal = $this->calcularScoreTotal($scores);
        $classificacao = $this->classificar($scoreTotal);

        return DB::transaction(function () use ($participante, $scores, $scoreTotal, $classificacao, $dados) {
            $score = ParticipanteScore::updateOrCreate(
                ['participante_id' => $participante->id],
                [
                    'user_id' => $participante->user_id,
                    'score_cadastral' => $scores['cadastral'],
                    'score_cnd_federal' => $scores['cnd_federal'],
                    'score_cnd_estadual' => $scores['cnd_estadual'],
                    'score_fgts' => $scores['fgts'],
                    'score_trabalhista' => $scores['trabalhista'],
                    'score_compliance' => $scores['compliance'],
                    'score_esg' => $scores['esg'],
                    'score_protestos' => $scores['protestos'],
                    'score_total' => $scoreTotal,
                    'classificacao' => $classificacao,
                    'ultima_consulta_em' => now(),
                    'dados_consultados' => $dados,
                ]
            );

            // Atualiza ultima_consulta_em no participante
            $participante->update(['ultima_consulta_em' => now()]);

            return $score;
        });
    }

    /**
     * Obtem estatisticas de risco para um usuario.
     */
    public function getEstatisticas(int $userId): array
    {
        $totais = ParticipanteScore::where('user_id', $userId)
            ->select(
                DB::raw('COUNT(*) as total'),
                DB::raw("COUNT(CASE WHEN classificacao = 'baixo' THEN 1 END) as baixo"),
                DB::raw("COUNT(CASE WHEN classificacao = 'medio' THEN 1 END) as medio"),
                DB::raw("COUNT(CASE WHEN classificacao = 'alto' THEN 1 END) as alto"),
                DB::raw("COUNT(CASE WHEN classificacao = 'critico' THEN 1 END) as critico"),
                DB::raw('AVG(score_total) as media_score')
            )
            ->first();

        return [
            'total_avaliados' => (int) ($totais->total ?? 0),
            'baixo_risco' => (int) ($totais->baixo ?? 0),
            'medio_risco' => (int) ($totais->medio ?? 0),
            'alto_risco' => (int) ($totais->alto ?? 0),
            'critico' => (int) ($totais->critico ?? 0),
            'media_score' => round((float) ($totais->media_score ?? 0), 1),
        ];
    }

    /**
     * Retorna os pesos configurados.
     */
    public function getPesos(): array
    {
        return $this->pesos;
    }

    // ============ Metodos de calculo individual ============

    private function calcularScoreCadastral(array $dados): int
    {
        $situacao = strtoupper($dados['situacao_cadastral'] ?? 'ATIVA');

        return match ($situacao) {
            'ATIVA' => 0,
            'SUSPENSA' => 50,
            'INAPTA', 'BAIXADA', 'NULA' => 100,
            default => 30,
        };
    }

    private function calcularScoreCndFederal(array $dados): int
    {
        $status = strtoupper($dados['cnd_federal'] ?? '');

        if (empty($status)) {
            return 50; // Nao consultado
        }

        return match ($status) {
            'NEGATIVA', 'REGULAR' => 0,
            'POSITIVA_COM_EFEITO', 'POSITIVA COM EFEITO DE NEGATIVA' => 20,
            'POSITIVA', 'IRREGULAR' => 70,
            default => 50,
        };
    }

    private function calcularScoreCndEstadual(array $dados): int
    {
        $status = strtoupper($dados['cnd_estadual'] ?? '');

        if (empty($status)) {
            return 50; // Nao consultado
        }

        return match ($status) {
            'NEGATIVA', 'REGULAR' => 0,
            'POSITIVA_COM_EFEITO', 'POSITIVA COM EFEITO DE NEGATIVA' => 20,
            'POSITIVA', 'IRREGULAR' => 70,
            default => 50,
        };
    }

    private function calcularScoreFgts(array $dados): int
    {
        $status = strtoupper($dados['crf_fgts'] ?? $dados['fgts'] ?? '');

        if (empty($status)) {
            return 50; // Nao consultado
        }

        return match ($status) {
            'REGULAR', 'REGULARIDADE' => 0,
            'IRREGULAR', 'IRREGULARIDADE' => 50,
            default => 30,
        };
    }

    private function calcularScoreTrabalhista(array $dados): int
    {
        $status = strtoupper($dados['cndt'] ?? '');

        if (empty($status)) {
            return 50; // Nao consultado
        }

        return match ($status) {
            'NEGATIVA', 'REGULAR' => 0,
            'POSITIVA', 'IRREGULAR' => 40,
            default => 30,
        };
    }

    private function calcularScoreCompliance(array $dados): int
    {
        $ceis = $dados['ceis'] ?? false;
        $cnep = $dados['cnep'] ?? false;
        $tcu = $dados['tcu_inidoenos'] ?? $dados['tcu'] ?? false;

        if ($ceis === true || $cnep === true || $tcu === true) {
            return 100; // Presente em lista restritiva
        }

        // Se foi consultado e nao encontrado
        if (isset($dados['ceis']) || isset($dados['cnep']) || isset($dados['tcu'])) {
            return 0;
        }

        return 50; // Nao consultado
    }

    private function calcularScoreEsg(array $dados): int
    {
        $trabalhoEscravo = $dados['trabalho_escravo'] ?? false;
        $ibama = $dados['ibama_autuacoes'] ?? [];

        $score = 0;

        // Lista suja de trabalho escravo - muito grave
        if ($trabalhoEscravo === true) {
            $score += 100;
        }

        // Autuacoes IBAMA
        if (is_array($ibama) && count($ibama) > 0) {
            $score += min(50, count($ibama) * 10);
        }

        return min(100, $score);
    }

    private function calcularScoreProtestos(array $dados): int
    {
        $protestos = $dados['protestos'] ?? [];
        $qtdProtestos = is_array($protestos) ? count($protestos) : (int) $protestos;
        $valorProtestos = $dados['valor_protestos'] ?? 0;

        if ($qtdProtestos === 0) {
            return 0;
        }

        // Score baseado em quantidade e valor
        $scorePorQtd = match (true) {
            $qtdProtestos <= 2 => 20,
            $qtdProtestos <= 5 => 40,
            $qtdProtestos <= 10 => 60,
            default => 80,
        };

        $scorePorValor = match (true) {
            $valorProtestos < 10000 => 0,
            $valorProtestos < 50000 => 10,
            $valorProtestos < 100000 => 20,
            default => 30,
        };

        return min(100, $scorePorQtd + $scorePorValor);
    }
}
