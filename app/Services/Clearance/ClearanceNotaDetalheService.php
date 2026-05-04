<?php

namespace App\Services\Clearance;

use App\Models\XmlNota;
use App\Services\Clearance\Comparacao\ComparacaoNotaService;
use App\Services\Clearance\Comparacao\ComparacaoSourceResolver;
use InvalidArgumentException;

class ClearanceNotaDetalheService
{
    public function __construct(
        private readonly ComparacaoSourceResolver $resolver,
        private readonly ComparacaoNotaService $comparator,
    ) {}

    /**
     * @return array{
     *   disponivel: bool,
     *   comparacao_url: string|null,
     *   tipo_documento: string,
     *   tipo_documento_label: string,
     *   severidade: array{label: string, hex: string},
     *   status_sefaz: array{label: string, hex: string},
     *   resumo: array{
     *     header_divergencias: int,
     *     totais_divergencias: int,
     *     itens_divergentes: int,
     *     itens_fantasma_declarado: int,
     *     itens_fantasma_sefaz: int,
     *     total_divergencias: int
     *   },
     *   totais: array{
     *     declarado: float|null,
     *     sefaz: float|null,
     *     delta: float|null,
     *     delta_percentual: float|null
     *   },
     *   origens: array{declarado: string|null, sefaz: string|null},
     *   verificado_em: string|null,
     *   situacao_sefaz: string|null,
     *   possui_snapshot: bool,
     *   possui_declarado: bool,
     *   motivo_indisponivel: string|null
     * }
     */
    public function montarResumo(XmlNota $nota): array
    {
        $comparacaoUrl = $this->comparacaoUrl($nota);
        $tipoDocumento = strtoupper((string) ($nota->tipo_documento ?: 'NFE'));

        if ($comparacaoUrl === null) {
            return $this->fallbackResumo($tipoDocumento, 'Chave de acesso indisponível para comparação.');
        }

        try {
            $resolved = $this->resolver->resolver((int) $nota->user_id, (string) $nota->nfe_id);
        } catch (InvalidArgumentException $e) {
            return $this->fallbackResumo($tipoDocumento, $e->getMessage(), $comparacaoUrl);
        }

        $declarado = $resolved->declarado?->carregar();
        $sefaz = $resolved->sefaz?->carregar();

        if ($declarado === null && $sefaz === null) {
            return $this->fallbackResumo($tipoDocumento, 'Sem fonte declarada e sem snapshot SEFAZ.', $comparacaoUrl);
        }

        $comparacao = $this->comparator->comparar($declarado, $sefaz, $resolved->tipoDocumento);
        $resumo = $comparacao->resumo;

        $valorDeclarado = isset($comparacao->declarado?->totais['valor_total']) ? (float) $comparacao->declarado->totais['valor_total'] : null;
        $valorSefaz = isset($comparacao->sefaz?->totais['valor_total']) ? (float) $comparacao->sefaz->totais['valor_total'] : null;
        $delta = $valorDeclarado !== null && $valorSefaz !== null
            ? $valorSefaz - $valorDeclarado
            : null;
        $deltaPercentual = $delta !== null && $valorDeclarado !== null && $valorDeclarado != 0.0
            ? ($delta / $valorDeclarado) * 100
            : null;

        return [
            'disponivel' => true,
            'comparacao_url' => $comparacaoUrl,
            'tipo_documento' => $resolved->tipoDocumento,
            'tipo_documento_label' => $this->tipoDocumentoLabel($resolved->tipoDocumento),
            'severidade' => $this->mapearSeveridade($resumo->severidade),
            'status_sefaz' => $this->mapearStatusSefaz($comparacao->sefaz?->metaSefaz['situacao'] ?? $nota->situacao_sefaz),
            'resumo' => [
                'header_divergencias' => $resumo->headerDivergencias,
                'totais_divergencias' => $resumo->totaisDivergencias,
                'itens_divergentes' => $resumo->itensDivergentes,
                'itens_fantasma_declarado' => $resumo->itensFantasmaDeclarado,
                'itens_fantasma_sefaz' => $resumo->itensFantasmaSefaz,
                'total_divergencias' => $resumo->headerDivergencias + $resumo->totaisDivergencias + $resumo->itensDivergentes + $resumo->itensFantasmaDeclarado + $resumo->itensFantasmaSefaz,
            ],
            'totais' => [
                'declarado' => $valorDeclarado,
                'sefaz' => $valorSefaz,
                'delta' => $delta,
                'delta_percentual' => $deltaPercentual,
            ],
            'origens' => [
                'declarado' => $resolved->declarado?->origemLabel(),
                'sefaz' => $resolved->sefaz?->origemLabel(),
            ],
            'verificado_em' => $comparacao->sefaz?->metaSefaz['verificado_em'] ?? $nota->verificado_sefaz_em?->format('Y-m-d H:i:s'),
            'situacao_sefaz' => $comparacao->sefaz?->metaSefaz['situacao'] ?? $nota->situacao_sefaz,
            'possui_snapshot' => $comparacao->sefaz !== null,
            'possui_declarado' => $comparacao->declarado !== null,
            'motivo_indisponivel' => null,
        ];
    }

