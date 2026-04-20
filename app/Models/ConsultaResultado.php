<?php

namespace App\Models;

use App\Services\RiskScoreService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsultaResultado extends Model
{
    protected $table = 'consulta_resultados';

    public const STATUS_PENDENTE = 'pendente';

    public const STATUS_SUCESSO = 'sucesso';

    public const STATUS_ERRO = 'erro';

    public const STATUS_TIMEOUT = 'timeout';

    protected $fillable = [
        'consulta_lote_id',
        'participante_id',
        'resultado_dados',
        'status',
        'error_message',
        'consultado_em',
    ];

    protected $casts = [
        'resultado_dados' => 'array',
        'consultado_em' => 'datetime',
    ];

    /**
     * Lote ao qual este resultado pertence.
     */
    public function lote(): BelongsTo
    {
        return $this->belongsTo(ConsultaLote::class, 'consulta_lote_id');
    }

    /**
     * Participante consultado.
     */
    public function participante(): BelongsTo
    {
        return $this->belongsTo(Participante::class);
    }

    /**
     * Retorna se a consulta foi bem sucedida.
     */
    public function isSucesso(): bool
    {
        return $this->status === self::STATUS_SUCESSO;
    }

    /**
     * Retorna se a consulta falhou.
     */
    public function isErro(): bool
    {
        return in_array($this->status, [self::STATUS_ERRO, self::STATUS_TIMEOUT]);
    }

    /**
     * Retorna se ainda está pendente.
     */
    public function isPendente(): bool
    {
        return $this->status === self::STATUS_PENDENTE;
    }

    /**
     * Calcula o score de risco on-demand usando RiskScoreService.
     *
     * @return array{scores: array, score_total: int, classificacao: string}
     */
    public function calcularScore(): array
    {
        if (empty($this->resultado_dados)) {
            return [
                'scores' => [],
                'score_total' => 50,
                'classificacao' => 'medio',
            ];
        }

        $service = app(RiskScoreService::class);
        $scores = $service->calcularScores($this->resultado_dados);
        $scoreTotal = $service->calcularScoreTotal($scores);
        $classificacao = $service->classificar($scoreTotal);

        return [
            'scores' => $scores,
            'score_total' => $scoreTotal,
            'classificacao' => $classificacao,
        ];
    }

    /**
     * Retorna lista de consultas realizadas.
     */
    public function getConsultasRealizadas(): array
    {
        return $this->resultado_dados['consultas_realizadas'] ?? [];
    }

    /**
     * Retorna um dado específico do resultado.
     */
    public function getDado(string $key, mixed $default = null): mixed
    {
        return $this->resultado_dados[$key] ?? $default;
    }

    /**
     * Retorna a situação cadastral.
     */
    public function getSituacaoCadastral(): ?string
    {
        return $this->getDado('situacao_cadastral');
    }

    /**
     * Retorna se é optante do Simples Nacional.
     */
    public function isSimples(): ?bool
    {
        return $this->getDado('simples_nacional');
    }

    /**
     * Retorna se é MEI.
     */
    public function isMei(): ?bool
    {
        return $this->getDado('mei');
    }

    /**
     * Retorna dados do SINTEGRA.
     */
    public function getSintegra(): ?array
    {
        return $this->getDado('sintegra');
    }

    /**
     * Retorna dados da CND Federal.
     */
    public function getCndFederal(): ?array
    {
        return $this->getDado('cnd_federal');
    }

    /**
     * Retorna dados do CRF (FGTS).
     */
    public function getCrfFgts(): ?array
    {
        return $this->getDado('crf_fgts');
    }

    /**
     * Retorna dados da CND Estadual.
     */
    public function getCndEstadual(): ?array
    {
        return $this->getDado('cnd_estadual');
    }

    /**
     * Retorna dados da CNDT.
     */
    public function getCndt(): ?array
    {
        return $this->getDado('cndt');
    }
}
