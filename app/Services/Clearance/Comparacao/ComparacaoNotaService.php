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

        $headerDiff = $this->compararCampos(
            $declarado->header,
            $sefaz->header,
            self::LABELS_HEADER,
        );

        $headerDivergencias = collect($headerDiff)->filter(fn ($c) => $c->divergente)->count();

        $partesDiff = $this->compararPartes($declarado->partes, $sefaz->partes, $tipoDocumento);

        $totaisDiff = $this->compararCampos(
            $declarado->totais,
            $sefaz->totais,
            self::LABELS_TOTAIS,
            tolerancia: (float) config('clearance.comparacao.tolerancia_monetaria', 0.01),
        );

        $totaisDivergencias = collect($totaisDiff)->filter(fn ($c) => $c->divergente)->count();

        return new Comparacao(
            chave: $chave,
            tipoDocumento: $tipoDocumento,
            declarado: $declarado,
            sefaz: $sefaz,
            headerDiff: $headerDiff,
            partesDiff: $partesDiff,
            totaisDiff: $totaisDiff,
            itensPareados: [],
            resumo: new ResumoComparacao(
                headerDivergencias: $headerDivergencias,
                totaisDivergencias: $totaisDivergencias,
                itensDivergentes: 0,
                itensFantasmaDeclarado: 0,
                itensFantasmaSefaz: 0,
                severidade: 'ok',
                sefazAusente: false,
                declaradoAusente: false,
            ),
        );
    }

    private const LABELS_HEADER = [
        'numero' => 'Número',
        'serie' => 'Série',
        'data_emissao' => 'Data emissão',
        'modelo' => 'Modelo',
        'natureza_operacao' => 'Natureza operação',
    ];

    private const LABELS_PARTE = [
        'cnpj' => 'CNPJ',
        'cpf' => 'CPF',
        'razao_social' => 'Razão social',
        'ie' => 'Inscrição estadual',
        'uf' => 'UF',
    ];

    private const PARTES_NFE = ['emit', 'dest'];

    private const PARTES_CTE = ['emit', 'dest', 'tomador', 'remetente'];

    private const LABELS_TOTAIS = [
        'valor_total' => 'Valor total',
        'base_icms' => 'Base ICMS',
        'valor_icms' => 'Valor ICMS',
        'valor_ipi' => 'Valor IPI',
        'valor_pis' => 'Valor PIS',
        'valor_cofins' => 'Valor COFINS',
        'valor_frete' => 'Valor frete',
        'valor_seguro' => 'Valor seguro',
        'valor_desconto' => 'Valor desconto',
    ];

    /**
     * @param  array<string, array<string, mixed>>  $declarado
     * @param  array<string, array<string, mixed>>  $sefaz
     * @return array<string, array<int, CampoComparado>>
     */
    private function compararPartes(array $declarado, array $sefaz, string $tipoDocumento): array
    {
        $partes = $tipoDocumento === 'CTE' ? self::PARTES_CTE : self::PARTES_NFE;
        $resultado = [];

        foreach ($partes as $parte) {
            $valoresDec = $declarado[$parte] ?? [];
            $valoresSef = $sefaz[$parte] ?? [];

            if ($valoresDec === [] && $valoresSef === []) {
                continue;
            }

            $resultado[$parte] = $this->compararCampos($valoresDec, $valoresSef, self::LABELS_PARTE);
        }

        return $resultado;
    }

    /**
     * @param  array<string, mixed>  $declarado
     * @param  array<string, mixed>  $sefaz
     * @param  array<string, string>  $labels
     * @return array<int, CampoComparado>
     */
    private function compararCampos(array $declarado, array $sefaz, array $labels, ?float $tolerancia = null): array
    {
        $resultado = [];

        foreach ($labels as $chave => $label) {
            $valorDec = $declarado[$chave] ?? null;
            $valorSef = $sefaz[$chave] ?? null;

            $divergente = $this->valoresDivergem($valorDec, $valorSef, $tolerancia);

            $resultado[] = new CampoComparado(
                chave: $chave,
                label: $label,
                declarado: $valorDec,
                sefaz: $valorSef,
                divergente: $divergente,
                tolerancia: null,
            );
        }

        return $resultado;
    }

    private function valoresDivergem(mixed $a, mixed $b, ?float $tolerancia = null): bool
    {
        if ($a === null && $b === null) {
            return false;
        }

        if ($a === null || $b === null) {
            return true;
        }

        if ($tolerancia !== null && is_numeric($a) && is_numeric($b)) {
            return abs((float) $a - (float) $b) > $tolerancia;
        }

        return (string) $a !== (string) $b;
    }
}
