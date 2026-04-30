<?php

namespace App\Services\Clearance\Comparacao;

use App\Models\XmlNota;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Calcula e persiste o resumo da comparação declarado vs SEFAZ
 * direto em xml_notas (campos divergencia_*), pra listagem/dashboard
 * não precisarem rodar o ComparacaoNotaService linha a linha.
 */
class DivergenciaSnapshotService
{
    public function __construct(
        private readonly ComparacaoSourceResolver $resolver,
        private readonly ComparacaoNotaService $comparator,
    ) {}

    /**
     * Recalcula e persiste o snapshot de divergência da nota.
     * Retorna true quando atualizou; false quando faltam fontes.
     */
    public function sincronizar(XmlNota $nota): bool
    {
        if (! $nota->user_id || ! $nota->nfe_id) {
            return false;
        }

        try {
            $resolved = $this->resolver->resolver($nota->user_id, $nota->nfe_id);
        } catch (Throwable $e) {
            Log::warning('DivergenciaSnapshot: resolver falhou', [
                'user_id' => $nota->user_id,
                'chave' => $nota->nfe_id,
                'erro' => $e->getMessage(),
            ]);

            return false;
        }

        $declarado = $resolved->declarado?->carregar();
        $sefaz = $resolved->sefaz?->carregar();

        if ($declarado === null && $sefaz === null) {
            return false;
        }

        $comparacao = $this->comparator->comparar($declarado, $sefaz, $resolved->tipoDocumento);
        $resumo = $comparacao->resumo;

        $count = $resumo->headerDivergencias
            + $resumo->totaisDivergencias
            + $resumo->itensDivergentes
            + $resumo->itensFantasmaDeclarado
            + $resumo->itensFantasmaSefaz;

        $nota->divergencia_severidade = $this->severidadeCanonica($resumo->severidade);
        $nota->divergencia_count = $count;
        $nota->divergencia_resumo = [
            'header' => $resumo->headerDivergencias,
            'totais' => $resumo->totaisDivergencias,
            'itens_divergentes' => $resumo->itensDivergentes,
            'itens_fantasma_declarado' => $resumo->itensFantasmaDeclarado,
            'itens_fantasma_sefaz' => $resumo->itensFantasmaSefaz,
            'sefaz_ausente' => $resumo->sefazAusente,
            'declarado_ausente' => $resumo->declaradoAusente,
        ];
        $nota->comparado_em = now();

        $nota->saveQuietly();

        return true;
    }

    private function severidadeCanonica(string $severidade): string
    {
        return match (strtolower($severidade)) {
            'critica' => XmlNota::DIVERGENCIA_CRITICA,
            'revisar' => XmlNota::DIVERGENCIA_REVISAR,
            default => XmlNota::DIVERGENCIA_OK,
        };
    }
}
