<?php

namespace App\Services\Consultas;

class ClassificadorCodigo
{
    /** @return 'sucesso'|'nao_encontrado'|'indeterminado'|'erro_participante'|'retry'|'fatal' */
    public function classificar(int $codigo): string
    {
        foreach (config('consultas.codigos') as $status => $codigos) {
            if (in_array($codigo, $codigos, true)) {
                return $status;
            }
        }

        return 'fatal';
    }
}
