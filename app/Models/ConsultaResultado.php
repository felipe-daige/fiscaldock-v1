<?php

namespace App\Models;

use App\Services\RiskScoreService;
use App\Support\SystemCriticalError;
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
     * Retorna a mensagem operacional mais útil para exibição na interface.
     */
    public function getMensagemExibivel(): ?string
    {
        if ($this->isErro()) {
            $publicError = $this->normalizeMensagemExibivel($this->publicErrorMessage());

            if ($publicError !== null) {
                return $publicError;
            }
        }

        $payload = is_array($this->resultado_dados) ? $this->resultado_dados : [];
        $mensagemRaiz = $this->normalizeMensagemExibivel($payload['mensagem'] ?? null);

        if ($mensagemRaiz !== null) {
            return $mensagemRaiz;
        }

        foreach ($this->getMensagemExibivelFallbackBlocks() as $block) {
            if (! array_key_exists($block, $payload)) {
                continue;
            }

            $mensagemBloco = $this->extractMensagemExibivelFromPayload($payload[$block]);

            if ($mensagemBloco !== null) {
                return $mensagemBloco;
            }
        }

        return null;
    }

    /**
     * Resolve o regime tributário legível a partir do payload ou do CRT cadastrado.
     */
    public function getRegimeTributarioLabel(): ?string
    {
        $regimePayload = $this->normalizeRegimeTributario($this->getDado('regime_tributario'));

        if ($regimePayload !== null) {
            return $regimePayload;
        }

        if ($this->isTruthyFlag($this->getDado('mei'))) {
            return 'MEI';
        }

        if ($this->isTruthyFlag($this->getDado('simples_nacional'))) {
            return 'Simples Nacional';
        }

        $regimeParticipante = $this->normalizeRegimeTributario($this->participante?->regime_tributario);

        if ($regimeParticipante !== null) {
            return $regimeParticipante;
        }

        $crtResultado = $this->parseCrt($this->getDado('crt'));

        if ($crtResultado !== null) {
            return $this->formatCrt($crtResultado);
        }

        $crtParticipante = $this->parseCrt($this->participante?->crt);

        if ($crtParticipante !== null) {
            return $this->formatCrt($crtParticipante);
        }

        return null;
    }

    /**
     * Retorna o payload enriquecido para geração de parecer fiscal.
     *
     * @return array<string, mixed>
     */
    public function getParecerFiscalPayload(): array
    {
        $payload = is_array($this->resultado_dados) ? $this->resultado_dados : [];

        $regime = $this->getRegimeTributarioLabel();
        $regimePayload = trim((string) ($payload['regime_tributario'] ?? ''));

        if ($regimePayload === '' && $regime !== null) {
            $payload['regime_tributario'] = $regime;
        }

        return $payload;
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

    /**
     * @return array<int, string>
     */
    private function getMensagemExibivelFallbackBlocks(): array
    {
        return [
            'cnd_federal',
            'cnd_estadual',
            'crf_fgts',
            'cndt',
            'sintegra',
            'nfe_clearance',
            'cte_clearance',
        ];
    }

    private function extractMensagemExibivelFromPayload(mixed $payload): ?string
    {
        if (! is_array($payload)) {
            return null;
        }

        $mensagem = $this->normalizeMensagemExibivel($payload['mensagem'] ?? null);

        if ($mensagem !== null) {
            return $mensagem;
        }

        if (! empty($payload['errors']) && is_array($payload['errors'])) {
            foreach ($payload['errors'] as $error) {
                $mensagemErro = $this->normalizeMensagemExibivel($error);

                if ($mensagemErro !== null) {
                    return $mensagemErro;
                }
            }
        }

        foreach ($payload as $nestedValue) {
            $mensagemNested = $this->extractMensagemExibivelFromPayload($nestedValue);

            if ($mensagemNested !== null) {
                return $mensagemNested;
            }
        }

        return null;
    }

    private function normalizeMensagemExibivel(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $message = trim((string) $value);

        return $message !== '' ? $message : null;
    }

    private function parseCrt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $crt = (int) $value;

        return in_array($crt, [1, 2, 3], true) ? $crt : null;
    }

    private function formatCrt(int $crt): string
    {
        return match ($crt) {
            1 => 'Simples Nacional',
            2 => 'Simples Excesso',
            3 => 'Regime Normal',
        };
    }

    private function normalizeRegimeTributario(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $regime = trim((string) $value);

        if ($regime === '') {
            return null;
        }

        return match (mb_strtolower($regime)) {
            'lucro presumido/real',
            'lucro presumido / real' => 'Regime Normal',
            default => $regime,
        };
    }

    private function isTruthyFlag(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (! is_scalar($value) || $value === '') {
            return false;
        }

        return in_array(strtolower(trim((string) $value)), ['1', 'true', 'sim', 'yes'], true);
    }

    /**
     * Retorna o erro pronto para exibição pública.
     *
     * @return array<string, string>
     */
    public function publicErrorUi(array $context = []): array
    {
        if (! $this->isErro() && blank($this->error_message)) {
            return [];
        }

        $defaultContext = [
            'context' => 'consulta-resultado',
            'reference' => $this->lote?->id ? 'Lote #'.$this->lote->id : null,
        ];

        return app(SystemCriticalError::class)->forAsyncFailure(
            $this->error_message,
            null,
            array_merge($defaultContext, $context)
        );
    }

    public function publicErrorMessage(array $context = []): string
    {
        return $this->publicErrorUi($context)['message'] ?? '';
    }
}
