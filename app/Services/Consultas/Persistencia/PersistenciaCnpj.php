<?php

namespace App\Services\Consultas\Persistencia;

use App\Models\ConsultaResultado;
use App\Services\Consultas\Dto\ResultadoFonte;

class PersistenciaCnpj
{
    /**
     * Chaves de fonte já persistidas para este alvo no lote (top-level de resultado_dados).
     * Usado pelo job p/ idempotência: em retry, não re-consultar (nem re-cobrar) o que já rodou.
     *
     * @param  string  $alvoTipo  'participante' | 'cliente'
     * @return string[]
     */
    public function chavesPersistidas(int $loteId, string $alvoTipo, int $alvoId): array
    {
        $chaveEscopo = $alvoTipo === 'cliente' ? 'cliente_id' : 'participante_id';

        $linha = ConsultaResultado::where('consulta_lote_id', $loteId)
            ->where($chaveEscopo, $alvoId)
            ->first();

        return $linha ? array_keys($linha->resultado_dados ?? []) : [];
    }

    /**
     * @param  string  $alvoTipo  'participante' | 'cliente'
     */
    public function gravar(int $loteId, string $alvoTipo, int $alvoId, ResultadoFonte $resultado): void
    {
        $chaveEscopo = $alvoTipo === 'cliente' ? 'cliente_id' : 'participante_id';

        $linha = ConsultaResultado::firstOrNew([
            'consulta_lote_id' => $loteId,
            $chaveEscopo => $alvoId,
        ]);

        $dados = $linha->resultado_dados ?? [];

        // merge: campos da fonte sobrescrevem; consultas_realizadas acumula sem duplicar
        $realizadas = array_values(array_unique(array_merge(
            $dados['consultas_realizadas'] ?? [],
            $resultado->dados['consultas_realizadas'] ?? [],
        )));

        $dados = array_merge($dados, $resultado->dados);
        if ($realizadas) {
            $dados['consultas_realizadas'] = $realizadas;
        }

        $linha->resultado_dados = $dados;
        $linha->status = $resultado->status === 'sucesso' ? 'sucesso' : ($linha->status ?: 'erro');
        if ($resultado->status !== 'sucesso' && $resultado->mensagem) {
            $linha->error_message = $resultado->mensagem;
        }
        $linha->consultado_em = now();
        $linha->save();
    }
}
