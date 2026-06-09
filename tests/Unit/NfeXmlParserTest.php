<?php

use App\Services\Xml\NfeXmlParser;

function fixtureNfe(string $name): string
{
    return file_get_contents(__DIR__ . '/../Fixtures/nfe/' . $name);
}

it('parseia o header de uma NF-e nfeProc', function () {
    $xml = fixtureNfe('50240197551165000193550010000248021000214750-nfe.xml');
    $p = (new NfeXmlParser())->parse($xml);

    $h = $p['header'];
    expect($h['chave_acesso'])->toBe('50240197551165000193550010000248021000214750');
    expect($h['modelo'])->toBe('55');
    expect($h['ambiente'])->toBe('1');
    expect($h['versao_layout'])->toBe('4.00');
    expect($h['numero_documento'])->toBe(24802);
    expect($h['serie'])->toBe('1');
    expect($h['natureza_operacao'])->toContain('RETORNO');
    expect($h['emit_documento'])->toBe('97551165000193');
    expect($h['emit_uf'])->toBe('MS');
    expect($h['dest_documento'])->toBe('44373108000600');
    expect((float) $h['valor_total'])->toBe(22000.00);
    expect((float) $h['tributos_total'])->toBe(6091.20);
    expect($h['chave_referenciada'])->toBe('35231144373108000600550000000269561733449478');
    expect($h['status_autorizacao'])->toBe('100');
    expect($h['protocolo_autorizacao'])->toBe('150240000158014');
    expect($h['data_emissao'])->toStartWith('2024-01-03');
});

it('parseia os itens com NCM/CFOP/valores', function () {
    $xml = fixtureNfe('50240197551165000193550010000248021000214750-nfe.xml');
    $p = (new NfeXmlParser())->parse($xml);

    expect($p['itens'])->toHaveCount(7);
    $i1 = $p['itens'][0];
    expect($i1['numero_item'])->toBe(1);
    expect($i1['codigo_item'])->toBe('002515');
    expect($i1['ncm'])->toBe('84339090');
    expect($i1['cfop'])->toBe('6916');
    expect((float) $i1['quantidade'])->toBe(2.0);
    expect((float) $i1['valor_total'])->toBe(2000.00);
    expect($i1['cst_icms'])->toBe('41');
    expect($i1['origem_mercadoria'])->toBe('0');
    expect($i1['cst_pis'])->toBe('99');
});

it('coloca o aninhado no payload e não guarda o XML cru', function () {
    $xml = fixtureNfe('50240197551165000193550010000248021000214750-nfe.xml');
    $p = (new NfeXmlParser())->parse($xml);

    expect($p['payload'])->toHaveKeys(['emit', 'dest', 'transp', 'pag', 'totais']);
    expect($p['payload']['emit']['xFant'])->toBe('HIDRATOP');
    expect(json_encode($p))->not->toContain('X509Certificate');
});

it('parseia todas as 10 amostras sem erro', function () {
    $files = glob(__DIR__ . '/../Fixtures/nfe/*-nfe.xml');
    expect($files)->toHaveCount(10);
    foreach ($files as $f) {
        $p = (new NfeXmlParser())->parse(file_get_contents($f));
        expect(strlen($p['header']['chave_acesso']))->toBe(44);
        expect($p['header']['modelo'])->toBe('55');
        expect(count($p['itens']))->toBeGreaterThan(0);
    }
});

it('rejeita modelo diferente de 55', function () {
    $xml = str_replace('<mod>55</mod>', '<mod>65</mod>', fixtureNfe('50240197551165000193550010000248021000214750-nfe.xml'));
    expect(fn () => (new NfeXmlParser())->parse($xml))
        ->toThrow(\App\Services\Xml\NfeParseException::class);
});
