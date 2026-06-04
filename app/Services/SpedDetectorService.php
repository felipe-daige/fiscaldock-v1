<?php

namespace App\Services;

class SpedDetectorService
{
    public const TIPO_PIS_COFINS = 'EFD PIS/COFINS';

    public const TIPO_ICMS_IPI = 'EFD ICMS/IPI';

    private const DISCRIMINADORES_PIS_COFINS = ['A100', 'A170', 'F600', 'M100', 'M200', 'M600', '0110'];

    private const DISCRIMINADORES_ICMS_IPI = ['E100', 'E110', 'E200', 'D100', 'D190', 'C190'];

    /**
     * @return array{tipo: string|null, valido: bool, erros: array<int,string>}
     */
    public function detectar(string $conteudo): array
    {
        $erros = [];

        if (! mb_check_encoding($conteudo, 'UTF-8')) {
            $conteudo = mb_convert_encoding($conteudo, 'UTF-8', 'ISO-8859-1');
        }

        if (! preg_match('/[\x20-\x7E\xC0-\xFF\r\n\t|]{20,}/', $conteudo)) {
            return ['tipo' => null, 'valido' => false, 'erros' => ['Arquivo binario nao parece ser um SPED de texto.']];
        }

        $linhas = preg_split('/\r\n|\r|\n/', $conteudo);
        $registros = [];
        foreach ($linhas as $linha) {
            if (! preg_match('/^\|([A-Z0-9]+)\|/', $linha, $m)) {
                continue;
            }
            $registros[$m[1]] = ($registros[$m[1]] ?? 0) + 1;
        }

        if (! isset($registros['0000'])) {
            $erros[] = 'Arquivo nao parece ser um SPED valido (sem registro 0000).';
        }

        if (! isset($registros['9999'])) {
            $erros[] = 'Arquivo nao parece ser um SPED valido (sem registro 9999).';
        }

        if ($erros !== []) {
            return ['tipo' => null, 'valido' => false, 'erros' => $erros];
        }

        foreach (self::DISCRIMINADORES_PIS_COFINS as $reg) {
            if (isset($registros[$reg])) {
                return ['tipo' => self::TIPO_PIS_COFINS, 'valido' => true, 'erros' => []];
            }
        }

        foreach (self::DISCRIMINADORES_ICMS_IPI as $reg) {
            if (isset($registros[$reg])) {
                return ['tipo' => self::TIPO_ICMS_IPI, 'valido' => true, 'erros' => []];
            }
        }

        return ['tipo' => null, 'valido' => true, 'erros' => []];
    }
}
