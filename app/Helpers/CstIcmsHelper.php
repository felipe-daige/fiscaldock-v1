<?php

namespace App\Helpers;

class CstIcmsHelper
{
    private static array $descricoes = [
        '00' => 'Tributada integralmente',
        '10' => 'Tributada com cobrança de ICMS por ST',
        '20' => 'Com redução de base de cálculo',
        '30' => 'Isenta/não tributada com cobrança de ICMS por ST',
        '40' => 'Isenta',
        '41' => 'Não tributada',
        '50' => 'Suspensão',
        '51' => 'Diferimento',
        '60' => 'ICMS cobrado anteriormente por ST',
        '70' => 'Com redução de BC e cobrança de ICMS por ST',
        '90' => 'Outros',
    ];

    public static function descricao(string|int|null $cst): string
    {
        if ($cst === null || $cst === '') {
            return 'Não informado';
        }

        $cst = str_pad((string) $cst, 2, '0', STR_PAD_LEFT);
        $cstBase = substr($cst, -2);

        return self::$descricoes[$cstBase] ?? 'CST ' . $cst;
    }
}
