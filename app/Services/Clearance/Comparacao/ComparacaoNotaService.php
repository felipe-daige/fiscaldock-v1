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
            $itensPareados = [];
            if ($declarado === null && $sefaz !== null) {
                foreach ($sefaz->itens as $it) {
                    $itensPareados[] = new ItemPareado(
                        declarado: null,
                        sefaz: $it,
                        matchType: 'fantasma_sefaz',
                        diffs: [],
                        temDivergencia: true,
                    );
                }
            }
            if ($sefaz === null && $declarado !== null) {
                foreach ($declarado->itens as $it) {
                    $itensPareados[] = new ItemPareado(
                        declarado: $it,
                        sefaz: null,
                        matchType: 'fantasma_declarado',
                        diffs: [],
                        temDivergencia: true,
                    );
                }
            }

            return new Comparacao(
                chave: $chave,
                tipoDocumento: $tipoDocumento,
                declarado: $declarado,
                sefaz: $sefaz,
                headerDiff: [],
                partesDiff: [],
                totaisDiff: [],
                itensPareados: $itensPareados,
                resumo: new ResumoComparacao(
                    headerDivergencias: 0,
                    totaisDivergencias: 0,
                    itensDivergentes: 0,
                    itensFantasmaDeclarado: $sefaz === null ? count($itensPareados) : 0,
                    itensFantasmaSefaz: $declarado === null ? count($itensPareados) : 0,
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
            camposSefSemDado: $sefaz->camposNaoRetornados['header'] ?? [],
        );

        $headerDivergencias = collect($headerDiff)->filter(fn ($c) => $c->divergente)->count();

        $partesDiff = $this->compararPartes($declarado->partes, $sefaz->partes, $tipoDocumento);

        $totaisDiff = $this->compararCampos(
            $declarado->totais,
            $sefaz->totais,
            self::LABELS_TOTAIS,
            tolerancia: (float) config('clearance.comparacao.tolerancia_monetaria', 0.01),
            camposSefSemDado: $sefaz->camposNaoRetornados['totais'] ?? [],
        );

        $totaisDivergencias = collect($totaisDiff)->filter(fn ($c) => $c->divergente)->count();

        $itensPareados = $this->parearItens($declarado->itens, $sefaz->itens, $sefaz->camposNaoRetornados['itens'] ?? []);
        $itensDivergentes = collect($itensPareados)->filter(fn ($p) => $p->temDivergencia && in_array($p->matchType, ['cprod', 'sequencia', 'nitem'], true))->count();
        $itensFantasmaDeclarado = collect($itensPareados)->filter(fn ($p) => $p->matchType === 'fantasma_declarado')->count();
        $itensFantasmaSefaz = collect($itensPareados)->filter(fn ($p) => $p->matchType === 'fantasma_sefaz')->count();

        $severidade = $this->calcularSeveridade($declarado, $sefaz, $totaisDiff, $itensPareados, $headerDivergencias);

        return new Comparacao(
            chave: $chave,
            tipoDocumento: $tipoDocumento,
            declarado: $declarado,
            sefaz: $sefaz,
            headerDiff: $headerDiff,
            partesDiff: $partesDiff,
            totaisDiff: $totaisDiff,
            itensPareados: $itensPareados,
            resumo: new ResumoComparacao(
                headerDivergencias: $headerDivergencias,
                totaisDivergencias: $totaisDivergencias,
                itensDivergentes: $itensDivergentes,
                itensFantasmaDeclarado: $itensFantasmaDeclarado,
                itensFantasmaSefaz: $itensFantasmaSefaz,
                severidade: $severidade,
                sefazAusente: false,
                declaradoAusente: false,
            ),
        );
    }

    /**
     * @param  array<int, CampoComparado>  $totaisDiff
     * @param  array<int, ItemPareado>  $itensPareados
     */
    private function calcularSeveridade(
        ?NotaNormalizada $declarado,
        ?NotaNormalizada $sefaz,
        array $totaisDiff,
        array $itensPareados,
        int $headerDivergencias,
    ): string {
        $situacao = strtoupper((string) ($sefaz->metaSefaz['situacao'] ?? ''));
        $valorDeclarado = (float) ($declarado->totais['valor_total'] ?? 0);
        if ($valorDeclarado > 0 && in_array($situacao, ['CANCELADA', 'DENEGADA', 'INUTILIZADA'], true)) {
            return 'critica';
        }

        $valorTotal = collect($totaisDiff)->firstWhere('chave', 'valor_total');
        if ($valorTotal !== null && $valorTotal->divergente) {
            $dec = (float) ($valorTotal->declarado ?? 0);
            $sef = (float) ($valorTotal->sefaz ?? 0);
            $delta = abs($dec - $sef);
            $pct = $dec > 0 ? ($delta / $dec) * 100 : 0;
            if ($delta > (float) config('clearance.comparacao.limiar_critico_valor_abs', 100.0)
                && $pct > (float) config('clearance.comparacao.limiar_critico_valor_pct', 10.0)) {
                return 'critica';
            }
        }

        foreach ($itensPareados as $par) {
            if (! in_array($par->matchType, ['cprod', 'sequencia', 'nitem'], true)) {
                continue;
            }
            $ncm = collect($par->diffs)->firstWhere('chave', 'ncm');
            $cfop = collect($par->diffs)->firstWhere('chave', 'cfop');
            if (($ncm && $ncm->divergente) || ($cfop && $cfop->divergente)) {
                return 'critica';
            }
        }

        $totaisDivergencias = collect($totaisDiff)->filter(fn ($c) => $c->divergente)->count();
        $itensComDiff = collect($itensPareados)->filter(fn ($p) => $p->temDivergencia && in_array($p->matchType, ['cprod', 'sequencia', 'nitem'], true))->count();
        $fantasmas = collect($itensPareados)->filter(fn ($p) => str_starts_with($p->matchType, 'fantasma_'))->count();

        if ($headerDivergencias > 0 || $totaisDivergencias > 0 || $itensComDiff > 0 || $fantasmas > 0) {
            return 'revisar';
        }

        return 'ok';
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
     * @param  array<int, string>  $camposSefSemDado  Chaves que a fonte SEFAZ comprovadamente não retorna (ex: InfoSimples NF-e não devolve NCM).
     * @return array<int, CampoComparado>
     */
    private function compararCampos(array $declarado, array $sefaz, array $labels, ?float $tolerancia = null, array $camposSefSemDado = []): array
    {
        $resultado = [];

        foreach ($labels as $chave => $label) {
            $valorDec = $declarado[$chave] ?? null;
            $valorSef = $sefaz[$chave] ?? null;

            $naoComparavel = in_array($chave, $camposSefSemDado, true)
                && $valorSef === null
                && $valorDec !== null && $valorDec !== '';

            $divergente = $naoComparavel ? false : $this->valoresDivergem($valorDec, $valorSef, $tolerancia);

            $resultado[] = new CampoComparado(
                chave: $chave,
                label: $label,
                declarado: $valorDec,
                sefaz: $valorSef,
                divergente: $divergente,
                tolerancia: null,
                naoComparavel: $naoComparavel,
            );
        }

        return $resultado;
    }

    /**
     * @param  array<int, ItemNormalizado>  $declarado
     * @param  array<int, ItemNormalizado>  $sefaz
     * @param  array<int, string>  $camposSefSemDado
     * @return array<int, ItemPareado>
     */
    private function parearItens(array $declarado, array $sefaz, array $camposSefSemDado = []): array
    {
        if (in_array('cProd', $camposSefSemDado, true)) {
            return $this->parearPorNItem($declarado, $sefaz, $camposSefSemDado);
        }

        $pares = [];
        $gruposDec = $this->agruparPorCProd($declarado);
        $gruposSef = $this->agruparPorCProd($sefaz);

        $cprods = collect(array_keys($gruposDec))->concat(array_keys($gruposSef))->unique()->filter()->values();

        foreach ($cprods as $cprod) {
            $itensDec = $gruposDec[$cprod] ?? [];
            $itensSef = $gruposSef[$cprod] ?? [];
            $countMin = min(count($itensDec), count($itensSef));

            for ($i = 0; $i < $countMin; $i++) {
                $pares[] = $this->criarParCProd($itensDec[$i], $itensSef[$i], $camposSefSemDado);
            }

            for ($i = $countMin; $i < count($itensDec); $i++) {
                $pares[] = new ItemPareado(
                    declarado: $itensDec[$i], sefaz: null,
                    matchType: 'fantasma_declarado',
                    diffs: [], temDivergencia: true,
                );
            }
            for ($i = $countMin; $i < count($itensSef); $i++) {
                $pares[] = new ItemPareado(
                    declarado: null, sefaz: $itensSef[$i],
                    matchType: 'fantasma_sefaz',
                    diffs: [], temDivergencia: true,
                );
            }
        }

        $itensSemCprodDec = array_values(array_filter($declarado, fn ($it) => $it->cProd === null || $it->cProd === ''));
        $itensSemCprodSef = array_values(array_filter($sefaz, fn ($it) => $it->cProd === null || $it->cProd === ''));
        $countMin = min(count($itensSemCprodDec), count($itensSemCprodSef));

        for ($i = 0; $i < $countMin; $i++) {
            $diffs = $this->compararItensCampos($itensSemCprodDec[$i], $itensSemCprodSef[$i], $camposSefSemDado);
            $pares[] = new ItemPareado(
                declarado: $itensSemCprodDec[$i],
                sefaz: $itensSemCprodSef[$i],
                matchType: 'sequencia',
                diffs: $diffs,
                temDivergencia: collect($diffs)->contains(fn ($c) => $c->divergente),
            );
        }

        for ($i = $countMin; $i < count($itensSemCprodDec); $i++) {
            $pares[] = new ItemPareado(
                declarado: $itensSemCprodDec[$i], sefaz: null,
                matchType: 'fantasma_declarado',
                diffs: [], temDivergencia: true,
            );
        }
        for ($i = $countMin; $i < count($itensSemCprodSef); $i++) {
            $pares[] = new ItemPareado(
                declarado: null, sefaz: $itensSemCprodSef[$i],
                matchType: 'fantasma_sefaz',
                diffs: [], temDivergencia: true,
            );
        }

        return $pares;
    }

    /**
     * @param  array<int, ItemNormalizado>  $declarado
     * @param  array<int, ItemNormalizado>  $sefaz
     * @param  array<int, string>  $camposSefSemDado
     * @return array<int, ItemPareado>
     */
    private function parearPorNItem(array $declarado, array $sefaz, array $camposSefSemDado): array
    {
        $pares = [];
        $sefPorNItem = [];
        foreach ($sefaz as $it) {
            if ($it->nItem !== null) {
                $sefPorNItem[$it->nItem] = $it;
            }
        }

        foreach ($declarado as $dec) {
            if ($dec->nItem !== null && isset($sefPorNItem[$dec->nItem])) {
                $sef = $sefPorNItem[$dec->nItem];
                unset($sefPorNItem[$dec->nItem]);
                $diffs = $this->compararItensCampos($dec, $sef, $camposSefSemDado);
                $pares[] = new ItemPareado(
                    declarado: $dec, sefaz: $sef,
                    matchType: 'nitem',
                    diffs: $diffs,
                    temDivergencia: collect($diffs)->contains(fn ($c) => $c->divergente),
                );
            } else {
                $pares[] = new ItemPareado(
                    declarado: $dec, sefaz: null,
                    matchType: 'fantasma_declarado',
                    diffs: [], temDivergencia: true,
                );
            }
        }

        foreach ($sefPorNItem as $sef) {
            $pares[] = new ItemPareado(
                declarado: null, sefaz: $sef,
                matchType: 'fantasma_sefaz',
                diffs: [], temDivergencia: true,
            );
        }

        return $pares;
    }

    /**
     * @param  array<int, ItemNormalizado>  $itens
     * @return array<string, array<int, ItemNormalizado>>
     */
    private function agruparPorCProd(array $itens): array
    {
        $grupos = [];
        foreach ($itens as $item) {
            if ($item->cProd === null || $item->cProd === '') {
                continue;
            }
            $grupos[$item->cProd][] = $item;
        }

        return $grupos;
    }

    /**
     * @param  array<int, string>  $camposSefSemDado
     */
    private function criarParCProd(ItemNormalizado $dec, ItemNormalizado $sef, array $camposSefSemDado = []): ItemPareado
    {
        $diffs = $this->compararItensCampos($dec, $sef, $camposSefSemDado);

        return new ItemPareado(
            declarado: $dec, sefaz: $sef,
            matchType: 'cprod',
            diffs: $diffs,
            temDivergencia: collect($diffs)->contains(fn ($c) => $c->divergente),
        );
    }

    /**
     * @param  array<int, string>  $camposSefSemDado
     * @return array<int, CampoComparado>
     */
    private function compararItensCampos(ItemNormalizado $dec, ItemNormalizado $sef, array $camposSefSemDado = []): array
    {
        $declaradoArr = ['cProd' => $dec->cProd, 'xProd' => $dec->xProd, 'ncm' => $dec->ncm, 'cfop' => $dec->cfop, 'qCom' => $dec->qCom, 'vUnCom' => $dec->vUnCom, 'vProd' => $dec->vProd];
        $sefazArr = ['cProd' => $sef->cProd, 'xProd' => $sef->xProd, 'ncm' => $sef->ncm, 'cfop' => $sef->cfop, 'qCom' => $sef->qCom, 'vUnCom' => $sef->vUnCom, 'vProd' => $sef->vProd];
        $labels = [
            'cProd' => 'Código',
            'xProd' => 'Descrição',
            'ncm' => 'NCM',
            'cfop' => 'CFOP',
            'qCom' => 'Quantidade',
            'vUnCom' => 'Vlr unit.',
            'vProd' => 'Vlr total',
        ];
        $tolerancia = (float) config('clearance.comparacao.tolerancia_monetaria', 0.01);

        return $this->compararCampos($declaradoArr, $sefazArr, $labels, tolerancia: $tolerancia, camposSefSemDado: $camposSefSemDado);
    }

    private function valoresDivergem(mixed $a, mixed $b, ?float $tolerancia = null): bool
    {
        if ($a === null && $b === null) {
            return false;
        }

        if ($tolerancia !== null) {
            $aNum = $a === null ? 0.0 : (is_numeric($a) ? (float) $a : null);
            $bNum = $b === null ? 0.0 : (is_numeric($b) ? (float) $b : null);
            if ($aNum !== null && $bNum !== null) {
                return abs($aNum - $bNum) > $tolerancia;
            }
        }

        if ($a === null || $b === null) {
            return true;
        }

        return (string) $a !== (string) $b;
    }
}
