<?php

use App\Services\Clearance\Comparacao\ComparacaoNotaService;
use App\Services\Clearance\Comparacao\NotaNormalizada;

function notaMinima(string $chave = '35202404123456789012345678901234567890123456'): NotaNormalizada
{
    return new NotaNormalizada(
        chave: $chave,
        tipoDocumento: 'NFE',
        header: ['numero' => '1234', 'serie' => '1', 'data_emissao' => '2026-04-12', 'modelo' => '55', 'natureza_operacao' => 'Venda'],
        metaSefaz: [],
        partes: ['emit' => ['cnpj' => '12345678000190', 'razao_social' => 'ACME', 'ie' => '123', 'uf' => 'SP'],
            'dest' => ['cnpj' => '98765432000110', 'razao_social' => 'XYZ', 'ie' => '456', 'uf' => 'SP']],
        totais: ['valor_total' => 1000.00, 'base_icms' => 1000.00, 'valor_icms' => 180.00, 'valor_ipi' => 0.0, 'valor_pis' => 6.50, 'valor_cofins' => 30.00, 'valor_frete' => 0.0, 'valor_seguro' => 0.0, 'valor_desconto' => 0.0],
        itens: [],
        origemLabel: 'fixture',
    );
}

it('retorna comparacao com declaradoAusente=true quando declarado é null', function () {
    $service = new ComparacaoNotaService;
    $sefaz = notaMinima();

    $resultado = $service->comparar(null, $sefaz, 'NFE');

    expect($resultado->resumo->declaradoAusente)->toBeTrue();
    expect($resultado->resumo->sefazAusente)->toBeFalse();
    expect($resultado->declarado)->toBeNull();
    expect($resultado->sefaz)->toBe($sefaz);
    expect($resultado->headerDiff)->toBe([]);
    expect($resultado->itensPareados)->toBe([]);
});

it('retorna comparacao com sefazAusente=true quando sefaz é null', function () {
    $service = new ComparacaoNotaService;
    $declarado = notaMinima();

    $resultado = $service->comparar($declarado, null, 'NFE');

    expect($resultado->resumo->sefazAusente)->toBeTrue();
    expect($resultado->resumo->declaradoAusente)->toBeFalse();
    expect($resultado->sefaz)->toBeNull();
});

it('compara header e gera CampoComparado por chave', function () {
    $service = new ComparacaoNotaService;
    $declarado = notaMinima();
    $sefaz = notaMinima();

    $resultado = $service->comparar($declarado, $sefaz, 'NFE');

    expect($resultado->headerDiff)->toHaveCount(5);
    expect($resultado->headerDiff[0])->toBeInstanceOf(\App\Services\Clearance\Comparacao\CampoComparado::class);
    expect(collect($resultado->headerDiff)->every(fn ($c) => $c->divergente === false))->toBeTrue();
    expect($resultado->resumo->headerDivergencias)->toBe(0);
});

it('marca campo divergente quando header diverge', function () {
    $service = new ComparacaoNotaService;
    $declarado = notaMinima();
    $sefaz = new \App\Services\Clearance\Comparacao\NotaNormalizada(
        chave: $declarado->chave,
        tipoDocumento: 'NFE',
        header: array_merge($declarado->header, ['numero' => '9999']),
        metaSefaz: [],
        partes: $declarado->partes,
        totais: $declarado->totais,
        itens: [],
        origemLabel: 'sefaz',
    );

    $resultado = $service->comparar($declarado, $sefaz, 'NFE');

    $numeroComparado = collect($resultado->headerDiff)->firstWhere('chave', 'numero');
    expect($numeroComparado->divergente)->toBeTrue();
    expect($numeroComparado->declarado)->toBe('1234');
    expect($numeroComparado->sefaz)->toBe('9999');
    expect($resultado->resumo->headerDivergencias)->toBe(1);
});

it('compara partes (emit e dest) e gera CampoComparado por sub-campo', function () {
    $service = new ComparacaoNotaService;
    $declarado = notaMinima();
    $sefazPartes = $declarado->partes;
    $sefazPartes['emit']['razao_social'] = 'OUTRA RAZAO';
    $sefaz = new \App\Services\Clearance\Comparacao\NotaNormalizada(
        chave: $declarado->chave,
        tipoDocumento: 'NFE',
        header: $declarado->header,
        metaSefaz: [],
        partes: $sefazPartes,
        totais: $declarado->totais,
        itens: [],
        origemLabel: 'sefaz',
    );

    $resultado = $service->comparar($declarado, $sefaz, 'NFE');

    expect(array_keys($resultado->partesDiff))->toContain('emit', 'dest');
    $razaoEmit = collect($resultado->partesDiff['emit'])->firstWhere('chave', 'razao_social');
    expect($razaoEmit->divergente)->toBeTrue();
    $cnpjEmit = collect($resultado->partesDiff['emit'])->firstWhere('chave', 'cnpj');
    expect($cnpjEmit->divergente)->toBeFalse();
});

