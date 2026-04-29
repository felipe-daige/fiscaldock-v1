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
