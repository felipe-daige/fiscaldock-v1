<?php

use App\Services\SpedDetectorService;

beforeEach(function () {
    $this->detector = new SpedDetectorService;
});

it('detecta EFD PIS/COFINS por presenca de A100', function () {
    $sped = "|0000|006|0|01022024|29022024|EMPRESA TESTE|12345678000100|MG|123456789|3106200|0|0|\r\n".
        "|0001|0|\r\n".
        "|0140|FILIAL|EMPRESA|12345678000100|MG|123456789||\r\n".
        "|0150|FORN001|FORNECEDOR LTDA|1058|98765432000100||123||RUA X|100|||\r\n".
        "|A100|0|0|FORN001|00||1|1|CHV001|01022024|01022024|1000.00|9|0.00|1000.00|6.50|1000.00|30.00|0|0|0|\r\n".
        "|A170|001|SERV|Servico|1000.00|0|||01|1000.00|0.65|6.50|01|1000.00|3.00|30.00|411|||\r\n".
        "|9999|6|\r\n";

    $result = $this->detector->detectar($sped);

    expect($result['valido'])->toBeTrue();
    expect($result['tipo'])->toBe('EFD PIS/COFINS');
    expect($result['erros'])->toBe([]);
});

it('detecta EFD ICMS/IPI por presenca de E100', function () {
    $sped = "|0000|016|0|01022024|29022024|EMPRESA TESTE|12345678000100|MG|123456789|3106200|0|A|0|0|\r\n".
        "|0001|0|\r\n".
        "|0150|FORN001|FORNECEDOR LTDA|1058|98765432000100||123||RUA X|100|||\r\n".
        "|C100|0|0|FORN001|55|00|1|123|44CHARS_CHAVE|01022024|01022024|1000.00|9|0.00|1000.00|0|1000.00|0|0|0|0|0|0|0|0|0|0|0|0|0|\r\n".
        "|E100|01022024|29022024|\r\n".
        "|E110|0|0|0|0|0|0|0|0|0|0|0|\r\n".
        "|9999|5|\r\n";

    $result = $this->detector->detectar($sped);

    expect($result['valido'])->toBeTrue();
    expect($result['tipo'])->toBe('EFD ICMS/IPI');
    expect($result['erros'])->toBe([]);
});

it('detecta EFD PIS/COFINS quando so tem 0110 (regime cumulativo)', function () {
    $sped = "|0000|006|0|01022024|29022024|EMPRESA|12345678000100|MG|123|3106200|0|0|\r\n".
        "|0110|1|2|1|\r\n".
        "|9999|2|\r\n";

    $result = $this->detector->detectar($sped);

    expect($result['valido'])->toBeTrue();
    expect($result['tipo'])->toBe('EFD PIS/COFINS');
});

it('rejeita arquivo sem registro 0000', function () {
    $sped = "qualquer texto aleatorio aqui\nsem header SPED\n";

    $result = $this->detector->detectar($sped);

    expect($result['valido'])->toBeFalse();
    expect($result['tipo'])->toBeNull();
    expect($result['erros'])->toContain('Arquivo nao parece ser um SPED valido (sem registro 0000).');
});

it('rejeita arquivo binario / nao texto', function () {
    $sped = "\x00\x01\x02\xff\xfe\xfd binary garbage \x00\x00";

    $result = $this->detector->detectar($sped);

    expect($result['valido'])->toBeFalse();
    expect($result['tipo'])->toBeNull();
});

it('rejeita arquivo sem registro 9999 (sem trailer)', function () {
    $sped = "|0000|006|0|01022024|29022024|EMPRESA|12345678000100|MG|123|3106200|0|0|\r\n".
        "|A100|0|0|FORN001|00||1|1|CHV001|01022024|01022024|1000.00|9|0.00|1000.00|6.50|1000.00|30.00|0|0|0|\r\n";

    $result = $this->detector->detectar($sped);

    expect($result['valido'])->toBeFalse();
    expect($result['erros'])->toContain('Arquivo nao parece ser um SPED valido (sem registro 9999).');
});

it('retorna tipo desconhecido quando nao acha discriminadores', function () {
    // Tem 0000 e 9999 mas nada que identifique o tipo
    $sped = "|0000|999|0|01022024|29022024|EMPRESA|12345678000100|MG|123|3106200|0|0|\r\n".
        "|0001|0|\r\n".
        "|9999|2|\r\n";

    $result = $this->detector->detectar($sped);

    expect($result['valido'])->toBeTrue();
    expect($result['tipo'])->toBeNull();
});

it('aceita encoding ISO-8859-1 (acentos)', function () {
    $sped = mb_convert_encoding(
        "|0000|006|0|01022024|29022024|CAFÉ EXPRESSÃO|12345678000100|MG|123|3106200|0|0|\r\n".
            "|A100|0|0|FORN|00||1|1|CHV|01022024|01022024|100.00|9|0|100|0.65|100|3|0|0|0|\r\n".
            "|9999|3|\r\n",
        'ISO-8859-1',
        'UTF-8'
    );

    $result = $this->detector->detectar($sped);

    expect($result['valido'])->toBeTrue();
    expect($result['tipo'])->toBe('EFD PIS/COFINS');
});

it('ICMS/IPI ganha quando arquivo tem D100 mesmo sem E100', function () {
    $sped = "|0000|016|0|01022024|29022024|EMPRESA|12345678000100|MG|123|3106200|0|A|0|0|\r\n".
        "|D100|0|0|FORN|57|00|1|1|44CHARSCHAVE|01022024|01022024|100|9|0|100|0|0|\r\n".
        "|9999|3|\r\n";

    $result = $this->detector->detectar($sped);

    expect($result['valido'])->toBeTrue();
    expect($result['tipo'])->toBe('EFD ICMS/IPI');
});
