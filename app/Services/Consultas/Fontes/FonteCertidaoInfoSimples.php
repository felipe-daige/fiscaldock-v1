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

    public function normalizar(array $raw, string $status = 'sucesso'): array
    {
        if ($status === 'sucesso') {
            return $this->bloco($this->mapearSucesso($raw['data'][0] ?? []));
        }

        // 611: a fonte não emitiu por dados insuficientes — INDETERMINADO, nunca irregular.
        if ($status === 'indeterminado') {
            return $this->bloco(['status' => 'INDETERMINADO', 'mensagem' => $this->mensagem($raw)]);
        }

        if ($status === 'nao_encontrado') {
            return $this->bloco(['status' => 'NAO_ENCONTRADA', 'mensagem' => $this->mensagem($raw)]);
        }

        // Cobertura do provedor indisponível para a UF/cidade do alvo (não foi consultado).
        if ($status === 'nao_aplicavel') {
            return $this->bloco([
                'status' => 'INDISPONIVEL',
                'mensagem' => 'Cobertura indisponível para esta UF/cidade no provedor.',
            ]);
        }

        // retry/fatal/erro_participante: falha técnica/parâmetro — nada a persistir aqui
        // (a mensagem do erro vai p/ consulta_resultados.error_message pelo job).
        return [];
    }
}