it('aplica tolerancia monetaria de R$0,01 nos totais', function () {
    $service = new ComparacaoNotaService;
    $declarado = notaMinima();
    $totaisSefaz = $declarado->totais;
    $totaisSefaz['valor_total'] = 1000.005;
    $sefaz = new \App\Services\Clearance\Comparacao\NotaNormalizada(
        chave: $declarado->chave,
        tipoDocumento: 'NFE',
        header: $declarado->header, metaSefaz: [], partes: $declarado->partes,
        totais: $totaisSefaz, itens: [], origemLabel: 'sefaz',
    );

    $resultado = $service->comparar($declarado, $sefaz, 'NFE');
    $valorTotal = collect($resultado->totaisDiff)->firstWhere('chave', 'valor_total');

    expect($valorTotal->divergente)->toBeFalse();
    expect($resultado->resumo->totaisDivergencias)->toBe(0);
});

it('detecta divergência em valor total acima da tolerancia', function () {
    $service = new ComparacaoNotaService;
    $declarado = notaMinima();
    $totaisSefaz = $declarado->totais;
    $totaisSefaz['valor_total'] = 800.00;
    $sefaz = new \App\Services\Clearance\Comparacao\NotaNormalizada(
        chave: $declarado->chave,
        tipoDocumento: 'NFE',
        header: $declarado->header, metaSefaz: [], partes: $declarado->partes,
        totais: $totaisSefaz, itens: [], origemLabel: 'sefaz',
    );

    $resultado = $service->comparar($declarado, $sefaz, 'NFE');
    $valorTotal = collect($resultado->totaisDiff)->firstWhere('chave', 'valor_total');

    expect($valorTotal->divergente)->toBeTrue();
    expect($resultado->resumo->totaisDivergencias)->toBeGreaterThan(0);
});

function itemNFE(array $attrs): \App\Services\Clearance\Comparacao\ItemNormalizado
{
    return new \App\Services\Clearance\Comparacao\ItemNormalizado(
        cProd: $attrs['cProd'] ?? null,
        nItem: $attrs['nItem'] ?? 1,
        xProd: $attrs['xProd'] ?? null,
        ncm: $attrs['ncm'] ?? null,
        cfop: $attrs['cfop'] ?? null,
        qCom: $attrs['qCom'] ?? null,
        uCom: $attrs['uCom'] ?? 'UN',
        vUnCom: $attrs['vUnCom'] ?? null,
        vProd: $attrs['vProd'] ?? null,
    );
}

function notaComItens(array $itens, string $sufixo = 'd'): \App\Services\Clearance\Comparacao\NotaNormalizada
{
    $base = notaMinima();

    return new \App\Services\Clearance\Comparacao\NotaNormalizada(
        chave: $base->chave,
        tipoDocumento: 'NFE',
        header: $base->header, metaSefaz: [], partes: $base->partes,
        totais: $base->totais, itens: $itens, origemLabel: $sufixo,
    );
}

it('parea itens por cProd quando cProds correspondem', function () {
    $service = new ComparacaoNotaService;
    $declarado = notaComItens([
        itemNFE(['cProd' => 'A', 'nItem' => 1, 'vProd' => 100]),
        itemNFE(['cProd' => 'B', 'nItem' => 2, 'vProd' => 200]),
    ]);
    $sefaz = notaComItens([
        itemNFE(['cProd' => 'A', 'nItem' => 1, 'vProd' => 100]),
        itemNFE(['cProd' => 'B', 'nItem' => 2, 'vProd' => 200]),
    ], 'sefaz');

    $resultado = $service->comparar($declarado, $sefaz, 'NFE');

    expect($resultado->itensPareados)->toHaveCount(2);
    expect($resultado->itensPareados[0]->matchType)->toBe('cprod');
    expect($resultado->itensPareados[1]->matchType)->toBe('cprod');
    expect($resultado->resumo->itensDivergentes)->toBe(0);
});

it('desempata cProd duplicado pela ordem de aparição', function () {
    $service = new ComparacaoNotaService;
    $declarado = notaComItens([
        itemNFE(['cProd' => 'A', 'nItem' => 1, 'vProd' => 100]),
        itemNFE(['cProd' => 'A', 'nItem' => 4, 'vProd' => 400]),
    ]);
    $sefaz = notaComItens([
        itemNFE(['cProd' => 'A', 'nItem' => 2, 'vProd' => 100]),
        itemNFE(['cProd' => 'A', 'nItem' => 5, 'vProd' => 400]),
    ], 'sefaz');

    $resultado = $service->comparar($declarado, $sefaz, 'NFE');

    expect($resultado->itensPareados)->toHaveCount(2);
    expect($resultado->itensPareados[0]->declarado->nItem)->toBe(1);
    expect($resultado->itensPareados[0]->sefaz->nItem)->toBe(2);
    expect($resultado->itensPareados[1]->declarado->nItem)->toBe(4);
    expect($resultado->itensPareados[1]->sefaz->nItem)->toBe(5);
});

it('marca cProd presente em só um lado como fantasma', function () {
    $service = new ComparacaoNotaService;
    $declarado = notaComItens([itemNFE(['cProd' => 'A', 'nItem' => 1, 'vProd' => 100])]);
    $sefaz = notaComItens([itemNFE(['cProd' => 'B', 'nItem' => 1, 'vProd' => 100])], 'sefaz');

    $resultado = $service->comparar($declarado, $sefaz, 'NFE');

    expect($resultado->itensPareados)->toHaveCount(2);
    $fantasmas = collect($resultado->itensPareados)->pluck('matchType')->all();
    expect($fantasmas)->toContain('fantasma_declarado');
    expect($fantasmas)->toContain('fantasma_sefaz');
});