    private function fallbackResumo(string $tipoDocumento, ?string $motivo = null, ?string $comparacaoUrl = null): array
    {
        return [
            'disponivel' => false,
            'comparacao_url' => $comparacaoUrl,
            'tipo_documento' => $tipoDocumento,
            'tipo_documento_label' => $this->tipoDocumentoLabel($tipoDocumento),
            'severidade' => ['label' => 'Sem snapshot', 'hex' => '#9ca3af'],
            'status_sefaz' => ['label' => 'Não verificada', 'hex' => '#9ca3af'],
            'resumo' => [
                'header_divergencias' => 0,
                'totais_divergencias' => 0,
                'itens_divergentes' => 0,
                'itens_fantasma_declarado' => 0,
                'itens_fantasma_sefaz' => 0,
                'total_divergencias' => 0,
            ],
            'totais' => [
                'declarado' => null,
                'sefaz' => null,
                'delta' => null,
                'delta_percentual' => null,
            ],
            'origens' => [
                'declarado' => null,
                'sefaz' => null,
            ],
            'verificado_em' => null,
            'situacao_sefaz' => null,
            'possui_snapshot' => false,
            'possui_declarado' => false,
            'motivo_indisponivel' => $motivo,
        ];
    }

    private function comparacaoUrl(XmlNota $nota): ?string
    {
        $chave = (string) $nota->nfe_id;

        if (strlen($chave) !== 44 || ! ctype_digit($chave)) {
            return null;
        }

        return route('app.clearance.nota.comparar', ['chave' => $chave]);
    }

    private function tipoDocumentoLabel(string $tipoDocumento): string
    {
        return match (strtoupper($tipoDocumento)) {
            'CTE' => 'CT-e',
            'NFCE' => 'NFC-e',
            default => 'NF-e',
        };
    }

    private function mapearSeveridade(string $severidade): array
    {
        return match (strtolower($severidade)) {
            'critica' => ['label' => 'Crítica', 'hex' => '#b91c1c'],
            'revisar' => ['label' => 'Revisar', 'hex' => '#d97706'],
            default => ['label' => 'OK', 'hex' => '#047857'],
        };
    }

    private function mapearStatusSefaz(?string $status): array
    {
        $status = strtoupper(trim((string) $status));

        return match ($status) {
            'AUTORIZADA' => ['label' => 'Autorizada', 'hex' => '#047857'],
            'CANCELADA' => ['label' => 'Cancelada', 'hex' => '#b91c1c'],
            'DENEGADA' => ['label' => 'Denegada', 'hex' => '#b91c1c'],
            'INUTILIZADA' => ['label' => 'Inutilizada', 'hex' => '#d97706'],
            'NAO_ENCONTRADA' => ['label' => 'Não encontrada', 'hex' => '#b91c1c'],
            'INDETERMINADO' => ['label' => 'Indeterminado', 'hex' => '#6b7280'],
            default => ['label' => 'Não verificada', 'hex' => '#9ca3af'],
        };
    }
}
