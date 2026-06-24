<?php

namespace App\Services\Consultas\Fontes;

/**
 * Base para certidões via InfoSimples (CND Federal, CNDT, CRF FGTS, CND Estadual...).
 * Trata o fluxo comum de não-sucesso (611→INDETERMINADO, 612→NAO_ENCONTRADA,
 * técnico→nada). Cada certidão só implementa `mapearSucesso()` (data[0]→bloco).
 */
abstract class FonteCertidaoInfoSimples extends FonteInfoSimplesBase
{
    /** Mapeia o data[0] da resposta de sucesso no bloco interno da certidão. */
    abstract protected function mapearSucesso(array $data): array;

    /**
     * Status da certidão a partir do data[0]. Nem toda certidão traz `tipo` (ex: CNDT, CND
     * Estadual não trazem) — nesse caso deriva de `conseguiu_emitir_certidao_negativa`:
     * true → Negativa (regular), false → Positiva (com débitos).
     */
    protected function statusCertidao(array $data): ?string
    {
        $tipo = $data['tipo'] ?? $data['situacao'] ?? null;
        if ($tipo !== null && $tipo !== '') {
            return $tipo;
        }

        if (array_key_exists('conseguiu_emitir_certidao_negativa', $data)) {
            return $data['conseguiu_emitir_certidao_negativa'] ? 'Negativa' : 'Positiva';
        }

        return null;
    }

    public function normalizar(array $raw, string $status = 'sucesso'): array
    {
        if ($status === 'sucesso') {
            $d0 = $raw['data'][0] ?? [];
            $dados = $this->mapearSucesso($d0);
            // Link do comprovante (PDF) da certidão emitida: InfoSimples devolve em site_receipt
            // (data[0]) ou site_receipts[] (top-level). mapearSucesso não o conhece — injeta aqui.
            $dados['comprovante'] ??= $d0['site_receipt'] ?? ($raw['site_receipts'][0] ?? null);

            return $this->bloco($dados);
        }

        // 611: a fonte não emitiu por dados insuficientes — INDETERMINADO, nunca irregular.
        if ($status === 'indeterminado') {
            return $this->bloco(['status' => 'INDETERMINADO', 'mensagem' => $this->mensagem($raw)]);
        }

        if ($status === 'nao_encontrado') {
            return $this->bloco(['status' => 'NAO_ENCONTRADA', 'mensagem' => $this->mensagem($raw)]);
        }

        // Não consultado: ou a fonte não se aplica ao alvo (path aplicavelPara=false, que injeta
        // o _motivo específico — ex.: UF/cidade sem cobertura), ou o provider devolveu nao_aplicavel
        // sem motivo (ex.: bloqueio de allowlist). O fallback NÃO presume causa geográfica, pois
        // certidões nacionais (Federal/CNDT/FGTS) não têm recorte de UF/cidade.
        if ($status === 'nao_aplicavel') {
            return $this->bloco([
                'status' => 'INDISPONIVEL',
                'mensagem' => $raw['_motivo'] ?? 'Certidão não consultada no provedor.',
            ]);
        }

        // retry/fatal/erro_participante: falha técnica/parâmetro — nada a persistir aqui
        // (a mensagem do erro vai p/ consulta_resultados.error_message pelo job).
        return [];
    }
}
