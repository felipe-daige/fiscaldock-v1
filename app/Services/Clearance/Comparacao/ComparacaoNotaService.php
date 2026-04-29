<?php

namespace App\Services\Clearance\Comparacao;

class ComparacaoNotaService
{
    public function comparar(?NotaNormalizada $declarado, ?NotaNormalizada $sefaz, string $tipoDocumento): Comparacao
    {
        if ($declarado === null && $sefaz === null) {
            throw new \InvalidArgumentException('Pelo menos um dos lados (declarado ou sefaz) precisa estar presente.');
        }

        $chave = $declarado->chave ?? $sefaz->chave;

        if ($declarado === null || $sefaz === null) {
            return new Comparacao(
                chave: $chave,
                tipoDocumento: $tipoDocumento,
                declarado: $declarado,
                sefaz: $sefaz,
                headerDiff: [],
                partesDiff: [],
                totaisDiff: [],
                itensPareados: [],
                resumo: new ResumoComparacao(
                    headerDivergencias: 0,
                    totaisDivergencias: 0,
                    itensDivergentes: 0,
                    itensFantasmaDeclarado: 0,
                    itensFantasmaSefaz: 0,
                    severidade: 'ok',
                    sefazAusente: $sefaz === null,
                    declaradoAusente: $declarado === null,
                ),
            );
        }

        throw new \LogicException('Comparação completa ainda não implementada.');
    }
}
